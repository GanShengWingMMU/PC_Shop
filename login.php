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

if (!isset($_SESSION['login_attempts'])) { $_SESSION['login_attempts'] = 0; }

$redirect_url = isset($_GET['redirect']) ? filter_var($_GET['redirect'], FILTER_SANITIZE_URL) : 'index.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_SESSION['lockout_time']) && time() < $_SESSION['lockout_time']) {
        $remaining = $_SESSION['lockout_time'] - time();
        $error_msg = "Too many failed attempts. Please wait " . $remaining . " seconds.";
    } else {
        $login_id = trim($_POST['login_id']);
        $password = $_POST['password'];

        if (empty($login_id) || empty($password)) {
            $error_msg = "Please enter your email and password.";
        } else {
            $stmt = $conn->prepare("SELECT customer_id, username, password, account_status FROM customers WHERE email = ? OR username = ?");
            $stmt->bind_param("ss", $login_id, $login_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if ($user['account_status'] !== 'Active') {
                    $error_msg = "Account disabled. Please contact support.";
                } else {
                    if (password_verify($password, $user['password'])) {
                        
                        // 🛡️ A+ 级修复：防止 Session Fixation 攻击
                        session_regenerate_id(true);
                        
                        unset($_SESSION['login_attempts'], $_SESSION['lockout_time']);
                        $_SESSION['customer_id'] = $user['customer_id'];
                        $_SESSION['username'] = $user['username'];
                        
                        // 🛡️ A+ 级修复：更严谨的 Open Redirect 拦截 (阻断 //evil.com)
                        $parsed = parse_url($redirect_url);
                        if (isset($parsed['host']) || isset($parsed['scheme'])) {
                            header("Location: index.php");
                        } else {
                            header("Location: " . $redirect_url);
                        }
                        exit();
                    } else {
                        $_SESSION['login_attempts']++;
                        if ($_SESSION['login_attempts'] >= 3) {
                            $_SESSION['lockout_time'] = time() + 30; 
                            $error_msg = "Account locked for 30 seconds.";
                        } else {
                            $error_msg = "Incorrect password. (" . $_SESSION['login_attempts'] . "/3 attempts)";
                        }
                    }
                }
            } else {
                $_SESSION['login_attempts']++;
                $error_msg = "Account not found or incorrect credentials.";
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
    <title>Log In - GridCitY PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* 🌟 高阶硬核科技风 (保留所有视觉特效) */
        body { background-color: #030305; color: #fff; position: relative; overflow-x: hidden; }
        .cyber-grid-bg {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background-image: linear-gradient(rgba(0, 242, 254, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 242, 254, 0.03) 1px, transparent 1px);
            background-size: 40px 40px; z-index: -2;
        }
        .cyber-glow-bg {
            position: fixed; top: -20vh; left: 50%; transform: translateX(-50%);
            width: 80vw; height: 60vh; background: radial-gradient(ellipse at center, rgba(0, 242, 254, 0.12) 0%, transparent 70%);
            filter: blur(60px); z-index: -1; pointer-events: none;
        }
        .tech-auth-card {
            position: relative; background: rgba(10, 10, 15, 0.45); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(0, 242, 254, 0.15); border-radius: 12px; padding: 45px 40px; width: 100%; max-width: 420px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6), inset 0 0 20px rgba(0, 242, 254, 0.05); overflow: hidden;
        }
        .tech-auth-card::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 1px;
            background: linear-gradient(90deg, transparent, #00f2fe, transparent); animation: cyber-scan 3s linear infinite;
        }
        @keyframes cyber-scan { 0% { left: -100%; } 100% { left: 200%; } }
        .tech-input-group { margin-bottom: 25px; position: relative; }
        .tech-label {
            font-family: 'Inter', sans-serif; color: #00f2fe; font-size: 0.8rem; font-weight: 600;
            margin-bottom: 8px; display: flex; justify-content: space-between;
        }
        .tech-input {
            width: 100%; background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff; padding: 14px 16px; border-radius: 6px; font-size: 0.95rem; transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);
        }
        .tech-input:focus { outline: none; border-color: #00f2fe; background: rgba(0, 242, 254, 0.03); box-shadow: 0 0 15px rgba(0, 242, 254, 0.2); }
        .tech-btn {
            background: transparent; color: #00f2fe; border: 1px solid #00f2fe; font-family: 'Inter', sans-serif; font-weight: 700;
            padding: 14px; width: 100%; border-radius: 6px; cursor: pointer; transition: all 0.3s ease; font-size: 1rem;
        }
        .tech-btn:hover { background: #00f2fe; color: #000; box-shadow: 0 0 20px rgba(0, 242, 254, 0.4); }
        .oauth-btn {
            flex: 1; text-align: center; padding: 12px; border-radius: 6px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
            color: #cbd5e1; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: 0.3s;
        }
        .oauth-btn:hover { background: rgba(255,255,255,0.08); color: #fff; border-color: rgba(255,255,255,0.2); }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="cyber-grid-bg"></div>
<div class="cyber-glow-bg"></div>

<main style="display: flex; align-items: center; justify-content: center; min-height: 85vh; padding: 20px;">
    <div class="tech-auth-card">
        
        <div style="text-align: center; margin-bottom: 35px;">
            <h2 style="font-weight: 900; font-size: 1.8rem; margin: 0 0 5px 0; letter-spacing: -0.5px;">Welcome Back</h2>
            <p style="color: #64748b; font-size: 0.85rem; margin: 0;">Log in to continue building your PC.</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div style="font-size: 0.8rem; color: #ff4d4d; background: rgba(255, 77, 77, 0.05); padding: 12px; border-radius: 6px; border: 1px solid rgba(255, 77, 77, 0.3); margin-bottom: 25px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['reset']) && $_GET['reset'] == 'success'): ?>
            <div style="font-size: 0.8rem; color: #00e676; background: rgba(0, 230, 118, 0.05); padding: 12px; border-radius: 6px; border: 1px solid rgba(0, 230, 118, 0.3); margin-bottom: 25px;">
                <i class="fas fa-check-circle"></i> Password reset successful. Please log in.
            </div>
        <?php endif; ?>

        <form action="login.php?redirect=<?php echo urlencode($redirect_url); ?>" method="POST">
            <div class="tech-input-group">
                <label class="tech-label">Email or Username</label>
                <input type="text" name="login_id" class="tech-input" required placeholder="Enter your email" value="<?php echo isset($_POST['login_id']) ? htmlspecialchars($_POST['login_id']) : ''; ?>">
            </div>

            <div class="tech-input-group">
                <div class="tech-label">
                    <span>Password</span>
                    <a href="forgot_password.php" style="color: #64748b; text-decoration: none; transition: 0.3s; font-weight: normal;">Forgot password?</a>
                </div>
                <div style="position: relative;">
                    <input type="password" name="password" class="tech-input" required placeholder="Enter your password" style="padding-right: 40px;">
                    <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #64748b; transition: 0.3s;"></i>
                </div>
            </div>

            <button type="submit" class="tech-btn">Sign In</button>
        </form>

        <div style="display: flex; align-items: center; margin: 30px 0;">
            <div style="flex: 1; height: 1px; background: rgba(255,255,255,0.05);"></div>
            <span style="padding: 0 15px; font-size: 0.75rem; color: #64748b; font-weight: 600;">OR CONTINUE WITH</span>
            <div style="flex: 1; height: 1px; background: rgba(255,255,255,0.05);"></div>
        </div>

        <div style="display: flex; gap: 15px;">
            <a href="<?php echo $google_login_url; ?>" class="oauth-btn"><i class="fa-brands fa-google" style="color: #EA4335;"></i> Google</a>
            <a href="<?php echo $discord_login_url; ?>" class="oauth-btn"><i class="fa-brands fa-discord" style="color: #5865F2;"></i> Discord</a>
        </div>

        <div style="text-align: center; margin-top: 30px; font-size: 0.85rem; color: #64748b;">
            Don't have an account? <a href="register.php" style="color: #00f2fe; text-decoration: none; font-weight: 700;">Sign Up</a>
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
                this.classList.replace('fa-eye', 'fa-eye-slash');
                this.style.color = '#00f2fe';
            } else {
                inputField.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye');
                this.style.color = '#64748b';
            }
        });
    });
});
</script>
</body>
</html>