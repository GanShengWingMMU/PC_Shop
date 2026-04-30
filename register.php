<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['customer_id'])) { header("Location: index.php"); exit(); }

$error_msg = "";
require_once 'keys.php'; // 包含 OAuth 密钥

$google_redirect_uri = 'http://localhost/projects/google_callback.php';
$google_login_url = "https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id=" . $google_client_id . "&redirect_uri=" . urlencode($google_redirect_uri) . "&scope=email%20profile";
$discord_redirect_uri = 'http://localhost/projects/discord_callback.php';
$discord_login_url = "https://discord.com/api/oauth2/authorize?client_id=" . $discord_client_id . "&redirect_uri=" . urlencode($discord_redirect_uri) . "&response_type=code&scope=" . urlencode("identify email");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error_msg = "ERR: Core fields missing.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "ERR: Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "ERR: Passwords mismatch.";
    } elseif (strlen($password) < 12 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W]/', $password)) {
        $error_msg = "SYS_POLICY: 12+ chars, uppercase, number, symbol required.";
    } else {
        $check_stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ? OR username = ?");
        $check_stmt->bind_param("ss", $email, $username);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $error_msg = "ERR: Identity already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, username, email, password, account_status) VALUES (?, ?, ?, ?, ?, 'Active')");
            $insert_stmt->bind_param("sssss", $first_name, $last_name, $username, $email, $hashed);
            if ($insert_stmt->execute()) {
                $_SESSION['customer_id'] = $insert_stmt->insert_id;
                $_SESSION['username'] = $username;
                header("Location: index.php"); exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Establish Profile - GridCitY PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background-color: #030305; color: #fff; position: relative; overflow-x: hidden; }
        .cyber-grid-bg {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background-image: linear-gradient(rgba(0, 242, 254, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 242, 254, 0.03) 1px, transparent 1px);
            background-size: 40px 40px; z-index: -2;
        }
        .cyber-glow-bg {
            position: fixed; top: -10vh; right: -10vw; width: 60vw; height: 60vh;
            background: radial-gradient(circle, rgba(0, 242, 254, 0.08) 0%, transparent 70%); filter: blur(80px); z-index: -1; pointer-events: none;
        }
        .tech-auth-card {
            position: relative; background: rgba(10, 10, 15, 0.45); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(0, 242, 254, 0.15); border-radius: 12px; padding: 45px 40px; width: 100%; max-width: 520px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6), inset 0 0 20px rgba(0, 242, 254, 0.05); overflow: hidden; margin: 40px auto;
        }
        .tech-auth-card::before { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 1px; background: linear-gradient(90deg, transparent, #00f2fe, transparent); animation: cyber-scan 3s linear infinite; }
        @keyframes cyber-scan { 0% { left: -100%; } 100% { left: 200%; } }
        
        .tech-input-group { margin-bottom: 20px; position: relative; }
        .tech-label { font-family: 'Inter', sans-serif; color: #00f2fe; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: block; }
        .tech-input {
            width: 100%; background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff; padding: 12px 16px; border-radius: 6px; font-size: 0.95rem; transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.5); font-family: 'Inter', sans-serif;
        }
        .tech-input:focus { outline: none; border-color: #00f2fe; background: rgba(0, 242, 254, 0.03); box-shadow: 0 0 15px rgba(0, 242, 254, 0.2); }
        .tech-btn {
            background: transparent; color: #00f2fe; border: 1px solid #00f2fe; font-family: 'Inter', sans-serif; font-weight: 700;
            text-transform: uppercase; letter-spacing: 2px; padding: 14px; width: 100%; border-radius: 6px; cursor: pointer; transition: 0.3s;
        }
        .tech-btn:hover { background: #00f2fe; color: #000; box-shadow: 0 0 20px rgba(0, 242, 254, 0.4); }
        
        .oauth-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 25px; }
        .oauth-btn { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); color: #cbd5e1; padding: 12px; border-radius: 6px; text-align: center; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: 0.3s; }
        .oauth-btn:hover { background: rgba(255,255,255,0.08); color: #fff; border-color: rgba(255,255,255,0.2); }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>
<div class="cyber-grid-bg"></div>
<div class="cyber-glow-bg"></div>

<main style="padding: 20px;">
    <div class="tech-auth-card">
        
        <div style="text-align: center; margin-bottom: 35px;">
            <div style="display: inline-block; padding: 6px 12px; background: rgba(0,242,254,0.1); border-radius: 20px; color: #00f2fe; font-size: 0.7rem; font-weight: bold; letter-spacing: 1px; margin-bottom: 15px; border: 1px solid rgba(0,242,254,0.2);">
                <i class="fas fa-user-plus"></i> NEW REGISTRATION
            </div>
            <h2 style="font-weight: 900; font-size: 1.8rem; margin: 0 0 5px 0;">Create Account</h2>
            <p style="color: #64748b; font-size: 0.85rem; margin: 0;">Establish your GridCitY identity.</p>
        </div>

        <?php if($error_msg): ?>
            <div style="font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; color: #ff4d4d; background: rgba(255,77,77,0.05); padding: 12px; border-radius: 6px; border: 1px solid rgba(255,77,77,0.3); margin-bottom: 25px;">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div style="display: flex; gap: 15px;">
                <div class="tech-input-group" style="flex:1;">
                    <label class="tech-label">First Name</label>
                    <input type="text" name="first_name" class="tech-input" required>
                </div>
                <div class="tech-input-group" style="flex:1;">
                    <label class="tech-label">Last Name</label>
                    <input type="text" name="last_name" class="tech-input" required>
                </div>
            </div>

            <div class="tech-input-group">
                <label class="tech-label">Username</label>
                <input type="text" name="username" class="tech-input" required>
            </div>

            <div class="tech-input-group">
                <label class="tech-label">Email Address</label>
                <input type="email" name="email" class="tech-input" required>
            </div>

            <div class="tech-input-group">
                <label class="tech-label">Password</label>
                <div style="position: relative;">
                    <input type="password" id="reg_pass" name="password" class="tech-input" required placeholder="Min 12 chars" style="padding-right: 40px; font-family: 'JetBrains Mono', monospace;">
                    <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #64748b;"></i>
                </div>
                <div style="height: 2px; background: rgba(255,255,255,0.05); margin-top: 5px; overflow: hidden;"><div id="strength_bar" style="height: 100%; width: 0%; transition: 0.3s;"></div></div>
            </div>
            
            <div class="tech-input-group" style="margin-bottom: 30px;">
                <label class="tech-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="tech-input" required style="font-family: 'JetBrains Mono', monospace;">
            </div>

            <button type="submit" class="tech-btn">Initialize Account</button>
        </form>

        <div style="display: flex; align-items: center; margin: 30px 0;"><div style="flex:1; height:1px; background:rgba(255,255,255,0.05);"></div><span style="padding: 0 15px; font-size: 0.7rem; color: #64748b; font-weight: 800; letter-spacing: 1px;">EXTERNAL OAUTH</span><div style="flex:1; height:1px; background:rgba(255,255,255,0.05);"></div></div>

        <div class="oauth-grid">
            <a href="<?php echo $google_login_url; ?>" class="oauth-btn"><i class="fa-brands fa-google" style="color: #EA4335;"></i> Google</a>
            <a href="<?php echo $discord_login_url; ?>" class="oauth-btn"><i class="fa-brands fa-discord" style="color: #5865F2;"></i> Discord</a>
        </div>

        <p style="text-align: center; margin-top: 35px; color: #64748b; font-size: 0.85rem;">Already established? <a href="login.php" style="color: #00f2fe; text-decoration: none; font-weight: 700;">Sign In</a></p>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleIcons = document.querySelectorAll('.toggle-password');
    toggleIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            const input = this.previousElementSibling;
            input.type = input.type === 'password' ? 'text' : 'password';
            this.classList.toggle('fa-eye-slash');
            this.style.color = input.type === 'password' ? '#64748b' : '#00f2fe';
        });
    });
    document.getElementById('reg_pass').addEventListener('input', function() {
        let val = this.value; let score = 0;
        if(val.length >= 12) score += 40;
        if(/[A-Z]/.test(val)) score += 20;
        if(/[0-9]/.test(val)) score += 20;
        if(/[^A-Za-z0-9]/.test(val)) score += 20;
        let bar = document.getElementById('strength_bar');
        bar.style.width = score + "%";
        bar.style.backgroundColor = score < 50 ? "#ff4d4d" : (score < 80 ? "#facc15" : "#00e676");
    });
});
</script>
</body>
</html>