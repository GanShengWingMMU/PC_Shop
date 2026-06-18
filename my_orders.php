<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// 自動將送達超過7天的訂單標記為完成
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
        $new_name = htmlspecialchars(trim($_POST['new_order_name']));
        
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

    // === 新增：處理退貨/退款請求 ===
  // === 升級版：處理退貨/退款請求 (包含圖片上傳) ===
    if ($_POST['action'] === 'request_return') {
        $return_order_id = intval($_POST['order_id']);
        $return_detail_id = intval($_POST['order_detail_id']); 
        $return_reason = htmlspecialchars(trim($_POST['return_reason']));
        
        // 處理圖片上傳邏輯
        $image_path = NULL;
        if (isset($_FILES['return_image']) && $_FILES['return_image']['error'] == 0) {
            $target_dir = "uploads/returns/";
            // 如果資料夾不存在，就自動建立它
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_extension = strtolower(pathinfo($_FILES["return_image"]["name"], PATHINFO_EXTENSION));
            // 產生唯一檔名避免覆蓋
            $new_filename = uniqid("return_") . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            // 允許的圖片格式
            $allowed_types = array("jpg", "jpeg", "png", "gif");
            if (in_array($file_extension, $allowed_types)) {
                if (move_uploaded_file($_FILES["return_image"]["tmp_name"], $target_file)) {
                    $image_path = $target_file; // 上傳成功，記錄路徑
                }
            }
        }

        if (!empty($return_reason)) {
            $check_order = $conn->prepare("SELECT order_id FROM orders WHERE order_id = ? AND customer_id = ?");
            $check_order->bind_param("ii", $return_order_id, $customer_id);
            $check_order->execute();
            if ($check_order->get_result()->num_rows > 0) {
                // 更新資料庫，包含 return_image
                $return_stmt = $conn->prepare("UPDATE order_details SET return_status = 'Pending', return_reason = ?, return_image = ? WHERE order_detail_id = ? AND order_id = ?");
                $return_stmt->bind_param("ssii", $return_reason, $image_path, $return_detail_id, $return_order_id);
                if ($return_stmt->execute()) {
                    $_SESSION['success_msg'] = "Return request with evidence submitted successfully.";
                }
                $return_stmt->close();
            }
            $check_order->close();
        }
        $current_tab = isset($_POST['current_status']) ? $_POST['current_status'] : 'Completed';
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
    
    // 改良 SQL 查詢：新增了 packages (pk) 的 Join，這樣買套裝主機也能正確顯示名稱了！
    $query_details = "
        SELECT od.*, p.product_name, sb.build_name, pk.package_name,
               (SELECT COUNT(*) FROM reviews r WHERE r.product_id = od.product_id AND r.customer_id = ?) AS is_reviewed
        FROM order_details od
        LEFT JOIN products p ON od.product_id = p.product_id
        LEFT JOIN saved_builds sb ON od.pc_build = sb.pc_build
        LEFT JOIN packages pk ON od.package_id = pk.package_id
        WHERE od.order_id = ?
    ";
    $stmt_details = $conn->prepare($query_details);
    $stmt_details->bind_param("ii", $customer_id, $order_id); 
    $stmt_details->execute();
    $result_details = $stmt_details->get_result();
    
    $items = [];
    while ($item = $result_details->fetch_assoc()) {
        // 動態決定商品名稱 (普通商品 / 自組電腦 / 套裝主機)
        $item_display_name = $item['product_name'];
        if (!$item_display_name) {
            if ($item['build_name']) {
                $item_display_name = "Custom Rig: " . $item['build_name'];
            } elseif ($item['package_name']) {
                $item_display_name = "Package: " . $item['package_name'];
            } else {
                $item_display_name = "Unknown Item";
            }
        }
        $item['display_name'] = $item_display_name;
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

    <style>
    /* 動作按鈕區域容器 */
    .item-action-row {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px dashed rgba(255, 255, 255, 0.1);
    }

    /* 退貨按鈕 (可點擊) */
    .btn-outline-muted {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #aaa;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-outline-muted:hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.4);
        color: #fff;
    }

    /* 退貨處理中標籤 (不可點擊) */
    .badge-outline-orange {
        background: rgba(235, 94, 40, 0.1); /* 極淡的主題橘色背景 */
        border: 1px solid rgba(235, 94, 40, 0.4);
        color: #eb5e28;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        user-select: none;
    }

    /* 評價按鈕 */
    .btn-solid-orange {
        background: #eb5e28;
        color: #fff;
        border: 1px solid #eb5e28;
        padding: 6px 16px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-solid-orange:hover {
        background: #d05020;
        box-shadow: 0 4px 10px rgba(235, 94, 40, 0.2);
    }

    /* 再次購買按鈕 */
    .btn-outline-gold {
        background: transparent;
        border: 1px solid #ffd700;
        color: #ffd700;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 0.85rem;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-outline-gold:hover {
        background: rgba(255, 215, 0, 0.1);
        box-shadow: 0 4px 10px rgba(255, 215, 0, 0.1);
    }

    /* 已評價文字 */
    .badge-text-green {
        color: #4CAF50;
        font-size: 0.85rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
</style>
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

<div class="order-body" style="display: flex; flex-direction: column; width: 100%;">
    <?php 
    $all_returned = true; // 用來檢查是不是所有東西都已經退貨了
    foreach ($order['items'] as $item): 
        if (empty($item['return_status'])) $all_returned = false;
    ?>
        <div class="order-item-wrapper" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px dashed rgba(255,255,255,0.1);">
            
            <div class="item-info" style="display: flex; gap: 15px; align-items: center; flex: 1;">
                <div class="item-thumb" style="width: 50px; height: 50px; background: rgba(255,255,255,0.05); border-radius: 8px; display: flex; justify-content: center; align-items: center;">
                    <i class="fa-solid <?php echo strpos(strtolower($item['display_name']), 'custom') !== false ? 'fa-screwdriver-wrench' : 'fa-box'; ?>" style="font-size: 1.5rem; color: var(--text-muted);"></i>
                </div>
                <div>
                    <h4 style="color: var(--text-main); margin: 0 0 5px 0; font-size: 1rem;">
                        <?php echo htmlspecialchars($item['display_name']); ?>
                    </h4>
                    <span class="specs">Qty: <?php echo $item['quantity']; ?></span>
                </div>
            </div>

           <div class="item-actions" style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                <?php if ($order['order_status'] == 'Completed'): ?>
                    <div style="display: flex; gap: 8px; align-items: center;"> <?php if (!empty($item['product_id'])): ?>
                            <?php if ($item['is_reviewed'] > 0): ?>
                                <span class="badge-text-green" style="font-size: 0.75rem; padding: 4px 0;">
                                    <i class="fa-solid fa-check-double"></i> Reviewed
                                </span>
                            <?php else: ?>
                                <a href="leave_review.php?product_id=<?php echo $item['product_id']; ?>" class="btn-solid-orange" style="font-size: 0.75rem; padding: 4px 10px;">
                                    <i class="fa-solid fa-star"></i> Review
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!empty($item['return_status'])): ?>
                            <span class="badge-outline-orange" style="font-size: 0.75rem; padding: 4px 10px;">
                                <i class="fa-solid fa-clock-rotate-left"></i> Return: <?php echo htmlspecialchars($item['return_status']); ?>
                            </span>
                        <?php else: ?>
                            <button type="button" class="btn-outline-muted" style="font-size: 0.75rem; padding: 4px 10px;" onclick="promptReturn(<?php echo $order['order_id']; ?>, <?php echo $item['order_detail_id']; ?>)">
                                <i class="fa-solid fa-box-archive"></i> Return
                            </button>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>
                
                <div style="color: var(--text-main); font-weight: bold; font-size: 1.1rem; font-family: 'JetBrains Mono', monospace;">
                    RM <?php echo number_format($item['unit_price'], 2); ?>
                </div>
            </div>
            </div>
    <?php endforeach; ?>
</div>

<div class="order-footer" style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px;">
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
    <div style="text-align: right;">
        <span class="specs" style="margin-right: 15px;">Order Total:</span>
        <span style="color: var(--accent-blue); font-size: 1.4rem; font-weight: bold;">RM <?php echo number_format($order['total_amount'], 2); ?></span>
    </div>
</div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- 重新命名表單 -->
    <form id="renameForm" method="POST" action="my_orders.php" style="display: none;">
        <input type="hidden" name="action" value="rename_order">
        <input type="hidden" name="order_id" id="renameOrderId">
        <input type="hidden" name="new_order_name" id="newOrderName">
        <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($current_filter); ?>">
    </form>



    <style>
        .custom-modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.8); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(5px);
        }
        .custom-modal {
            background: #111; border: 1px solid rgba(235, 94, 40, 0.3); border-radius: 12px;
            width: 90%; max-width: 450px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .custom-modal h3 { color: #fff; margin-top: 0; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .custom-modal label { color: #aaa; font-size: 0.9rem; margin-bottom: 8px; display: block; }
        .custom-modal textarea, .custom-modal input[type="file"] {
            width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            color: #fff; padding: 10px; border-radius: 6px; margin-bottom: 15px;
        }
        .custom-modal .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
    </style>

    <div id="returnModal" class="custom-modal-overlay">
        <div class="custom-modal">
            <h3><i class="fa-solid fa-box-archive" style="color: #eb5e28;"></i> Request Return</h3>
            <form id="returnForm" method="POST" action="my_orders.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="request_return">
                <input type="hidden" name="order_id" id="modalOrderId">
                <input type="hidden" name="order_detail_id" id="modalDetailId">
                <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($current_filter); ?>">
                
                <label>Reason for return (Required)*</label>
                <textarea name="return_reason" id="modalReason" rows="3" required placeholder="E.g., Damaged, Wrong Item..."></textarea>
                
                <label>Upload Evidence (Optional Photo)</label>
                <input type="file" name="return_image" accept="image/*">
                
                <div class="modal-actions">
                    <button type="button" class="btn-outline-muted" onclick="closeReturnModal()">Cancel</button>
                    <button type="submit" class="btn-solid-orange">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function promptRename(orderId, currentName) {
            let newName = prompt("Enter a custom name for this order:", currentName);
            if (newName !== null && newName.trim() !== "") {
                document.getElementById('renameOrderId').value = orderId;
                document.getElementById('newOrderName').value = newName;
                document.getElementById('renameForm').submit();
            }
        }

        // === 新增：觸發退貨的 JavaScript ===
// === 修改：打開自製的退貨視窗 ===
        function promptReturn(orderId, orderDetailId) {
            // 將 ID 塞進視窗裡的隱藏欄位
            document.getElementById('modalOrderId').value = orderId;
            document.getElementById('modalDetailId').value = orderDetailId;
            document.getElementById('modalReason').value = ""; // 清空上次填寫的理由
            
            // 顯示視窗
            document.getElementById('returnModal').style.display = 'flex';
        }

        // === 新增：關閉退貨視窗 ===
        function closeReturnModal() {
            document.getElementById('returnModal').style.display = 'none';
        }

        // === 新增：觸發整筆退貨的 JavaScript ===
 
    </script>
</body>
</html>