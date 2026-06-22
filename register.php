<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['customer_id'])) { header("Location: index.php"); exit(); }

$error_msg = "";
require_once 'keys.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars(trim($_POST['username']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];


    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $error_msg = "Username must be 3-20 characters long and contain only letters, numbers, and underscores.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format.";
    } elseif (strlen($password) < 12 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W]/', $password)) {
        $error_msg = "Password must be at least 12 characters and include uppercase, number, and symbol.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } else {
        $check_stmt = $conn->prepare("SELECT * FROM customers WHERE email = ? OR username = ?");
        $check_stmt->bind_param("ss", $email, $username);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();

        if ($check_res->num_rows > 0) {
            $error_msg = "Username or Email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO customers (username, email, password) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if ($insert_stmt->execute()) {
                $_SESSION['customer_id'] = $insert_stmt->insert_id;
                $_SESSION['username'] = $username;
                header("Location: index.php");
                exit();
            } else {
                $error_msg = "Database error. Please try again.";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - GridCity PC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        body { background: #030305; color: #fff; font-family: 'Inter', sans-serif; display: flex; flex-direction: column; min-height: 100vh; margin: 0; }
        .cyber-grid-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-image: linear-gradient(rgba(0, 242, 254, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 242, 254, 0.03) 1px, transparent 1px); background-size: 40px 40px; z-index: -2; }
        .cyber-glow-bg { position: fixed; top: -20vh; right: -10vw; width: 60vw; height: 80vh; background: radial-gradient(circle, rgba(0, 242, 254, 0.08) 0%, transparent 60%); filter: blur(80px); z-index: -1; pointer-events: none; }

        .auth-container { max-width: 450px; width: 100%; margin: 60px auto; padding: 40px; background: rgba(10, 10, 15, 0.6); border: 1px solid rgba(0, 242, 254, 0.2); border-radius: 12px; box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6), inset 0 0 20px rgba(0, 242, 254, 0.05); backdrop-filter: blur(20px); position: relative; z-index: 1; }
        .auth-header { text-align: center; margin-bottom: 30px; }
        .auth-header h1 { font-size: 2rem; font-weight: 900; margin: 0 0 10px 0; letter-spacing: -1px; }
        .auth-header p { color: #94a3b8; font-size: 0.9rem; margin: 0; }

        .form-group { margin-bottom: 20px; position: relative; }
        .form-group label { display: block; color: #94a3b8; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; }
        .form-control { width: 100%; background: rgba(0, 0, 0, 0.5); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; padding: 14px 16px; border-radius: 6px; font-size: 0.95rem; font-family: 'Inter', sans-serif; transition: 0.3s; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: #00f2fe; box-shadow: 0 0 15px rgba(0, 242, 254, 0.2); background: rgba(0, 242, 254, 0.03); }
        .form-control::placeholder { color: #475569; }

        .btn-submit { width: 100%; background: transparent; color: #00f2fe; border: 1px solid #00f2fe; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; padding: 15px; border-radius: 6px; cursor: pointer; transition: 0.3s; margin-top: 10px; font-size: 1rem; }
        .btn-submit:hover { background: #00f2fe; color: #000; box-shadow: 0 0 20px rgba(0, 242, 254, 0.4); }

        .oauth-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 10px; background: rgba(255, 255, 255, 0.05); color: #fff; border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: 0.3s; }
        .oauth-btn:hover { background: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.2); transform: translateY(-2px); }

        .error-msg { color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 12px; border-radius: 6px; border: 1px solid rgba(255,77,77,0.3); font-size: 0.85rem; font-weight: 600; margin-bottom: 20px; text-align: center; }

        .pwd-checklist { list-style: none; padding: 0; margin: 10px 0 10px 0; font-size: 0.75rem; color: #64748b; font-family: 'JetBrains Mono', monospace; display: none; }
        .pwd-checklist li { margin-bottom: 5px; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .pwd-checklist li.valid { color: #00e676; }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="cyber-grid-bg"></div>
<div class="cyber-glow-bg"></div>

<main style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div class="auth-container">
        <div class="auth-header">
            <h1>Create Account</h1>
            <p>Join GridCity to track builds and earn coins</p>
        </div>

        <?php if(!empty($error_msg)): ?>
            <div class="error-msg"><i class="fas fa-exclamation-triangle"></i> <?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required placeholder="Choose a display name">
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="name@example.com">
            </div>

            <div class="form-group" style="position: relative;">
                <label>Password</label>
                <input type="password" name="password" id="reg_pass" class="form-control" required placeholder="Create strong password" style="padding-right: 40px;">
                <i class="fa-solid fa-eye toggle-password" style="position: absolute; right: 15px; top: 40px; cursor: pointer; color: #64748b; transition: 0.3s;"></i>
                
                <ul class="pwd-checklist" id="pwd-checklist">
                    <li id="req-len"><i class="fas fa-times-circle"></i> 12+ characters</li>
                    <li id="req-up"><i class="fas fa-times-circle"></i> 1 Uppercase</li>
                    <li id="req-num"><i class="fas fa-times-circle"></i> 1 Number</li>
                    <li id="req-sym"><i class="fas fa-times-circle"></i> 1 Symbol</li>
                </ul>
            </div>
            
            <div class="strength-bar" style="height: 5px; background: rgba(255,255,255,0.1); border-radius: 3px; margin-bottom: 20px; overflow: hidden;">
                <div id="strength-bar-fill" style="height: 100%; width: 0%; background: #ff4d4d; transition: 0.3s;"></div>
            </div>

            <div class="form-group" style="position: relative;">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required placeholder="Retype password" style="padding-right: 40px;">
                <i class="fa-solid fa-eye toggle-password" style="position: absolute; right: 15px; top: 40px; cursor: pointer; color: #64748b; transition: 0.3s;"></i>
            </div>

            <button type="submit" class="btn-submit">Sign Up</button>
        </form>

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
            this.style.color = input.type === 'password' ? '#00f2fe' : '#64748b';
        });
    });

    document.getElementById('reg_pass').addEventListener('input', function() {
        let val = this.value; 
        let score = 0;
        
        const checklist = document.getElementById('pwd-checklist');
        const bar = document.getElementById('strength-bar-fill');
        
  
        if(val.length > 0) { checklist.style.display = 'block'; } 
        else { checklist.style.display = 'none'; bar.style.width = '0%'; return; }

        const reqLen = document.getElementById('req-len');
        const reqUp = document.getElementById('req-up');
        const reqNum = document.getElementById('req-num');
        const reqSym = document.getElementById('req-sym');

        const tick = '<i class="fas fa-check-circle"></i> ';
        const cross = '<i class="fas fa-times-circle"></i> ';

  
        if(val.length >= 12) { score += 40; reqLen.className = 'valid'; reqLen.innerHTML = tick + '12+ characters'; }
        else { reqLen.className = ''; reqLen.innerHTML = cross + '12+ characters'; }

        if(/[A-Z]/.test(val)) { score += 20; reqUp.className = 'valid'; reqUp.innerHTML = tick + '1 Uppercase'; }
        else { reqUp.className = ''; reqUp.innerHTML = cross + '1 Uppercase'; }

        if(/[0-9]/.test(val)) { score += 20; reqNum.className = 'valid'; reqNum.innerHTML = tick + '1 Number'; }
        else { reqNum.className = ''; reqNum.innerHTML = cross + '1 Number'; }

        if(/[\W]/.test(val)) { score += 20; reqSym.className = 'valid'; reqSym.innerHTML = tick + '1 Symbol'; }
        else { reqSym.className = ''; reqSym.innerHTML = cross + '1 Symbol'; }


        bar.style.width = score + '%';
        if(score < 50) bar.style.background = '#ff4d4d'; 
        else if(score < 100) bar.style.background = '#facc15'; 
        else bar.style.background = '#00e676'; 
    });
});
</script>

</body>
</html>