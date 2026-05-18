<?php
session_start();
require_once 'config.php';

// 確保顧客只能看自己的收據
if (!isset($_SESSION['customer_id']) || !isset($_GET['id'])) {
    die("Access Denied.");
}

$customer_id = $_SESSION['customer_id'];
$order_id = intval($_GET['id']);

// 抓取訂單主檔與顧客資料
$query = "SELECT o.*, c.username, c.email 
          FROM orders o 
          JOIN customers c ON o.customer_id = c.customer_id 
          WHERE o.order_id = ? AND o.customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $customer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found or access denied.");
}

// 抓取訂單明細
$query_details = "
    SELECT od.*, p.product_name, sb.build_name 
    FROM order_details od
    LEFT JOIN products p ON od.product_id = p.product_id
    LEFT JOIN saved_builds sb ON od.pc_build = sb.pc_build
    WHERE od.order_id = ?
";
$stmt_details = $conn->prepare($query_details);
$stmt_details->bind_param("i", $order_id);
$stmt_details->execute();
$details_result = $stmt_details->get_result();
$items = [];
$subtotal = 0;
while ($item = $details_result->fetch_assoc()) {
    $items[] = $item;
    $subtotal += ($item['unit_price'] * $item['quantity']);
}
?>

<!DOCTYPE html>
<html lang="en">
 <style>
        .btn-print {
            background: transparent;
            color: var(--text-main);
            border: 1px solid var(--text-muted);
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: 0.3s;
        }
        .btn-print:hover {
            background: var(--text-main);
            color: #000;
        }

                /* 基礎發票排版 (白底黑字最適合列印) */
        body { font-family: 'Arial', sans-serif; background: #fff; color: #000; margin: 0; padding: 20px; }
        .invoice-container { max-width: 800px; margin: 0 auto; padding: 40px; border: 1px solid #ddd; }
        
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { margin: 0; color: #000; font-size: 2.5rem; text-transform: uppercase; letter-spacing: 2px; }
        .header p { margin: 5px 0; color: #555; }
        
        .billing-info { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .info-box { width: 45%; }
        .info-box h3 { border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 10px; color: #333; }
        .info-box p { margin: 3px 0; line-height: 1.5; color: #555; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; color: #333; }
        td.text-right, th.text-right { text-align: right; }
        
        .totals { width: 50%; float: right; }
        .totals-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .totals-row.grand-total { font-size: 1.5rem; font-weight: bold; color: #000; border-bottom: none; border-top: 2px solid #000; padding-top: 15px; }
        
        /* 網頁上的功能按鈕區 */
        .action-buttons { text-align: center; margin-bottom: 20px; }
        .btn { padding: 10px 20px; background: #000; color: #fff; text-decoration: none; border: none; cursor: pointer; font-size: 1rem; border-radius: 4px; }
        
        /* 🌟 魔法在此：當進入「列印/PDF存檔」模式時，隱藏不要印出來的東西，消除邊界 */
        @media print {
            body { padding: 0; }
            .invoice-container { border: none; padding: 0; max-width: 100%; }
            .action-buttons { display: none !important; } /* 列印時隱藏按鈕 */
        }</style>
<head>
    <meta charset="UTF-8">
    <title>Invoice_#<?php echo str_pad($order['order_id'], 5, "0", STR_PAD_LEFT); ?></title>
</head>
<body>

    <div class="action-buttons">
        <button class="btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
        <a href="my_orders.php" style="color: #666; text-decoration: none; margin-left: 20px;">Return to Orders</a>
    </div>

    <div class="invoice-container">
        
        <div class="header">
            <div>
                <h1>GridCitY PC</h1>
                <p>123 Tech Boulevard, Cyberjaya</p>
                <p>Selangor, Malaysia 63100</p>
                <p>Email: support@gridcitypc.com</p>
            </div>
            <div style="text-align: right;">
                <h2 style="margin: 0; color: #000;">RECEIPT</h2>
                <p><strong>Order #:</strong> <?php echo str_pad($order['order_id'], 5, "0", STR_PAD_LEFT); ?></p>
                <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($order['order_date'])); ?></p>
                <p><strong>Status:</strong> <?php echo strtoupper($order['order_status']); ?></p>
            </div>
        </div>

        <div class="billing-info">
            <div class="info-box">
                <h3>Billed To</h3>
                <p><strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong></p>
                <p><?php echo htmlspecialchars($order['email']); ?></p>
            </div>
            <div class="info-box">
                <h3>Shipped To</h3>
                <p>
                    <?php 
                        $clean_address = str_replace(['\r\n', '\n', '\r'], "\n", $order['shipping_address']);
                        echo nl2br(htmlspecialchars($clean_address)); 
                    ?>
                </p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: center;">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($item['product_name'] ? $item['product_name'] : "Custom Rig: " . $item['build_name']); ?></strong>
                    </td>
                    <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                    <td class="text-right">RM <?php echo number_format($item['unit_price'], 2); ?></td>
                    <td class="text-right">RM <?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <span>Subtotal:</span>
                <span>RM <?php echo number_format($subtotal, 2); ?></span>
            </div>
            
            <?php 
            // 🌟 财务逻辑修复：将总折扣拆分为“金币折扣”和“优惠券折扣”
            $coin_discount = $order['coins_used'] / 10;
            $promo_discount = $order['discount_amount'] - $coin_discount;
            ?>

            <?php if ($promo_discount > 0): ?>
            <div class="totals-row" style="color: #e53935;">
                <span>Voucher Discount:</span>
                <span>- RM <?php echo number_format($promo_discount, 2); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($order['coins_used'] > 0): ?>
            <div class="totals-row" style="color: #e53935;">
                <span>Coins Reclaimed (<?php echo $order['coins_used']; ?> Coins):</span>
                <span>- RM <?php echo number_format($coin_discount, 2); ?></span>
            </div>
            <?php endif; ?>

            <div class="totals-row grand-total">
                <span>Total Paid:</span>
                <span>RM <?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>

        <div style="clear: both;"></div>

        <div style="margin-top: 80px; text-align: center; color: #777; font-size: 0.9rem; border-top: 1px solid #eee; padding-top: 20px;">
            <p>Thank you for shopping with GridCitY PC!</p>
            <p>This is a computer-generated document. No signature is required.</p>
        </div>

    </div>

</body>
</html>