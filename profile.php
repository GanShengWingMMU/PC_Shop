<?php
ob_start(); 
session_start();
require_once 'config.php';
if (!isset($_SESSION['customer_id'])) { header("Location: login.php"); exit(); }
$customer_id = $_SESSION['customer_id'];
$update_msg = $update_err = "";
$addr_msg = $addr_err = "";

$open_acc = 'account';

// ==========================================
// 🌟 核心逻辑 1：处理个人资料更新与密码安全验证
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $new_user = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    $new_birthday = trim($_POST['birthday']);

    $raw_phone = trim($_POST['phone_number']);
    if (!empty($raw_phone)) {
        $raw_phone = preg_replace('/^\+?60/', '', $raw_phone); 
        $raw_phone = ltrim($raw_phone, '0'); 
        $new_phone = "+60" . $raw_phone; 
    } else {
        $new_phone = "";
    }

    // 后端严谨校验
    if (empty($new_user) || empty($new_email)) { 
        $update_err = "Core fields (Username/Email) cannot be empty."; 
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $new_user)) {
        $update_err = "Username must be 3-20 characters (letters, numbers, underscore).";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) { 
        $update_err = "Invalid email format."; 
    } elseif (!empty($new_phone) && !preg_match('/^\+60[0-9]{8,10}$/', $new_phone)) {
        $update_err = "Phone number must be a valid Malaysian format (8 to 10 digits after +60).";
    } elseif (!empty($new_birthday) && (strtotime($new_birthday) === false || strtotime($new_birthday) > time())) {
        // 🌟 修复：严谨的生日日期检查，防止写入非法或未来日期
        $update_err = "Invalid birthday date.";
    } else {
        
        if (!empty($new_pass)) {
            $current_pass = $_POST['current_password'] ?? '';
            
            $stmt_pwd = $conn->prepare("SELECT password FROM customers WHERE customer_id = ?");
            $stmt_pwd->bind_param("i", $customer_id);
            $stmt_pwd->execute();
            $curr_hash = $stmt_pwd->get_result()->fetch_assoc()['password'];
            $stmt_pwd->close();

            if (empty($current_pass)) {
                $update_err = "Authentication required: Please enter your Current Password to authorize password change.";
            } elseif (!password_verify($current_pass, $curr_hash)) {
                $update_err = "Authentication failed: Incorrect Current Password.";
            } elseif (strlen($new_pass) < 12 || !preg_match('/[A-Z]/', $new_pass) || !preg_match('/[0-9]/', $new_pass) || !preg_match('/[\W]/', $new_pass)) {
                $update_err = "New password must be at least 12 characters and include uppercase, number, and symbol.";
            } elseif ($new_pass !== $confirm_pass) { 
                $update_err = "New passwords do not match."; 
            }
        }
        
        if (empty($update_err)) {
            $check_stmt = $conn->prepare("SELECT customer_id FROM customers WHERE (email = ? OR username = ?) AND customer_id != ?");
            $check_stmt->bind_param("ssi", $new_email, $new_user, $customer_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $update_err = "The selected Username or Email is already taken by another account.";
            } else {
                $sql = "UPDATE customers SET username=?, email=?, phone_number=?, birthday=? " . (!empty($new_pass) ? ", password=?" : "") . " WHERE customer_id=?";
                $stmt = $conn->prepare($sql);
                
                if (!empty($new_pass)) {
                    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmt->bind_param("sssssi", $new_user, $new_email, $new_phone, $new_birthday, $hashed, $customer_id);
                } else { 
                    $stmt->bind_param("ssssi", $new_user, $new_email, $new_phone, $new_birthday, $customer_id); 
                }
                
                if ($stmt->execute()) { 
                    $_SESSION['username'] = $new_user; 
                    $update_msg = "Profile updated successfully."; 
                } else {
                    $update_err = "Database error. Failed to update profile.";
                }
                $stmt->close();
            }
            $check_stmt->close();
        }
    }
}

