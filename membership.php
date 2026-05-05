<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) {
    $_SESSION['error_msg'] = "Please login to view membership options.";
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// 🌟 修正 1：必須把 `vip_expiry_date` 也從資料庫抓出來，不然下面 HTML 顯示時會報錯！
$stmt = $conn->prepare("SELECT membership_tier, reward_coins, vip_expiry_date, auto_renew FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$current_tier = $user_data['membership_tier'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 🎯 情況 A：第一次加入 (首月免費)
    if (isset($_POST['join_vip'])) {
        $expiry_date = date('Y-m-d H:i:s', strtotime('+30 days'));

        $update_sql = "UPDATE customers 
                       SET membership_tier = 'VIP', 
                           reward_coins = reward_coins + 500,
                           vip_expiry_date = ? 
                       WHERE customer_id = ?";
                       
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $expiry_date, $customer_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Welcome to ELITE! Your first month is free and expires on " . date('d M Y', strtotime($expiry_date));
        }
        $stmt->close();
        
        header("Location: membership.php");
        exit();
    }
    
    // 🌟 修正 2：補上點擊「Renew (續約)」按鈕的處理邏輯！
    // 🎯 情況 B：點擊續約按鈕 (延長 30 天並扣款模擬)
 // 🎯 情況 B：切換自動續約 (Toggle Auto-Renew)
    if (isset($_POST['toggle_auto_renew'])) {
        $new_status = ($user_data['auto_renew'] == 1) ? 0 : 1;
        
        $toggle_sql = "UPDATE customers SET auto_renew = ? WHERE customer_id = ?";
        $toggle_stmt = $conn->prepare($toggle_sql);
        $toggle_stmt->bind_param("ii", $new_status, $customer_id);
        
        if ($toggle_stmt->execute()) {
            if ($new_status == 1) {
                $_SESSION['success_msg'] = "Auto-Renew ENABLED. We will automatically deduct RM 29.90 from your default payment method when your subscription expires.";
            } else {
                $_SESSION['error_msg'] = "Auto-Renew DISABLED. You will lose your ELITE status when your current subscription expires.";
            }
        }
        $toggle_stmt->close();
        
        header("Location: membership.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GridCitY ELITE Membership</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .membership-wrapper { max-width: 800px; margin: 50px auto; text-align: center; }
        
        .vip-card {
            background: linear-gradient(135deg, #111 0%, #1a1a1a 100%);
            border: 2px solid #ffd700;
            border-radius: 20px;
            padding: 50px 30px;
            box-shadow: 0 15px 40px rgba(255, 215, 0, 0.15);
            position: relative;
            overflow: hidden;
        }
        .vip-card::before {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,215,0,0.1) 0%, transparent 60%);
            animation: spin 10s linear infinite;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        
        .vip-title {
            color: #ffd700; font-size: 3rem; font-weight: 900; letter-spacing: 2px;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.4); margin-bottom: 10px;
            position: relative; z-index: 1;
        }
        .perks-list {
            text-align: left; max-width: 400px; margin: 40px auto;
            color: var(--text-main); font-size: 1.1rem; line-height: 1.8; position: relative; z-index: 1;
        }
        .perks-list i { color: #00f2fe; margin-right: 10px; font-size: 1.2rem; }
        
        .btn-upgrade {
            background: #ffd700; color: #000; font-size: 1.2rem; font-weight: 900;
            padding: 15px 40px; border: none; border-radius: 30px; cursor: pointer;
            transition: 0.3s; position: relative; z-index: 1; text-transform: uppercase;
        }
        .btn-upgrade:hover { transform: scale(1.05); box-shadow: 0 0 30px rgba(255, 215, 0, 0.6); }
        
        .active-status {
            background: rgba(0, 255, 0, 0.1); border: 1px solid #00ff00; color: #00ff00;
            padding: 15px 30px; border-radius: 12px; font-size: 1.2rem; font-weight: bold;
            display: inline-block; position: relative; z-index: 1;
        }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="main-container membership-wrapper">
    
    <?php if (isset($_SESSION['success_msg'])): ?>
        <div style="background: rgba(0, 255, 0, 0.1); border: 1px solid #00ff00; color: #00ff00; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold;">
            <i class="fa-solid fa-crown"></i> <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_msg'])): ?>
        <div style="background: rgba(255, 0, 0, 0.1); border: 1px solid #ff4d4d; color: #ff4d4d; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold;">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
        </div>
    <?php endif; ?>

    <div class="vip-card">
        <h1 class="vip-title">GridCitY ELITE</h1>
        <p style="color: var(--text-muted); font-size: 1.1rem; position: relative; z-index: 1;">Elevate your building experience with exclusive perks.</p>

        <div class="perks-list">
            <div><i class="fa-solid fa-tags"></i> <strong>5% Flat Discount</strong> on all orders</div>
            <div><i class="fa-solid fa-coins"></i> <strong>500 Bonus Coins</strong> instantly upon joining</div>
            <div><i class="fa-solid fa-truck-fast"></i> Priority Assembly & Shipping</div>
            <div><i class="fa-solid fa-headset"></i> 24/7 Dedicated Tech Support</div>
        </div>

<?php if ($user_data['membership_tier'] === 'VIP'): ?>
    <div style="background: rgba(0, 255, 0, 0.1); border: 1px solid #00ff00; padding: 15px; border-radius: 8px; text-align: center; margin-top: 20px; position: relative; z-index: 1;">
        <h4 style="color: #00ff00; margin: 0 0 5px 0;"><i class="fa-solid fa-circle-check"></i> ELITE Status Active</h4>
        <p style="color: #ccc; font-size: 0.9rem; margin: 0;">
            Valid until: <strong style="color: #fff;"><?php echo date('d M Y, h:i A', strtotime($user_data['vip_expiry_date'])); ?></strong>
        </p>
    </div>
    
    <!-- 🌟 自動續約開關 UI -->
    <form method="POST" style="text-align: center; margin-top: 20px; position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center; gap: 10px;">
        <?php if ($user_data['auto_renew'] == 1): ?>
            <span style="color: #00f2fe; font-weight: bold; font-size: 0.9rem;"><i class="fa-solid fa-arrows-rotate fa-spin"></i> Auto-Renew is ON</span>
            <button type="submit" name="toggle_auto_renew" style="background: transparent; border: 1px solid #ff4d4d; color: #ff4d4d; padding: 8px 20px; border-radius: 20px; font-weight: bold; cursor: pointer; transition: 0.3s;" onmouseover="this.style.background='rgba(255,77,77,0.1)'" onmouseout="this.style.background='transparent'">
                Cancel Auto-Renew
            </button>
        <?php else: ?>
            <span style="color: #ffcc00; font-weight: bold; font-size: 0.9rem;"><i class="fa-solid fa-circle-pause"></i> Auto-Renew is OFF</span>
            <button type="submit" name="toggle_auto_renew" style="background: #ffd700; color: #000; padding: 12px 25px; border-radius: 25px; border: none; font-weight: bold; cursor: pointer; box-shadow: 0 0 15px rgba(255,215,0,0.4); transition: 0.3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                Turn On Auto-Renew (RM 29.90/mo)
            </button>
        <?php endif; ?>
    </form>
<?php else: ?>
    <form method="POST" style="text-align: center; margin-top: 20px; position: relative; z-index: 1;">
        <button type="submit" name="join_vip" style="background: #00f2fe; color: #000; padding: 15px 30px; border-radius: 30px; border: none; font-weight: 900; font-size: 1.1rem; cursor: pointer;">
            Claim First Month FREE <i class="fa-solid fa-arrow-right"></i>
        </button>
    </form>
<?php endif; ?>
    </div>

</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>