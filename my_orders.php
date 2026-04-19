<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

$auto_complete_query = "UPDATE orders SET order_status = 'Completed' WHERE order_status = 'Delivered' AND order_date <= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$conn->query($auto_complete_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'complete_order') {
        $complete_order_id = intval($_POST['order_id']);
        $update_stmt = $conn->prepare("UPDATE orders SET order_status = 'Completed' WHERE order_id = ? AND customer_id = ? AND order_status = 'Delivered'");
        $update_stmt->bind_param("ii", $complete_order_id, $customer_id);
        if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
            $_SESSION['success_msg'] = "Order completed! You can now rate your products.";
        }
        $update_stmt->close();
        header("Location: my_orders.php?status=Completed");
        exit();
    }
    
    if ($_POST['action'] === 'rename_order') {
        $rename_order_id = intval($_POST['order_id']);
        $new_name = trim(mysqli_real_escape_string($conn, $_POST['new_order_name']));
        
        if (!empty($new_name)) {
            $rename_stmt = $conn->prepare("UPDATE orders SET order_name = ? WHERE order_id = ? AND customer_id = ?");
            $rename_stmt->bind_param("sii", $new_name, $rename_order_id, $customer_id);
            if ($rename_stmt->execute()) {
                $_SESSION['success_msg'] = "Order renamed successfully!";
            }
            $rename_stmt->close();
        }
        $current_tab = isset($_POST['current_status']) ? $_POST['current_status'] : 'All';
        header("Location: my_orders.php?status=" . urlencode($current_tab));
        exit();
    }
}

$current_filter = isset($_GET['status']) ? $_GET['status'] : 'All';

$orders = [];
if ($current_filter === 'All') {
    $query_orders = "SELECT * FROM orders WHERE customer_id = ? ORDER BY order_date DESC";
    $stmt = $conn->prepare($query_orders);
    $stmt->bind_param("i", $customer_id);
} else {
    $query_orders = "SELECT * FROM orders WHERE customer_id = ? AND order_status = ? ORDER BY order_date DESC";
    $stmt = $conn->prepare($query_orders);
    $stmt->bind_param("is", $customer_id, $current_filter);
}

$stmt->execute();
$result_orders = $stmt->get_result();

while ($row = $result_orders->fetch_assoc()) {
    $order_id = $row['order_id'];
    
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
    $result_details = $stmt_details->get_result();
    
    $items = [];
    while ($item = $result_details->fetch_assoc()) {
        $items[] = $item;
    }
    $stmt_details->close();
    
    $row['items'] = $items;
    $orders[] = $row;
}
$stmt->close();


