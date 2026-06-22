<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$message = "";


if (!isset($_SESSION['otp_attempts'])) {
    $_SESSION['otp_attempts'] = 0;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if ($_SESSION['otp_attempts'] >= 5) {
        $message = "SECURITY LOCK: Maximum attempts reached. Please request a new OTP.";
    } else {
        $entered_otp = trim($_POST['otp']);
        $current_time = date("Y-m-d H:i:s");
        
        $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ? AND reset_token = ? AND reset_token_expire > ?");
        $stmt->bind_param("sss", $email, $entered_otp, $current_time);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            unset($_SESSION['otp_attempts']);
            $_SESSION['otp_verified'] = true;
            header("Location: reset_password.php");
            exit();
        } else {
            $_SESSION['otp_attempts']++;
            $remaining = 5 - $_SESSION['otp_attempts'];
            $message = "Invalid or expired Security Code. " . $remaining . " attempts remaining.";
        }
        $stmt->close();
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

<main class="main-container" style="display: flex; align-items: center; justify-content: center; min-height: 80vh; padding: 2rem 0;">
    <div class="auth-container" style="width: 100%; max-width: 500px; margin: 0;">
        
        <div style="background: rgba(0, 242, 254, 0.05); border: 1px solid rgba(0, 242, 254, 0.2); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
            <h4 style="color: var(--accent-blue); margin-top: 0; font-weight: bold;"><i class="fa-solid fa-envelope-circle-check"></i> Verification Email Sent</h4>
            <p class="specs" style="margin-bottom: 0;">An email containing your 6-digit Security Code has been sent to <strong class="price" style="font-size: inherit; color: var(--accent-blue); font-weight: normal;"><?php echo htmlspecialchars($email); ?></strong>. Please check your inbox.</p>
        </div>

        <div class="auth-title">
            <h2>Enter Security Code</h2>
            <p class="specs">Please check your email for a 6-digit code.</p>
        </div>

        <?php if (!empty($message)) echo "<p class='text-danger' style='text-align: center; margin-bottom: 1rem; font-weight: bold;'>$message</p>"; ?>

        <form action="verify_otp.php" method="POST" class="form">
            <div class="form-group input-group">
                <label class="form-label" for="otp">6-Digit OTP</label>
                <input type="text" id="otp" name="otp" maxlength="6" class="form-control form-control-otp" required placeholder="000000" style="text-align: center; letter-spacing: 8px; font-size: 1.5rem; font-weight: 900;" <?php echo (isset($_SESSION['otp_attempts']) && $_SESSION['otp_attempts'] >= 5) ? 'disabled' : ''; ?>>
            </div>

            <button type="submit" class="btn btn-primary btn-submit-login" <?php echo (isset($_SESSION['otp_attempts']) && $_SESSION['otp_attempts'] >= 5) ? 'style="opacity:0.5; cursor:not-allowed;" disabled' : ''; ?>>Verify Code</button>
        </form>
        
        <div class="specs" style="margin-top: 1rem; text-align: center;">
            Didn't receive the email or locked out? <a href="forgot_password.php" class="highlight-link">Try again</a>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>