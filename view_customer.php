<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

// 🌟 权限验证
$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';

if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_dashboard.php");
    exit();
    die("<div style='background:#000; color:#ff4d4d; padding:50px; text-align:center; font-family:monospace;'>ACCESS DENIED: ALPHA REQUIRED.</div>");
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($user_id <= 0) { header("Location: manage_users.php"); exit(); }

// 🌟 1. 获取顾客基础资料
$stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$customer) { header("Location: manage_users.php"); exit(); }

// 🌟 2. 抓取地址簿
$address_data = null;
$addr_query = "SELECT * FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC LIMIT 1"; 
$addr_stmt = $conn->prepare($addr_query);

if ($addr_stmt) {
    $addr_stmt->bind_param("i", $user_id);
    $addr_stmt->execute();
    $result = $addr_stmt->get_result();
    if ($result) {
        $address_data = $result->fetch_assoc();
    }
    $addr_stmt->close();
}

// 🌟 3. 精准抓取电话 (根据真实的数据库列名 phone_number)
$display_phone = '';
if (!empty($address_data['phone_number'])) {
    $display_phone = trim($address_data['phone_number']);
} elseif (!empty($customer['phone_number'])) {
    $display_phone = trim($customer['phone_number']);
}

// 🌟 4. 处理地址显示
$display_address = '';
if (!empty($address_data['full_address'])) {
    $raw_addr = str_replace(array("\r\n", "\n\r", "\r", "<br>", "<br/>"), "\n", $address_data['full_address']);
    $raw_lines = explode("\n", $raw_addr);
    
    $clean_lines = array();
    foreach ($raw_lines as $l) {
        $l = trim($l);
        if ($l !== '') $clean_lines[] = $l;
    }
    
    if (count($clean_lines) > 0) {
        // 兼容极少数带有 "|" 的旧格式数据
        if (strpos($clean_lines[0], '|') !== false) {
            $name_and_phone = array_shift($clean_lines); 
            $np_parts = explode("|", $name_and_phone);
            if (empty($display_phone) && count($np_parts) > 1) {
                $display_phone = trim(end($np_parts)); 
            }
        }
        $display_address = implode("\n", $clean_lines);
    }
}

// 🌟 5. 精准计算年龄 (根据真实的数据库列名 birthday)
$age_display = '<span style="color:#555; font-style:italic; font-weight: normal;">Not provided</span>';
if (!empty($customer['birthday']) && $customer['birthday'] !== '0000-00-00') {
    try {
        $dob = new DateTime($customer['birthday']);
        $today = new DateTime('today');
        $age_display = $dob->diff($today)->y . ' Years Old';
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Profile - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .info-box { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); padding: 20px; border-radius: 8px; }
        .info-label { color: #f39c12; font-size: 12px; text-transform: uppercase; font-weight: bold; margin-bottom: 5px; letter-spacing: 1px; }
        .info-value { color: #fff; font-size: 16px; font-family: 'Inter', sans-serif; word-wrap: break-word; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="admin-container" style="display: flex; min-height: 100vh; width: 100%;">
        <?php include 'admin_sidebar.php'; ?>

        <div class="admin-content" style="flex: 1; padding: 30px; box-sizing: border-box;">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <h2 style="color: #f39c12; margin: 0;"><i class="fas fa-id-card"></i> Customer Intel Profile</h2>
                <a href="manage_users.php" class="btn-action" style="color: #888; border-color: #555; text-decoration:none;">&larr; Return to Database</a>
            </header>

            <div class="form-card" style="max-width: 800px; margin: 0 auto; background: rgba(0,0,0,0.4); padding: 40px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                
                <div style="text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 20px;">
                    <i class="fas fa-user-circle" style="font-size: 60px; color: #f39c12; margin-bottom: 15px;"></i>
                    <h2 style="margin: 0; color: #fff;"><?php echo htmlspecialchars($customer['username']); ?></h2>
                    <p style="margin: 5px 0 0 0; color: #888; font-family: 'JetBrains Mono';">ID: USR-<?php echo $customer['customer_id']; ?></p>
                </div>

                <div class="profile-grid">
                    <div class="info-box">
                        <div class="info-label"><i class="fas fa-envelope"></i> Email Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($customer['email']); ?></div>
                    </div>
                    
                    <div class="info-box">
                        <div class="info-label"><i class="fas fa-phone"></i> Phone Number</div>
                        <div class="info-value" style="color: #00f2fe; font-weight: bold;">
                            <?php echo !empty($display_phone) ? htmlspecialchars($display_phone) : '<span style="color:#555; font-style:italic; font-weight: normal;">Not provided</span>'; ?>
                        </div>
                    </div>

                    <div class="info-box">
                        <div class="info-label"><i class="fas fa-birthday-cake"></i> Age</div>
                        <div class="info-value" style="color: #00e676; font-weight: bold;">
                            <?php echo $age_display; ?>
                        </div>
                    </div>

                    <div class="info-box">
                        <div class="info-label"><i class="fas fa-calendar-alt"></i> Joined Date</div>
                        <div class="info-value">
                            <?php echo (!empty($customer['created_at'])) ? date('d M Y', strtotime($customer['created_at'])) : '<span style="color:#555; font-style:italic;">Unknown</span>'; ?>
                        </div>
                    </div>

                    <div class="info-box" style="grid-column: 1 / -1;">
                        <div class="info-label"><i class="fas fa-map-marker-alt"></i> Delivery Address</div>
                        <div class="info-value">
                            <?php 
                            if (!empty($display_address)) {
                                echo nl2br(htmlspecialchars($display_address));
                            } else {
                                echo '<span style="color:#555; font-style:italic;">No address on file</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>