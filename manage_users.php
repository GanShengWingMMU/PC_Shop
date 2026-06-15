<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

// 🌟 权限验证：只有老板(SuperAdmin)能进入
$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || strtolower($current_role) !== 'superadmin') {
    die("<div style='background:#000; color:#ff4d4d; padding:50px; text-align:center; font-family:monospace;'>ACCESS DENIED: ALPHA REQUIRED.</div>");
}

$message = "";
// 💡 注：为了数据库的绝对安全，已经把顶部的“删除(Neutralize)”后端逻辑彻底移除了，防止有人恶意通过网址删号。
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Customers - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>
    <div class="admin-container">
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-shield-alt"></i> GridCity PC Admin</h3>
                <p style="color:#555; font-size:11px; font-family:'JetBrains Mono';">Unified Architecture v4.0</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php" <?php if(basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php') echo 'class="active"'; ?>>Dashboard</a></li>
                
                <?php 
                $sidebar_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
                if (strtolower($sidebar_role) === 'superadmin'): 
                ?>
                    <li><a href="manage_staff.php" style="color: var(--accent-warning);" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_staff.php') echo 'class="active"'; ?>><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                    <li><a href="manage_users.php" class="active">Manage Customers</a></li>
                <?php endif; ?>
                
                <li><a href="manage_categories.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_categories.php') echo 'class="active"'; ?>>Categories</a></li>
                <li><a href="manage_products.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_products.php') echo 'class="active"'; ?>>Products</a></li> 
                <li><a href="manage_packages.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_packages.php') echo 'class="active"'; ?>>Packages</a></li>
                <li><a href="manage_orders.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_orders.php') echo 'class="active"'; ?>>Orders</a></li>
                
                <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="margin-bottom: 30px;">
                <h2 style="color: #ffd700; margin:0;"><i class="fas fa-users"></i> Customer Information</h2>
            </header>
            
            <?php echo $message; ?>
            
            <div class="table-container" style="background: rgba(0,0,0,0.4); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(255,215,0,0.2);">
                            <th style="padding:15px; color:#ffd700; text-align:left;">ID</th>
                            <th style="padding:15px; color:#ffd700; text-align:left;">Name / Email</th>
                            <th style="padding:15px; color:#ffd700; text-align:left;">Order Activity</th>
                            <th style="padding:15px; color:#ffd700; text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 🌟 升级的 SQL 查询：不仅算总订单数，还抓取最新一笔订单的状态！
                        $sql = "SELECT c.*, 
                                (SELECT COUNT(*) FROM orders WHERE customer_id = c.customer_id) as total_orders,
                                (SELECT order_status FROM orders WHERE customer_id = c.customer_id ORDER BY order_date DESC LIMIT 1) as latest_status
                                FROM customers c ORDER BY c.customer_id DESC";
                        $res = $conn->query($sql);
                        
                        while ($row = $res->fetch_assoc()) {
                            $uid = $row['customer_id'];
                            $total_orders = $row['total_orders'];
                            $latest_status = $row['latest_status'];

                            echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05);'>";
                            echo "<td style='padding:15px; color:#888; text-align:left;'>USR-$uid</td>";
                            echo "<td style='padding:15px; text-align:left;'><strong>".htmlspecialchars($row['username'])."</strong><br><span style='color:#64748b; font-size:12px;'>".htmlspecialchars($row['email'])."</span></td>";
                            
                            // 🌟 状态色彩同步系统 (完全贴合 Dashboard 颜色)
                            $status_bg = "rgba(255, 255, 255, 0.05)"; $status_color = "#888"; 
                            if ($latest_status == 'Pending') { $status_bg = "rgba(250, 204, 21, 0.1)"; $status_color = "#facc15"; }
                            elseif ($latest_status == 'Processing') { $status_bg = "rgba(0, 242, 254, 0.1)"; $status_color = "#00f2fe"; }
                            elseif ($latest_status == 'Shipped') { $status_bg = "rgba(168, 85, 247, 0.1)"; $status_color = "#a855f7"; }
                            elseif ($latest_status == 'Completed') { $status_bg = "rgba(0, 230, 118, 0.1)"; $status_color = "#00e676"; }
                            elseif ($latest_status == 'Cancelled') { $status_bg = "rgba(255, 77, 77, 0.1)"; $status_color = "#ff4d4d"; }

                            // 🌟 显示订单总数 + 最新状态 Badge
                            echo "<td style='padding:15px; text-align:left;'>
                                    <div style='margin-bottom:8px;'><span style='background:rgba(255,215,0,0.1); color:#ffd700; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:bold;'>{$total_orders} Orders</span></div>";
                            
                            if ($total_orders > 0) {
                                echo "<div><span style='font-size:11px; color:#64748b; margin-right:6px;'>Latest:</span><span style='padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; background: {$status_bg}; color: {$status_color}; border: 1px solid {$status_color};'>{$latest_status}</span></div>";
                            } else {
                                echo "<div><span style='font-size:11px; color:#64748b;'>No recent activity</span></div>";
                            }
                            echo "</td>";
                            
                            // 点击依然能跳转进去查看完整详细历史
                            echo "<td style='padding:15px; text-align:right;'>
                                    <a href='view_customer.php?id=$uid' class='btn-action' style='color:#00f2fe; border-color:#00f2fe; padding:6px 12px; font-size:12px; text-decoration:none; transition:0.3s;' onmouseover=\"this.style.background='rgba(0,242,254,0.1)'\" onmouseout=\"this.style.background='transparent'\"><i class='fas fa-eye'></i> Details</a>
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