<?php
session_start();
require_once 'config.php'; 

// 🌟 安全准入防线
if (!isset($_SESSION['admin_role']) || (strtolower($_SESSION['admin_role']) !== 'admin' && strtolower($_SESSION['admin_role']) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

// ==========================================
// 🛡️ 核心防御：更新订单状态 (Prepared Statement 防注入)
// ==========================================
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = trim($_POST['new_status']);
    
    // 白名单机制：只能改为系统允许的状态，防止黑客通过抓包修改成非法字符串
    $allowed_statuses = ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'];
    
    if (in_array($new_status, $allowed_statuses)) {
        $stmt_update = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stmt_update->bind_param("si", $new_status, $order_id);
        if ($stmt_update->execute()) {
            header("Location: manage_orders.php?updated=1");
            exit();
        }
        $stmt_update->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Orders - GridCity PC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css?v=<?php echo time(); ?>">
    <style>
        /* 订单状态专属徽章颜色 */
        .badge { padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;}
        .badge-pending { background: rgba(243, 156, 18, 0.1); color: var(--accent-warning); border: 1px solid rgba(243, 156, 18, 0.3); }
        .badge-processing { background: rgba(0, 242, 254, 0.1); color: var(--accent-blue); border: 1px solid rgba(0, 242, 254, 0.3); }
        .badge-shipped { background: rgba(155, 89, 182, 0.1); color: #9b59b6; border: 1px solid rgba(155, 89, 182, 0.3); }
        .badge-completed { background: rgba(0, 230, 118, 0.1); color: #00e676; border: 1px solid rgba(0, 230, 118, 0.3); }
        .badge-cancelled { background: rgba(255, 77, 77, 0.1); color: var(--accent-danger); border: 1px solid rgba(255, 77, 77, 0.3); }
        
        /* 下拉菜单暗黑美化 */
        .status-select {
            background: var(--bg-main); color: var(--text-main); border: 1px solid var(--border-color);
            padding: 6px; border-radius: 4px; font-family: 'Inter', sans-serif; cursor: pointer; font-size: 12px;
        }
        
        /* 商品清单美化 */
        .item-list { font-size: 12px; color: var(--text-main); line-height: 1.5; }
        .item-row { margin-bottom: 4px; padding-bottom: 4px; border-bottom: 1px dashed rgba(255,255,255,0.1); }
        .item-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .qty-badge { background: rgba(255,255,255,0.1); padding: 2px 5px; border-radius: 3px; color: var(--accent-blue); font-weight: bold; margin-right: 5px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>
            <img src="image/Admin_dashboard_logo.jpg" alt="GridCity PC Logo" class="sidebar-logo">
            <span>GridCity PC</span>
        </h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_products.php">Products</a></li> 
            <li><a href="manage_packages.php">Packages</a></li>
            <li><a href="manage_categories.php">Categories</a></li>
            <li><a href="manage_orders.php" class="active">Orders</a></li> 
            <li><a href="admin_builder.php">Build System</a></li>
            
            <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'superadmin'): ?>
                <li><a href="manage_staff.php" style="color: var(--accent-warning);"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                <li><a href="manage_users.php">Manage Customers</a></li>
            <?php endif; ?>
            
            <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
        </ul>
    </div>

    <div class="main-content">
        
        <div class="header-top" style="margin-bottom: 25px;">
            <div>
                <h1 style="margin: 0; font-size: 28px; color: var(--text-main);">Customer Orders</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">View purchased items and update order statuses.</p>
            </div>
        </div>

        <?php 
        if(isset($_GET['updated']) && $_GET['updated'] == 1) {
            echo "<div style='color: #00e676; background: rgba(0,230,118,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(0,230,118,0.3);'>✅ Order status updated successfully!</div>";
        }
        ?>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th width="8%">Order ID</th>
                        <th width="12%">Customer</th> 
                        <th width="12%">Date</th>
                        <th width="30%">Items Purchased</th>
                        <th width="12%">Total Amount</th>
                        <th width="10%">Status</th>
                        <th width="16%">Update Status</th>
                    </tr>
                </thead>
<tbody>
                    <?php
                    // 🌟 核心防御：联表查询并使用预处理
                    $sql = "SELECT o.*, c.username, c.email 
                            FROM orders o 
                            JOIN customers c ON o.customer_id = c.customer_id 
                            ORDER BY o.created_at DESC";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $res = $stmt->get_result();

                    if ($res->num_rows > 0) {
                        while ($row = $res->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>#" . $row['order_id'] . "</td>";
                            
                            // 🛡️ XSS 防御：买家名字和邮箱必须过滤
                            echo "<td><strong>" . htmlspecialchars($row['username']) . "</strong><br><span style='font-size:12px; color:#888;'>" . htmlspecialchars($row['email']) . "</span></td>";
                            
                            echo "<td>RM " . number_format($row['total_amount'], 2) . "</td>";
                            
                            // 🛡️ XSS 核弹拆除：订单地址极易被植入跨站脚本，必须套上 htmlspecialchars！
                            echo "<td style='max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;' title='" . htmlspecialchars($row['shipping_address']) . "'>" . htmlspecialchars($row['shipping_address']) . "</td>";
                            
                            echo "<td>" . date('d M Y, H:i', strtotime($row['created_at'])) . "</td>";
                            
                            $status = $row['order_status'];
                            $status_color = "";
                            if ($status == 'Pending') $status_color = "color: #facc15;";
                            elseif ($status == 'Processing') $status_color = "color: #00f2fe;";
                            elseif ($status == 'Shipped') $status_color = "color: #a855f7;";
                            elseif ($status == 'Completed') $status_color = "color: #00e676;";
                            elseif ($status == 'Cancelled') $status_color = "color: #ff4d4d;";
                            
                            echo "<td style='font-weight:bold; $status_color'>" . $status . "</td>";
                            
                            echo "<td>
                                    <form method='POST' action='manage_orders.php' style='display:flex; gap:5px; align-items:center;'>
                                        <input type='hidden' name='order_id' value='" . $row['order_id'] . "'>
                                        <select name='new_status' class='form-control' style='padding: 5px; font-size:12px; width:auto; border-radius:4px;'>
                                            <option value='Pending' " . ($status == 'Pending' ? 'selected' : '') . ">Pending</option>
                                            <option value='Processing' " . ($status == 'Processing' ? 'selected' : '') . ">Processing</option>
                                            <option value='Shipped' " . ($status == 'Shipped' ? 'selected' : '') . ">Shipped</option>
                                            <option value='Completed' " . ($status == 'Completed' ? 'selected' : '') . ">Completed</option>
                                            <option value='Cancelled' " . ($status == 'Cancelled' ? 'selected' : '') . ">Cancelled</option>
                                        </select>
                                        
                                        <button type='submit' name='update_status' class='btn-action' style='padding: 5px 10px; font-size:12px;'>Update</button>
                                    </form>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center; padding: 40px; color: var(--text-muted);'>
                                <i class='fas fa-box-open' style='font-size: 2rem; margin-bottom: 10px; display: block;'></i>
                                No orders found yet.
                              </td></tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>