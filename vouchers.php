<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// 抓取會員等級
$stmt = $conn->prepare("SELECT membership_tier FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$current_tier = $user_data['membership_tier'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Vouchers - GridCitY</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .voucher-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .page-header { margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 20px; }
        .page-header h1 { color: #fff; font-size: 2rem; margin: 0; }
        
        .voucher-section { margin-bottom: 50px; }
        .section-title { color: #888; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .section-title span { flex: 1; height: 1px; background: #333; }

        /* 票根樣式 */
        .voucher-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(450px, 1fr)); gap: 20px; }
        .voucher-card { display: flex; background: #1a1a1a; border-radius: 12px; overflow: hidden; border: 1px solid #333; transition: 0.3s; position: relative; }
        .voucher-card:hover { border-color: #00f2fe; transform: translateY(-3px); }
        
        .card-left { width: 120px; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 20px; text-align: center; position: relative; border-right: 2px dashed #1a1a1a; }
        .card-left::before, .card-left::after { content: ''; position: absolute; width: 20px; height: 20px; background: #0a0a0a; border-radius: 50%; right: -11px; }
        .card-left::before { top: -10px; }
        .card-left::after { bottom: -10px; }

        /* 不同等級的顏色 */
        .public-bg { background: linear-gradient(135deg, #00f2fe, #4facfe); color: #000; }
        .vip-bg { background: linear-gradient(135deg, #ffd700, #f39c12); color: #000; }
        .locked-bg { background: #333; color: #666; }

        .card-middle { padding: 20px; flex: 1; }
        .card-right { padding: 20px; display: flex; flex-direction: column; justify-content: center; align-items: center; border-left: 1px dashed #444; min-width: 130px; }
        
        .copy-btn { background: rgba(0, 242, 254, 0.1); color: #00f2fe; border: 1px solid #00f2fe; padding: 8px 15px; border-radius: 20px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 0.85rem; }
        .copy-btn:hover { background: #00f2fe; color: #000; }
        .vip-copy-btn { border-color: #ffd700; color: #ffd700; background: rgba(255, 215, 0, 0.1); }
        .vip-copy-btn:hover { background: #ffd700; color: #000; }
    </style>
</head>
<body style="background-color: #0a0a0a;">

<?php include 'includes/header.php'; ?>

<main class="voucher-container">
    <div class="page-header">
        <h1><i class="fa-solid fa-wallet"></i> My Voucher Wallet</h1>
        <p style="color: #888;">Collect and manage your discount codes for better savings.</p>
    </div>

    <div class="voucher-section">
        <div class="section-title"><i class="fa-solid fa-crown" style="color: #ffd700;"></i> ELITE Exclusive Vouchers <span></span></div>
        <div class="voucher-grid">
            <?php
            $vip_stmt = $conn->prepare("SELECT * FROM promo_codes WHERE is_vip_only = 1 AND status = 'Active'");
            $vip_stmt->execute();
            $vip_res = $vip_stmt->get_result();
            
            while ($v = $vip_res->fetch_assoc()):
                $is_locked = ($current_tier !== 'VIP');
            ?>
                <div class="voucher-card" style="<?php echo $is_locked ? 'opacity: 0.6; filter: grayscale(0.8);' : ''; ?>">
                    <div class="card-left <?php echo $is_locked ? 'locked-bg' : 'vip-bg'; ?>">
                        <span style="font-weight: 900; font-size: 1.5rem;"><?php echo $v['discount_percentage']; ?>%</span>
                        <span style="font-size: 0.7rem; font-weight: bold;">OFF</span>
                    </div>
                    <div class="card-middle">
                        <h3 style="color: #fff; margin: 0;"><?php echo $v['target_category']; ?> Discount</h3>
                        <p style="color: #ffd700; font-size: 0.8rem; margin: 5px 0 0 0;">Exclusive for ELITE Members</p>
                    </div>
                    <div class="card-right">
                        <?php if ($is_locked): ?>
                            <a href="membership.php" style="color: #888; text-decoration: none; font-size: 0.8rem; text-align: center;">
                                <i class="fa-solid fa-lock"></i><br>Unlock Now
                            </a>
                        <?php else: ?>
                            <span style="font-family: monospace; color: #888; font-size: 0.8rem; margin-bottom: 8px;">Code: <strong style="color: #fff;"><?php echo $v['code_name']; ?></strong></span>
                            <button class="copy-btn vip-copy-btn" onclick="copyCode('<?php echo $v['code_name']; ?>', this)">Copy</button>
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
            $pub_stmt = $conn->prepare("SELECT * FROM promo_codes WHERE is_vip_only = 0 AND status = 'Active'");
            $pub_stmt->execute();
            $pub_res = $pub_stmt->get_result();
            
            while ($v = $pub_res->fetch_assoc()):
            ?>
                <div class="voucher-card">
                    <div class="card-left public-bg">
                        <span style="font-weight: 900; font-size: 1.5rem;"><?php echo $v['discount_percentage']; ?>%</span>
                        <span style="font-size: 0.7rem; font-weight: bold;">OFF</span>
                    </div>
                    <div class="card-middle">
                        <h3 style="color: #fff; margin: 0;"><?php echo $v['target_category']; ?> Discount</h3>
                        <p style="color: #00f2fe; font-size: 0.8rem; margin: 5px 0 0 0;">Available for All Customers</p>
                    </div>
                    <div class="card-right">
                        <span style="font-family: monospace; color: #888; font-size: 0.8rem; margin-bottom: 8px;">Code: <strong style="color: #fff;"><?php echo $v['code_name']; ?></strong></span>
                        <button class="copy-btn" onclick="copyCode('<?php echo $v['code_name']; ?>', this)">Copy</button>
                    </div>
                </div>
            <?php endwhile; $pub_stmt->close(); ?>
        </div>
    </div>
</main>

<script>
function copyCode(code, btn) {
    navigator.clipboard.writeText(code).then(() => {
        const originalText = btn.innerText;
        btn.innerText = 'Copied!';
        setTimeout(() => btn.innerText = originalText, 2000);
    });
}
</script>

</body>
</html>