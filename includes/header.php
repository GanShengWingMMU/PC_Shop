<?php 
// 1. 开启输出缓冲，防止 Header 跳转报错
ob_start(); 

// 2. 智能开启 Session (如果上面没开过，这里才开，完美解决冲突！)
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}

// 3. 引入数据库
require_once 'config.php'; 

$header_wallet_balance = 0.00;
$current_tier = 'Standard';

if (isset($_SESSION['customer_id'])) {
    $header_user_id = $_SESSION['customer_id'];
    $header_stmt = $conn->prepare("SELECT wallet_balance, membership_tier, vip_expiry_date FROM customers WHERE customer_id = ?");
    $header_stmt->bind_param("i", $header_user_id);
    $header_stmt->execute();
    $header_result = $header_stmt->get_result();
    
    if ($header_row = $header_result->fetch_assoc()) {
        $header_wallet_balance = $header_row['wallet_balance'];
        $current_tier = $header_row['membership_tier'];
        $vip_expiry = $header_row['vip_expiry_date'];
        
// ==========================================
        // 🚨 VIP 到期巡邏 & 自動扣款系統 (Auto-Renew Engine)
        // ==========================================
        if ($current_tier === 'VIP' && $vip_expiry !== null) {
            $now = time();
            $expiry_time = strtotime($vip_expiry);
            
            // 如果過期了！
            if ($now > $expiry_time) {
                $renew_cost = 29.90;
                $payment_success = false;
                $auto_renew_enabled = $header_row['auto_renew'];

                // 檢查是否有開啟自動續約
                if ($auto_renew_enabled == 1) {
                    
                    // 💸 策略 1：嘗試從 Default Credit Card 扣款
                    $card_stmt = $conn->prepare("
                        SELECT b.id, b.balance 
                        FROM saved_cards sc 
                        JOIN bank b ON sc.bank_id = b.id 
                        WHERE sc.customer_id = ? AND sc.is_default = 1
                    ");
                    $card_stmt->bind_param("i", $header_user_id);
                    $card_stmt->execute();
                    $card_res = $card_stmt->get_result();
                    
                    if ($card_row = $card_res->fetch_assoc()) {
                        if ($card_row['balance'] >= $renew_cost) {
                            // 銀行餘額足夠，直接扣銀行！
                            $conn->query("UPDATE bank SET balance = balance - $renew_cost WHERE id = " . $card_row['id']);
                            $payment_success = true;
                        }
                    }
                    $card_stmt->close();

                    // 💸 策略 2：如果信用卡失敗 (或沒綁定)，退而求其次扣 E-Wallet
                    if (!$payment_success && $header_wallet_balance >= $renew_cost) {
                        $conn->query("UPDATE customers SET wallet_balance = wallet_balance - $renew_cost WHERE customer_id = $header_user_id");
                        $conn->query("INSERT INTO wallet_transactions (customer_id, type, amount) VALUES ($header_user_id, 'Auto-Renew Payment', -$renew_cost)");
                        $payment_success = true;
                    }

                    // ✅ 判定續約結果
                    if ($payment_success) {
                        // 扣款成功：延長 30 天！
                        $new_expiry = date('Y-m-d H:i:s', strtotime('+30 days', $expiry_time));
                        $conn->query("UPDATE customers SET vip_expiry_date = '$new_expiry' WHERE customer_id = $header_user_id");
                    } else {
                        // 扣款失敗 (沒錢或沒綁卡)：強制降級並關閉自動續約！
                        $conn->query("UPDATE customers SET membership_tier = 'Standard', vip_expiry_date = NULL, auto_renew = 0 WHERE customer_id = $header_user_id");
                        $current_tier = 'Standard';
                    }
                    
                } else {
                    // 沒有開啟自動續約：直接降級
                    $conn->query("UPDATE customers SET membership_tier = 'Standard', vip_expiry_date = NULL WHERE customer_id = $header_user_id");
                    $current_tier = 'Standard';
                }
            }
        }
    }
    $header_stmt->close();
}

// 🌟 修正：從資料庫即時計算購物車內商品總數
$cart_item_count = 0;
if (isset($_SESSION['customer_id'])) {
    $count_stmt = $conn->prepare("SELECT SUM(quantity) AS total_items FROM shopping_cart WHERE customer_id = ?");
    $count_stmt->bind_param("i", $_SESSION['customer_id']);
    $count_stmt->execute();
    $count_res = $count_stmt->get_result();
    if ($count_row = $count_res->fetch_assoc()) {
        $cart_item_count = $count_row['total_items'] ?? 0; // 如果沒有商品就顯示 0
    }
    $count_stmt->close();
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
    
    <style>
        .profile-dropdown:hover .dropdown-content {
            display: block !important;
            animation: fadeIn 0.2s ease-in-out;
        }

.dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 180px;
            padding: 10px 0; 
            margin-top: 0px; 
            
            background-clip: padding-box;
            background-color: #11151c; 
            border: 1px solid rgba(0, 243, 255, 0.2);
            border-radius: 8px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.5);
            z-index: 9999;
        }

        .profile-dropdown::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            height: 15px; 
            background: transparent; 
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
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
        
        <!-- 🌟 1. 👑 ELITE VIP 按鈕 -->
        <a href="membership.php" style="color: #ffd700; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 8px; padding: 8px 15px; background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 20px; transition: 0.3s; margin-right: 15px;" onmouseover="this.style.boxShadow='0 0 15px rgba(255, 215, 0, 0.5)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.boxShadow='none'; this.style.transform='none';">
            <i class="fa-solid fa-crown"></i> ELITE VIP
        </a>

        <!-- 🌟 2. 👤 使用者名稱與下拉選單 -->
        <?php if(isset($_SESSION['customer_id'])): ?>
        <div class="profile-dropdown" style="position: relative; display: inline-block; margin-right: 15px;">
            <a href="profile.php" style="color: #00f2fe; text-decoration: none; padding-bottom: 5px;">
            <i class="fas fa-user-astronaut"></i> Hi, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?> <i class="fa-solid fa-chevron-down" style="font-size: 0.8rem; margin-left: 5px;"></i>
            </a>
            