$show_timeline = true;
$tab_index = -1;
switch($current_filter) {
    case 'Pending': $tab_index = 0; break;
    case 'Processing': $tab_index = 1; break;
    case 'Shipped': $tab_index = 2; break;
    case 'Delivered': $tab_index = 3; break;
    case 'Completed': $tab_index = 4; break;
    default: $show_timeline = false; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GridCitY PC - My Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">

</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="main-container cart-page-wrapper">
        <div class="auth-title" style="text-align: left; margin-bottom: 20px;">
            <h2><i class="fa-solid fa-box-open"></i> Order Tracking</h2>
            <p class="specs">Track your parcels and review your past purchases.</p>
        </div>

        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="cart-empty-state" style="border-color: #4CAF50; padding: 15px; margin-bottom: 25px; background: rgba(76, 175, 80, 0.1);">
                <p style="color: #4CAF50; margin: 0; font-weight: bold;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
                </p>
            </div>
        <?php endif; ?>

<div class="order-tabs">
            <a href="my_orders.php?status=All" class="<?php echo $current_filter == 'All' ? 'active' : ''; ?>">All</a>
            
            <a href="my_orders.php?status=Pending" class="<?php echo $current_filter == 'Pending' ? 'active' : ''; ?>">Pending</a>
            
            <a href="my_orders.php?status=Processing" class="<?php echo $current_filter == 'Processing' ? 'active' : ''; ?>">Processing</a>
            
            <a href="my_orders.php?status=Shipped" class="<?php echo $current_filter == 'Shipped' ? 'active' : ''; ?>">Shipped</a>
            <a href="my_orders.php?status=Delivered" class="<?php echo $current_filter == 'Delivered' ? 'active' : ''; ?>">Delivered</a>
            <a href="my_orders.php?status=Completed" class="<?php echo $current_filter == 'Completed' ? 'active' : ''; ?>">Completed</a>
        </div>

        <?php if ($show_timeline): ?>
            <div class="global-timeline">
                <div class="timeline">
                    <div class="step <?php echo $tab_index >= 0 ? 'active' : ''; ?>">
                        <div class="icon"><i class="fa-solid fa-clipboard-list"></i></div>
                        <div class="text">Placed</div>
                    </div>
                    <div class="step <?php echo $tab_index >= 1 ? 'active' : ''; ?>">
                        <div class="icon"><i class="fa-solid fa-box-open"></i></div>
                        <div class="text">Preparing</div>
                    </div>
                    <div class="step <?php echo $tab_index >= 2 ? 'active' : ''; ?>">
                        <div class="icon"><i class="fa-solid fa-truck-fast"></i></div>
                        <div class="text">Shipped</div>
                    </div>
                    <div class="step <?php echo $tab_index >= 3 ? 'active' : ''; ?>">
                        <div class="icon"><i class="fa-solid fa-house-circle-check"></i></div>
                        <div class="text">Delivered</div>
                    </div>
                    <div class="step <?php echo $tab_index >= 4 ? 'active' : ''; ?>">
                        <div class="icon"><i class="fa-solid fa-star"></i></div>
                        <div class="text">Completed</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="cart-empty-state">
                <i class="fa-solid fa-box" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 15px;"></i>
                <h3 style="color: var(--text-main);">No orders found in this category.</h3>
            </div>
        <?php else: ?>
            
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="status-badge badge-<?php echo htmlspecialchars($order['order_status']); ?>">
                                <?php echo htmlspecialchars($order['order_status']); ?>
                            </span>
                            <div style="border-left: 1px solid rgba(255,255,255,0.2); height: 20px; margin: 0 5px;"></div>
                            <span style="color: var(--text-main); font-weight: bold; font-size: 1.1rem;">
                                <?php echo htmlspecialchars($order['order_name'] ?? 'Order #' . str_pad($order['order_id'], 5, "0", STR_PAD_LEFT)); ?>
                            </span>
                            <?php if (in_array($order['order_status'], ['Pending', 'Processing', 'Shipped'])): ?>
    <button class="btn-rename" onclick="promptRename(<?php echo $order['order_id']; ?>, '<?php echo addslashes(htmlspecialchars($order['order_name'] ?? 'Order #'.$order['order_id'])); ?>')">
        <i class="fa-solid fa-pen"></i>
    </button>
<?php endif; ?>
                        </div>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <span class="specs"><i class="fa-regular fa-clock"></i> <?php echo date('d M Y', strtotime($order['order_date'])); ?></span>
                            <a href="invoice.php?id=<?php echo $order['order_id']; ?>" target="_blank" class="btn" style="padding: 4px 10px; border: 1px solid var(--text-muted); color: var(--text-main); border-radius: 4px; font-size: 0.8rem; text-decoration: none;">
                                <i class="fa-solid fa-receipt"></i>
                            </a>
                        </div>
                    </div>

                    <div class="order-body">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="order-item">
                                <div style="flex: 3;">
                                    <h4 style="color: var(--text-main); margin: 0 0 5px 0;">
                                        <?php echo htmlspecialchars($item['product_name'] ? $item['product_name'] : "Custom Rig: " . $item['build_name']); ?>
                                    </h4>
                                    <span class="specs">Qty: <?php echo $item['quantity']; ?></span>
                                </div>
                                <div style="flex: 1; text-align: right; color: var(--text-main); font-weight: bold;">
                                    RM <?php echo number_format($item['unit_price'], 2); ?>
                                    
                                    <?php if (!empty($item['product_id']) && $order['order_status'] == 'Completed'): ?>
                                        <br>
                                        <a href="leave_review.php?product_id=<?php echo $item['product_id']; ?>" style="display: inline-block; margin-top: 8px; color: #ffd700; font-size: 0.8rem; text-decoration: none; border: 1px solid #ffd700; padding: 4px 10px; border-radius: 4px; background: rgba(255, 215, 0, 0.05); transition: 0.3s;">
                                            <i class="fa-regular fa-star"></i> Rate
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-footer">
                        <div>
                            <?php if ($order['order_status'] == 'Delivered'): ?>
                                <form method="POST" action="my_orders.php" style="margin: 0;">
                                    <input type="hidden" name="action" value="complete_order">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <button type="submit" class="btn-received" onclick="return confirm('Have you received the products in good condition?');">
                                        <i class="fa-solid fa-box-open"></i> Order Received
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="specs" style="margin-right: 15px;">Order Total:</span>
                            <span style="color: var(--accent-blue); font-size: 1.4rem; font-weight: bold;">RM <?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>

    <form id="renameForm" method="POST" action="my_orders.php" style="display: none;">
        <input type="hidden" name="action" value="rename_order">
        <input type="hidden" name="order_id" id="renameOrderId">
        <input type="hidden" name="new_order_name" id="newOrderName">
        <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($current_filter); ?>">
    </form>

    <script>
        function promptRename(orderId, currentName) {
            let newName = prompt("Enter a custom name for this order:", currentName);
            if (newName !== null && newName.trim() !== "") {
                document.getElementById('renameOrderId').value = orderId;
                document.getElementById('newOrderName').value = newName;
                document.getElementById('renameForm').submit();
            }
        }
    </script>
</body>
</html>