// ==========================================
// 🌟 核心逻辑 2：处理地址管理
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_address'])) {
    $open_acc = 'address'; 
    $recipient = htmlspecialchars(trim($_POST['recipient_name']));
    $line1 = htmlspecialchars(trim($_POST['address_line1']));
    $line2 = htmlspecialchars(trim($_POST['address_line2']));
    $city = htmlspecialchars(trim($_POST['city']));
    $state = htmlspecialchars(trim($_POST['state']));
    $postcode = trim($_POST['postcode']);
    
    $raw_addr_phone = trim($_POST['addr_phone']);
    $raw_addr_phone = preg_replace('/^\+?60/', '', $raw_addr_phone);
    $raw_addr_phone = ltrim($raw_addr_phone, '0');
    $phone = "+60" . $raw_addr_phone;
    
    if (!preg_match('/^\+60[0-9]{8,10}$/', $phone)) {
        $addr_err = "Delivery phone must be a valid Malaysian format (8 to 10 digits).";
    } elseif (!preg_match('/^[0-9]{5}$/', $postcode)) {
        // 🌟 修复：严谨的邮政编码后端验证 (大马邮政编码固定5位数字)
        $addr_err = "Postcode must be exactly 5 digits.";
    } else {
        $postcode = htmlspecialchars($postcode);
        $full_addr = $line1 . ($line2 ? ", " . $line2 : "") . ", " . $postcode . " " . $city . ", " . $state;
        
        $stmt = $conn->prepare("INSERT INTO customer_addresses (customer_id, recipient_name, phone_number, address_line1, address_line2, city, state, postcode, full_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssss", $customer_id, $recipient, $phone, $line1, $line2, $city, $state, $postcode, $full_addr);
        if($stmt->execute()) { $addr_msg = "New address added."; }
        else { $addr_err = "Failed to add address."; }
        $stmt->close();
    }
}

