<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

// 🌟 核心自动逻辑：实时检测 Processing 订单，超过 4 分钟自动改为 Shipped
$conn->query("UPDATE orders SET order_status = 'Shipped' 
              WHERE order_status = 'Processing' 
              AND order_date < NOW() - INTERVAL 4 MINUTE");

// 🌟 1. 捕捉排序参数
$current_sort = $_GET['sort'] ?? 'desc';
// 确定 SQL 的 ORDER BY 部分
$order_by = 'o.order_id DESC'; // 默认：最新订单
if ($current_sort === 'asc') $order_by = 'o.order_id ASC';
elseif ($current_sort === 'price_desc') $order_by = 'o.total_amount DESC';
elseif ($current_sort === 'price_asc') $order_by = 'o.total_amount ASC';

// 🌟 更新訂單狀態
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = trim($_POST['new_status']);
    $allowed = ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'];
    
    $saved_sort = isset($_POST['current_sort']) ? $_POST['current_sort'] : 'desc';
    
    if (in_array($new_status, $allowed)) {
        $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        if ($stmt->execute()) {
            header("Location: manage_orders.php?updated=1&sort=" . urlencode($saved_sort));
            exit();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Management - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .admin-sort-dropdown { background: rgba(0,0,0,0.6); color: #00f2fe; border: 1px solid rgba(0,242,254,0.3); padding: 8px 15px; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="admin-container">
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-shield-alt"></i> GridCity PC Admin</h3>
                <p style="color:#555; font-size:11px; font-family:'JetBrains Mono';">Unified Architecture v4.0</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <?php 
                $role = strtolower($_SESSION['admin_role'] ?? $_SESSION['role'] ?? '');
                if ($role === 'superadmin'): ?>
                    <li><a href="manage_staff.php"> Manage Staff</a></li>
                <?php endif; ?>
                <li><a href="manage_users.php"> Manage Customers</a></li>
                <li><a href="manage_categories.php">Categories</a></li>
                <li><a href="manage_products.php">Products</a></li> 
                <li><a href="manage_packages.php">Packages</a></li>
                <li><a href="manage_orders.php">Orders</a></li>
                <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h2 style="color: #00f2fe; margin:0;"><i class="fas fa-box-open"></i> Global Order Fulfillment Center</h2>
                </div>
                <form method="GET" action="manage_orders.php" id="sortForm">
                    <select name="sort" class="admin-sort-dropdown" onchange="document.getElementById('sortForm').submit();">
                        <option value="desc" <?php echo $current_sort == 'desc' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="asc" <?php echo $current_sort == 'asc' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="price_desc" <?php echo $current_sort == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="price_asc" <?php echo $current_sort == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                    </select>
                </form>
            </header>

            <?php if (isset($_GET['updated'])) echo "<div style='color:#00f2fe; background:rgba(0,242,254,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(0,242,254,0.3);'>✅ Logistics status updated successfully.</div>"; ?>

            <div class="table-container" style="background: rgba(0,0,0,0.4); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(0,242,254,0.2); text-align: left;">
                            <th style="padding:15px;">ID</th>
                            <th style="padding:15px;">Customer</th>
                            <th style="padding:15px;">Date</th>
                            <th style="padding:15px;">Items</th>
                            <th style="padding:15px;">Total</th>
                            <th style="padding:15px;">Status</th>
                            <th style="padding:15px;">Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 🌟 使用动态的 $order_by
                        $sql = "SELECT o.*, c.username FROM orders o JOIN customers c ON o.customer_id = c.customer_id ORDER BY $order_by";
                        $res = $conn->query($sql);
                        while ($row = $res->fetch_assoc()) {
                            $status = $row['order_status'];
                            $status_color = "#facc15";
                            if ($status == 'Processing') $status_color = "#00f2fe";
                            elseif ($status == 'Shipped') $status_color = "#a855f7";
                            elseif ($status == 'Completed') $status_color = "#00e676";
                            elseif ($status == 'Cancelled') $status_color = "#ff4d4d";

                            echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05);'>";
                            echo "<td style='padding:15px;'>#{$row['order_id']}</td>";
                            echo "<td style='padding:15px;'>" . htmlspecialchars($row['username']) . "</td>";
                            echo "<td style='padding:15px;'>" . date('d M, Y', strtotime($row['order_date'])) . "</td>";
                            echo "<td style='padding:15px;'>...</td>"; 
                            echo "<td style='padding:15px; color:#00e676; font-weight:bold;'>RM " . number_format($row['total_amount'], 2) . "</td>";
                            echo "<td style='padding:15px; font-weight:bold; color:$status_color'>$status</td>";
                            echo "<td style='padding:15px;'>
                                    <form method='POST'>
                                        <input type='hidden' name='order_id' value='{$row['order_id']}'>
                                        <input type='hidden' name='current_sort' value='$current_sort'>
                                        <select name='new_status' style='background:#000; color:#fff; border:1px solid #333; padding:5px; border-radius:4px;'>
                                            <option value='Pending' ".($status=='Pending'?'selected':'').">Pending</option>
                                            <option value='Processing' ".($status=='Processing'?'selected':'').">Processing</option>
                                            <option value='Shipped' ".($status=='Shipped'?'selected':'').">Shipped</option>
                                            <option value='Completed' ".($status=='Completed'?'selected':'').">Completed</option>
                                        </select>
                                        <button type='submit' name='update_status' style='background:#00f2fe; color:#000; border:none; padding:5px 10px; cursor:pointer;'>Go</button>
                                    </form>
                                  </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>