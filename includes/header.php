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
    
    <?php 
        // 自动侦测当前所在页面，用于点亮底部线条
        $current_page = basename($_SERVER['PHP_SELF']); 
    ?>
    <div class="nav-links">
        <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home</a>
        <a href="components.php" class="<?php echo ($current_page == 'components.php') ? 'active' : ''; ?>">Components</a>
        <a href="packages.php" class="<?php echo ($current_page == 'packages.php') ? 'active' : ''; ?>">Packages</a>
        <a href="builder.php" class="<?php echo ($current_page == 'builder.php') ? 'active' : ''; ?>"><i class="fas fa-tools"></i> PC Builder</a>
        <a href="community.php" class="<?php echo ($current_page == 'community.php') ? 'active' : ''; ?>"><i class="fas fa-network-wired"></i> Neural Network</a>
        
        <!-- 🌟 新增：独立滑动的能量条 -->
        <div class="nav-indicator"></div>
    </div>

    <div class="nav-actions" style="display: flex; align-items: center;">
  
        <?php if(isset($_SESSION['customer_id'])): ?>
        <div class="profile-dropdown" style="position: relative; display: inline-block; margin-right: 15px;">
            <a href="profile.php" style="color: #00f2fe; text-decoration: none; padding-bottom: 5px;">
            <i class="fas fa-user-astronaut"></i> Hi, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?> <i class="fa-solid fa-chevron-down" style="font-size: 0.8rem; margin-left: 5px;"></i>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const navLinks = document.querySelectorAll('.nav-links a');
    const indicator = document.querySelector('.nav-indicator');
    const activeLink = document.querySelector('.nav-links a.active');

    function setIndicator(link) {
        if (!link) return;
        const linkRect = link.getBoundingClientRect();
        const navRect = link.parentElement.getBoundingClientRect();
        
        indicator.style.left = (linkRect.left - navRect.left) + 'px';
        indicator.style.width = linkRect.width + 'px';
    }

    // 1. 页面加载完毕后：瞬间就位，没有任何动画！
    if (activeLink) {
        indicator.style.transition = 'none'; // 关键：关闭动画
        setIndicator(activeLink);            // 瞬间定位
        indicator.offsetHeight;              // 强制浏览器刷新渲染
        indicator.style.transition = '';     // 恢复动画（为后面的点击滑动做准备）
    }

    // 2. 点击事件拦截：丝滑划过
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const targetUrl = this.getAttribute('href');
            if (this.classList.contains('active')) return;

            e.preventDefault(); 
            
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            setIndicator(this); // 触发滑动
            
            setTimeout(() => {
                window.location.href = targetUrl;
            }, 150); 
        });
    });
});
</script>

<main class="main-container">