if (isset($_GET['del_addr'])) {
    $open_acc = 'address';
    $addr_id = intval($_GET['del_addr']);
    $stmt = $conn->prepare("DELETE FROM customer_addresses WHERE address_id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $addr_id, $customer_id);
    $stmt->execute();
    header("Location: profile.php?tab=address"); exit();
}

if (isset($_GET['set_default'])) {
    $open_acc = 'address';
    $addr_id = intval($_GET['set_default']);
    
    // 🌟 修复：杜绝 SQL 注入隐患，改用 Prepared Statements
    $stmt_reset = $conn->prepare("UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ?");
    $stmt_reset->bind_param("i", $customer_id);
    $stmt_reset->execute();
    $stmt_reset->close();

    $stmt = $conn->prepare("UPDATE customer_addresses SET is_default = 1 WHERE address_id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $addr_id, $customer_id);
    $stmt->execute();
    header("Location: profile.php?tab=address"); exit();
}

if (isset($_GET['tab'])) { $open_acc = $_GET['tab']; }

$user = $conn->query("SELECT * FROM customers WHERE customer_id = $customer_id")->fetch_assoc();
$addresses = $conn->query("SELECT * FROM customer_addresses WHERE customer_id = $customer_id ORDER BY is_default DESC, created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - GridCitY PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        body { background: #030305; color: #fff; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .cyber-grid-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-image: linear-gradient(rgba(0, 242, 254, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 242, 254, 0.03) 1px, transparent 1px); background-size: 40px 40px; z-index: -2; }
        .cyber-glow-bg { position: fixed; top: -10vh; right: -10vw; width: 60vw; height: 60vh; background: radial-gradient(circle, rgba(0, 242, 254, 0.08) 0%, transparent 70%); filter: blur(80px); z-index: -1; pointer-events: none; }
        
        .dashboard-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; position: relative; z-index: 1; }
        
        .tech-auth-card {
            position: relative; background: rgba(10, 10, 15, 0.45); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(0, 242, 254, 0.15); border-radius: 12px; box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6), inset 0 0 20px rgba(0, 242, 254, 0.05);
            overflow: hidden;
        }
        .tech-auth-card::before { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 1px; background: linear-gradient(90deg, transparent, #00f2fe, transparent); animation: cyber-scan 3s linear infinite; }
        
        .identity-banner { padding: 30px 40px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; }
        .user-info-large h1 { font-size: 2rem; font-weight: 900; margin: 5px 0 0 0; letter-spacing: -1px; }
        .user-info-large p { color: #00f2fe; margin: 0; font-family: 'JetBrains Mono', monospace; font-size: 0.8rem; letter-spacing: 1px; text-transform: uppercase; }
        .balance-badge { display: flex; gap: 30px; text-align: right; }
        .bal-item h4 { font-family: 'JetBrains Mono', monospace; font-size: 1.8rem; font-weight: 800; margin: 0; color: #fff;}
        .bal-item.credits h4 { color: #00f2fe; text-shadow: 0 0 20px rgba(0,242,254,0.4); }
        .bal-item.coins h4 { color: #ffd700; text-shadow: 0 0 20px rgba(255,215,0,0.4); }
        .bal-item span { font-size: 0.75rem; color: #64748b; font-weight: 800; letter-spacing: 1px; }

        .dashboard-grid { display: grid; grid-template-columns: 1fr 400px; gap: 40px; }
        @media(max-width: 900px) { .dashboard-grid { grid-template-columns: 1fr; } }
        
        .accordion-item { background: rgba(0,0,0,0.3); border: 1px solid rgba(0, 242, 254, 0.15); border-radius: 8px; margin-bottom: 20px; overflow: hidden; transition: 0.3s; }
        .accordion-item:hover { border-color: rgba(0, 242, 254, 0.4); box-shadow: 0 0 15px rgba(0, 242, 254, 0.1); }
        .accordion-header { padding: 20px 25px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; color: #00f2fe; font-weight: 800; font-size: 1.1rem; background: rgba(0,242,254,0.05); user-select: none; }
        .accordion-header i.chevron { transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .accordion-content { max-height: 0; opacity: 0; overflow: hidden; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); padding: 0 25px; background: rgba(10,10,15,0.6); }
        
        .accordion-item.active { border-color: #00f2fe; box-shadow: 0 0 20px rgba(0, 242, 254, 0.15); }
        .accordion-item.active .accordion-header { background: rgba(0,242,254,0.1); }
        .accordion-item.active .accordion-header i.chevron { transform: rotate(180deg); }
        .accordion-item.active .accordion-content { max-height: 2500px; opacity: 1; padding: 25px; border-top: 1px solid rgba(0, 242, 254, 0.2); }

        .tech-input-group { margin-bottom: 20px; position: relative; }
        .tech-label { color: #94a3b8; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; display: block; }
        .tech-input { width: 100%; background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; padding: 12px 16px; border-radius: 6px; font-size: 0.95rem; transition: 0.3s; font-family: 'Inter', sans-serif; box-sizing: border-box;}
        .tech-input:focus { outline: none; border-color: #00f2fe; background: rgba(0, 242, 254, 0.03); box-shadow: 0 0 15px rgba(0, 242, 254, 0.2); }
        .tech-btn { background: transparent; color: #00f2fe; border: 1px solid #00f2fe; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 12px 20px; border-radius: 6px; cursor: pointer; transition: 0.3s; display: inline-block; text-align: center; text-decoration: none; box-sizing: border-box; }
        .tech-btn:hover { background: #00f2fe; color: #000; box-shadow: 0 0 20px rgba(0, 242, 254, 0.4); }

        /* 🌟 核心修复 1：电话号码专属 Input Group UI */
        .phone-input-group { display: flex; align-items: center; background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 6px; transition: 0.3s; }
        .phone-input-group:focus-within { border-color: #00f2fe; box-shadow: 0 0 15px rgba(0, 242, 254, 0.2); }
        .phone-prefix { padding: 12px 15px; background: rgba(255,255,255,0.05); color: #00f2fe; font-weight: bold; border-right: 1px solid rgba(255, 255, 255, 0.1); font-family: 'JetBrains Mono', monospace; }
        .phone-input-group .tech-input { border: none; background: transparent; box-shadow: none; border-radius: 0; flex: 1; }

        /* 🌟 核心修复 2：将 Date Picker 原生日历图标变成白色赛博朋克风 */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
            opacity: 0.6;
            transition: 0.3s;
        }
        input[type="date"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
        }

        .pwd-checklist { list-style: none; padding: 0; margin: 10px 0 0 0; font-size: 0.75rem; color: #64748b; font-family: 'JetBrains Mono', monospace; display: none; }
        .pwd-checklist li { margin-bottom: 5px; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .pwd-checklist li.valid { color: #00e676; }

        .address-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px; }
        @media(max-width: 600px) { .address-grid { grid-template-columns: 1fr; } }
        .addr-card { background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.08); padding: 20px; border-radius: 8px; position: relative; }
        .addr-card.is-default { border-color: #00f2fe; background: rgba(0, 242, 254, 0.03); }
        .badge-default { position: absolute; top: 15px; right: 15px; background: #00f2fe; color: #000; font-size: 0.65rem; font-weight: bold; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; }

        .side-panel { display: flex; flex-direction: column; gap: 20px; }
        .blueprints-scroll-container { max-height: 480px; overflow-y: auto; overflow-x: hidden; padding-right: 10px; display: flex; flex-direction: column; gap: 15px; }
        .blueprints-scroll-container::-webkit-scrollbar { width: 4px; }
        .blueprints-scroll-container::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.02); border-radius: 4px; }
        .blueprints-scroll-container::-webkit-scrollbar-thumb { background: rgba(0, 242, 254, 0.3); border-radius: 4px; transition: 0.3s; }
        .blueprints-scroll-container::-webkit-scrollbar-thumb:hover { background: #00f2fe; box-shadow: 0 0 10px #00f2fe; }

        .blueprint-card { background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 8px; padding: 20px; transition: all 0.4s; overflow: hidden; flex-shrink: 0; }
        .blueprint-card:hover { border-color: #00f2fe; background: rgba(0, 242, 254, 0.03); box-shadow: inset 0 0 20px rgba(0,242,254,0.05); }
        .bp-title { font-weight: 800; font-size: 1rem; margin: 0 0 5px 0; }
        .bp-price { font-family: 'JetBrains Mono', monospace; color: #00f2fe; font-size: 1.1rem; font-weight: 700; }
        .bp-details { max-height: 0; opacity: 0; overflow: hidden; transition: all 0.4s; margin-top: 0; padding-top: 0; border-top: 1px dashed transparent; }
        .blueprint-card:hover .bp-details { max-height: 400px; opacity: 1; margin-top: 15px; padding-top: 15px; border-top: 1px dashed rgba(0, 242, 254, 0.3); }
        .bp-part-item { display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 8px; }
        .bp-part-cat { color: #00f2fe; font-family: 'JetBrains Mono', monospace; font-size: 0.7rem; font-weight: bold; }
        .bp-part-name { color: #cbd5e1; text-align: right; width: 65%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .action-link { font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; color: #cbd5e1; text-decoration: none; border: 1px solid rgba(255,255,255,0.1); padding: 8px; border-radius: 4px; transition: 0.3s; text-align: center; cursor: pointer; flex: 1; display: flex; align-items: center; justify-content: center; gap: 5px;}
        .action-link:hover { background: #fff; color: #000; border-color: #fff; }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>
<div class="cyber-grid-bg"></div>
<div class="cyber-glow-bg"></div>

<div class="dashboard-container">
    
    <div class="tech-auth-card identity-banner">
        <div class="user-info-large">
            <p><i class="fas fa-satellite-dish"></i> ACTIVE NEURAL LINK</p>
            <h1><?php echo htmlspecialchars($user['username']); ?></h1>
            <div style="font-size: 0.85rem; color: #64748b; margin-top: 5px;">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></div>
        </div>
        <div class="balance-badge">
            <div class="bal-item coins">
                <span>REWARD COINS</span>
                <h4><?php echo number_format($user['reward_coins']); ?></h4>
            </div>
            <div class="bal-item credits">
                <span>WALLET BALANCE</span>
                <h4>RM <?php echo number_format($user['wallet_balance'], 2); ?></h4>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        
        <div class="main-column">
            
            <div class="accordion-item <?php echo $open_acc == 'account' ? 'active' : ''; ?>" id="acc-account">
                <div class="accordion-header" onclick="toggleAccordion('acc-account')">
                    <span><i class="fas fa-user-shield" style="margin-right: 10px;"></i> Account Settings</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="accordion-content">
                    <?php if($update_msg) echo "<div style='font-size: 0.85rem; color: #00e676; background: rgba(0,230,118,0.05); padding: 12px; border: 1px solid rgba(0,230,118,0.3); border-radius: 6px; margin-bottom: 20px;'><i class='fas fa-check'></i> $update_msg</div>"; ?>
                    <?php if($update_err) echo "<div style='font-size: 0.85rem; color: #ff4d4d; background: rgba(255,77,77,0.05); padding: 12px; border: 1px solid rgba(255,77,77,0.3); border-radius: 6px; margin-bottom: 20px;'><i class='fas fa-exclamation-triangle'></i> $update_err</div>"; ?>

                    <form method="POST">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="tech-input-group">
                                <label class="tech-label">Username</label>
                                <input type="text" name="username" class="tech-input" value="<?php echo htmlspecialchars($user['username']); ?>" pattern="[a-zA-Z0-9_]{3,20}" title="3-20 letters, numbers, or underscores" required>
                            </div>
                            <div class="tech-input-group">
                                <label class="tech-label">Email Address</label>
                                <input type="email" name="email" class="tech-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="tech-input-group">
                                <label class="tech-label">Account Phone Number</label>
                                <div class="phone-input-group">
                                    <span class="phone-prefix">+60</span>
                                    <?php 
                                        // 从数据库抓出来时，清洗掉前面多余的 60 或 +60 以及 0，还给用户最干净的纯数字
                                        $display_phone = preg_replace('/^\+?60/', '', $user['phone_number'] ?? ''); 
                                        $display_phone = ltrim($display_phone, '0');
                                    ?>
                                    <input type="tel" name="phone_number" class="tech-input" value="<?php echo htmlspecialchars($display_phone); ?>" pattern="[0-9]{8,10}" maxlength="10" title="Enter 8 to 10 digits (e.g., 123456789)" placeholder="123456789">
                                </div>
                            </div>
                            <div class="tech-input-group">
                                <label class="tech-label">Date of Birth</label>
                                <input type="date" name="birthday" class="tech-input" value="<?php echo htmlspecialchars($user['birthday']); ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <h4 style="color: #cbd5e1; margin-top: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">Security Credentials</h4>
                        <p style="font-size: 0.75rem; color: #64748b; margin-top: 0;">Leave password fields blank if you only want to update the profile details above.</p>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                            <div class="tech-input-group" style="grid-column: span 2;">
                                <label class="tech-label" style="color: #facc15;"><i class="fas fa-lock"></i> Current Password (Required if changing password)</label>
                                <input type="password" name="current_password" class="tech-input" placeholder="Type current password to authorize changes" autocomplete="new-password" style="padding-right: 40px; font-family: 'JetBrains Mono'; border-color: rgba(250, 204, 21, 0.4);">
                                <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 38px; cursor: pointer; color: #64748b; transition: 0.3s;"></i>
                            </div>

                            <div class="tech-input-group">
                                <label class="tech-label">New Password</label>
                                <input type="password" name="new_password" id="new_password" class="tech-input" placeholder="Type new password (Min 12 chars)" autocomplete="new-password" style="padding-right: 40px; font-family: 'JetBrains Mono';">
                                <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 38px; cursor: pointer; color: #64748b; transition: 0.3s;"></i>
                                
                                <ul class="pwd-checklist" id="pwd-checklist">
                                    <li id="req-len"><i class="fas fa-times-circle"></i> 12+ characters</li>
                                    <li id="req-up"><i class="fas fa-times-circle"></i> 1 Uppercase</li>
                                    <li id="req-num"><i class="fas fa-times-circle"></i> 1 Number</li>
                                    <li id="req-sym"><i class="fas fa-times-circle"></i> 1 Symbol</li>
                                </ul>
                            </div>
                            <div class="tech-input-group">
                                <label class="tech-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="tech-input" placeholder="Retype new password" autocomplete="new-password" style="padding-right: 40px; font-family: 'JetBrains Mono';">
                                <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 38px; cursor: pointer; color: #64748b; transition: 0.3s;"></i>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="tech-btn" style="width: auto; margin-top: 15px;">Update Profile</button>
                    </form>
                </div>
            </div>

            <div class="accordion-item <?php echo $open_acc == 'vouchers' ? 'active' : ''; ?>" id="acc-vouchers">
                <div class="accordion-header" onclick="toggleAccordion('acc-vouchers')">
                    <span><i class="fas fa-crown" style="margin-right: 10px; color: #ffd700;"></i> ELITE Status & Vouchers</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="accordion-content">
                    <?php if ($user['membership_tier'] === 'VIP'): ?>
                        <div style="background: linear-gradient(135deg, rgba(255,215,0,0.1) 0%, rgba(10,10,15,0.9) 100%); border: 1px solid rgba(255,215,0,0.4); padding: 30px; border-radius: 16px; position: relative; overflow: hidden;">
                            <h4 style="color: #ffd700; margin: 0 0 12px 0; font-size: 1.5rem; font-weight: 900;"><i class="fa-solid fa-circle-check"></i> ELITE Member</h4>
                            <p style="font-size: 0.95rem; color: #e2e8f0; margin: 0 0 8px 0;">Premium status active. Accessing high-value codes.</p>
                            <p style="font-size: 0.85rem; color: #94a3b8; font-family: 'JetBrains Mono'; margin: 0 0 25px 0;">Valid until: <span style="color: #fff; font-weight: bold;"><?php echo date('d M Y', strtotime($user['vip_expiry_date'])); ?></span></p>
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <a href="vouchers.php" class="tech-btn" style="background: #ffd700; color: #000; border: none; box-shadow: 0 4px 15px rgba(255,215,0,0.3);"><i class="fa-solid fa-ticket"></i> Open Voucher Wallet</a>
                                <a href="membership.php" class="tech-btn" style="border: 1px solid rgba(255,215,0,0.5); color: #ffd700;">Manage Subscription</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="background: rgba(15, 23, 42, 0.6); border: 1px dashed rgba(0, 242, 254, 0.5); padding: 30px; border-radius: 16px;">
                            <h4 style="color: #fff; margin: 0 0 12px 0; font-size: 1.3rem; font-weight: 800;">Standard Member</h4>
                            <p style="font-size: 0.95rem; color: #94a3b8; margin: 0 0 25px 0; line-height: 1.6;">Access <span style="color:#00f2fe; font-weight:bold;">Public Vouchers</span>. Upgrade to <strong style="color:#ffd700;">ELITE</strong> to unlock 25% OFF codes & 500 Coins!</p>
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <a href="membership.php" class="tech-btn" style="background: linear-gradient(135deg, #00f2fe, #4facfe); color: #000; border: none; box-shadow: 0 4px 15px rgba(0,242,254,0.4);"><i class="fa-solid fa-bolt"></i> Upgrade to ELITE</a>
                                <a href="vouchers.php" class="tech-btn" style="color: #cbd5e1; border-color: rgba(255,255,255,0.2);">View Public Vouchers</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="accordion-item <?php echo $open_acc == 'address' ? 'active' : ''; ?>" id="acc-address">
                <div class="accordion-header" onclick="toggleAccordion('acc-address')">
                    <span><i class="fas fa-location-crosshairs" style="margin-right: 10px;"></i> Address Book</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="accordion-content">
                    
                    <?php if($addr_msg) echo "<div style='font-size: 0.85rem; color: #00e676; background: rgba(0,230,118,0.05); padding: 12px; border: 1px solid rgba(0,230,118,0.3); border-radius: 6px; margin-bottom: 20px;'><i class='fas fa-check'></i> $addr_msg</div>"; ?>
                    <?php if($addr_err) echo "<div style='font-size: 0.85rem; color: #ff4d4d; background: rgba(255,77,77,0.05); padding: 12px; border: 1px solid rgba(255,77,77,0.3); border-radius: 6px; margin-bottom: 20px;'><i class='fas fa-exclamation-triangle'></i> $addr_err</div>"; ?>

                    <button onclick="document.getElementById('add-addr-form').style.display='block'" class="tech-btn" style="width: auto; padding: 10px 20px; font-size: 0.8rem; margin-bottom: 20px;"><i class="fas fa-plus"></i> Add New Address</button>

                    <div id="add-addr-form" style="display: none; background: rgba(0,0,0,0.4); border: 1px dashed rgba(0,242,254,0.3); padding: 25px; border-radius: 8px; margin-bottom: 25px;">
                        <form method="POST" action="profile.php">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="tech-input-group" style="margin-bottom:0;"><label class="tech-label">Recipient Name</label><input type="text" name="recipient_name" class="tech-input" required></div>
                                <div class="tech-input-group" style="margin-bottom:0;">
                                    <label class="tech-label">Delivery Phone</label>
                                    <div class="phone-input-group">
                                        <span class="phone-prefix">+60</span>
                                        <input type="tel" name="addr_phone" class="tech-input" value="<?php echo htmlspecialchars($display_phone); ?>" pattern="[0-9]{8,10}" maxlength="10" title="Enter 8 to 10 digits" placeholder="123456789" required>
                                    </div>
                                </div>
                                <div class="tech-input-group" style="grid-column: span 2; margin-bottom:0;"><label class="tech-label">Address Line 1</label><input type="text" name="address_line1" class="tech-input" required></div>
                                <div class="tech-input-group" style="grid-column: span 2; margin-bottom:0;"><label class="tech-label">Address Line 2 (Optional)</label><input type="text" name="address_line2" class="tech-input"></div>
                                <div class="tech-input-group" style="margin-bottom:0;"><label class="tech-label">City</label><input type="text" name="city" class="tech-input" required></div>
                                <div class="tech-input-group" style="margin-bottom:0;"><label class="tech-label">State</label><input type="text" name="state" class="tech-input" required></div>
                                <div class="tech-input-group" style="margin-bottom:0;"><label class="tech-label">Postcode</label><input type="text" name="postcode" class="tech-input" required></div>
                            </div>
                            <div style="margin-top: 20px; display: flex; gap: 10px;">
                                <button type="submit" name="add_address" class="tech-btn" style="width: auto; padding: 10px 25px;">Save Address</button>
                                <button type="button" onclick="document.getElementById('add-addr-form').style.display='none'" class="action-link" style="border:none; flex: none;">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <div class="address-grid">
                        <?php if($addresses->num_rows > 0): while($addr = $addresses->fetch_assoc()): ?>
                            <div class="addr-card <?php echo $addr['is_default'] ? 'is-default' : ''; ?>">
                                <?php if($addr['is_default']) echo '<span class="badge-default">Default</span>'; ?>
                                
                                <h4 style="margin: 0 0 5px 0; color: #fff; font-size: 0.95rem;"><?php echo htmlspecialchars($addr['recipient_name'] ?: $user['username']); ?></h4>
                                <div style="color: #00f2fe; font-family: 'JetBrains Mono'; font-size: 0.8rem; margin-bottom: 15px;">
                                    <i class="fas fa-phone" style="font-size: 0.7rem;"></i> <?php echo htmlspecialchars($addr['phone_number'] ?: 'N/A'); ?>
                                </div>
                                
                                <p style="color: #cbd5e1; font-size: 0.85rem; line-height: 1.5; margin: 0 0 20px 0; min-height: 60px;">
                                    <?php 
                                        if (!empty($addr['address_line1'])) {
                                            echo htmlspecialchars($addr['address_line1']) . "<br>";
                                            echo htmlspecialchars($addr['postcode']) . " " . htmlspecialchars($addr['city']) . ", " . htmlspecialchars($addr['state']);
                                        } else {
                                            echo nl2br(htmlspecialchars($addr['full_address'])); 
                                        }
                                    ?>
                                </p>
                                
                                <div style="display: flex; gap: 8px;">
                                    <?php if(!$addr['is_default']): ?>
                                        <a href="profile.php?set_default=<?php echo $addr['address_id']; ?>" class="action-link">Set Default</a>
                                    <?php endif; ?>
                                    <a href="profile.php?del_addr=<?php echo $addr['address_id']; ?>" onclick="return confirm('Delete this address?')" class="action-link" style="color: #ff4d4d; border-color: rgba(255,77,77,0.2); flex: none; width: 35px;"><i class="fas fa-trash"></i></a>
                                </div>
                            </div>
                        <?php endwhile; else: ?>
                            <p style="grid-column: span 2; color: #64748b; font-style: italic; font-size: 0.85rem;">No addresses registered.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <div class="side-panel">
            
            <div class="tech-auth-card" style="padding: 25px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin:0; font-weight: 900; font-size: 1.1rem;"><i class="fas fa-microchip" style="color: #00f2fe; margin-right: 8px;"></i> Saved Blueprints</h3>
                    <a href="builder.php" style="color: #00f2fe; font-size: 0.85rem; text-decoration: none;"><i class="fas fa-plus"></i> New</a>
                </div>
                
                <div class="blueprints-scroll-container">
                    <?php
                    $builds = $conn->query("SELECT * FROM saved_builds WHERE customer_id = $customer_id ORDER BY created_at DESC");
                    if($builds->num_rows > 0): 
                        while($b = $builds->fetch_assoc()): 
                            $current_build_id = $b['pc_build'];
                    ?>
                        <div class="blueprint-card">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <h4 class="bp-title"><?php echo htmlspecialchars($b['build_name']); ?></h4>
                                    <span class="bp-price">RM <?php echo number_format($b['total_price'], 2); ?></span>
                                </div>
                            </div>
                            
                            <div class="bp-details">
                                <?php
                                $items_sql = "SELECT c.category_name, p.product_name 
                                              FROM build_items bi JOIN products p ON bi.product_id = p.product_id 
                                              JOIN categories c ON p.category_id = c.category_id WHERE bi.pc_build = $current_build_id";
                                $items_res = $conn->query($items_sql);
                                if ($items_res->num_rows > 0) {
                                    while ($item = $items_res->fetch_assoc()) {
                                        echo '<div class="bp-part-item">';
                                        echo '<span class="bp-part-cat">' . htmlspecialchars($item['category_name']) . '</span>';
                                        echo '<span class="bp-part-name" title="' . htmlspecialchars($item['product_name']) . '">' . htmlspecialchars($item['product_name']) . '</span>';
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>

                            <div style="margin-top: 15px; display: flex; gap: 8px;">
                                <a href="load_build.php?id=<?php echo $b['pc_build']; ?>" class="action-link" style="background: rgba(255,255,255,0.05); color:#fff; border-color: rgba(255,255,255,0.2);"><i class="fas fa-download"></i> Load</a>
                                <a href="export_pdf.php?id=<?php echo $b['pc_build']; ?>" target="_blank" class="action-link" style="background: transparent; color: #00f2fe; border-color: rgba(0,242,254,0.3);"><i class="fas fa-file-pdf"></i> PDF</a>
                                <a href="delete_build.php?id=<?php echo $b['pc_build']; ?>" class="action-link" style="color: #ff4d4d; border-color: rgba(255,77,77,0.2); flex: none; width: 35px;"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    <?php endwhile; else: ?>
                        <p style="color: #64748b; font-size: 0.85rem; text-align: center; padding: 30px; border: 1px dashed rgba(255,255,255,0.1); border-radius: 8px; font-family: 'JetBrains Mono', monospace;">NO BLUEPRINTS FOUND.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tech-auth-card" style="padding: 25px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: 0.3s;" onclick="window.location.href='my_orders.php'" onmouseover="this.style.borderColor='#00f2fe'" onmouseout="this.style.borderColor='rgba(0, 242, 254, 0.15)'">
                <div>
                    <h3 style="margin: 0 0 5px 0; font-size: 1.1rem; font-weight: 800;"><i class="fas fa-box-open" style="color: #00f2fe; margin-right: 8px;"></i> Order History</h3>
                    <p style="margin: 0; font-size: 0.8rem; color: #64748b;">Track your shipments</p>
                </div>
                <i class="fas fa-arrow-right-long" style="color: #00f2fe; font-size: 1.2rem;"></i>
            </div>
            
            <a href="logout.php" class="tech-btn" style="color: #ff4d4d; border-color: rgba(255,77,77,0.3);"><i class="fas fa-power-off"></i> Logout</a>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// 1. 手风琴面板切换
function toggleAccordion(id) {
    const items = document.querySelectorAll('.accordion-item');
    items.forEach(item => {
        if(item.id === id) {
            item.classList.toggle('active');
        } else {
            item.classList.remove('active');
        }
    });
}

// 2. 密码眼睛切换 (支持多个密码框)
document.querySelectorAll('.toggle-password').forEach(icon => {
    icon.addEventListener('click', function() {
        const input = this.previousElementSibling;
        if (input.type === 'password') {
            input.type = 'text';
            this.classList.replace('fa-eye', 'fa-eye-slash');
            this.style.color = '#00f2fe';
        } else {
            input.type = 'password';
            this.classList.replace('fa-eye-slash', 'fa-eye');
            this.style.color = '#64748b';
        }
    });
});

// 3. 动态密码安全条件打勾面板
document.getElementById('new_password').addEventListener('input', function() {
    const val = this.value;
    const checklist = document.getElementById('pwd-checklist');
    
    // 输入框有值时显示面板
    if(val.length > 0) { checklist.style.display = 'block'; } 
    else { checklist.style.display = 'none'; return; }

    const reqLen = document.getElementById('req-len');
    const reqUp = document.getElementById('req-up');
    const reqNum = document.getElementById('req-num');
    const reqSym = document.getElementById('req-sym');

    const tick = '<i class="fas fa-check-circle"></i> ';
    const cross = '<i class="fas fa-times-circle"></i> ';

    if(val.length >= 12) { reqLen.className = 'valid'; reqLen.innerHTML = tick + '12+ characters'; }
    else { reqLen.className = ''; reqLen.innerHTML = cross + '12+ characters'; }

    if(/[A-Z]/.test(val)) { reqUp.className = 'valid'; reqUp.innerHTML = tick + '1 Uppercase'; }
    else { reqUp.className = ''; reqUp.innerHTML = cross + '1 Uppercase'; }

    if(/[0-9]/.test(val)) { reqNum.className = 'valid'; reqNum.innerHTML = tick + '1 Number'; }
    else { reqNum.className = ''; reqNum.innerHTML = cross + '1 Number'; }

    if(/[\W]/.test(val)) { reqSym.className = 'valid'; reqSym.innerHTML = tick + '1 Symbol'; }
    else { reqSym.className = ''; reqSym.innerHTML = cross + '1 Symbol'; }
});
</script>

</body>
</html>