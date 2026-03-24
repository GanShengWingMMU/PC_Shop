<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$message = "";

$demo_otp = $_SESSION['demo_otp'] ?? ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = mysqli_real_escape_string($conn, $_POST['otp']);
    
    $current_time = date("Y-m-d H:i:s");
    $verify_sql = "SELECT customer_id FROM customers WHERE email = '$email' AND reset_token = '$entered_otp' AND reset_token_expire > '$current_time'";
    $result = $conn->query($verify_sql);

    if ($result->num_rows > 0) {
        $_SESSION['otp_verified'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        $message = "<div class='error-alert' style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>Invalid or expired OTP. Please try again.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Store - Verify OTP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif;">

    <?php include 'header.php'; ?>

    <main class="login-page-wrapper">
        <div class="login-card">
            
            <div style="background-color: #e8f0fe; border-left: 4px solid #4285f4; padding: 15px; margin-bottom: 25px; border-radius: 4px;">
                <h4 style="margin-top: 0; color: #1967d2;"><i class="fa-solid fa-envelope-circle-check"></i> Verification Email Sent</h4>
                <p style="margin-bottom: 0; font-size: 14px; color: #3c4043; line-height: 1.5;">An email containing your 6-digit OTP has been sent to <strong><?php echo htmlspecialchars($email); ?></strong>. Please check your inbox (and spam folder) to proceed.</p>
            </div>

            <div class="login-header">
                <h2>Enter Security Code</h2>
                <p>Please check your email for a 6-digit code.</p>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <form action="verify_otp.php" method="POST" class="login-form">
                <div class="input-group">
                    <label for="otp">6-Digit OTP</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-shield-halved"></i>
                        <input type="text" id="otp" name="otp" maxlength="6" style="letter-spacing: 5px; font-size: 18px; font-weight: bold; text-align: center;">
                    </div>
                </div>

                <button type="submit" class="btn-submit-login">Verify Code</button>
            </form>
            
            <div class="login-footer">
                Didn't receive the email? <a href="forgot_password.php">Try again</a>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

</body>
</html>