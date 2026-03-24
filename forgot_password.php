<?php
session_start();
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$message = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    $check_sql = "SELECT customer_id, first_name FROM customers WHERE email = '$email'";
    $result = $conn->query($check_sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $first_name = $row['first_name'];

        $otp = sprintf("%06d", mt_rand(1, 999999)); 
        $expiry_time = date("Y-m-d H:i:s", time() + 3600); 

        $update_sql = "UPDATE customers SET reset_token = '$otp', reset_token_expire = '$expiry_time' WHERE email = '$email'";
        
        if ($conn->query($update_sql) === TRUE) {
            
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();                                            
                $mail->Host       = 'smtp.gmail.com';                     
                $mail->SMTPAuth   = true;                                   
                
                $mail->Username   = 'ganshengwing1126@gmail.com'; 
                
                // 💡 小提醒：Google 應用程式密碼通常建議把中間的空格刪掉（變成 zojrbckepnkdqgli），這樣連線最穩定
                $mail->Password   = 'zojr bcke pnkd qgli'; 
                
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            
                $mail->Port       = 587;                                    

                $mail->setFrom('ganshengwing1126@gmail.com', 'PC Store Support');
                $mail->addAddress($email, $first_name); 

                $mail->isHTML(true);                                  
                $mail->Subject = 'Your OTP for Password Reset - PC Store';
                
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>
                        <h2 style='color: #4285F4;'>PC Store - Password Reset</h2>
                        <p>Hi <b>{$first_name}</b>,</p>
                        <p>We received a request to reset your password. Here is your One-Time Password (OTP):</p>
                        <div style='background-color: #f4f7f6; padding: 15px; text-align: center; border-radius: 5px; margin: 20px 0;'>
                            <h1 style='letter-spacing: 5px; margin: 0; color: #333;'>{$otp}</h1>
                        </div>
                        <p>This code will expire in 1 hour. If you did not request this, please ignore this email.</p>
                        <br>
                        <p>Best regards,<br>The PC Store Team</p>
                    </div>
                ";

                $mail->send();

                $_SESSION['reset_email'] = $email;
                $_SESSION['demo_otp'] = $otp;
                
                header("Location: verify_otp.php");
                exit();

            } catch (Exception $e) {
                // 套用科技風的錯誤提示框
                $message = "<div class='text-danger' style='text-align: center; margin-bottom: 15px; border: 1px solid #ff4d4d; padding: 10px; border-radius: 6px; background: rgba(255, 77, 77, 0.1);'><i class='fas fa-exclamation-circle'></i> Message could not be sent. Mailer Error: {$mail->ErrorInfo}</div>";
            }

        } else {
            $message = "<div class='text-danger' style='text-align: center; margin-bottom: 15px; border: 1px solid #ff4d4d; padding: 10px; border-radius: 6px; background: rgba(255, 77, 77, 0.1);'><i class='fas fa-exclamation-circle'></i> Database error.</div>";
        }
    } else {
        $_SESSION['reset_email'] = $email;
        $_SESSION['demo_otp'] = "000000"; 
        header("Location: verify_otp.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Store - Forgot Password</title>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="main-container" style="display: flex; align-items: center; justify-content: center; min-height: 60vh;">
        <div class="auth-container" style="width: 100%; max-width: 450px; margin: 0;">
            <h2 class="auth-title">Forgot Password?</h2>
            <p style="text-align: center; color: var(--text-muted); margin-bottom: 2rem;">Enter your email address to receive a 6-digit OTP.</p>

            <?php if (!empty($message)) echo $message; ?>

            <form action="forgot_password.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required placeholder="name@example.com">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px; font-size: 1.1rem;">
                    Send Reset Link <i class="fa-solid fa-paper-plane" style="margin-left: 5px;"></i>
                </button>
            </form>

            <div style="text-align: center; margin-top: 25px; font-size: 0.9rem; color: var(--text-muted);">
                Remember your password? <a href="login.php" style="color: var(--accent-blue); font-weight: bold;">Back to Login</a>
            </div>
        </div>
    </main>

 <?php include 'includes/footer.php'; ?>

</body>
</html>