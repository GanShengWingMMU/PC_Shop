<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$message = "";


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
        $message = "Invalid or expired Security Code. Please check your email and try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GridCitY PC - Verify OTP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="main-container cart-page-wrapper">
        
        <div class="auth-container">
            
            <div class="cart-empty-state" style="margin-bottom: 25px; text-align: left; padding: 2rem;">
                <h4 class="specs" style="margin-top: 0; color: var(--text-main); font-size: 1rem; font-weight: bold;"><i class="fa-solid fa-envelope-circle-check"></i> Verification Email Sent</h4>
                <p class="specs" style="margin-bottom: 0;">An email containing your 6-digit Security Code has been sent to <strong class="price" style="font-size: inherit; color: var(--accent-blue); font-weight: normal;"><?php echo htmlspecialchars($email); ?></strong>. Please check your inbox (and spam folder) to proceed.</p>
            </div>

            <div class="auth-title">
                <h2>Enter Security Code</h2>
                <p class="specs">Please check your email for a 6-digit code.</p>
            </div>

            <?php if (!empty($message)) echo "<p class='text-danger'>$message</p>"; ?>

            <form action="verify_otp.php" method="POST" class="form">
                
                <div class="form-group input-group">
                    <label class="form-label" for="otp">6-Digit OTP</label>
                    
                    <input type="text" id="otp" name="otp" maxlength="6" class="form-control form-control-otp">
                </div>

                <button type="submit" class="btn btn-primary btn-submit-login">Verify Code</button>
            </form>
            
            <div class="specs" style="margin-top: 1rem; text-align: center;">
                Didn't receive the email? <a href="forgot_password.php" class="highlight-link">Try again</a>
            </div>
        </div>
    </main>


</body>
</html>