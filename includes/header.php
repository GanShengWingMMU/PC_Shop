<?php 
// 1. 开启输出缓冲，防止 Header 跳转报错
ob_start(); 

// 2. 智能开启 Session (如果上面没开过，这里才开，完美解决冲突！)
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}

// 3. 引入数据库
require_once 'config.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GridCitY PC Shop - Custom Builds & Parts</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">
        <a href="index.php">GridCitY PC</a>
    </div>
    
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="components.php">Components</a>
        <a href="packages.php">Packages</a>
        <a href="builder.php" class="highlight-link"><i class="fas fa-tools"></i> PC Builder</a>
    </div>

    <div class="nav-actions">
        <?php 
        // ==========================================
        // 核心修复：根据登录状态动态显示按钮
        // ==========================================
        if(isset($_SESSION['customer_id'])): 
        ?>
            <span style="color: var(--text-main); font-weight: 600; margin-right: 15px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-user-circle" style="font-size: 1.2rem; color: var(--accent-blue);"></i>
                Hi, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!
            </span>
            <a href="logout.php" class="btn btn-outline" style="padding: 8px 16px; border-color: #ff4d4d; color: #ff4d4d;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>

        <?php else: ?>
            <a href="login.php" class="btn" style="color: var(--text-main); font-weight: 600;">Login</a>
            <a href="register.php" class="btn btn-outline" style="padding: 8px 16px;"><i class="fas fa-user-plus"></i> Register</a>
        <?php endif; ?>

        <a href="cart.php" class="btn btn-primary" style="padding: 8px 16px; margin-left: 10px;">
            <i class="fas fa-shopping-cart"></i> Cart (0)
        </a>
    </div>
</nav>

<main class="main-container">