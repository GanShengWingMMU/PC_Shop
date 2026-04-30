<?php
session_start();
include 'db_connect.php'; 

// 🌟 保安代码：允许 admin 和 superadmin 进入
if (!isset($_SESSION['role']) || (strtolower($_SESSION['role']) !== 'admin' && strtolower($_SESSION['role']) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

// ==========================================
// 处理更新订单状态的逻辑
// ==========================================
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    
    // 更新数据库里的订单状态
    $sql_update = "UPDATE orders SET order_status = '$new_status' WHERE order_id = $order_id";
    if (mysqli_query($conn, $sql_update)) {
        header("Location: manage_orders.php?updated=1");
        exit();
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
        .badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .badge-pending { background: rgba(243, 156, 18, 0.1); color: var(--accent-warning); border: 1px solid rgba(243, 156, 18, 0.3); }
        .badge-processing { background: rgba(0, 242, 254, 0.1); color: var(--accent-blue); border: 1px solid rgba(0, 242, 254, 0.3); }
        .badge-shipped { background: rgba(155, 89, 182, 0.1); color: #9b59b6; border: 1px solid rgba(155, 89, 182, 0.3); }
        .badge-completed { background: rgba(0, 230, 118, 0.1); color: #00e676; border: 1px solid rgba(0, 230, 118, 0.3); }
        .badge-cancelled { background: rgba(255, 77, 77, 0.1); color: var(--accent-danger); border: 1px solid rgba(255, 77, 77, 0.3); }
        
        /* 下拉菜单暗黑美化 */
        .status-select {
            background: var(--bg-main); color: var(--text-main); border: 1px solid var(--border-color);
            padding: 6px; border-radius: 4px; font-family: 'Inter', sans-serif; cursor: pointer;
        }
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
            <li><a href="manage_orders.php">Orders</a></li>
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
                <p style="color: var(--text-muted); margin-top: 5px;">View and update order statuses.</p>
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
                        <th width="10%">Order ID</th>
                        <th width="15%">Customer</th> 
                        <th width="15%">Date</th>
                        <th width="15%">Total Amount</th>
                        <th width="15%">Current Status</th>
                        <th width="30%">Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // 🌟 联合查询
                    $sql_orders = "SELECT o.*, c.username 
                                   FROM orders o 
                                   LEFT JOIN customers c ON o.customer_id = c.customer_id 
                                   ORDER BY o.order_id DESC";
                    
                    $res_orders = mysqli_query($conn, $sql_orders);

                    if ($res_orders && mysqli_num_rows($res_orders) > 0) {
                        while($row = mysqli_fetch_assoc($res_orders)) {
                            echo "<tr>";
                            echo "<td><strong>#" . $row['order_id'] . "</strong></td>";
                            
                            // 显示顾客名字
                            $customer_name = !empty($row['username']) ? htmlspecialchars($row['username']) : 'Guest';
                            echo "<td><i class='fas fa-user' style='color: var(--text-muted); font-size: 12px;'></i> " . $customer_name . "</td>";
                            
                            // 显示日期
                            $date = isset($row['order_date']) ? date('Y-m-d H:i', strtotime($row['order_date'])) : 'N/A';
                            echo "<td style='color: var(--text-muted); font-size: 0.9rem;'>" . $date . "</td>";
                            
                            // 显示总金额
                            $amount = isset($row['total_amount']) ? number_format($row['total_amount'], 2) : '0.00';
                            echo "<td><strong style='color: var(--accent-blue);'>RM " . $amount . "</strong></td>";
                            
                            // 🌟 动态显示不同颜色的状态徽章 
                            $status = isset($row['order_status']) ? $row['order_status'] : 'Pending';
                            $badge_class = 'badge-pending'; // 默认
                            if ($status == 'Processing') $badge_class = 'badge-processing';
                            if ($status == 'Shipped') $badge_class = 'badge-shipped';
                            if ($status == 'Completed') $badge_class = 'badge-completed';
                            if ($status == 'Cancelled') $badge_class = 'badge-cancelled';
                            
                            echo "<td><span class='badge {$badge_class}'>{$status}</span></td>";
                            
                            // 🌟 更新状态的操作区
                            echo "<td>
                                    <form action='manage_orders.php' method='POST' style='display:flex; gap:10px; align-items:center;'>
                                        <input type='hidden' name='order_id' value='" . $row['order_id'] . "'>
                                        
                                        <select name='new_status' class='status-select'>
                                            <option value='Pending' " . ($status == 'Pending' ? 'selected' : '') . ">Pending</option>
                                            <option value='Processing' " . ($status == 'Processing' ? 'selected' : '') . ">Processing</option>
                                            <option value='Shipped' " . ($status == 'Shipped' ? 'selected' : '') . ">Shipped</option>
                                            <option value='Completed' " . ($status == 'Completed' ? 'selected' : '') . ">Completed</option>
                                            <option value='Cancelled' " . ($status == 'Cancelled' ? 'selected' : '') . ">Cancelled</option>
                                        </select>
                                        
                                        <button type='submit' name='update_status' class='btn-action' style='padding: 6px 12px;'>Update</button>
                                        
                                    </form>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center; padding: 40px; color: var(--text-muted);'>
                                <i class='fas fa-box-open' style='font-size: 2rem; margin-bottom: 10px; display: block;'></i>
                                No orders found yet.
                              </td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>