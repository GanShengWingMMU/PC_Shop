<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) {
    $_SESSION['error_msg'] = "Please login to view membership options.";
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// 🌟 抓取會員狀態、到期日與自動續約狀態
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
    <title>GridCitY ELITE - VIP Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .vip-dashboard {
            max-width: 1200px; margin: 40px auto; padding: 0 20px;
        }

        /* 🌟 1. 頂部狀態橫幅 (Hero Banner) */
        .hero-banner {
            background: linear-gradient(135deg, rgba(255,215,0,0.1) 0%, rgba(0,0,0,0.8) 100%), url('https://via.placeholder.com/1200x300/111/111') center/cover;
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 16px;
            padding: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .hero-title {
            color: #ffd700; font-size: 2.5rem; font-weight: 900; margin: 0 0 10px 0;
            text-shadow: 0 0 15px rgba(255, 215, 0, 0.4);
        }
        
        /* 🌟 2. 常駐福利區塊 (Benefits Grid) */
        .section-title {
            color: #fff; font-size: 1.5rem; font-weight: bold; margin-bottom: 20px;
            border-left: 4px solid #ffd700; padding-left: 10px;
        }
        .benefits-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;
            margin-bottom: 50px;
        }
        .benefit-card {
            background: #151a25; border: 1px solid #222; border-radius: 12px;
            padding: 20px; display: flex; align-items: flex-start; gap: 15px;
            transition: 0.3s;
        }
        .benefit-card:hover { transform: translateY(-5px); border-color: rgba(255,215,0,0.5); }
        .benefit-icon {
            background: rgba(0, 242, 254, 0.1); color: #00f2fe;
            width: 50px; height: 50px; border-radius: 50%;
            display: flex; justify-content: center; align-items: center; font-size: 1.5rem; flex-shrink: 0;
        }

        /* 🌟 3. 每月優惠券票根 (Shopee Style Vouchers) */
        .voucher-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px;
        }
        .voucher-ticket {
            display: flex; background: #1a1a1a; border-radius: 12px; overflow: hidden;
            border: 1px solid #333; position: relative; transition: 0.3s;
        }
        .voucher-ticket:hover { box-shadow: 0 5px 20px rgba(255, 215, 0, 0.1); border-color: #ffd700; }
        
        .voucher-left {
            background: linear-gradient(135deg, #ffd700, #f39c12); color: #000;
            width: 120px; display: flex; flex-direction: column; justify-content: center; align-items: center;
            padding: 15px; text-align: center; border-right: 2px dashed #1a1a1a;
            position: relative;
        }
        /* 製作假虛線邊緣效果 */
        .voucher-left::before, .voucher-left::after {
            content: ''; position: absolute; width: 20px; height: 20px; background: #0a0a0a; border-radius: 50%; right: -11px;
        }
        .voucher-left::before { top: -10px; }
        .voucher-left::after { bottom: -10px; }

        .voucher-middle {
            padding: 20px; flex: 1; display: flex; flex-direction: column; justify-content: center;
        }
        .voucher-right {
            padding: 20px; display: flex; flex-direction: column; justify-content: center; align-items: center;
            border-left: 1px dashed #444; min-width: 130px;
        }
        .copy-btn {
            background: rgba(255,215,0,0.1); color: #ffd700; border: 1px solid #ffd700;
            padding: 8px 15px; border-radius: 20px; font-weight: bold; cursor: pointer; transition: 0.3s;
            font-size: 0.85rem;
        }
        .copy-btn:hover { background: #ffd700; color: #000; }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="main-container vip-dashboard">

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

    <div class="hero-banner">
        <div>
            <h1 class="hero-title">GridCitY ELITE</h1>
            <?php if ($current_tier === 'VIP'): ?>
                <span style="background: rgba(0, 255, 0, 0.2); color: #00ff00; padding: 5px 15px; border-radius: 20px; font-weight: bold; border: 1px solid #00ff00;">
                    <i class="fa-solid fa-circle-check"></i> Status: Active
                </span>
                <p style="color: #ccc; margin-top: 15px;">Valid until: <strong style="color: #fff;"><?php echo date('d M Y, h:i A', strtotime($user_data['vip_expiry_date'])); ?></strong></p>
            <?php else: ?>
                <p style="color: #ccc; font-size: 1.1rem; margin-top: 10px;">Unlock premium discounts and monthly vouchers.</p>
            <?php endif; ?>
        </div>

        <div>
            <?php if ($current_tier === 'VIP'): ?>
                <form method="POST" style="text-align: right;">
                    <?php if ($user_data['auto_renew'] == 1): ?>
                        <div style="margin-bottom: 10px; color: #00f2fe; font-weight: bold; font-size: 0.9rem;"><i class="fa-solid fa-arrows-rotate fa-spin"></i> Auto-Renew is ON</div>
                        <button type="submit" name="toggle_auto_renew" style="background: transparent; border: 1px solid #ff4d4d; color: #ff4d4d; padding: 10px 25px; border-radius: 25px; font-weight: bold; cursor: pointer;">
                            Cancel Auto-Renew
                        </button>
                    <?php else: ?>
                        <div style="margin-bottom: 10px; color: #ffcc00; font-weight: bold; font-size: 0.9rem;"><i class="fa-solid fa-circle-pause"></i> Auto-Renew is OFF</div>
                        <button type="submit" name="toggle_auto_renew" style="background: #ffd700; color: #000; padding: 12px 30px; border-radius: 25px; border: none; font-weight: bold; cursor: pointer; box-shadow: 0 0 15px rgba(255,215,0,0.4);">
                            Turn On Auto-Renew (RM 29.90/mo)
                        </button>
                    <?php endif; ?>
                </form>
            <?php else: ?>
                <form method="POST">
                    <button type="submit" name="join_vip" style="background: #00f2fe; color: #000; padding: 15px 35px; border-radius: 30px; border: none; font-weight: 900; font-size: 1.2rem; cursor: pointer; box-shadow: 0 0 20px rgba(0,242,254,0.4);">
                        Claim First Month FREE <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <h2 class="section-title">ELITE Membership Benefits</h2>
    <div class="benefits-grid">
        <div class="benefit-card">
            <div class="benefit-icon"><i class="fa-solid fa-ticket"></i></div>
            <div>
                <h4 style="color: #fff; margin: 0 0 5px 0;">Monthly Vouchers</h4>
                <p style="color: #888; font-size: 0.85rem; margin: 0;">Access up to 20% OFF codes refreshed every month.</p>
            </div>
        </div>
        <div class="benefit-card">
            <div class="benefit-icon"><i class="fa-solid fa-coins"></i></div>
            <div>
                <h4 style="color: #fff; margin: 0 0 5px 0;">Instant 500 Coins</h4>
                <p style="color: #888; font-size: 0.85rem; margin: 0;">Get 500 reward coins instantly upon subscription.</p>
            </div>
        </div>
        <div class="benefit-card">
            <div class="benefit-icon"><i class="fa-solid fa-truck-fast"></i></div>
            <div>
                <h4 style="color: #fff; margin: 0 0 5px 0;">Priority Shipping</h4>
                <p style="color: #888; font-size: 0.85rem; margin: 0;">Jump the queue. Your orders are assembled first.</p>
            </div>
        </div>
        <div class="benefit-card">
            <div class="benefit-icon"><i class="fa-solid fa-headset"></i></div>
            <div>
                <h4 style="color: #fff; margin: 0 0 5px 0;">24/7 Tech Support</h4>
                <p style="color: #888; font-size: 0.85rem; margin: 0;">Direct line to our senior PC technicians.</p>
            </div>
        </div>
    </div>

    <h2 class="section-title">What You'll Enjoy Monthly</h2>
    
    <?php if ($current_tier === 'VIP'): ?>
        <div class="voucher-grid">
            <?php
            // 從資料庫抓取 Active 的 VIP 優惠碼
            $promo_stmt = $conn->prepare("SELECT code_name, discount_percentage, target_category FROM promo_codes WHERE is_vip_only = 1 AND status = 'Active'");
            $promo_stmt->execute();
            $promo_result = $promo_stmt->get_result();
            
            if ($promo_result->num_rows > 0) {
                while ($promo = $promo_result->fetch_assoc()) {
                    ?>
                    <div class="voucher-ticket">
                        <div class="voucher-left">
                            <i class="fa-solid fa-crown" style="font-size: 1.5rem; margin-bottom: 5px; color: #fff;"></i>
                            <span style="font-size: 1.2rem;">ELITE</span>
                        </div>
                        <div class="voucher-middle">
                            <h3 style="color: #fff; margin: 0 0 5px 0; font-size: 1.3rem;"><?php echo $promo['discount_percentage']; ?>% OFF</h3>
                            <p style="color: #00f2fe; font-weight: bold; margin: 0 0 5px 0; font-size: 0.85rem;">Applicable to: <?php echo $promo['target_category']; ?></p>
                            <span style="color: #777; font-size: 0.8rem; background: rgba(255,255,255,0.05); padding: 3px 8px; border-radius: 4px; display: inline-block; width: fit-content;">Monthly Voucher</span>
                        </div>
                        <div class="voucher-right">
                            <span style="font-family: monospace; color: #ccc; margin-bottom: 10px; font-size: 0.9rem;">Code: <strong style="color: #fff;"><?php echo $promo['code_name']; ?></strong></span>
                            <button class="copy-btn" onclick="copyPromoCode('<?php echo $promo['code_name']; ?>', this)">Copy Code</button>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<p style="color: #aaa; width: 100%;">No active promo codes at the moment. Check back later!</p>';
            }
            $promo_stmt->close();
            ?>
        </div>
    <?php else: ?>
        <div style="background: rgba(255,255,255,0.02); border: 1px dashed #444; padding: 40px; text-align: center; border-radius: 12px;">
            <i class="fa-solid fa-lock" style="font-size: 3rem; color: #555; margin-bottom: 15px;"></i>
            <h3 style="color: #ccc; margin-bottom: 10px;">Vouchers Locked</h3>
            <p style="color: #777; max-width: 400px; margin: 0 auto;">Join ELITE today to unlock exclusive monthly discount codes for Packages and Components.</p>
        </div>
    <?php endif; ?>

</main>

<?php include 'includes/footer.php'; ?>

<script>
function copyPromoCode(code, btnElement) {
    navigator.clipboard.writeText(code).then(() => {
        const originalText = btnElement.innerText;
        btnElement.innerText = 'Copied!';
        btnElement.style.background = '#00ff00';
        btnElement.style.color = '#000';
        btnElement.style.borderColor = '#00ff00';
        
        setTimeout(() => {
            btnElement.innerText = originalText;
            btnElement.style.background = 'rgba(255,215,0,0.1)';
            btnElement.style.color = '#ffd700';
            btnElement.style.borderColor = '#ffd700';
        }, 2000);
    });
}
</script>

</body>
</html>