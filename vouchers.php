<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// 🌟 1. 抓取 lifetime_coins 来做真假 Elite 验证
$stmt = $conn->prepare("SELECT membership_tier, username, lifetime_coins FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$current_tier = $user_data['membership_tier'];
$username = $user_data['username'];
$lifetime_coins = $user_data['lifetime_coins'] ?? 0;

// 🌟 2. 核心修复：定义真正的 ELITE
$is_elite = ($current_tier === 'VIP' || $lifetime_coins >= 1000);

// 🌟 3. 查询可用券时，只看是不是 Elite，而不是是不是 VIP
$count_query = "
    SELECT COUNT(p.promo_id) as total FROM promo_codes p 
    LEFT JOIN used_vouchers uv ON p.promo_id = uv.promo_id AND uv.customer_id = ? 
    WHERE p.status = 'Active' AND uv.promo_id IS NULL 
    AND (p.is_vip_only = 0 OR (p.is_vip_only = 1 AND ? = 1))
";
$count_stmt = $conn->prepare($count_query);
$elite_int = $is_elite ? 1 : 0; // 转换成 0 或 1 传给 SQL
$count_stmt->bind_param("ii", $customer_id, $elite_int);
$count_stmt->execute();
$total_vouchers = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Vouchers - GridCitY PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .voucher-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .page-header { margin-bottom: 25px; border-bottom: 1px solid #333; padding-bottom: 20px; }
        .page-header h1 { color: #fff; font-size: 2rem; margin: 0; }
        
        /* 🌟 錢包總覽 Dashboard */
        .wallet-dashboard {
            background: linear-gradient(90deg, rgba(0, 242, 254, 0.05), rgba(0,0,0,0));
            padding: 25px 30px;
            border-radius: 16px;
            border-left: 4px solid #00f2fe;
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border-top: 1px solid rgba(255,255,255,0.02);
            border-bottom: 1px solid rgba(255,255,255,0.02);
        }

        .voucher-section { margin-bottom: 50px; }
        .section-title { color: #888; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .section-title span { flex: 1; height: 1px; background: #333; }

        /* 票根樣式 */
        .voucher-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(450px, 1fr)); gap: 20px; }
        .voucher-card { display: flex; background: #1a1a1a; border-radius: 12px; overflow: hidden; border: 1px solid #333; transition: 0.3s; position: relative; }
        .voucher-card:hover { border-color: #00f2fe; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,242,254,0.1); }
        
        .card-left { width: 120px; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 20px; text-align: center; position: relative; border-right: 2px dashed #1a1a1a; }
        .card-left::before, .card-left::after { content: ''; position: absolute; width: 20px; height: 20px; background: #0a0a0a; border-radius: 50%; right: -11px; }
        .card-left::before { top: -10px; }
        .card-left::after { bottom: -10px; }

        .public-bg { background: linear-gradient(135deg, #00f2fe, #4facfe); color: #000; }
        .vip-bg { background: linear-gradient(135deg, #ffd700, #f39c12); color: #000; }
        .locked-bg { background: #333; color: #666; }
        .voucher-card.locked:hover { border-color: #333; transform: none; box-shadow: none; }

        .card-middle { padding: 20px; flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .card-right { padding: 20px; display: flex; flex-direction: column; justify-content: center; align-items: center; border-left: 1px dashed #444; min-width: 130px; }
        
        .copy-btn { background: rgba(0, 242, 254, 0.1); color: #00f2fe; border: 1px solid #00f2fe; padding: 8px 15px; border-radius: 20px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 0.85rem; width: 100%; }
        .copy-btn:hover { background: #00f2fe; color: #000; }
        .vip-copy-btn { border-color: #ffd700; color: #ffd700; background: rgba(255, 215, 0, 0.1); }
        .vip-copy-btn:hover { background: #ffd700; color: #000; }
        
        .terms-text { color: #888; font-size: 0.75rem; margin-top: 8px; line-height: 1.4; }
    </style>
</head>
<body style="background-color: #0a0a0a;">

<?php include 'includes/header.php'; ?>

<main class="voucher-container">
    <div class="page-header">
        <h1><i class="fa-solid fa-wallet"></i> My Voucher Wallet</h1>
        <p style="color: #888;">Collect and manage your discount codes for better savings.</p>
    </div>

    <div class="wallet-dashboard">
        <div>
            <h2 style="margin: 0; color: #fff; font-size: 1.5rem;">Hello, <?php echo htmlspecialchars($username); ?>!</h2>
            <p style="margin: 5px 0 0 0; color: #aaa; font-size: 0.95rem;">You have <span style="color: #00f2fe; font-weight: bold; font-size: 1.1rem;"><?php echo $total_vouchers; ?></span> vouchers waiting for you.</p>
        </div>
        <div style="text-align: right; background: rgba(0,0,0,0.4); padding: 10px 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
            <div style="color: #888; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 1px;">Current Tier</div>
            <div style="color: <?php echo $is_elite ? '#ffd700' : '#00f2fe'; ?>; font-weight: 900; font-size: 1.3rem;">
                <i class="fa-solid <?php echo $is_elite ? 'fa-crown' : 'fa-user'; ?>"></i> <?php echo $is_elite ? 'ELITE STATUS' : strtoupper($current_tier); ?>
            </div>
        </div>
    </div>

    <div class="voucher-section">
        <div class="section-title"><i class="fa-solid fa-crown" style="color: #ffd700;"></i> ELITE Exclusive Vouchers <span></span></div>
        <div class="voucher-grid">
            <?php
            $vip_stmt = $conn->prepare("
                SELECT p.* FROM promo_codes p 
                LEFT JOIN used_vouchers uv ON p.promo_id = uv.promo_id AND uv.customer_id = ? 
                WHERE p.is_vip_only = 1 AND p.status = 'Active' AND uv.promo_id IS NULL 
                ORDER BY p.discount_value DESC
            ");
            $vip_stmt->bind_param("i", $customer_id);
            $vip_stmt->execute();
            $vip_res = $vip_stmt->get_result();
            
            while ($v = $vip_res->fetch_assoc()):
                // 🌟 5. 判断卡片是否锁定，只看是不是 Elite
                $is_locked = !$is_elite;
            ?>
                <div class="voucher-card <?php echo $is_locked ? 'locked' : ''; ?>" style="<?php echo $is_locked ? 'opacity: 0.6; filter: grayscale(0.8);' : 'border-color: rgba(255,215,0,0.3);'; ?>">
                    
                    <div class="card-left <?php echo $is_locked ? 'locked-bg' : 'vip-bg'; ?>">
                        <?php if($v['discount_type'] == 'Fixed'): ?>
                            <span style="font-weight: 900; font-size: 1.3rem;">RM<?php echo floatval($v['discount_value']); ?></span>
                        <?php else: ?>
                            <span style="font-weight: 900; font-size: 1.5rem;"><?php echo floatval($v['discount_value']); ?>%</span>
                        <?php endif; ?>
                        <span style="font-size: 0.7rem; font-weight: bold;">OFF</span>
                    </div>

                    <div class="card-middle">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">
                            <h3 style="color: #fff; margin: 0; font-size: 1.1rem;"><?php echo htmlspecialchars($v['target_category']); ?> Discount</h3>
                            <span style="background: rgba(255,215,0,0.1); color: #ffd700; border: 1px solid rgba(255,215,0,0.3); font-size: 0.6rem; padding: 2px 6px; border-radius: 4px;">ELITE</span>
                        </div>
                        
                        <div class="terms-text">
                            <?php 
                                $terms = [];
                                if ($v['min_spend'] > 0) $terms[] = "Min Spend RM " . floatval($v['min_spend']);
                                if ($v['max_cap'] > 0) $terms[] = "Capped at RM " . floatval($v['max_cap']);
                                echo empty($terms) ? "No minimum spend limit" : implode(' • ', $terms);
                            ?>
                        </div>
                    </div>

                    <div class="card-right">
                        <?php if ($is_locked): ?>
                            <a href="membership.php" style="color: #888; text-decoration: none; font-size: 0.85rem; text-align: center; display: block; width: 100%; border: 1px solid #444; padding: 8px 0; border-radius: 20px; transition: 0.3s;" onmouseover="this.style.borderColor='#ffd700'; this.style.color='#ffd700';">
                                <i class="fa-solid fa-lock"></i> Unlock
                            </a>
                        <?php else: ?>
                            <span style="font-family: monospace; color: #aaa; font-size: 0.75rem; margin-bottom: 8px;">Code: <strong style="color: #fff; font-size: 0.9rem;"><?php echo htmlspecialchars($v['code_name']); ?></strong></span>
                            <button class="copy-btn vip-copy-btn" onclick="copyCode('<?php echo htmlspecialchars($v['code_name']); ?>', this)">Copy Code</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; $vip_stmt->close(); ?>
        </div>
    </div>

    <div class="voucher-section">
        <div class="section-title"><i class="fa-solid fa-earth-americas" style="color: #00f2fe;"></i> Public Vouchers <span></span></div>
        <div class="voucher-grid">
            <?php
            $pub_stmt = $conn->prepare("
                SELECT p.* FROM promo_codes p 
                LEFT JOIN used_vouchers uv ON p.promo_id = uv.promo_id AND uv.customer_id = ? 
                WHERE p.is_vip_only = 0 AND p.status = 'Active' AND uv.promo_id IS NULL 
                ORDER BY p.discount_value DESC
            ");
            $pub_stmt->bind_param("i", $customer_id);
            $pub_stmt->execute();
            $pub_res = $pub_stmt->get_result();
            
            while ($v = $pub_res->fetch_assoc()):
            ?>
                <div class="voucher-card" style="border-color: rgba(0,242,254,0.2);">
                    
                    <div class="card-left public-bg">
                        <?php if($v['discount_type'] == 'Fixed'): ?>
                            <span style="font-weight: 900; font-size: 1.3rem;">RM<?php echo floatval($v['discount_value']); ?></span>
                        <?php else: ?>
                            <span style="font-weight: 900; font-size: 1.5rem;"><?php echo floatval($v['discount_value']); ?>%</span>
                        <?php endif; ?>
                        <span style="font-size: 0.7rem; font-weight: bold;">OFF</span>
                    </div>

                    <div class="card-middle">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">
                            <h3 style="color: #fff; margin: 0; font-size: 1.1rem;"><?php echo htmlspecialchars($v['target_category']); ?> Discount</h3>
                            <span style="background: rgba(0,242,254,0.1); color: #00f2fe; border: 1px solid rgba(0,242,254,0.3); font-size: 0.6rem; padding: 2px 6px; border-radius: 4px;">PUBLIC</span>
                        </div>
                        
                        <div class="terms-text">
                            <?php 
                                $terms = [];
                                if ($v['min_spend'] > 0) $terms[] = "Min Spend RM " . floatval($v['min_spend']);
                                if ($v['max_cap'] > 0) $terms[] = "Capped at RM " . floatval($v['max_cap']);
                                echo empty($terms) ? "No minimum spend limit" : implode(' • ', $terms);
                            ?>
                        </div>
                    </div>

                    <div class="card-right">
                        <span style="font-family: monospace; color: #aaa; font-size: 0.75rem; margin-bottom: 8px;">Code: <strong style="color: #fff; font-size: 0.9rem;"><?php echo htmlspecialchars($v['code_name']); ?></strong></span>
                        <button class="copy-btn" onclick="copyCode('<?php echo htmlspecialchars($v['code_name']); ?>', this)">Copy Code</button>
                    </div>
                </div>
            <?php endwhile; $pub_stmt->close(); ?>
        </div>
    </div>
</main>

<script>
function copyCode(code, btn) {
    navigator.clipboard.writeText(code).then(() => {
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
        setTimeout(() => btn.innerHTML = originalText, 2000);
    });
}
</script>

</body>
</html>