<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: admin_forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = trim($_POST['otp']);

    $stmt = $conn->prepare("SELECT reset_token, reset_token_expire FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stored_otp = $row['reset_token'];
        $expiry_time = $row['reset_token_expire'];

        if ($entered_otp === $stored_otp) {
            if (strtotime($expiry_time) >= time()) {
                $_SESSION['otp_verified'] = true;
                header("Location: admin_reset_password.php");
                exit();
            } else {
                $error = "OTP has expired. Please request a new one.";
            }
        } else {
            $error = "Invalid OTP. Please try again.";
        }
    } else {
        $error = "Error verifying account.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<style>
    body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-image: linear-gradient(rgba(10, 10, 15, 0.6), rgba(10, 10, 15, 0.8)), url('image/Login_background.png'); background-size: cover; background-position: center; display: flex; justify-content: center; align-items: center; height: 100vh; }
    .login-container { background: rgba(15, 15, 20, 0.65); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 40px; border-radius: 12px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.6), 0 0 20px rgba(0, 242, 254, 0.2); border: 1px solid rgba(255, 255, 255, 0.1); border-top: 1px solid rgba(0, 242, 254, 0.3); width: 100%; max-width: 400px; text-align: center; }
    .login-container h1 { background: linear-gradient(135deg, #00f2fe, #4facfe) !important; -webkit-background-clip: text !important; -webkit-text-fill-color: transparent !important; margin-top: 0; margin-bottom: 5px; font-weight: 900; letter-spacing: 1px; }
    .login-container p { color: #cccccc !important; font-size: 12px; margin-bottom: 30px; }
    .form-group { margin-bottom: 20px; text-align: left; }
    .form-group label { display: block; color: #ffffff !important; font-weight: bold; font-size: 13px; margin-bottom: 8px; text-align: center;}
    .form-control { width: 100%; padding: 12px 15px; background: rgba(0, 0, 0, 0.5); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 6px; color: #00f2fe !important; font-size: 24px; font-weight: bold; text-align: center; letter-spacing: 8px; box-sizing: border-box; transition: 0.3s; }
    .form-control:focus { outline: none; border-color: #00f2fe; box-shadow: 0 0 10px rgba(0, 242, 254, 0.3); background: rgba(0, 0, 0, 0.8); }
    .btn-login { width: 100%; padding: 12px; background: linear-gradient(135deg, #00f2fe, #4facfe); border: none; border-radius: 6px; color: #000000; font-weight: bold; font-size: 15px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
    .btn-login:hover { box-shadow: 0 0 15px rgba(0, 242, 254, 0.6); transform: translateY(-2px); }
    .error-msg { background: rgba(255, 77, 77, 0.1); color: #ff4d4d; border: 1px solid rgba(255, 77, 77, 0.3); padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; }
</style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Verify OTP</h1>
            <p>Code sent to: <span style="color:#00f2fe;"><?php echo htmlspecialchars($email); ?></span></p>
        </div>
        <?php if(!empty($error)) echo "<div class='error-msg'>$error</div>"; ?>
        <form action="" method="POST">
            <div class="form-group">
                <label>Enter 6-Digit Code</label>
                <input type="text" name="otp" class="form-control" required maxlength="6" autocomplete="off">
            </div>
            <button type="submit" class="btn-login">Verify Identity</button>
        </form>
    </div>
</body>
</html>