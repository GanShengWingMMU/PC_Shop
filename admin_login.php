<?php
session_start();
include 'db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

  $sql = "SELECT * FROM users WHERE username = '$username' AND (role = 'admin' OR role = 'superadmin')";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password']) || $password == $user['password']) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Admin not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - PC SHOP</title>
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
            <h1>PC SHOP</h1>
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