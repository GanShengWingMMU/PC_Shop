<?php
session_start();
include 'db_connect.php'; 

// 🌟 终极防线：只有老板 (superadmin) 才能招人！
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: admin_dashboard.php");
    exit();
}

$error = "";

// 处理提交表单的逻辑
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $role = mysqli_real_escape_string($conn, $_POST['role']); 
    
    // 🛡️ 核心新增：PHP 后端强密码验证 (至少12位，包含大小写、数字和特殊符号)
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_\-+=]).{12,}$/';
    
    if (!preg_match($pattern, $password)) {
        $error = "Password must be at least 12 characters long and contain uppercase, lowercase, numbers, and special symbols (!@#$%^&*()).";
    } else {
        // 检查这个账号名是否已经被用过了
        $check_sql = "SELECT * FROM users WHERE username = '$username'";
        $check_res = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_res) > 0) {
            $error = "Username already exists. Please choose another one.";
        } else {
            // 密码合格且账号没重复，进行加密处理
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $insert_sql = "INSERT INTO users (username, password, email, role) 
                           VALUES ('$username', '$hashed_password', '$email', '$role')";
                           
            if (mysqli_query($conn, $insert_sql)) {
                header("Location: manage_staff.php?success=1");
                exit();
            } else {
                $error = "Database Error: Failed to add new staff.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Staff - Superadmin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css?v=<?php echo time(); ?>">
    <style>
        .staff-form-card {
            background: var(--bg-surface); padding: 40px; border-radius: 12px;
            border: 1px solid var(--border-color); box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            max-width: 600px; margin: 0 auto; position: relative; overflow: hidden;
        }
        .staff-form-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(to right, #f39c12, #e67e22); 
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>
            <img src="image/Admin_dashboard_logo.jpg" alt="ROG Logo" class="sidebar-logo">
            <span>PC SHOP</span>
        </h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_products.php">Products</a></li> 
            <li><a href="manage_categories.php">Categories</a></li>
            <li><a href="manage_orders.php">Orders</a></li>
            <li><a href="admin_builder.php">Build System</a></li>
            
            <li><a href="manage_staff.php" class="active" style="color: var(--accent-warning); border-left-color: var(--accent-warning);"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
            <li><a href="manage_users.php">Manage Customers</a></li>
            
            <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
        </ul>
    </div>

    <div class="main-content">
        
        <div class="header-top" style="margin-bottom: 30px;">
            <a href="manage_staff.php" class="btn-action" style="display: inline-block; margin-bottom: 15px; border:none; color: var(--text-muted);">&larr; Back to Staff List</a>
            <h1 style="margin: 0; font-size: 28px; color: var(--text-main);">Add New Staff</h1>
            <p style="color: var(--text-muted); margin-top: 5px;">Create a new administrator or superadmin account.</p>
        </div>

        <div class="staff-form-card">
            <?php 
            if(!empty($error)) {
                echo "<div class='error-msg' style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 12px; border-radius: 6px; border: 1px solid rgba(255,77,77,0.3); margin-bottom: 20px; text-align: center; font-weight: bold;'>⚠️ $error</div>";
            }
            ?>

            <form action="" method="POST">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; color: var(--text-muted); font-weight: bold; margin-bottom: 8px;">Username *</label>
                    <input type="text" name="username" class="form-control" required placeholder="Enter login username (e.g., admin_jason)">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; color: var(--text-muted); font-weight: bold; margin-bottom: 8px;">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="staff@pcshop.com">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; color: var(--text-muted); font-weight: bold; margin-bottom: 8px;">Password *</label>
                    <input type="password" name="password" class="form-control" required 
                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*()_\-+=]).{16,}" 
                           title="Must be at least 16 characters long, contain at least one uppercase letter, one lowercase letter, one number, and one special character (!@#$%^&*)."
                           placeholder="Enter a secure password">
                    <small style="color: var(--accent-warning); margin-top: 8px; display: block; font-weight: 600;">
                        <i class="fas fa-shield-alt"></i> Must be at least 16 characters, including Uppercase, Lowercase, Number & Symbol (!@#$%^&*).
                    </small>
                </div>

                <div class="form-group" style="margin-bottom: 30px;">
                    <label style="display: block; color: var(--text-muted); font-weight: bold; margin-bottom: 8px;">Role (Privilege Level) *</label>
                    <select name="role" class="form-control" required style="cursor: pointer; appearance: auto;">
                        <option value="admin">Admin (Manage Products, Orders, etc.)</option>
                        <option value="superadmin">Superadmin (Full Access + Manage Staff)</option>
                    </select>
                </div>

                <button type="submit" class="quick-action-btn" style="background: linear-gradient(135deg, #f39c12, #e67e22); font-size: 16px;">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
                
            </form>
        </div>

    </div>
</body>
</html>