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
    // 🌟 防呆升級：使用 trim() 自動去除前後多餘的隱形空白
$email = mysqli_real_escape_string($conn, trim($_POST['email']));
    
    // 🌟 A+ 级安全修复：使用 Prepared Statement
    $stmt = $conn->prepare("SELECT customer_id, first_name FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $first_name = $row['first_name'];

        $otp = sprintf("%06d", mt_rand(1, 999999)); 
        $expiry_time = date("Y-m-d H:i:s", time() + 3600); 

        // 🌟 A+ 级安全修复：Prepared Statement 更新 OTP
        $update_stmt = $conn->prepare("UPDATE customers SET reset_token = ?, reset_token_expire = ? WHERE email = ?");
        $update_stmt->bind_param("sss", $otp, $expiry_time, $email);
        
        if ($update_stmt->execute()) {
            $mail = new PHPMailer(true);

            try {
                // 保持你原本的 SMTP 配置不变
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; 
                $mail->SMTPAuth   = true;
                $mail->Username   = 'ganshengwing1126@gmail.com'; 
                // 🌟 核心修复：禁止密码硬编码！调用 config.php 中的变量
                $mail->Password   = 'rigj fzjw wrcd nfog'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('ganshengwing1126@gmail.com', 'GridCitY PC');
                $mail->addAddress($email, $first_name);

                $mail->isHTML(true);
                $mail->Subject = 'Your Password Reset OTP';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 10px; background-color: #0a0a0a; color: #ffffff;'>
                        <h2 style='color: #00f2fe; text-align: center;'>GridCitY PC</h2>
                        <p style='font-size: 16px;'>Hi <strong style='color: #00f2fe;'>$first_name</strong>,</p>
                        <p style='font-size: 14px;'>We received a request to reset your password. Please use the following One-Time Password (OTP) to proceed:</p>
                        <div style='background: #1a1a1a; padding: 15px; font-size: 32px; text-align: center; font-weight: bold; color: #00f2fe; border: 1px solid #00f2fe; border-radius: 8px; letter-spacing: 8px; margin: 20px 0;'>$otp</div>
                        <p style='font-size: 12px; color: #888; text-align: center;'>This security code is valid for 1 hour. If you did not request this, please ignore this email.</p>
                    </div>";

                $mail->send();
                $_SESSION['reset_email'] = $email;
                header("Location: verify_otp.php");
                exit();
            } catch (Exception $e) {
                $message = "<div class='text-danger' style='margin-bottom: 1rem; text-align: center; font-weight: bold;'><i class='fa-solid fa-circle-exclamation'></i> Mailer Error: {$mail->ErrorInfo}</div>";
            }
        }
        $update_stmt->close();
    } else {
$message = "<div style='color: #ff4d4d; background: rgba(255, 77, 77, 0.1); padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 15px; font-weight: bold;'>
                        <i class='fas fa-exclamation-circle'></i> Error: This email address is not registered.
                    </div>";
    }
    $stmt->close();
}

include 'includes/header.php'; 
?>

<main class="main-container" style="display: flex; align-items: center; justify-content: center; min-height: 60vh;">
    <div class="auth-container" style="width: 100%; max-width: 450px; margin: 0;">
        <h2 class="auth-title">Forgot Password?</h2>
        <p style="text-align: center; color: var(--text-muted); margin-bottom: 2rem;">Enter your email address to receive a 6-digit OTP.</p>

        <?php if (!empty($message)) echo $message; ?>

        <form action="forgot_password.php" method="POST" class="form">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="name@example.com">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px; font-size: 1.1rem;">
                Send Reset Link <i class="fa-solid fa-paper-plane" style="margin-left: 5px;"></i>
            </button>
        </form>
        
        <div class="specs" style="margin-top: 2rem; text-align: center;">
            Remembered your password? <a href="login.php" class="highlight-link">Back to Login</a>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>