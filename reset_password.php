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


    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match. Please type carefully.";
    } elseif (strlen($new_password) < 12 || !preg_match('/[A-Z]/', $new_password) || !preg_match('/[0-9]/', $new_password) || !preg_match('/[\W]/', $new_password)) {
        $message = "Password must be at least 12 characters and include uppercase, numbers, and symbols.";
    } else {
// 
        $check_stmt = $conn->prepare("SELECT password FROM customers WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $user_data = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();


        if ($user_data && password_verify($new_password, $user_data['password'])) {
  
            $message = "Your new password cannot be the same as your old password!";
        } else {

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
  
            $stmt = $conn->prepare("UPDATE customers SET password = ?, reset_token = NULL, reset_token_expire = NULL WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);
            if ($stmt->execute()) {

                unset($_SESSION['otp_verified']);
                unset($_SESSION['reset_email']);

                header("Location: login.php?reset=success"); 
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GridCitY PC - Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="main-container" style="display: flex; align-items: center; justify-content: center; min-height: 80vh;">
    <div class="auth-container" style="width: 100%; max-width: 450px; margin: 0;">
        <div class="auth-title">
            <h2>Set New Password</h2>
            <p class="specs">Your identity is verified. Choose a strong new password.</p>
        </div>

        <?php if (!empty($message)) echo "<p class='text-danger' style='text-align: center; margin-bottom: 1rem;'>$message</p>"; ?>

        <form action="reset_password.php" method="POST" class="form">
            <div class="form-group">
                <label class="form-label" for="new_password">New Password</label>
                <div style="position: relative;">
                    <input type="password" id="new_password" name="new_password" required placeholder="Minimum 12 characters" class="form-control" style="padding-right: 40px;">
                    <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; transition: 0.2s;"></i>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <div style="position: relative;">
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your new password" class="form-control" style="padding-right: 40px;">
                    <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; transition: 0.2s;"></i>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-submit-login">Save Password</button>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleIcons = document.querySelectorAll('.toggle-password');
    toggleIcons.forEach(function(icon) {
        icon.addEventListener('click', function() {
            const inputField = this.previousElementSibling;
            if (inputField.type === 'password') {
                inputField.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash');
                this.style.color = 'var(--accent-blue)';
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