<div class="dropdown-content">
                <a href="profile.php"><i class="fa-regular fa-id-badge"></i> My Dashboard</a>
                <a href="my_orders.php"><i class="fa-solid fa-box-open"></i> My Orders</a>
                <a href="wallet_topup.php"><i class="fa-solid fa-wallet"></i> Digital Wallet</a>
                
                <a href="vouchers.php"><i class="fa-solid fa-ticket" style="color: #ffd700;"></i> My Vouchers</a>
                
                <div style="border-top: 1px solid rgba(255,255,255,0.1); margin: 5px 0;"></div>
                <a href="logout.php" style="color: #ff4d4d;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>

        </div>
        <?php else: ?>
            <a href="login.php" style="text-decoration: none; margin-right: 15px;"><i class="fas fa-sign-in-alt"></i> Login</a>
            <a href="register.php" style="text-decoration: none; margin-right: 15px;"><i class="fas fa-user-plus"></i> Register</a>
        <?php endif; ?>

        <!-- 🌟 3. 🛒 購物車按鈕 -->
<a href="cart.php" style="background: #00f2fe; color: #000; padding: 8px 20px; border-radius: 30px; font-weight: 900; text-decoration: none; display: flex; align-items: center; gap: 8px; box-shadow: 0 0 15px rgba(0, 242, 254, 0.4); transition: 0.3s;" onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 0 25px rgba(0, 242, 254, 0.7)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 0 15px rgba(0, 242, 254, 0.4)';">
    <i class="fa-solid fa-cart-shopping"></i> Cart (<?php echo $cart_item_count; ?>)
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