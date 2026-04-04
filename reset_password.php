<?php
session_start();
require_once 'config.php';

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
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update_sql = "UPDATE customers 
                       SET password = '$hashed_password', reset_token = NULL, reset_token_expire = NULL 
                       WHERE email = '$email'";
        
        if ($conn->query($update_sql) === TRUE) {
            unset($_SESSION['otp_verified']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['demo_otp']);

            header("Location: login.php?reset=success");
            exit();
        } else {
            $message = "Database error. Please try again later.";
        }
    } else {
        $message = "Passwords do not match. Please type carefully.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GridCitY PC - Create New Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="main-container cart-page-wrapper">
        
        <div class="auth-container">
            
            <div class="auth-title">
                <h2>Create New Password</h2>
                <p class="specs">Your new password must be different from previously used passwords.</p>
            </div>

            <?php if (!empty($message)) echo "<p class='text-danger' style='text-align: center; margin-bottom: 1.5rem;'>$message</p>"; ?>

            <form action="reset_password.php" method="POST" class="form">
                
                <div class="form-group">
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required placeholder="Enter new password" minlength="6" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your new password" minlength="6" class="form-control">
                </div>

                <button type="submit" class="btn btn-primary btn-submit-login">Save Password</button>
            </form>
        </div>
    </main>


</body>
</html>