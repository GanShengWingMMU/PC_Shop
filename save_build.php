<?php
ob_start();
session_start();
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$cart = isset($_SESSION['pc_build']) ? $_SESSION['pc_build'] : [];

if (empty($cart)) {
    header("Location: builder.php");
    exit();
}

$total_price = 0;
foreach ($cart as $item) {
    $total_price += $item['price'];
}

$message = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_build'])) {
    $build_name = trim($conn->real_escape_string($_POST['build_name']));
    if (empty($build_name)) { $build_name = "My Custom PC (" . date('M d, Y') . ")"; }
    
    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("INSERT INTO saved_builds (customer_id, build_name, total_price) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $customer_id, $build_name, $total_price);
        $stmt->execute();
        
        $build_id = $conn->insert_id; 
        $stmt->close();

        // 存入 build_items 表
        $stmt_items = $conn->prepare("INSERT INTO build_items (pc_build, product_id, quantity) VALUES (?, ?, 1)");
        foreach ($cart as $cat_id => $item) {
            $pid = $item['product_id'];
            $stmt_items->bind_param("ii", $build_id, $pid);
            $stmt_items->execute();
        }
        $stmt_items->close();

        // ==========================================
        // 🧠 核心黑科技 V2.0：基于花销比例的防偏见追踪
        // ==========================================
        $add_gamer = 0; $add_creator = 0; $add_student = 0;

        $gpu_spend = isset($cart[4]) ? $cart[4]['price'] : 0;
        $cpu_spend = isset($cart[1]) ? $cart[1]['price'] : 0;
        $ram_spend = isset($cart[3]) ? $cart[3]['price'] : 0;

        $gpu_ratio = $total_price > 0 ? ($gpu_spend / $total_price) : 0;
        $cpu_ratio = $total_price > 0 ? ($cpu_spend / $total_price) : 0;

        if ($gpu_ratio >= 0.35) {
            $add_gamer = 5; $add_creator = 1; 
        } elseif ($cpu_ratio >= 0.25 || $ram_spend >= 600) {
            $add_creator = 5; $add_gamer = 2; $add_student = 1;
        } else {
            $add_student = 5; $add_creator = 2; $add_gamer = 1;
        }

        $stmt_dna = $conn->prepare("UPDATE customers SET pref_gamer = pref_gamer + ?, pref_creator = pref_creator + ?, pref_student = pref_student + ? WHERE customer_id = ?");
        $stmt_dna->bind_param("iiii", $add_gamer, $add_creator, $add_student, $customer_id);
        $stmt_dna->execute();
        $stmt_dna->close();
        // ==========================================

        $conn->commit();
        
        $message = "Configuration successfully secured to your armory.";
        $msg_type = "success";
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "System Error: Failed to save blueprint. " . $e->getMessage();
        $msg_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Save Configuration - GridCitY PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* 🌟 全局深空材质与环境光 */
        body { background-color: #030305; color: #fff; position: relative; overflow-x: hidden; font-family: 'Inter', sans-serif; }
        .cyber-grid-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-image: linear-gradient(rgba(0, 242, 254, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 242, 254, 0.03) 1px, transparent 1px); background-size: 40px 40px; z-index: -2; }
        .cyber-glow-bg { position: fixed; top: -10vh; left: 50%; transform: translateX(-50%); width: 80vw; height: 60vh; background: radial-gradient(ellipse at center, rgba(0, 242, 254, 0.1) 0%, transparent 70%); filter: blur(70px); z-index: -1; pointer-events: none; }
        
        /* 🌟 核心表单容器 */
        .tech-auth-card {
            position: relative; background: rgba(10, 10, 15, 0.45); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(0, 242, 254, 0.15); border-radius: 12px; padding: 50px 45px; width: 100%; max-width: 500px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6), inset 0 0 20px rgba(0, 242, 254, 0.05); overflow: hidden; margin: 60px auto;
        }
        /* 顶部扫描线 */
        .tech-auth-card::before { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 1px; background: linear-gradient(90deg, transparent, #00f2fe, transparent); animation: cyber-scan 3s linear infinite; }
        @keyframes cyber-scan { 0% { left: -100%; } 100% { left: 200%; } }

        /* 🌟 黑卡账单区域 (Receipt Box) */
        .receipt-box {
            background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 8px; padding: 25px 25px 30px 25px; margin-bottom: 30px;
        }
        .receipt-header {
            display: flex; justify-content: space-between; color: #64748b; font-family: 'JetBrains Mono', monospace; font-size: 0.75rem;
            letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 25px; padding-bottom: 10px;
        }
        .receipt-item { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.85rem; align-items: baseline; }
        .item-name { color: #e2e8f0; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 70%; }
        .item-price { color: #94a3b8; font-family: 'JetBrains Mono', monospace; }
        
        .receipt-divider { border-top: 1px dashed rgba(255,255,255,0.15); margin: 25px 0 20px 0; }
        
        .receipt-total { display: flex; justify-content: space-between; align-items: baseline; }
        .total-label { font-size: 0.95rem; font-weight: 800; color: #f8fafc; }
        .total-price { font-family: 'JetBrains Mono', monospace; font-size: 1.6rem; font-weight: 800; color: #00f2fe; text-shadow: 0 0 15px rgba(0,242,254,0.3); }

        /* 🌟 输入框与按钮 */
        .tech-label { color: #00f2fe; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; display: block; }
        .tech-input {
            width: 100%; background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff; padding: 14px 16px; border-radius: 8px; font-size: 0.95rem; transition: 0.3s; margin-bottom: 25px;
        }
        .tech-input:focus { outline: none; border-color: #00f2fe; background: rgba(0, 242, 254, 0.03); box-shadow: 0 0 15px rgba(0, 242, 254, 0.15); }
        
        .btn-submit {
            background: #ffffff; color: #000; font-weight: 800; padding: 16px; width: 100%; border-radius: 8px;
            border: none; cursor: pointer; transition: 0.3s; font-size: 1.05rem; box-shadow: 0 4px 15px rgba(255,255,255,0.1);
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(255,255,255,0.25); background: #f8fafc; }
        
        .btn-outline {
            background: transparent; color: #00f2fe; border: 1px solid #00f2fe; font-weight: 700;
            padding: 14px; width: 100%; border-radius: 8px; cursor: pointer; transition: 0.3s; text-align: center; display: block; text-decoration: none; box-sizing: border-box;
        }
        .btn-outline:hover { background: #00f2fe; color: #000; box-shadow: 0 0 20px rgba(0, 242, 254, 0.4); }

        .back-link { display: block; text-align: center; margin-top: 25px; color: #64748b; font-size: 0.9rem; text-decoration: none; transition: 0.3s; font-weight: 500; }
        .back-link:hover { color: #fff; }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>
<div class="cyber-grid-bg"></div>
<div class="cyber-glow-bg"></div>

<main style="display: flex; align-items: center; justify-content: center; min-height: 85vh; padding: 20px;">
    <div class="tech-auth-card">
        
        <div style="text-align: center; margin-bottom: 35px;">
            <h2 style="font-weight: 900; font-size: 2rem; margin: 0 0 8px 0; letter-spacing: -0.5px;">Save Configuration</h2>
            <p style="color: #64748b; font-size: 0.95rem; margin: 0; font-weight: 400;">Secure your build payload into the armory.</p>
        </div>

        <?php if ($message): ?>
            <?php if ($msg_type == 'success'): ?>
                <div style="font-size: 0.85rem; color: #00e676; background: rgba(0, 230, 118, 0.05); padding: 15px; border-radius: 8px; border: 1px solid rgba(0, 230, 118, 0.2); margin-bottom: 30px; text-align: center; line-height: 1.5;">
                    <i class="fas fa-check-circle" style="font-size: 1.5rem; display: block; margin-bottom: 10px;"></i>
                    <?php echo $message; ?>
                </div>
                <div style="display: flex; gap: 15px; flex-direction: column;">
                    <a href="profile.php" class="btn-submit" style="text-align: center; text-decoration: none; box-sizing: border-box;">View Armory</a>
                    <a href="builder.php" class="btn-outline">Keep Building</a>
                </div>
            <?php else: ?>
                <div style="font-family: 'JetBrains Mono', monospace; font-size: 0.8rem; color: #ff4d4d; background: rgba(255, 77, 77, 0.05); padding: 15px; border-radius: 8px; border: 1px solid rgba(255, 77, 77, 0.2); margin-bottom: 30px; text-align: center;">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo strtoupper($message); ?>
                </div>
                <a href="builder.php" class="btn-outline">Return to Builder</a>
            <?php endif; ?>
        <?php else: ?>

            <!-- 🌟 黑卡账单预览区 -->
            <div class="receipt-box">
                <div class="receipt-header">
                    <span>Component List</span>
                    <span><?php echo count($cart); ?> Items</span>
                </div>
                
                <?php foreach ($cart as $item): ?>
                    <div class="receipt-item">
                        <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="item-price">RM <?php echo number_format($item['price'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
                
                <div class="receipt-divider"></div>
                
                <div class="receipt-total">
                    <span class="total-label">Est. Total</span>
                    <span class="total-price">RM <?php echo number_format($total_price, 2); ?></span>
                </div>
            </div>

            <form method="POST" action="">
                <div>
                    <label class="tech-label">Configuration Name</label>
                    <input type="text" name="build_name" class="tech-input" placeholder="e.g., Deep Learning Workstation" required>
                </div>
                <button type="submit" name="save_build" class="btn-submit">Confirm & Save</button>
            </form>
            
            <a href="builder.php" class="back-link"><i class="fas fa-arrow-left" style="margin-right: 5px;"></i> Back to Builder</a>
            
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>