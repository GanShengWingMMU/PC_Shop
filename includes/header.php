<?php 
// 1. 开启输出缓冲，防止 Header 跳转报错
ob_start(); 

// 2. 智能开启 Session (如果上面没开过，这里才开，完美解决冲突！)
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}

// 3. 引入数据库
require_once 'config.php'; 

// 🌟 4. 取得會員的錢包餘額 (如果已登入)
$header_wallet_balance = 0.00;
if (isset($_SESSION['customer_id'])) {
    $header_user_id = $_SESSION['customer_id'];
    $header_stmt = $conn->prepare("SELECT wallet_balance FROM customers WHERE customer_id = ?");
    $header_stmt->bind_param("i", $header_user_id);
    $header_stmt->execute();
    $header_result = $header_stmt->get_result();
    if ($header_row = $header_result->fetch_assoc()) {
        $header_wallet_balance = $header_row['wallet_balance'];
    }
    $header_stmt->close();
}
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

    <div class="nav-actions" style="display: flex; align-items: center;">
  
        <?php if(isset($_SESSION['customer_id'])): ?>
<div class="profile-dropdown" style="position: relative; display: inline-block; margin-right: 15px;">
    <a href="profile.php" style="color: #00f2fe; text-decoration: none; padding-bottom: 5px;">
        <i class="fas fa-user-astronaut"></i> Hi, <?php echo htmlspecialchars($_SESSION['username']); ?> <i class="fa-solid fa-chevron-down" style="font-size: 0.8rem; margin-left: 5px;"></i>
    </a>
    
    <div class="dropdown-content">
        <a href="profile.php"><i class="fa-regular fa-id-badge"></i> My Dashboard</a>
        <a href="my_orders.php"><i class="fa-solid fa-box-open"></i> My Orders</a>
        <a href="wallet_topup.php"><i class="fa-solid fa-wallet"></i> Digital Wallet</a>
        <div style="border-top: 1px solid rgba(255,255,255,0.1); margin: 5px 0;"></div>
        <a href="logout.php" style="color: #ff4d4d;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
        <?php else: ?>
            <a href="login.php" style="text-decoration: none;"><i class="fas fa-sign-in-alt"></i> Login</a>
            <a href="register.php" style="text-decoration: none;"><i class="fas fa-user-plus"></i> Register</a>
        <?php endif; ?>

        <a href="cart.php" class="btn btn-primary" style="padding: 8px 16px; margin-left: 15px;">
            <i class="fas fa-shopping-cart"></i> Cart (0)
        </a>
    </div>
</nav>

<main class="main-container">