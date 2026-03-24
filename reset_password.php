<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true || !isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        $safe_password = mysqli_real_escape_string($conn, $new_password);
        
        $update_sql = "UPDATE customers 
                       SET password = '$safe_password', reset_token = NULL, reset_token_expire = NULL 
                       WHERE email = '$email'";
        
        if ($conn->query($update_sql) === TRUE) {
            unset($_SESSION['otp_verified']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['demo_otp']);

            header("Location: login.php?reset=success");
            exit();
        } else {
            $message = "<div class='error-alert' style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>Database error. Please try again.</div>";
        }
    } else {
        $message = "<div class='error-alert' style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>Passwords do not match. Please type carefully.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Store - Create New Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif;">

    <?php include 'header.php'; ?>

    <main class="login-page-wrapper">
        <div class="login-card">
            <div class="login-header">
                <h2>Create New Password</h2>
                <p>Your new password must be different from previously used passwords.</p>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <form action="reset_password.php" method="POST" class="login-form">
                <div class="input-group">
                    <label for="new_password">New Password</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="new_password" name="new_password" required placeholder="Enter new password" minlength="6">
                    </div>
                </div>

                <div class="input-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your new password" minlength="6">
                    </div>
                </div>

                <button type="submit" class="btn-submit-login">Save Password</button>
            </form>
        </div>
    </main>

    <?php include 'footer.php'; ?>

</body>
</html>