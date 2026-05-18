<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
// 🌟 嚴格保安：只有 Superadmin 才能看顧客個資！
if (empty($current_role) || strtolower($current_role) !== 'superadmin') {
    die("<div style='background:#000; color:#ff4d4d; padding:50px; text-align:center; font-family:monospace; font-size:20px;'>ACCESS DENIED: CLEARANCE LEVEL ALPHA REQUIRED.</div>");
}

$message = "";
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt_del = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
    $stmt_del->bind_param("i", $delete_id);
    if ($stmt_del->execute()) {
        header("Location: manage_users.php?deleted=1");
        exit();
    }
    $stmt_del->close();
}
if (isset($_GET['deleted'])) $message = "<div style='color: #00e676; background: rgba(0,230,118,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px;'>✅ Customer profile neutralized.</div>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Customers - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>
    <div class="admin-container">
        <nav class="admin-sidebar">
            <div class="sidebar-header"><h3><i class="fas fa-shield-alt"></i> GridCity Admin</h3></div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_orders.php"><i class="fas fa-shopping-cart"></i> Manage Orders</a></li>
                <li><a href="manage_products.php"><i class="fas fa-box"></i> Manage Products</a></li>
                <li><a href="manage_categories.php"><i class="fas fa-tags"></i> Manage Categories</a></li>
                <li><a href="manage_packages.php"><i class="fas fa-layer-group"></i> Manage Packages</a></li>
                <li><a href="manage_staff.php"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                <li><a href="manage_users.php" class="active"><i class="fas fa-users"></i> Manage Customers</a></li>
                <li><a href="admin_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="margin-bottom: 30px;">
                <h2 style="color: #ffd700; margin:0;"><i class="fas fa-users-cog"></i> Citizen Database (CRM)</h2>
                <p style="color: #888; font-size:13px; margin-top:5px;">Superadmin clearance confirmed. Monitoring user telemetry.</p>
            </header>

            <?php echo $message; ?>

            <div class="table-container" style="background: rgba(0,0,0,0.4); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(255,215,0,0.2); text-align: left;">
                            <th style="padding:15px; color:#ffd700;">ID</th>
                            <th style="padding:15px; color:#ffd700;">Username & Email</th>
                            <th style="padding:15px; color:#ffd700;">Orders Placed</th>
                            <th style="padding:15px; color:#ffd700;">Registration Date</th>
                            <th style="padding:15px; color:#ffd700; text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 🌟 連動訂單表，計算忠誠度
                        $sql = "SELECT c.*, (SELECT COUNT(*) FROM orders WHERE customer_id = c.customer_id) as total_orders FROM customers c ORDER BY c.customer_id DESC";
                        $res = $conn->query($sql);
                        if ($res && $res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $uid = $row['customer_id'];
                                echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05);'>";
                                echo "<td style='padding:15px; font-family:\"JetBrains Mono\"; color:#888;'>USR-$uid</td>";
                                echo "<td style='padding:15px;'><strong>".htmlspecialchars($row['username'])."</strong><br><span style='color:#64748b; font-size:12px;'>".htmlspecialchars($row['email'])."</span></td>";
                                echo "<td style='padding:15px;'><span style='background:rgba(255,215,0,0.1); color:#ffd700; padding:4px 10px; border-radius:4px; font-family:\"JetBrains Mono\"; font-size:12px; font-weight:bold;'>{$row['total_orders']} Orders</span></td>";
                                echo "<td style='padding:15px; color:#94a3b8; font-size:13px;'>".date('d M Y', strtotime($row['created_at']))."</td>";
                                echo "<td style='padding:15px; text-align:right;'>
                                        <a href='manage_users.php?delete_id=$uid' class='btn-action' style='color:#ff4d4d; border-color:#ff4d4d; padding:6px 12px; font-size:12px; text-decoration:none;' onclick='return confirm(\"Permanently ban and delete this user? This cannot be undone.\");'>Ban / Delete</a>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding:30px; color:#888;'>No citizens found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>