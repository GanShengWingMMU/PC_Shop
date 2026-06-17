<?php
session_start();
require_once 'config.php';

// 这里假设你写了一个 admin_verify_otp.php (逻辑跟客户的一样，只是查 admins 表)
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true || !isset($_SESSION['reset_email'])) {
    header("Location: admin_forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 12 || !preg_match('/[A-Z]/', $new_password) || !preg_match('/[0-9]/', $new_password) || !preg_match('/[\W]/', $new_password)) {
        $error = "Security requirement: 12+ chars, uppercase, number, symbol.";
    } else {
        $check_stmt = $conn->prepare("SELECT password FROM admins WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $user_data = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if ($user_data && password_verify($new_password, $user_data['password'])) {
            $error = "New password cannot be identical to the old password.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // 更新密码，清空 Token
            $stmt = $conn->prepare("UPDATE admins SET password = ?, reset_token = NULL, reset_token_expire = NULL WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);
            if ($stmt->execute()) {
                unset($_SESSION['otp_verified']);
                unset($_SESSION['reset_email']);
                // 成功后导向登录页
                echo "<script>alert('Password updated successfully! Please login again.'); window.location.href='admin_login.php';</script>";
                exit();
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set New Password - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-image: linear-gradient(rgba(10, 10, 15, 0.6), rgba(10, 10, 15, 0.8)), url('image/Login_background.png'); background-size: cover; background-position: center; display: flex; justify-content: center; align-items: center; height: 100vh; }
    .login-container { background: rgba(15, 15, 20, 0.65); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 40px; border-radius: 12px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.6), 0 0 20px rgba(0, 242, 254, 0.2); border: 1px solid rgba(255, 255, 255, 0.1); border-top: 1px solid rgba(0, 242, 254, 0.3); width: 100%; max-width: 400px; text-align: center; }
    .login-container h1 { background: linear-gradient(135deg, #00f2fe, #4facfe) !important; -webkit-background-clip: text !important; -webkit-text-fill-color: transparent !important; margin-top: 0; margin-bottom: 5px; font-weight: 900; letter-spacing: 1px; }
    .login-container p { color: #cccccc !important; font-size: 12px; margin-bottom: 30px; }
    .form-group { margin-bottom: 20px; text-align: left; position: relative; }
    .form-group label { display: block; color: #ffffff !important; font-weight: bold; font-size: 13px; margin-bottom: 8px; }
    .form-control { width: 100%; padding: 12px 15px; background: rgba(0, 0, 0, 0.5); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 6px; color: #ffffff !important; font-size: 14px; box-sizing: border-box; transition: 0.3s; padding-right: 40px;}
    .form-control:focus { outline: none; border-color: #00f2fe; box-shadow: 0 0 10px rgba(0, 242, 254, 0.3); background: rgba(0, 0, 0, 0.7); }
    .btn-login { width: 100%; padding: 12px; background: linear-gradient(135deg, #00f2fe, #4facfe); border: none; border-radius: 6px; color: #000000; font-weight: bold; font-size: 15px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
    .btn-login:hover { box-shadow: 0 0 15px rgba(0, 242, 254, 0.6); transform: translateY(-2px); }
    .error-msg { background: rgba(255, 77, 77, 0.1); color: #ff4d4d; border: 1px solid rgba(255, 77, 77, 0.3); padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; }
    .toggle-password { position: absolute; right: 15px; top: 38px; color: #888; cursor: pointer; transition: 0.2s; }
</style>
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <h1>Update Cipher</h1>
            <p>Identity Verified. Please set a new high-security password.</p>
        </div>

        <?php if(!empty($error)) echo "<div class='error-msg'>$error</div>"; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" required placeholder="Min 12 chars + symbols">
                <i class="fas fa-eye toggle-password"></i>
            </div>
            
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required placeholder="Confirm new password">
                <i class="fas fa-eye toggle-password"></i>
            </div>
            
            <button type="submit" class="btn-login">Save Password</button>
        </form>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleIcons = document.querySelectorAll('.toggle-password');
    toggleIcons.forEach(function(icon) {
        icon.addEventListener('click', function() {
            const inputField = this.previousElementSibling;
            if (inputField.type === 'password') {
                inputField.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash');
                this.style.color = '#00f2fe';
            } else {
                inputField.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye');
                this.style.color = '#888';
            }
        });
    });
});
</script>
</body>
</html>