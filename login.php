<?php
// 1. Start Session and Database connection before outputting anything
session_start();
require_once 'config.php';

// If user is already logged in, redirect them to the homepage
if (isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit();
}

$error_msg = "";

// ==========================================
// 🌟 OAuth 2.0 URLs Generation (Google & Discord)
// ==========================================

// --- Load API Keys from the secret safe ---
require_once 'keys.php'; 

// --- Google Login Configuration ---
$google_redirect_uri = 'http://localhost/projects/google_callback.php';
$google_login_url = "https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id=" . $google_client_id . "&redirect_uri=" . urlencode($google_redirect_uri) . "&scope=email%20profile";

// --- Discord Login Configuration ---
$discord_redirect_uri = 'http://localhost/projects/discord_callback.php';
$discord_login_url = "https://discord.com/api/oauth2/authorize?client_id=" . $discord_client_id . "&redirect_uri=" . urlencode($discord_redirect_uri) . "&response_type=code&scope=" . urlencode("identify email");

// ==========================================
// 2. Handle Traditional Email/Password Login
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_msg = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT customer_id, first_name, last_name, password, account_status FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc(); 
            
            // Check account status
            if ($user['account_status'] !== 'Active') {
                $error_msg = "Your account is disabled. Please contact support.";
            } else {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    
                    // Login successful, generate session
                    session_regenerate_id(true); 
                    $_SESSION['customer_id'] = $user['customer_id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['user_type'] = 'Customer'; 

                    // Redirect to homepage
                    header("Location: index.php");
                    exit(); 
                } else {
                    $error_msg = "Invalid email or password.";
                }
            }
        } else {
            $error_msg = "Invalid email or password."; 
        }
        $stmt->close();
    }
}

// ==========================================
// 3. Load Frontend UI after Backend Logic
// ==========================================
include 'includes/header.php';
?>

<div class="auth-container" style="max-width: 450px; margin-top: 6rem;">
    <h2 class="auth-title">Welcome Back</h2>
    
    <?php if (isset($_GET['reset']) && $_GET['reset'] == 'success'): ?>
        <div class="text-success">
            <i class="fa-solid fa-circle-check"></i> Password updated successfully! Please login.
        </div>
    <?php endif; ?>

    <?php if(!empty($error_msg)): ?>
        <div class="text-danger" style="text-align: center; margin-bottom: 15px; border: 1px solid #ff4d4d; padding: 10px; border-radius: 6px; background: rgba(255, 77, 77, 0.1);">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="name@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <div class="form-group">
            <label class="form-label" style="display: flex; justify-content: space-between;">
                Password
            </label>
            <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                            <a href="forgot_password.php" style="color: var(--accent-blue); font-size: 0.85rem; font-weight: normal;">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px; font-size: 1.1rem;">
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
            <img src="image/discord.png" alt="Discord" style="width: 20px; height: 20px; object-fit: contain;"> Discord
        </a>
    </div>
    
    <div style="text-align: center; margin-top: 25px; font-size: 0.9rem; color: var(--text-muted);">
        Don't have an account? <a href="register.php" style="color: var(--accent-blue); font-weight: bold;">Create one</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>