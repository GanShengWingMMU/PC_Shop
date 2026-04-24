<?php
session_start();
require_once 'config.php';

// 1. 權限與購物車檢查
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$error_message = "";

// 取得顧客目前的錢包餘額與金幣
$user_query = "SELECT wallet_balance, reward_coins FROM customers WHERE customer_id = ?";
$stmt_user = $conn->prepare($user_query);
$stmt_user->bind_param("i", $customer_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$current_balance = $user_data['wallet_balance'];
$current_coins = $user_data['reward_coins'];

$default_address = "";
$address_query = "SELECT full_address FROM customer_addresses WHERE customer_id = ? AND is_default = 1 LIMIT 1";
$stmt_addr = $conn->prepare($address_query);
$stmt_addr->bind_param("i", $customer_id);
$stmt_addr->execute();
$addr_result = $stmt_addr->get_result();

if ($addr_result->num_rows > 0) {
    $addr_data = $addr_result->fetch_assoc();
    $default_address = $addr_data['full_address'];
}
$stmt_addr->close();

// 取得購物車內容與計算原始總價
$cart_query = "SELECT c.cart_id, c.quantity, 
                      p.product_id, p.product_name, p.price AS product_price, p.category_id,
                      b.pc_build, b.build_name, b.total_price AS build_price
               FROM shopping_cart c
               LEFT JOIN products p ON c.product_id = p.product_id
               LEFT JOIN saved_builds b ON c.pc_build = b.pc_build
               WHERE c.customer_id = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$cart_result = $stmt->get_result();

$cart_items = [];
$total_amount = 0;

if ($cart_result->num_rows === 0) {
    header("Location: cart.php");
    exit();
}

while ($row = $cart_result->fetch_assoc()) {
    $cart_items[] = $row;
    $price = $row['product_id'] ? $row['product_price'] : $row['build_price'];
    $total_amount += ($price * $row['quantity']);
}
$stmt->close();

// ==========================================
// 🧠 企业级动态结算引擎 (Enterprise Checkout Engine)
// 统一根据购买力度进行划档打折
// ==========================================
$system_discount_amount = 0;
$system_discount_name = "";

if ($total_amount >= 8000) {
    $system_discount_name = "Enthusiast Auto-Discount (8% OFF)";
    $system_discount_amount = $total_amount * 0.08;
} elseif ($total_amount >= 5000) {
    $system_discount_name = "Pro Tier Auto-Discount (5% OFF)";
    $system_discount_amount = $total_amount * 0.05;
} elseif ($total_amount >= 3000) {
    $system_discount_name = "Mainstream Auto-Discount (3% OFF)";
    $system_discount_amount = $total_amount * 0.03;
}


// 處理結帳送出表單 (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = mysqli_real_escape_string($conn, $_POST['shipping_address']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    
    // 計算金幣折扣邏輯 (10 Coins = RM 1)
    $use_coins = isset($_POST['use_coins']) ? true : false;
    $coins_used = 0;
    $coins_discount_amount = 0;

    if ($use_coins && $current_coins > 0) {
        $coins_used = $current_coins;
        $coins_discount_amount = floor($current_coins / 10); 
    }

    // 🌟 计算最终总折扣和应付总额
    $total_discount = $system_discount_amount + $coins_discount_amount;
    $final_amount = $total_amount - $total_discount;
    if ($final_amount < 0) $final_amount = 0; 

    // 金流安全檢查
    if ($payment_method === 'E-Wallet' && $current_balance < $final_amount) {
        $error_message = "Insufficient E-Wallet balance! Please top up or choose another payment method.";
    } else {
        $conn->begin_transaction();

        try {
            // A. 寫入 orders 表 (记录总折扣额)
            $order_status = 'Pending';
            $insert_order = "INSERT INTO orders (customer_id, total_amount, discount_amount, coins_used, order_status, shipping_address) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_order = $conn->prepare($insert_order);
            $stmt_order->bind_param("iddiss", $customer_id, $final_amount, $total_discount, $coins_used, $order_status, $shipping_address);
            $stmt_order->execute();
            $order_id = $stmt_order->insert_id;
            $stmt_order->close();

            // B. 寫入 order_details 表
            $insert_detail = "INSERT INTO order_details (order_id, product_id, pc_build, quantity, unit_price) VALUES (?, ?, ?, ?, ?)";
            $stmt_detail = $conn->prepare($insert_detail);
            foreach ($cart_items as $item) {
                $pid = $item['product_id'] ? $item['product_id'] : NULL;
                $build_id = $item['pc_build'] ? $item['pc_build'] : NULL;
                $qty = $item['quantity'];
                $unit_price = $item['product_id'] ? $item['product_price'] : $item['build_price'];
                $stmt_detail->bind_param("iiidi", $order_id, $pid, $build_id, $qty, $unit_price);
                $stmt_detail->execute();
            }
            $stmt_detail->close();

            // C. 寫入 payments 表
            $payment_status = ($payment_method == 'Cash on Delivery') ? 'Pending' : 'Paid';
            $insert_payment = "INSERT INTO payments (order_id, payment_method, payment_status) VALUES (?, ?, ?)";
            $stmt_payment = $conn->prepare($insert_payment);
            $stmt_payment->bind_param("iss", $order_id, $payment_method, $payment_status);
            $stmt_payment->execute();
            $stmt_payment->close();

            // D. E-Wallet 扣款
            if ($payment_method === 'E-Wallet') {
                $deduct_wallet = "UPDATE customers SET wallet_balance = wallet_balance - ? WHERE customer_id = ?";
                $stmt_deduct = $conn->prepare($deduct_wallet);
                $stmt_deduct->bind_param("di", $final_amount, $customer_id);
                $stmt_deduct->execute();
                
                $type = 'Payment';
                $insert_trans = "INSERT INTO wallet_transactions (customer_id, type, amount) VALUES (?, ?, ?)";
                $stmt_trans = $conn->prepare($insert_trans);
                $neg_amount = -$final_amount; 
                $stmt_trans->bind_param("isd", $customer_id, $type, $neg_amount);
                $stmt_trans->execute();
            }

            // E. 扣除使用的金幣
            if ($coins_used > 0) {
                $deduct_coins = "UPDATE customers SET reward_coins = reward_coins - ? WHERE customer_id = ?";
                $stmt_coins = $conn->prepare($deduct_coins);
                $stmt_coins->bind_param("ii", $coins_used, $customer_id);
                $stmt_coins->execute();
            }

            // ==========================================
            // 🧠 终极黑科技：真实购买行为的 AI 深度追踪
            // ==========================================
            $add_gamer = 0; $add_creator = 0; $add_student = 0;
            $has_high_end_gpu = false; $has_high_end_cpu = false;

            foreach ($cart_items as $item) {
                if ($item['product_id']) {
                    if ($item['category_id'] == 4 && $item['product_price'] > 1500) $has_high_end_gpu = true;
                    if ($item['category_id'] == 1 && $item['product_price'] > 1200) $has_high_end_cpu = true;
                } elseif ($item['pc_build'] && $item['build_price'] > 4500) {
                    $has_high_end_gpu = true; 
                }
            }

            if ($has_high_end_gpu || $final_amount > 5000) {
                $add_gamer += (5 * 3); $add_creator += (1 * 3);
            } elseif ($has_high_end_cpu || $final_amount > 3000) {
                $add_creator += (5 * 3); $add_gamer += (2 * 3); $add_student += (1 * 3);
            } else {
                $add_student += (5 * 3); $add_creator += (2 * 3); $add_gamer += (1 * 3);
            }

            $update_dna = "UPDATE customers SET pref_gamer = pref_gamer + ?, pref_creator = pref_creator + ?, pref_student = pref_student + ? WHERE customer_id = ?";
            $stmt_dna = $conn->prepare($update_dna);
            $stmt_dna->bind_param("iiii", $add_gamer, $add_creator, $add_student, $customer_id);
            $stmt_dna->execute();
            $stmt_dna->close();

            // F. 清空購物車
            $clear_cart = "DELETE FROM shopping_cart WHERE customer_id = ?";
            $stmt_clear = $conn->prepare($clear_cart);
            $stmt_clear->bind_param("i", $customer_id);
            $stmt_clear->execute();

            $conn->commit();
            $_SESSION['success_msg'] = "Order placed successfully! Your Order ID is #$order_id";
            header("Location: profile.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Checkout failed. Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GridCitY PC - Secure Checkout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="main-container cart-page-wrapper">
        <div class="auth-title" style="text-align: left; margin-bottom: 30px;">
            <h2><i class="fa-solid fa-lock"></i> Secure Checkout</h2>
            <p class="specs">Complete your order details below. Automatic volume discounts apply.</p>
        </div>

        <?php if (!empty($error_message)) echo "<div class='text-danger' style='margin-bottom: 20px; border: 1px solid #ff4d4d; padding: 15px; border-radius: 8px; background: rgba(255,77,77,0.1);'><i class='fa-solid fa-circle-exclamation'></i> $error_message</div>"; ?>

        <div class="checkout-grid">
            
            <div class="auth-container" style="margin: 0; max-width: 100%;">
                <h3 style="margin-bottom: 20px; color: var(--text-main);"><i class="fa-solid fa-truck-fast"></i> Shipping & Payment</h3>
                
                <form action="checkout.php" method="POST" class="form" id="checkoutForm">
                    <div class="form-group input-group">
                        <label class="form-label" for="shipping_address">Full Delivery Address</label>
                        <textarea id="shipping_address" name="shipping_address" class="form-control" rows="3" required placeholder="Enter your full home address..."><?php echo htmlspecialchars($default_address); ?></textarea>
                    </div>

                    <?php if ($current_coins >= 10): ?>
                    <div class="form-group" style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <label style="display: flex; align-items: center; cursor: pointer; color: var(--text-main);">
                            <input type="checkbox" id="use_coins" name="use_coins" value="1" style="margin-right: 10px; width: 18px; height: 18px;">
                            <span>
                                Use <strong><?php echo $current_coins; ?> Coins</strong> to get 
                                <strong style="color: #ffd700;">RM <?php echo number_format(floor($current_coins/10), 2); ?> OFF</strong>
                            </span>
                        </label>
                    </div>
                    <?php endif; ?>

                    <div class="form-group input-group">
                        <label class="form-label" for="payment_method">Payment Method</label>
                        <select id="payment_method" name="payment_method" class="form-control" required>
                            <option value="E-Wallet">GridCitY E-Wallet (Bal: RM <?php echo number_format($current_balance, 2); ?>)</option>
                            <option value="Credit Card">Credit Card (Mock Payment)</option>
                            <option value="Online Banking">Online Banking (FPX)</option>
                            <option value="Cash on Delivery">Cash on Delivery (COD)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-submit-login" style="width: 100%; margin-top: 10px; padding: 15px; font-size: 1.1rem;">
                        <i class="fa-solid fa-check-double"></i> Confirm & Place Order
                    </button>
                </form>
            </div>

            <div class="auth-container" style="margin: 0; max-width: 100%; background: linear-gradient(145deg, var(--bg-darker), #151a25);">
                <h3 style="margin-bottom: 20px; color: var(--text-main);"><i class="fa-solid fa-receipt"></i> Order Summary</h3>
                
                <div class="summary-list">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="summary-item">
                            <div style="flex: 3;">
                                <span style="color: var(--text-main); display: block;">
                                    <?php echo htmlspecialchars($item['product_name'] ? $item['product_name'] : "Rig: " . $item['build_name']); ?>
                                </span>
                                <span class="specs">Qty: <?php echo $item['quantity']; ?></span>
                            </div>
                            <div style="flex: 1; text-align: right; color: var(--text-muted);">
                                RM <?php echo number_format($item['product_id'] ? $item['product_price'] : $item['build_price'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-item" style="margin-top: 15px; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 15px;">
                    <span class="specs">Subtotal (Raw Price)</span>
                    <span class="specs" id="subtotal-display">RM <?php echo number_format($total_amount, 2); ?></span>
                </div>
                
                <?php if ($system_discount_amount > 0): ?>
                <div class="summary-item" style="color: #00f2fe; margin-top: 10px; font-weight: bold;">
                    <span><i class="fas fa-bolt"></i> <?php echo $system_discount_name; ?></span>
                    <span>- RM <?php echo number_format($system_discount_amount, 2); ?></span>
                </div>
                <?php endif; ?>

                <div class="summary-item" id="discount-row" style="display: none; color: #ffd700; margin-top: 10px;">
                    <span><i class="fas fa-coins"></i> Coins Discount</span>
                    <span id="discount-display" data-discount="<?php echo floor($current_coins/10); ?>">- RM <?php echo number_format(floor($current_coins/10), 2); ?></span>
                </div>

                <div class="summary-item total-row" style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 15px; margin-top: 10px; font-size: 1.3rem;">
                    <span>Final Total</span>
                    <span id="final-total-display" style="color: #00e676; font-weight: 900;">
                        RM <?php echo number_format($total_amount - $system_discount_amount, 2); ?>
                    </span>
                </div>
            </div>

        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const useCoinsCheckbox = document.getElementById('use_coins');
            const discountRow = document.getElementById('discount-row');
            const finalTotalDisplay = document.getElementById('final-total-display');
            
            // 抓取 PHP 算好的基底数据
            const subtotal = <?php echo $total_amount; ?>;
            const systemDiscount = <?php echo $system_discount_amount; ?>;
            
            if (useCoinsCheckbox) {
                const coinDiscount = parseFloat(document.getElementById('discount-display').getAttribute('data-discount'));

                useCoinsCheckbox.addEventListener('change', function() {
                    let finalAmount = subtotal - systemDiscount; // 先扣除梯队折扣
                    
                    if (this.checked) {
                        discountRow.style.display = 'flex';
                        finalAmount = finalAmount - coinDiscount; // 再扣除金币
                    } else {
                        discountRow.style.display = 'none';
                    }
                    
                    if (finalAmount < 0) finalAmount = 0;
                    finalTotalDisplay.innerHTML = 'RM ' + finalAmount.toFixed(2);
                });
            }
        });
    </script>
</body>
</html>