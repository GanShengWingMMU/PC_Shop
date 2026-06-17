<?php
session_start();
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$error = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    
    $stmt = $conn->prepare("SELECT admin_id, username FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $username = $row['username'];

        $otp = sprintf("%06d", mt_rand(1, 999999)); 
        $expiry_time = date("Y-m-d H:i:s", time() + 3600); 

        $update_stmt = $conn->prepare("UPDATE admins SET reset_token = ?, reset_token_expire = ? WHERE email = ?");
        $update_stmt->bind_param("sss", $otp, $expiry_time, $email);
        
        if ($update_stmt->execute()) {
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; 
                $mail->SMTPAuth   = true;
                
                // 🌟 你的邮箱设定
                $mail->Username   = 'ahaa3153@gmail.com'; 
                $mail->Password   = 'ojhnofgqawsvclvq';
                
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('ahaa3153@gmail.com', 'GridCity PC Admin');
                $mail->addAddress($email, $username);

                $mail->isHTML(true);
                $mail->Subject = 'Admin Portal - Password Reset OTP';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #333; padding: 20px; border-radius: 10px; background-color: #0b0b12; color: #ffffff;'>
                        <h2 style='color: #00f2fe; text-align: center; font-weight: 900; text-transform: uppercase;'>GridCity PC Admin</h2>
                        <p style='font-size: 16px; color: #ccc;'>Commander <strong style='color: #00f2fe;'>$username</strong>,</p>
                        <p style='font-size: 14px; color: #aaa;'>A security override was requested for your administrative access. Use the following code to proceed:</p>
                        <div style='background: #000; padding: 15px; font-size: 32px; text-align: center; font-weight: bold; color: #00f2fe; border: 1px solid #00f2fe; border-radius: 8px; letter-spacing: 8px; margin: 20px 0;'>$otp</div>
                        <p style='font-size: 12px; color: #ff4d4d; text-align: center;'>This security token expires in 1 hour. If unauthorized, secure your station immediately.</p>
                    </div>";

                $mail->send();
                $_SESSION['reset_email'] = $email;
                header("Location: admin_verify_otp.php");
                exit();
            } catch (Exception $e) {
                $error = "Mailer Error: {$mail->ErrorInfo}";
            }
        }
        $update_stmt->close();
    } else {
        $error = "Access Denied: This email address is not registered as an Administrator.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<style>
    body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-image: linear-gradient(rgba(10, 10, 15, 0.6), rgba(10, 10, 15, 0.8)), url('image/Login_background.png'); background-size: cover; background-position: center; display: flex; justify-content: center; align-items: center; height: 100vh; }
    .login-container { background: rgba(15, 15, 20, 0.65); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 40px; border-radius: 12px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.6), 0 0 20px rgba(0, 242, 254, 0.2); border: 1px solid rgba(255, 255, 255, 0.1); border-top: 1px solid rgba(0, 242, 254, 0.3); width: 100%; max-width: 400px; text-align: center; }
    .login-container h1 { background: linear-gradient(135deg, #00f2fe, #4facfe) !important; -webkit-background-clip: text !important; -webkit-text-fill-color: transparent !important; margin-top: 0; margin-bottom: 5px; font-weight: 900; letter-spacing: 1px; }
    .login-container p { color: #cccccc !important; font-size: 12px; margin-bottom: 30px; }
    .form-group { margin-bottom: 20px; text-align: left; }
    .form-group label { display: block; color: #ffffff !important; font-weight: bold; font-size: 13px; margin-bottom: 8px; }
    .form-control { width: 100%; padding: 12px 15px; background: rgba(0, 0, 0, 0.5); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 6px; color: #ffffff !important; font-size: 14px; box-sizing: border-box; transition: 0.3s; }
    .form-control:focus { outline: none; border-color: #00f2fe; box-shadow: 0 0 10px rgba(0, 242, 254, 0.3); background: rgba(0, 0, 0, 0.7); }
    .btn-login { width: 100%; padding: 12px; background: linear-gradient(135deg, #00f2fe, #4facfe); border: none; border-radius: 6px; color: #000000; font-weight: bold; font-size: 15px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
    .btn-login:hover { box-shadow: 0 0 15px rgba(0, 242, 254, 0.6); transform: translateY(-2px); }
    .login-container a { color: #00f2fe !important; text-decoration: none; font-size: 13px; display: inline-block; margin-top: 20px; transition: 0.3s; }
    .login-container a:hover { color: #ffffff !important; }
    .error-msg { background: rgba(255, 77, 77, 0.1); color: #ff4d4d; border: 1px solid rgba(255, 77, 77, 0.3); padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; }
</style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Security Override</h1>
            <p>Enter email to receive authorization OTP</p>
        </div>
        <?php if(!empty($error)) echo "<div class='error-msg'>$error</div>"; ?>
        <form action="" method="POST">
            <div class="form-group">
                <label>Admin Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="name@gridcity.com">
            </div>
            <button type="submit" class="btn-login">Send Reset Link</button>
        </form>
        <a href="admin_login.php" class="back-link">&larr; Return to Login Portal</a>
    </div>
</body>
</html>