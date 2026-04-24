<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit();
}

$error_msg = "";

require_once 'keys.php'; 

$google_redirect_uri = 'http://localhost/projects/google_callback.php';
$google_login_url = "https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id=" . $google_client_id . "&redirect_uri=" . urlencode($google_redirect_uri) . "&scope=email%20profile";

$discord_redirect_uri = 'http://localhost/projects/discord_callback.php';
$discord_login_url = "https://discord.com/api/oauth2/authorize?client_id=" . $discord_client_id . "&redirect_uri=" . urlencode($discord_redirect_uri) . "&response_type=code&scope=" . urlencode("identify email");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 🌟 接收前端传来的账号（可能是邮箱，也可能是用户名）
    $login_id = trim($_POST['login_id']);
    $password = $_POST['password'];

    if (empty($login_id) || empty($password)) {
        $error_msg = "Please enter your username/email and password.";
    } else {
        // 🌟 核心升级：同时匹配 Email 或 Username
        $stmt = $conn->prepare("SELECT customer_id, username, password, account_status FROM customers WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $login_id, $login_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user['account_status'] !== 'Active') {
                $error_msg = "Your account is disabled. Please contact support.";
            } else {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['customer_id'] = $user['customer_id'];
                    // 🌟 存入 username
                    $_SESSION['username'] = $user['username'];
                    
                    header("Location: index.php");
                    exit();
                } else {
                    $error_msg = "Invalid password.";
                }
            }
        } else {
            $error_msg = "No account found with that Email or Username.";
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
    <title>GridCitY PC - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="main-container" style="display: flex; align-items: center; justify-content: center; min-height: 80vh;">
    <div class="auth-container" style="width: 100%; max-width: 450px; margin: 0;">
        
        <div class="auth-title">
            <h2>Welcome Back</h2>
            <p class="specs">Access your custom builds, orders, and wallet.</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="text-danger" style="text-align: center; margin-bottom: 15px; border: 1px solid #ff4d4d; padding: 10px; border-radius: 6px; background: rgba(255, 77, 77, 0.1);">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['reset']) && $_GET['reset'] == 'success'): ?>
            <div style="color: #00e676; text-align: center; margin-bottom: 15px; border: 1px solid #00e676; padding: 10px; border-radius: 6px; background: rgba(0, 230, 118, 0.1);">
                <i class="fas fa-check-circle"></i> Password updated successfully! Please login.
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="form">
            <div class="form-group">
                <label class="form-label">Username or Email</label>
                <input type="text" name="login_id" class="form-control" placeholder="Enter username or email" required value="<?php echo isset($_POST['login_id']) ? htmlspecialchars($_POST['login_id']) : (isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''); ?>">
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label class="form-label" style="margin:0;">Password</label>
                    <a href="forgot_password.php" style="color: var(--accent-blue); font-size: 0.85rem; text-decoration: none;">Forgot?</a>
                </div>
                <div style="position: relative; margin-top: 8px;">
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required style="padding-right: 40px; margin-top: 0;">
                    <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; transition: 0.2s;"></i>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                Login <i class="fas fa-sign-in-alt" style="margin-left: 5px;"></i>
            </button>
        </form>

        <div style="text-align: center; margin: 25px 0; color: var(--text-muted); position: relative;">
            <span style="background: var(--bg-surface); padding: 0 15px; position: relative; z-index: 1; font-size: 0.85rem; letter-spacing: 1px;">OR CONTINUE WITH</span>
            <div style="position: absolute; top: 50%; left: 0; right: 0; height: 1px; background: var(--border-color); z-index: 0;"></div>
        </div>

        <div style="display: flex; gap: 15px;">
            <a href="<?php echo $google_login_url; ?>" class="btn btn-outline" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 0.95rem;">
                <img src="image/google.png" alt="Google" style="width: 18px; height: 18px; object-fit: contain;"> Google
            </a>
            <a href="<?php echo $discord_login_url; ?>" class="btn btn-outline" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 0.95rem;">
                <i class="fa-brands fa-discord" style="color: #5865F2; font-size: 1.2rem;"></i> Discord
            </a>
        </div>

        <div style="text-align: center; margin-top: 25px; font-size: 0.9rem; color: var(--text-muted);">
            New to GridCitY PC? <a href="register.php" style="color: var(--accent-blue); font-weight: bold;">Create an account</a>
        </div>
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
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
                this.style.color = 'var(--accent-blue)';
            } else {
                inputField.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
                this.style.color = '#888';
            }
        });
    });
});
</script>

</body>
</html>