<?php
session_start();
// 🌟 智慧相容：優先載入 config.php，若不存在則降級載入 db_connect.php
if (file_exists('config.php')) {
    require_once 'config.php';
} else {
    include 'db_connect.php';
}

// 防止已登入的管理員重複訪問登入頁
if (isset($_SESSION['admin_id']) || isset($_SESSION['user_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$error = '';

// 初始化暴力破解攔截器
if (!isset($_SESSION['admin_login_attempts'])) { 
    $_SESSION['admin_login_attempts'] = 0; 
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_SESSION['admin_lockout_time']) && time() < $_SESSION['admin_lockout_time']) {
        $remaining = $_SESSION['admin_lockout_time'] - time();
        $error = "Security Lockdown: Too many failed attempts. Gateway locked. Retry in " . $remaining . "s.";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            $error = "Please fill in all security credentials.";
        } else {
            // 🌟 安全防線：全面實裝 Prepared Statement，攔截萬能密碼注入
            $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? AND (role = 'admin' OR role = 'superadmin')");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // 🌟 智慧相容：同時驗證加密哈希與開發期明文
                $is_password_correct = false;
                if (password_verify($password, $user['password'])) {
                    $is_password_correct = true;
                } elseif ($password === $user['password']) {
                    $is_password_correct = true;
                    $_SESSION['security_notice'] = "⚠️ Dev Notice: Admin password is currently stored in plain text. Please hash it before deployment.";
                }

                if ($is_password_correct) {
                    // 防禦會話固定攻擊
                    session_regenerate_id(true);
                    
                    unset($_SESSION['admin_login_attempts']);
                    unset($_SESSION['admin_lockout_time']);
                    
                    // 自動識別資料庫主鍵欄位名
                    $admin_pk = $user['admin_id'] ?? $user['user_id'] ?? 0;
                    
                    // 🌟 寫入 Session
                    $_SESSION['admin_id'] = $admin_pk;
                    $_SESSION['user_id'] = $admin_pk;
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['admin_role'] = $user['role'];
                    $_SESSION['role'] = $user['role']; 
                    
                    // ==========================================
                    // 🚨 新增：Security Audit Logging (寫入登入日誌)
                    // ==========================================
                    $ip_address = $_SERVER['REMOTE_ADDR']; // 獲取使用者的真實 IP
                    // 防止在 localhost 測試時出現奇怪的 IPv6 地址 (::1)，將其轉換為標準的 127.0.0.1
                    if ($ip_address == '::1') { $ip_address = '127.0.0.1'; }
                    
                    $log_sql = "INSERT INTO admin_logs (admin_id, username, role, ip_address) VALUES (?, ?, ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    if ($log_stmt) {
                        $log_stmt->bind_param("isss", $admin_pk, $user['username'], $user['role'], $ip_address);
                        $log_stmt->execute();
                        $log_stmt->close();
                    }
                    // ==========================================

                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    $_SESSION['admin_login_attempts']++;
                    $error = "Invalid administrative username or password.";
                }
            } else {
                $_SESSION['admin_login_attempts']++;
                $error = "Invalid administrative username or password."; 
            }
            $stmt->close();

            // 觸發 5 次鎖死 60 秒的懲罰機制
            if ($_SESSION['admin_login_attempts'] >= 5) {
                $_SESSION['admin_lockout_time'] = time() + 60; 
                $error = "Security Lockdown: Intrusive behavior detected. Gateway locked for 60 seconds.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - GridCity PC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<style>
    /* 🌟 全屏背景设置 */
    body {
        margin: 0;
        padding: 0;
        font-family: 'Inter', 'JetBrains Mono', sans-serif;
        /* 这里加了一层半透明的黑色遮罩，防止背景太亮盖住文字 */
        background-image: linear-gradient(rgba(10, 10, 15, 0.6), rgba(10, 10, 15, 0.8)), url('image/Login_background.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    /* 🌟 登录框的毛玻璃特效 (Glassmorphism) */
    .login-container {
        background: rgba(15, 15, 20, 0.65); /* 半透明深色背景 */
        backdrop-filter: blur(12px); /* 核心：背景高斯模糊 */
        -webkit-backdrop-filter: blur(12px); /* 兼容 Safari */
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.6), 0 0 20px rgba(0, 242, 254, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-top: 1px solid rgba(0, 242, 254, 0.3); /* 顶部青色霓虹边框 */
        width: 100%;
        max-width: 400px;
        text-align: center;
    }

    /* 🌟 登录框大标题 (GridCity PC) - 完美同步按钮的渐变青蓝色 */
    .login-container h1 {
        background: linear-gradient(135deg, #00f2fe, #4facfe) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        margin-top: 0;
        margin-bottom: 5px;
        font-weight: 900;
        letter-spacing: 1px;
        filter: drop-shadow(0 0 10px rgba(0, 242, 254, 0.5));
    }

    /* 🌟 副标题样式 (从暗灰色改成明亮的浅灰色) */
    .login-container p {
        color: #cccccc !important; 
        font-size: 12px;
        margin-bottom: 30px;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    /* 🌟 输入框样式 */
    .form-group {
        margin-bottom: 20px;
        text-align: left;
    }

    /* 🌟 账号密码的提示字 (改成纯白 + 加粗) */
    .form-group label {
        display: block;
        color: #ffffff !important; 
        font-weight: bold;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        background: rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 6px;
        color: #ffffff !important; /* 确保用户打字时是白色的 */
        font-size: 14px;
        box-sizing: border-box;
        transition: 0.3s;
    }
    
    /* 🌟 输入框里还没打字时的占位符 (稍微提亮一点) */
    .form-control::placeholder {
        color: #999999;
    }

    .form-control:focus {
        outline: none;
        border-color: #00f2fe;
        box-shadow: 0 0 10px rgba(0, 242, 254, 0.3);
        background: rgba(0, 0, 0, 0.7);
    }

    /* 🌟 按钮样式 */
    .btn-login {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #00f2fe, #4facfe);
        border: none;
        border-radius: 6px;
        color: #000000;
        font-weight: bold;
        font-size: 15px;
        cursor: pointer;
        transition: 0.3s;
        margin-top: 10px;
    }

    .btn-login:hover {
        box-shadow: 0 0 15px rgba(0, 242, 254, 0.6);
        transform: translateY(-2px);
    }

    .login-container a {
        color: #00f2fe !important;
        text-decoration: none;
        font-size: 13px;
        display: inline-block;
        margin-top: 20px;
        transition: 0.3s;
    }

    .login-container a:hover {
        color: #ffffff !important;
        text-shadow: 0 0 8px rgba(0, 242, 254, 0.8);
    }
    
    .error-msg {
        background: rgba(255, 77, 77, 0.1);
        color: #ff4d4d;
        border: 1px solid rgba(255, 77, 77, 0.3);
        padding: 10px;
        border-radius: 6px;
        font-size: 13px;
        margin-bottom: 20px;
    }
</style>
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <h1>GridCity PC</h1>
            <p>System Administration</p>
        </div>

        <?php if(!empty($error)) echo "<div class='error-msg'>$error</div>"; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required placeholder="Enter admin username">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Enter password">
            </div>
            
            <button type="submit" class="btn-login">Access Dashboard</button>
        </form>

        <a href="index.php" class="back-link">&larr; Back to Customer Page</a>
    </div>

</body>
</html>