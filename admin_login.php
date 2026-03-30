<?php
session_start();

// 1. 如果你已经有通行证了，直接跳去 Dashboard，不用再登录
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$errorMsg = "";

// 2. 当你点击 "Access Dashboard" 按钮时，PHP 开始审核你的账号密码
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 💡 审核：判断账号是不是 admin，密码是不是 password
    if ($username === 'admin' && $password === 'password') {
        
        // 审核通过！PHP 亲自给你发放 VIP 通行证 (Session)
        $_SESSION['role'] = 'admin'; 
        $_SESSION['username'] = $username;
        
        // 带着通行证，大摇大摆地走向 Dashboard！
        header("Location: admin_dashboard.php");
        exit();
    } else {
        // 审核失败：显示红色报错
        $errorMsg = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Administrator Login - PC shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Lora:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        body.admin-login-body {
            background-image: url('photo/Admin_Login_background.jpg');; /* 你的背景图 */
            background-size: cover;       
            background-position: center;  
            background-repeat: no-repeat; 
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background-color: #333; 
        }

        .admin-box {
            background: rgba(255, 255, 255, 0.92); 
            padding: 50px;
            width: 400px;
            border-top: 5px solid #8a2be2; /* 你的电竞紫边框 */
            box-shadow: 0 15px 25px rgba(0,0,0,0.6);
            border-radius: 8px;
        }

        #errorMessage {
            color: red; 
            text-align: center; 
            background: #ffe6e6; 
            padding: 10px; 
            border: 1px solid red;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .form-input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .btn-main {
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
        }

        .btn-main:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body class="admin-login-body">

    <div class="admin-box">
        <h1 style="text-align: center; font-family: 'Inter', serif; color: #8a2be2; margin-bottom: 5px; font-size: 2.8rem; font-weight: bold; letter-spacing: 2px; margin-top: 0;">
            PC SHOP
        </h1>

        <h2 style="text-align: center; font-family: 'Inter', serif; color: #333; margin-top: 0; margin-bottom: 30px; font-size: 1.1rem; letter-spacing: 1px;">
            System Administration
        </h2>

        <?php if(!empty($errorMsg)): ?>
            <p id="errorMessage"><?php echo $errorMsg; ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div style="margin-bottom: 20px;">
                <label style="font-weight: bold;">Username</label>
                <input type="text" name="username" class="form-input" required placeholder="admin">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="font-weight: bold;">Password</label>
                <input type="password" name="password" class="form-input" required placeholder="password">
            </div>

            <button type="submit" class="btn-main" style="width: 100%; background: #8a2be2; color: white;">
                Access Dashboard
            </button>
        </form>
        
        <p style="text-align: center; margin-top: 20px; font-size: 1rem; font-weight: bold;">
            <a href="index.php" style="color: #8a2be2; text-decoration: none;">&larr; Back to Customer Page</a>
        </p>
    </div>

</body>
</html>