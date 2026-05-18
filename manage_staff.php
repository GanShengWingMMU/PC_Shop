<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
// 🌟 絕對隔離：只有 Superadmin 老闆可以管理其他員工！
if (empty($current_role) || strtolower($current_role) !== 'superadmin') {
    die("<div style='background:#000; color:#ff4d4d; padding:50px; text-align:center; font-family:monospace; font-size:20px;'>ACCESS DENIED: CLEARANCE LEVEL ALPHA REQUIRED.</div>");
}

$message = "";
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // 防呆：絕對不能刪除自己！
    if ($delete_id == ($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0)) {
        $message = "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px;'><i class='fas fa-exclamation-triangle'></i> Override Denied: You cannot delete your own Superadmin account!</div>";
    } else {
        $stmt_del = $conn->prepare("DELETE FROM admins WHERE admin_id = ?");
        $stmt_del->bind_param("i", $delete_id);
        if ($stmt_del->execute()) {
            header("Location: manage_staff.php?deleted=1");
            exit();
        }
        $stmt_del->close();
    }
}
if (isset($_GET['deleted'])) $message = "<div style='color: #00e676; background: rgba(0,230,118,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px;'>✅ Staff access revoked.</div>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Staff - Admin</title>
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
                <li><a href="manage_staff.php" class="active"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Customers</a></li>
                <li><a href="admin_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div>
                    <h2 style="color: #ff4d4d; margin:0;"><i class="fas fa-user-shield"></i> Security & Staff Roster</h2>
                </div>
                <a href="add_staff.php" class="btn-action" style="background: rgba(255,77,77,0.1); color:#ff4d4d; border:1px solid #ff4d4d; font-weight:bold; padding:10px 20px; border-radius:6px; text-decoration:none;"><i class="fas fa-plus"></i> Appoint New Staff</a>
            </header>

            <?php echo $message; ?>

            <div class="table-container" style="background: rgba(0,0,0,0.4); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(255,77,77,0.2); text-align: left;">
                            <th style="padding:15px; color:#ff4d4d;">Clearance ID</th>
                            <th style="padding:15px; color:#ff4d4d;">Username & Email</th>
                            <th style="padding:15px; color:#ff4d4d;">Access Role</th>
                            <th style="padding:15px; color:#ff4d4d; text-align:right;">Operations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM admins ORDER BY admin_id ASC";
                        $res = $conn->query($sql);
                        if ($res && $res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $aid = $row['admin_id'] ?? $row['user_id'];
                                $role = strtoupper($row['role']);
                                $role_color = ($role == 'SUPERADMIN') ? '#ff007f' : '#00f2fe';
                                
                                echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05);'>";
                                echo "<td style='padding:15px; font-family:\"JetBrains Mono\"; color:#888;'>STAFF-$aid</td>";
                                echo "<td style='padding:15px;'><strong>".htmlspecialchars($row['username'])."</strong><br><span style='color:#64748b; font-size:12px;'>".htmlspecialchars($row['email'])."</span></td>";
                                echo "<td style='padding:15px;'><span style='border: 1px solid $role_color; color:$role_color; padding:4px 10px; border-radius:4px; font-family:\"JetBrains Mono\"; font-size:12px; font-weight:bold;'>$role</span></td>";
                                
                                echo "<td style='padding:15px; text-align:right;'>";
                                if ($aid != ($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0)) {
                                    echo "<a href='manage_staff.php?delete_id=$aid' class='btn-action' style='color:#ff4d4d; border-color:#ff4d4d; padding:6px 12px; font-size:12px; text-decoration:none;' onclick='return confirm(\"Revoke access for this staff member?\");'>Revoke</a>";
                                } else {
                                    echo "<span style='color:#64748b; font-size:12px;'><i class='fas fa-shield-alt'></i> You (Active)</span>";
                                }
                                echo "</td></tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>