<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php"); exit();
}

$message = "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Information - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        /* 🌟 统一的赛博霓虹表格样式 */
        .cyber-table { width: 100%; border-collapse: collapse; text-align: left; background: rgba(0,0,0,0.5); border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); }
        .cyber-table th { padding: 18px 20px; color:#ffd700; font-size: 12px; text-transform: uppercase; background: rgba(255,215,0,0.05); border-bottom: 2px solid rgba(255,215,0,0.2); letter-spacing: 1px; }
        .cyber-table td { padding: 18px 20px; border-bottom: 1px solid rgba(255,255,255,0.03); color: #fff; }
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; border: 1px solid; }
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
                ?>

                <?php if ($role === 'superadmin'): ?>
                    <li><a href="manage_staff.php"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                <?php endif; ?>

                <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Customers</a></li>
                
                <li><a href="manage_categories.php">Categories</a></li>
                <li><a href="manage_products.php">Products</a></li> 
                <li><a href="manage_packages.php">Packages</a></li>
                <li><a href="manage_orders.php">Orders</a></li>
                <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="margin-bottom: 30px;">
                <h2 style="color: #ffd700; margin:0;"><i class="fas fa-users"></i> Customer Information</h2>
            </header>
            
            <table class="cyber-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name / Email</th>
                        <th>Order Activity</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT c.*, 
                            (SELECT COUNT(*) FROM orders WHERE customer_id = c.customer_id) as total_orders,
                            (SELECT order_status FROM orders WHERE customer_id = c.customer_id ORDER BY order_date DESC LIMIT 1) as latest_status
                            FROM customers c ORDER BY c.customer_id DESC";
                    $res = $conn->query($sql);
                    
                    while ($row = $res->fetch_assoc()) {
                        $uid = $row['customer_id'];
                        $total = $row['total_orders'];
                        $status = $row['latest_status'];

                        // 状态颜色逻辑
                        $bg = "rgba(255,255,255,0.05)"; $col = "#888";
                        if ($status == 'Pending') { $bg = "rgba(250, 204, 21, 0.1)"; $col = "#facc15"; }
                        elseif ($status == 'Processing') { $bg = "rgba(0, 242, 254, 0.1)"; $col = "#00f2fe"; }
                        elseif ($status == 'Shipped') { $bg = "rgba(168, 85, 247, 0.1)"; $col = "#a855f7"; }
                        elseif ($status == 'Completed') { $bg = "rgba(0, 230, 118, 0.1)"; $col = "#00e676"; }
                        elseif ($status == 'Cancelled') { $bg = "rgba(255, 77, 77, 0.1)"; $col = "#ff4d4d"; }

                        echo "<tr>";
                        echo "<td style='color:#64748b; font-family:JetBrains Mono;'>USR-".$uid."</td>";
                        echo "<td><strong>".htmlspecialchars($row['username'])."</strong><br><span style='color:#64748b; font-size:12px;'>".htmlspecialchars($row['email'])."</span></td>";
                        echo "<td>
                                <div style='margin-bottom:5px;'><span style='background:rgba(255,215,0,0.1); color:#ffd700; padding:2px 8px; border-radius:4px; font-size:11px;'>$total Orders</span></div>";
                        if($total > 0) {
                            echo "<div><span style='font-size:10px; color:#64748b;'>Latest: </span><span class='status-badge' style='background:$bg; color:$col; border-color:$col;'>".($status ?: 'N/A')."</span></div>";
                        } else {
                            echo "<div><span style='font-size:10px; color:#64748b;'>No activity</span></div>";
                        }
                        echo "</td>";
                        echo "<td style='text-align:right;'>
                                <a href='view_customer.php?id=$uid' style='background:transparent; color:#00f2fe; border:1px solid #00f2fe; padding:6px 12px; border-radius:4px; text-decoration:none; font-size:12px; transition:0.3s;' onmouseover=\"this.style.background='rgba(0,242,254,0.1)'\" onmouseout=\"this.style.background='transparent'\"><i class='fas fa-eye'></i> Details</a>
                              </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>