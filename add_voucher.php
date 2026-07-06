<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php"); exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['forge_promo'])) {
    $promo_code = strtoupper(trim($_POST['promo_code']));
    $discount_type = $_POST['discount_type'];
    $discount_value = floatval($_POST['discount_value']);
    $target_category = $_POST['target_category'];
    $min_spend = floatval($_POST['min_spend']);
    $max_cap = floatval($_POST['max_cap'] ?? 0);
    $is_vip_only = isset($_POST['is_vip_only']) ? 1 : 0;
    $status = 'Active';

    if(empty($promo_code) || $discount_value <= 0) {
        $error = "Protocol Code and Discount Value are required.";
    } else {
        $chk = $conn->prepare("SELECT promo_id FROM promo_codes WHERE code_name = ?");
        $chk->bind_param("s", $promo_code);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = "Protocol Code already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO promo_codes (code_name, discount_type, discount_value, target_category, min_spend, max_cap, is_vip_only, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdsddis", $promo_code, $discount_type, $discount_value, $target_category, $min_spend, $max_cap, $is_vip_only, $status);
            
            if ($stmt->execute()) {
                header("Location: manage_vouchers.php?msg=forged");
                exit();
            } else {
                $error = "Database Error: Cannot forge protocol.";
            }
            $stmt->close();
        }
        $chk->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forge Promo Protocol - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        /* 🌟 恢复原生的全网页滚动，匹配第二张图片的布局 */
        html, body { 
            height: auto; 
            min-height: 100vh; 
            margin: 0; 
            overflow-y: auto; 
            background-color: var(--bg-main, #0f0f14); 
        }
        
        .admin-container { display: flex; min-height: 100vh; width: 100%; }
        .admin-sidebar { position: fixed; top: 0; left: 0; height: 100vh; z-index: 100; }
        
        .admin-content { 
            margin-left: 250px; 
            flex: 1; 
            padding: 30px !important; 
            padding-bottom: 120px !important; 
            min-height: 100vh; 
            box-sizing: border-box; 
        }

     
        .forge-form { 
            background: rgba(0,0,0,0.5); 
            padding: 40px; 
            border-radius: 12px; 
            border: 1px solid rgba(255,255,255,0.05); 
            border-top: 3px solid #00f2fe; /* 顶部的赛博朋克青色亮线 */
            display: block; 
            overflow: visible; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .form-control { width: 100%; background: rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 12px; border-radius: 6px; box-sizing: border-box; transition: 0.3s; }
        .form-control:focus { outline: none; border-color: #00f2fe; box-shadow: 0 0 10px rgba(0, 242, 254, 0.2); }
        .form-control::placeholder { color: rgba(255,255,255,0.3); }
        
        .form-label { color: #cbd5e1; font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block; }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'admin_sidebar.php'; ?>
        
        <div class="admin-content">
            <header class="admin-header" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="color: #00f2fe; margin:0;"><i class="fas fa-hammer"></i> Forge Promo Protocol</h2>
                    <p style="color:#888; font-size:12px; margin-top:5px;">Inject new discount algorithms into the central database.</p>
                </div>
                <a href="manage_vouchers.php" style="color: #888; text-decoration:none;">&larr; Back to Database</a>
            </header>

            <?php if($error) echo "<div style='color:#ff4d4d; background:rgba(255,77,77,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(255,77,77,0.3);'>$error</div>"; ?>

            <form method="POST" class="forge-form">
                <div style="margin-bottom: 25px;">
                    <label class="form-label">>_ PROTOCOL CODE NAME</label>
                    <input type="text" name="promo_code" class="form-control" placeholder="e.g. CYBER2026" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                    <div>
                        <label class="form-label">% DISCOUNT TYPE</label>
                        <select name="discount_type" id="discount_type" class="form-control">
                            <option value="Percentage">Percentage (%)</option>
                            <option value="Fixed">Fixed Amount (RM)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label"><i class="fas fa-bolt"></i> DISCOUNT VALUE</label>
                        <input type="number" step="0.01" name="discount_value" class="form-control" placeholder="e.g. 15 for 15%" required>
                    </div>
                </div>

                <div style="margin-bottom: 25px;">
                    <label class="form-label"><i class="fas fa-bullseye"></i> TARGET CATEGORY</label>
                    <select name="target_category" class="form-control">
                        <option value="All">All Products & Packages (Global)</option>
                        <option value="Components">Hardware Components Only</option>
                        <option value="Packages">Master Blueprints Only</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                    <div>
                        <label class="form-label"><i class="fas fa-shopping-basket"></i> MINIMUM SPEND (RM)</label>
                        <input type="number" step="0.01" name="min_spend" class="form-control" value="0.00" required>
                    </div>
                    <div>
                        <label class="form-label"><i class="fas fa-shield-alt"></i> MAXIMUM CAP (RM)</label>
                        <input type="number" step="0.01" name="max_cap" id="max_cap" class="form-control" value="0.00">
                        <div style="font-size: 11px; color: #888; margin-top: 6px;">Set to <strong>0.00</strong> for unlimited. Only applies to Percentage.</div>
                    </div>
                </div>

                <div style="background: rgba(255, 215, 0, 0.05); border: 1px dashed rgba(255, 215, 0, 0.4); padding: 18px; border-radius: 8px; margin-bottom: 30px; display: flex; align-items: center; gap: 12px;">
                    <input type="checkbox" name="is_vip_only" id="is_vip_only" style="width: 22px; height: 22px; accent-color: #ffd700; cursor: pointer;">
                    <label for="is_vip_only" style="color: #ffd700; font-weight: bold; font-size: 14px; cursor: pointer; text-transform: uppercase; line-height: 1.4;">
                        <i class="fas fa-crown"></i> ELITE TIER EXCLUSIVE <br>
                        <span style="font-size:11px; color:#888; font-weight:normal;">If checked, only citizens with VIP rank can deploy this protocol.</span>
                    </label>
                </div>

                <button type="submit" name="forge_promo" style="width: 100%; background: linear-gradient(135deg, #00f2fe, #4facfe); color: #000; border: none; padding: 18px; border-radius: 8px; font-weight: 900; font-size: 16px; cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px;">
                    <i class="fas fa-fire"></i> FORGE PROTOCOL
                </button>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const typeSelect = document.getElementById('discount_type');
            const capInput = document.getElementById('max_cap');

            function toggleMaxCap() {
                if (typeSelect.value === 'Percentage') {
                    capInput.disabled = false;
                    capInput.style.opacity = '1';
                } else {
                    capInput.disabled = true;
                    capInput.value = '0.00';
                    capInput.style.opacity = '0.3';
                }
            }
            typeSelect.addEventListener('change', toggleMaxCap);
            toggleMaxCap();
        });
    </script>
</body>
</html>