<?php
session_start();
if (file_exists('config.php')) {
    require_once 'config.php';
} else {
    include 'db_connect.php';
}

if (isset($_SESSION['admin_id']) || isset($_SESSION['user_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$error = '';
if (!isset($_SESSION['admin_login_attempts'])) { 
    $_SESSION['admin_login_attempts'] = 0; 
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_SESSION['admin_lockout_time']) && time() < $_SESSION['admin_lockout_time']) {
        $remaining = $_SESSION['admin_lockout_time'] - time();
        $error = "Security Lockdown: Gateway locked. Retry in " . $remaining . "s.";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            $error = "Please fill in all security credentials.";
        } else {
      
            $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? AND (role = 'admin' OR role = 'superadmin')");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
               
                $status = isset($user['status']) ? $user['status'] : 'Active'; 
                if (strtolower($status) === 'inactive') {
                    $error = "⚠️ Access Denied: This account has been suspended by an administrator.";
                } else {
                
                    $is_password_correct = false;
                    if (password_verify($password, $user['password'])) {
                        $is_password_correct = true;
                    } elseif ($password === $user['password']) {
                        $is_password_correct = true;
                        $_SESSION['security_notice'] = "⚠️ Dev Notice: Admin password stored in plain text.";
                    }

                    if ($is_password_correct) {
                        session_regenerate_id(true);
                        unset($_SESSION['admin_login_attempts']);
                        unset($_SESSION['admin_lockout_time']);
                        
                        $admin_pk = $user['admin_id'] ?? $user['user_id'] ?? 0;
                        
                        $_SESSION['admin_id'] = $admin_pk;
                        $_SESSION['user_id'] = $admin_pk;
                        $_SESSION['admin_username'] = $user['username'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['admin_role'] = $user['role'];
                        $_SESSION['role'] = $user['role']; 
                        
                        $ip_address = $_SERVER['REMOTE_ADDR'] == '::1' ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
                        $log_sql = "INSERT INTO admin_logs (admin_id, username, role, action_event, ip_address) VALUES (?, ?, ?, 'System Login', ?)";
                        $log_stmt = $conn->prepare($log_sql);
                        if ($log_stmt) {
                            $log_stmt->bind_param("isss", $admin_pk, $user['username'], $user['role'], $ip_address);
                            $log_stmt->execute();
                            $log_stmt->close();
                        }

                        header("Location: admin_dashboard.php");
                        exit();
                    } else {
                        $_SESSION['admin_login_attempts']++;
                        $error = "Invalid administrative username or password.";
                    }
                }
            } else {
                $_SESSION['admin_login_attempts']++;
                $error = "Invalid administrative username or password."; 
            }
            $stmt->close();

            if ($_SESSION['admin_login_attempts'] >= 5) {
                $_SESSION['admin_lockout_time'] = time() + 60; 
                $error = "Security Lockdown: Intrusive behavior detected. Gateway locked for 60 seconds.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - GridCity PC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<style>
    body {
        margin: 0; padding: 0; font-family: 'Inter', sans-serif;
        background-image: linear-gradient(rgba(10, 10, 15, 0.6), rgba(10, 10, 15, 0.8)), url('image/Login_background.png');
        background-size: cover; background-position: center; display: flex; justify-content: center; align-items: center; height: 100vh;
    }
    .login-container {
        background: rgba(15, 15, 20, 0.65); backdrop-filter: blur(12px); padding: 40px; border-radius: 12px;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.6), 0 0 20px rgba(0, 242, 254, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.1); width: 100%; max-width: 400px; text-align: center;
    }
    .login-container h1 {
        background: linear-gradient(135deg, #00f2fe, #4facfe) !important;
        -webkit-background-clip: text !important; -webkit-text-fill-color: transparent !important;
        margin: 0 0 5px; font-weight: 900;
    }
    .login-container p { color: #cccccc; font-size: 12px; margin-bottom: 30px; text-transform: uppercase; letter-spacing: 2px; }
    .form-group { margin-bottom: 20px; text-align: left; }
    .form-control {
        width: 100%; padding: 12px; background: rgba(0, 0, 0, 0.5); border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 6px; color: #fff; box-sizing: border-box;
    }
    .btn-login {
        width: 100%; padding: 12px; background: linear-gradient(135deg, #00f2fe, #4facfe); border: none; border-radius: 6px;
        color: #000; font-weight: bold; cursor: pointer; margin-top: 10px;
    }
    .error-msg { background: rgba(255, 77, 77, 0.1); color: #ff4d4d; border: 1px solid rgba(255, 77, 77, 0.3); padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; }
    
   
    .login-container a { color: #00f2fe !important; text-decoration: none; font-size: 13px; display: inline-block; transition: 0.3s; }
    .login-container a:hover { color: #ffffff !important; text-shadow: 0 0 8px rgba(0, 242, 254, 0.8); }
</style>
</head>
<body>
    <div class="login-container">
        <h1>GridCity PC</h1>
        <p>System Administration</p>
        <?php if(!empty($error)) echo "<div class='error-msg'>$error</div>"; ?>
        <form method="POST">
            <div class="form-group">
                <label style="color:#fff; font-size:13px; margin-bottom:8px; display:block;">Username</label>
                <input type="text" name="username" class="form-control" required placeholder="Enter username">
            </div>
            <div class="form-group" style="margin-bottom: 5px;">
                <label style="color:#fff; font-size:13px; margin-bottom:8px; display:block;">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Enter password">
            </div>
            
            <div style="text-align: right; margin-bottom: 20px;">
                <a href="admin_forgot_password.php" style="font-size: 12px; color: #888 !important;">Forgot Password?</a>
            </div>
            
            <button type="submit" class="btn-login">Access Dashboard</button>
        </form>
        
        <a href="index.php" style="margin-top: 20px; display: inline-block;">&larr; Back to Customer Page</a>
    </div>
</body>
</html>