<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || strtolower($current_role) !== 'superadmin') {
    die("<div style='background:#000; color:#ff4d4d; padding:50px; text-align:center; font-family:monospace;'>ACCESS DENIED: ALPHA REQUIRED.</div>");
}

$message = "";
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if ($delete_id == ($_SESSION['admin_id'] ?? 0)) {
        $message = "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px;'>⚠️ Cannot delete yourself!</div>";
    } else {
        $stmt_del = $conn->prepare("DELETE FROM admins WHERE admin_id = ?");
        $stmt_del->bind_param("i", $delete_id);
        if ($stmt_del->execute()) { header("Location: manage_staff.php?deleted=1"); exit(); }
        $stmt_del->close();
    }
}
if (isset($_GET['deleted'])) $message = "<div style='color: #00e676; background: rgba(0,230,118,0.1); padding: 15px; border-radius: 6px;'>✅ Access revoked.</div>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Staff - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>
    <div class="admin-container">
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-shield-alt"></i> GridCity Admin</h3>
                <p style="color:#555; font-size:11px; font-family:'JetBrains Mono';">Unified Architecture v4.0</p>
            </div>
           <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php" <?php if(basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php') echo 'class="active"'; ?>>Dashboard</a></li>
                
                <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'superadmin'): ?>
                    <li><a href="manage_staff.php" style="color: var(--accent-warning);" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_staff.php') echo 'class="active"'; ?>><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                    <li><a href="manage_users.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_users.php') echo 'class="active"'; ?>>Manage Customers</a></li>
                <?php endif; ?>
                
                <li><a href="manage_categories.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_categories.php') echo 'class="active"'; ?>>Categories</a></li>
                <li><a href="manage_products.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_products.php') echo 'class="active"'; ?>>Products</a></li> 
                <li><a href="manage_packages.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_packages.php') echo 'class="active"'; ?>>Packages</a></li>
                <li><a href="manage_orders.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_orders.php') echo 'class="active"'; ?>>Orders</a></li>
                
                <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="margin-bottom: 30px; display:flex; justify-content:space-between; align-items:center;">
                <h2 style="color: #ff4d4d; margin:0;"><i class="fas fa-user-shield"></i> Security Roster</h2>
                <a href="add_staff.php" class="btn-action" style="background: rgba(255,77,77,0.1); color:#ff4d4d; border-color:#ff4d4d; padding:10px 20px; border-radius:6px; text-decoration:none;"><i class="fas fa-plus"></i> Add Staff</a>
            </header>
            <?php echo $message; ?>
            <div class="table-container" style="background: rgba(0,0,0,0.4); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(255,77,77,0.2);">
                            <th style="padding:15px; color:#ff4d4d;">ID</th>
                            <th style="padding:15px; color:#ff4d4d;">Username</th>
                            <th style="padding:15px; color:#ff4d4d;">Role</th>
                            <th style="padding:15px; color:#ff4d4d; text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM admins ORDER BY admin_id ASC";
                        $res = $conn->query($sql);
                        while ($row = $res->fetch_assoc()) {
                            $aid = $row['admin_id'];
                            $is_me = ($aid == ($_SESSION['admin_id'] ?? 0));
                            echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05);'>";
                            echo "<td style='padding:15px; color:#888;'>STAFF-$aid</td>";
                            echo "<td style='padding:15px; font-weight:bold; color:#fff;'>".htmlspecialchars($row['username'])."</td>";
                            echo "<td style='padding:15px;'><span style='color:".($row['role']=='SuperAdmin'?'#ff007f':'#00f2fe').";'>".strtoupper($row['role'])."</span></td>";
                            echo "<td style='padding:15px; text-align:right;'>";
                            if (!$is_me) echo "<a href='manage_staff.php?delete_id=$aid' class='btn-action' style='color:#ff4d4d; border-color:#ff4d4d; padding:6px 12px; font-size:12px;'>Revoke</a>";
                            else echo "<span style='color:#64748b; font-size:12px;'>You</span>";
                            echo "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>