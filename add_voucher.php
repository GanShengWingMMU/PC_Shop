<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
$admin_id = $_SESSION['admin_id'] ?? 0;
$admin_username = $_SESSION['admin_username'] ?? 'UnknownAdmin';

if (empty($current_role) || strtolower($current_role) !== 'superadmin') {
    echo "<script>alert('ACCESS DENIED: ALPHA CLEARANCE REQUIRED to Forge Protocols.'); window.location.href='manage_vouchers.php';</script>";
    exit();
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_voucher'])) {
    $code_name = strtoupper(trim($_POST['code_name']));
    $discount_type = $_POST['discount_type'];
    $discount_value = floatval($_POST['discount_value']);
    $target_category = $_POST['target_category'];
    $min_spend = floatval($_POST['min_spend']);
    $max_cap = 0.00; 
    $is_vip_only = isset($_POST['is_vip_only']) ? 1 : 0;
    $status = 'Active'; 

    $check = $conn->prepare("SELECT promo_id FROM promo_codes WHERE code_name = ?");
    $check->bind_param("s", $code_name);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error_msg = "OVERRIDE FAILED: The code name '{$code_name}' already exists in the database.";
    } else {
        $stmt = $conn->prepare("INSERT INTO promo_codes (code_name, discount_type, discount_value, target_category, min_spend, max_cap, is_vip_only, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsddis", $code_name, $discount_type, $discount_value, $target_category, $min_spend, $max_cap, $is_vip_only, $status);
        
        if ($stmt->execute()) {
            // 🌟 写入 Log
            $action_msg = "Forged New Promo Code: $code_name";
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $log_stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, username, role, action_event, ip_address) VALUES (?, ?, ?, ?, ?)");
            $log_stmt->bind_param("issss", $admin_id, $admin_username, $current_role, $action_msg, $ip_address);
            $log_stmt->execute();
            $log_stmt->close();

            header("Location: manage_vouchers.php?msg=forged");
            exit();
        } else {
            $error_msg = "SYSTEM ERROR: Failed to inject protocol into database.";
        }
        $stmt->close();
    }
    $check->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forge Promo - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        /* 🌟 强制破除外部 CSS 限制，释放滚动条 */
        html, body {
            height: 100% !important;
            overflow: auto !important; 
        }
        .admin-container {
            height: 100% !important;
            display: flex;
        }
        .admin-content {
            flex-grow: 1;
            height: 100vh !important;
            overflow-y: auto !important; 
            box-sizing: border-box;
            padding-bottom: 100px !important;
        }
        
        .form-panel { background: rgba(0,0,0,0.5); border-radius: 12px; border: 1px solid rgba(0,242,254,0.2); padding: 30px; max-width: 800px; margin: 0 auto; box-shadow: 0 10px 30px rgba(0,0,0,0.8); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full-width { grid-column: span 2; }
        .form-group label { color: #00f2fe; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        
        .cyber-input, .cyber-select { background: rgba(10,10,15,0.8); color: #fff; border: 1px solid rgba(255,255,255,0.1); padding: 12px 15px; border-radius: 6px; font-family: 'JetBrains Mono', monospace; font-size: 14px; outline: none; }
        .cyber-input:focus, .cyber-select:focus { border-color: #00f2fe; box-shadow: 0 0 10px rgba(0,242,254,0.2); }
        .cyber-select option { background: #0b0f19; color: #fff; }
        
        .elite-toggle { display: flex; align-items: center; gap: 15px; background: rgba(255,215,0,0.05); padding: 15px; border-radius: 6px; border: 1px dashed rgba(255,215,0,0.3); cursor: pointer; }
        .elite-toggle input { width: 20px; height: 20px; accent-color: #ffd700; cursor: pointer; }
        .elite-toggle span { color: #ffd700; font-weight: bold; font-size: 14px; }
        
        .btn-forge { background: linear-gradient(135deg, #00f2fe, #4facfe); color: #000; padding: 15px; border: none; border-radius: 6px; font-weight: 900; font-size: 16px; text-transform: uppercase; letter-spacing: 2px; cursor: pointer; width: 100%; display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px; }
        .btn-forge:hover { background: #00f2fe; color: #000; box-shadow: 0 0 15px rgba(0,242,254,0.4); }
    </style>
</head>
<body>
    <div class="admin-container">
       <?php include 'admin_sidebar.php'; ?>

        <div class="admin-content">
            <div style="padding: 30px;">
                <header class="admin-header" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 style="color: #00f2fe; margin:0;"><i class="fas fa-hammer"></i> Forge Promo Protocol</h2>
                        <p style="color:#888; font-size:13px; margin-top:5px;">Inject new discount algorithms into the central database.</p>
                    </div>
                    <a href="manage_vouchers.php" style="color:#64748b; text-decoration:none; font-size:14px; border-bottom:1px dashed #64748b;"><i class="fas fa-arrow-left"></i> Back to Database</a>
                </header>

                <?php if (!empty($error_msg)) echo "<div style='color:#ff4d4d; background:rgba(255,77,77,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(255,77,77,0.3); text-align:center;'><b><i class='fas fa-exclamation-triangle'></i> {$error_msg}</b></div>"; ?>

                <form method="POST" class="form-panel">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label><i class="fas fa-terminal"></i> Protocol Code Name</label>
                            <input type="text" name="code_name" class="cyber-input" placeholder="e.g. CYBER2026" required autocomplete="off" oninput="this.value = this.value.toUpperCase().replace(/\s/g, '')" style="font-size: 18px; color: #00f2fe; font-weight: bold;">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-percentage"></i> Discount Type</label>
                            <select name="discount_type" class="cyber-select" id="disc_type" onchange="updatePlaceholders()" required>
                                <option value="Percentage">Percentage (%)</option>
                                <option value="Fixed">Fixed Amount (RM)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-bolt"></i> Discount Value</label>
                            <input type="number" name="discount_value" id="disc_val" class="cyber-input" placeholder="e.g. 15 for 15%" step="0.01" min="0.01" required>
                        </div>

                        <div class="form-group full-width">
                            <label><i class="fas fa-crosshairs"></i> Target Category</label>
                            <select name="target_category" class="cyber-select" required>
                                <option value="All">All Products & Packages (Global)</option>
                                <option value="Components">PC Components Only</option>
                                <option value="Packages">Custom Builds & Packages Only</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label><i class="fas fa-shopping-basket"></i> Minimum Spend (RM)</label>
                            <input type="number" name="min_spend" class="cyber-input" placeholder="0.00 if none" step="0.01" min="0" value="0.00" required>
                        </div>

                        <div class="form-group full-width" style="margin-top: 10px;">
                            <label class="elite-toggle">
                                <input type="checkbox" name="is_vip_only" value="1">
                                <div>
                                    <span><i class="fas fa-crown"></i> ELITE TIER EXCLUSIVE</span>
                                    <div style="color: #888; font-size: 11px; font-weight: normal; margin-top: 3px; font-family: 'Inter', sans-serif;">If checked, only citizens with VIP rank can deploy this protocol.</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" name="add_voucher" class="btn-forge"><i class="fas fa-fire"></i> Forge Protocol</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function updatePlaceholders() {
            const type = document.getElementById('disc_type').value;
            const valInput = document.getElementById('disc_val');
            if (type === 'Percentage') {
                valInput.placeholder = "e.g. 15 for 15%";
            } else {
                valInput.placeholder = "e.g. 50 for RM 50.00";
            }
        }
    </script>
</body>
</html>