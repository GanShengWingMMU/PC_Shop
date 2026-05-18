<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
// 🌟 絕對權限鎖：只有 Superadmin 能新增管理員！
if (empty($current_role) || strtolower($current_role) !== 'superadmin') {
    die("<div style='background:#000; color:#ff4d4d; padding:50px; text-align:center; font-family:monospace; font-size:20px;'>ACCESS DENIED: CLEARANCE LEVEL ALPHA REQUIRED.</div>");
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = trim($_POST['role']);

    if (empty($username) || empty($password) || empty($email)) {
        $error = "All fields are required.";
    } else {
        // 查重：避免重複的帳號或信箱
        $check = $conn->prepare("SELECT admin_id FROM admins WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Username or Email already exists in the security database.";
        } else {
            // 🌟 企業級加密：絕對不可使用明文儲存密碼！
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO admins (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
            if ($stmt->execute()) {
                $success = "✅ Security clearance granted. New staff added.";
            } else {
                $error = "Database Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appoint Staff - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
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
                <h2 style="color: #ff4d4d; margin: 0;"><i class="fas fa-user-plus"></i> Appoint Security Personnel</h2>
                <a href="manage_staff.php" class="btn-action" style="color: #888; border-color: #555; text-decoration:none;">&larr; Roster</a>
            </header>

            <?php 
            if ($error) echo "<div style='color:#ff4d4d; background:rgba(255,77,77,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(255,77,77,0.3);'>$error</div>"; 
            if ($success) echo "<div style='color:#00e676; background:rgba(0,230,118,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(0,230,118,0.3);'>$success</div>"; 
            ?>

            <form method="POST" style="background: rgba(0,0,0,0.5); padding: 30px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); max-width: 600px; margin: 0 auto;">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:8px; color:#888; font-size:13px; text-transform:uppercase;">Admin Username</label>
                    <input type="text" name="username" class="form-control" required style="width: 100%; padding: 12px; background: rgba(0,0,0,0.6); border: 1px solid #333; color: #fff; border-radius: 6px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:8px; color:#888; font-size:13px; text-transform:uppercase;">Official Email</label>
                    <input type="email" name="email" class="form-control" required style="width: 100%; padding: 12px; background: rgba(0,0,0,0.6); border: 1px solid #333; color: #fff; border-radius: 6px;">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:8px; color:#888; font-size:13px; text-transform:uppercase;">Temporary Password (Will be Hashed)</label>
                    <input type="password" name="password" class="form-control" required style="width: 100%; padding: 12px; background: rgba(0,0,0,0.6); border: 1px solid #333; color: #fff; border-radius: 6px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 30px;">
                    <label style="display:block; margin-bottom:8px; color:#888; font-size:13px; text-transform:uppercase;">Clearance Level</label>
                    <select name="role" class="form-control" style="width: 100%; padding: 12px; background: rgba(0,0,0,0.6); border: 1px solid #333; color: #fff; border-radius: 6px;">
                        <option value="Admin">Admin (Standard Operations)</option>
                        <option value="Superadmin">Superadmin (Full Control)</option>
                    </select>
                </div>

                <button type="submit" style="width: 100%; background: rgba(255,77,77,0.1); border: 1px solid #ff4d4d; color: #ff4d4d; padding: 15px; border-radius: 8px; font-weight: 900; font-size: 16px; cursor: pointer; transition: 0.3s;" onmouseover="this.style.background='#ff4d4d'; this.style.color='#000';" onmouseout="this.style.background='rgba(255,77,77,0.1)'; this.style.color='#ff4d4d';">
                    <i class="fas fa-fingerprint"></i> Issue Security Clearance
                </button>
            </form>
        </div>
    </div>
</body>
</html>