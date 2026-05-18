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
                
                // 🌟 智慧相容：同時驗證加密哈希與開發期明文（並提供安全提示）
                $is_password_correct = false;
                if (password_verify($password, $user['password'])) {
                    $is_password_correct = true;
                } elseif ($password === $user['password']) {
                    $is_password_correct = true;
                    // 如果是明文登入，設定一個提示，可以顯示在後台儀表板上
                    $_SESSION['security_notice'] = "⚠️ Dev Notice: Admin password is currently stored in plain text. Please hash it before deployment.";
                }

                if ($is_password_correct) {
                    // 防禦會話固定攻擊
                    session_regenerate_id(true);
                    
                    unset($_SESSION['admin_login_attempts']);
                    unset($_SESSION['admin_lockout_time']);
                    
                    // 🌟 終極相容：自動識別資料庫主鍵欄位名
                    $admin_pk = $user['admin_id'] ?? $user['user_id'] ?? 0;
                    
                    // 🌟 終極大一統：同時寫入新舊兩套 Session 鍵值，徹底粉碎無窮導向死循環！
                    $_SESSION['admin_id'] = $admin_pk;
                    $_SESSION['user_id'] = $admin_pk;
                    
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['username'] = $user['username'];
                    
                    $_SESSION['admin_role'] = $user['role'];
                    $_SESSION['role'] = $user['role']; 
                    
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
        :root {
            --bg-main: #0a0a0a;
            --bg-surface: #141414;
            --accent-blue: #00f2fe;
            --accent-purple: #4facfe;
            --text-main: #ffffff;
            --text-muted: #888888;
            --border-color: rgba(255, 255, 255, 0.08);
        }
        
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            /* 🌟 核心魔法：加入背景图片，并盖上一层 75% 黑色的半透明滤镜 */
            background-image: linear-gradient(rgba(10, 10, 10, 0.75), rgba(10, 10, 10, 0.75)), url('image/Login_background.jpg');
            background-size: cover;          /* 让图片填满整个屏幕 */
            background-position: center;     /* 让图片居中显示 */
            background-repeat: no-repeat;    /* 防止图片重复拼贴 */
            
            /* 让登录框完美居中在屏幕正中间 */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-container {
            background: var(--bg-surface);
            padding: 40px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 15px 50px rgba(0,0,0,0.9); /* 加深了阴影，让卡片更立体 */
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
            /* 加入一点点毛玻璃效果让背景透过来一点点 */
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            background: rgba(20, 20, 20, 0.85);
        }

        /* 顶部霓虹装饰线 */
        .login-container::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(to right, var(--accent-purple), var(--accent-blue));
        }

        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header h1 {
            margin: 0; font-size: 2.2rem;
            background: linear-gradient(to right, var(--accent-blue), var(--accent-purple));
            -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
            font-weight: 800; letter-spacing: 1px;
        }
        .login-header p { color: var(--text-muted); font-size: 0.9rem; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 2px;}

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: var(--text-muted); font-size: 0.9rem; font-weight: 600; margin-bottom: 8px; }
        .form-control {
            width: 100%; padding: 12px 15px; box-sizing: border-box;
            background-color: rgba(0, 0, 0, 0.6); border: 1px solid var(--border-color);
            border-radius: 6px; color: var(--text-main); font-size: 1rem;
            transition: all 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--accent-blue); box-shadow: 0 0 10px rgba(0, 242, 254, 0.3); background-color: rgba(0, 0, 0, 0.9); }

        .btn-login {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            border: none; border-radius: 6px; color: #000; font-weight: 800; font-size: 1rem;
            cursor: pointer; transition: 0.3s; box-shadow: 0 4px 15px rgba(0, 242, 254, 0.2);
            margin-top: 10px;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0, 242, 254, 0.4); }

        .error-msg { color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 12px; border-radius: 6px; border: 1px solid rgba(255,77,77,0.3); text-align: center; margin-bottom: 20px; font-size: 0.9rem; font-weight: bold;}
        .back-link { display: block; text-align: center; margin-top: 25px; color: var(--text-muted); font-size: 0.85rem; text-decoration: none; transition: 0.2s;}
        .back-link:hover { color: var(--accent-blue); }
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