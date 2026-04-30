<?php
ob_start(); session_start();
require_once 'config.php';
if (!isset($_SESSION['customer_id'])) { header("Location: login.php"); exit(); }
$customer_id = $_SESSION['customer_id'];
$update_msg = $update_err = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $new_user = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (empty($new_user) || empty($new_email)) { $update_err = "ERR: Core fields cannot be empty."; }
    elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) { $update_err = "ERR: Invalid email format."; }
    else {
        if (!empty($new_pass)) {
            if (strlen($new_pass) < 12 || !preg_match('/[A-Z]/', $new_pass) || !preg_match('/[0-9]/', $new_pass) || !preg_match('/[\W]/', $new_pass)) {
                $update_err = "SYS_POLICY: 12+ chars, uppercase, number, and symbol required.";
            } elseif ($new_pass !== $confirm_pass) { $update_err = "ERR: Passwords mismatch."; }
        }
        if (empty($update_err)) {
            $stmt = $conn->prepare("UPDATE customers SET username=?, email=? " . (!empty($new_pass) ? ", password=?" : "") . " WHERE customer_id=?");
            if (!empty($new_pass)) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt->bind_param("sssi", $new_user, $new_email, $hashed, $customer_id);
            } else { $stmt->bind_param("ssi", $new_user, $new_email, $customer_id); }
            if ($stmt->execute()) { $_SESSION['username'] = $new_user; $update_msg = "SYS: Profile synchronized successfully."; }
        }
    }
}
$user = $conn->query("SELECT * FROM customers WHERE customer_id = $customer_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Command Center - GridCitY PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #030305; color: #fff; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .cyber-grid-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-image: linear-gradient(rgba(0, 242, 254, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 242, 254, 0.03) 1px, transparent 1px); background-size: 40px 40px; z-index: -2; }
        .cyber-glow-bg { position: fixed; top: -10vh; right: -10vw; width: 60vw; height: 60vh; background: radial-gradient(circle, rgba(0, 242, 254, 0.08) 0%, transparent 70%); filter: blur(80px); z-index: -1; pointer-events: none; }
        
        .dashboard-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; position: relative; z-index: 1; }
        
        /* 全局共享科技卡片样式 */
        .tech-auth-card {
            position: relative; background: rgba(10, 10, 15, 0.45); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(0, 242, 254, 0.15); border-radius: 12px; box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6), inset 0 0 20px rgba(0, 242, 254, 0.05);
            overflow: hidden;
        }
        .tech-auth-card::before { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 1px; background: linear-gradient(90deg, transparent, #00f2fe, transparent); animation: cyber-scan 3s linear infinite; }
        
        /* 顶部横幅特供 */
        .identity-banner { padding: 40px 45px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 40px; }
        .user-info-large h1 { font-size: 2.2rem; font-weight: 900; margin: 5px 0 0 0; letter-spacing: -1px; }
        .user-info-large p { color: #00f2fe; margin: 0; font-family: 'JetBrains Mono', monospace; font-size: 0.8rem; letter-spacing: 1px; text-transform: uppercase; }
        .balance-badge { text-align: right; }
        .balance-amount { font-family: 'JetBrains Mono', monospace; font-size: 2.2rem; font-weight: 800; color: #00f2fe; text-shadow: 0 0 20px rgba(0,242,254,0.4); }

        /* 分栏网格布局 */
        .dashboard-grid { display: grid; grid-template-columns: 1fr 400px; gap: 40px; }
        
        /* 表单区域 */
        .settings-panel { padding: 40px; }
        .tech-input-group { margin-bottom: 20px; }
        .tech-label { color: #00f2fe; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: block; }
        .tech-input { width: 100%; background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; padding: 12px 16px; border-radius: 6px; font-size: 0.95rem; transition: 0.3s; font-family: 'Inter', sans-serif; }
        .tech-input:focus { outline: none; border-color: #00f2fe; background: rgba(0, 242, 254, 0.03); box-shadow: 0 0 15px rgba(0, 242, 254, 0.2); }
        .tech-btn { background: transparent; color: #00f2fe; border: 1px solid #00f2fe; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; padding: 14px; width: 100%; border-radius: 6px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .tech-btn:hover { background: #00f2fe; color: #000; box-shadow: 0 0 20px rgba(0, 242, 254, 0.4); }

        .side-panel { display: flex; flex-direction: column; gap: 20px; }
        
        /* 🌟 赛博滚动视窗 (Cyber Scroll Container) */
        .blueprints-scroll-container {
            max-height: 410px; /* 大约容纳3个未展开的卡片 */
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 10px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        /* 自定义科技风滚动条 */
        .blueprints-scroll-container::-webkit-scrollbar { width: 4px; }
        .blueprints-scroll-container::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.02); border-radius: 4px; }
        .blueprints-scroll-container::-webkit-scrollbar-thumb { background: rgba(0, 242, 254, 0.3); border-radius: 4px; transition: 0.3s; }
        .blueprints-scroll-container::-webkit-scrollbar-thumb:hover { background: #00f2fe; box-shadow: 0 0 10px #00f2fe; }

        /* 动态展开卡片核心 CSS */
        .blueprint-card { 
            background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 8px; 
            padding: 20px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column; justify-content: space-between; 
            position: relative; overflow: hidden; flex-shrink: 0; /* 防止挤压变形 */
        }
        .blueprint-card:hover { border-color: #00f2fe; background: rgba(0, 242, 254, 0.03); box-shadow: inset 0 0 20px rgba(0,242,254,0.05); }
        .bp-title { font-weight: 800; font-size: 1rem; margin: 0 0 10px 0; }
        .bp-price { font-family: 'JetBrains Mono', monospace; color: #00f2fe; font-size: 1.1rem; font-weight: 700; }
        
        /* 隐藏的零件详情面板 */
        .bp-details {
            max-height: 0; opacity: 0; overflow: hidden; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 0; border-top: 1px dashed transparent; padding-top: 0;
        }
        .blueprint-card:hover .bp-details {
            max-height: 400px; opacity: 1; margin-top: 15px; padding-top: 15px; border-top: 1px dashed rgba(0, 242, 254, 0.3);
        }
        .bp-part-item { display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 8px; }
        .bp-part-cat { color: #00f2fe; font-family: 'JetBrains Mono', monospace; font-size: 0.7rem; font-weight: bold; }
        .bp-part-name { color: #cbd5e1; text-align: right; width: 65%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .action-link { font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; color: #cbd5e1; text-decoration: none; border: 1px solid rgba(255,255,255,0.1); padding: 8px 12px; border-radius: 4px; transition: 0.3s; text-align: center; }
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
            <p style="font-size: 0.75rem; color: #64748b; font-weight: 800; margin-bottom: 5px; letter-spacing: 1px;">AVAILABLE CREDITS</p>
            <div class="balance-amount">RM <?php echo number_format($user['wallet_balance'], 2); ?></div>
        </div>
    </div>

    <div class="dashboard-grid">
        
        <!-- 左侧设置区域保持不变 -->
        <div class="tech-auth-card settings-panel">
            <h3 style="margin: 0 0 30px 0; font-weight: 900; font-size: 1.4rem;"><i class="fas fa-sliders" style="color: #00f2fe; margin-right: 10px;"></i> System Config</h3>
            
            <?php if($update_msg) echo "<div style='font-family: \"JetBrains Mono\", monospace; font-size: 0.75rem; color: #00e676; background: rgba(0,230,118,0.05); padding: 12px; border: 1px solid rgba(0,230,118,0.3); border-radius: 6px; margin-bottom: 20px;'><i class='fas fa-check'></i> $update_msg</div>"; ?>
            <?php if($update_err) echo "<div style='font-family: \"JetBrains Mono\", monospace; font-size: 0.75rem; color: #ff4d4d; background: rgba(255,77,77,0.05); padding: 12px; border: 1px solid rgba(255,77,77,0.3); border-radius: 6px; margin-bottom: 20px;'><i class='fas fa-exclamation-triangle'></i> $update_err</div>"; ?>

            <form method="POST">
                <div style="display: flex; gap: 20px;">
                    <div class="tech-input-group" style="flex:1;">
                        <label class="tech-label">Identifier</label>
                        <input type="text" name="username" class="tech-input" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="tech-input-group" style="flex:1;">
                        <label class="tech-label">Email Node</label>
                        <input type="email" name="email" class="tech-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>
                
                <div class="tech-input-group">
                    <label class="tech-label">Update Security Key <span style="font-weight: normal; color: #64748b; font-family: 'Inter'; text-transform: none; font-size: 0.7rem;">(Optional)</span></label>
                    <input type="password" name="new_password" class="tech-input" placeholder="Enter new protocol" style="font-family: 'JetBrains Mono', monospace;">
                </div>
                
                <div class="tech-input-group">
                    <label class="tech-label">Confirm Key</label>
                    <input type="password" name="confirm_password" class="tech-input" placeholder="Confirm protocol" style="font-family: 'JetBrains Mono', monospace;">
                </div>
                
                <button type="submit" name="update_profile" class="tech-btn">Synchronize Data</button>
            </form>
        </div>

        <div class="side-panel">
            
            <div class="tech-auth-card" style="padding: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin:0; font-weight: 900; font-size: 1.1rem;"><i class="fas fa-microchip" style="color: #00f2fe; margin-right: 8px;"></i> Saved Blueprints</h3>
                </div>
                
                <!-- 🌟 赛博滚动视窗包装开始 -->
                <div class="blueprints-scroll-container">
                    <?php
                    // 🌟 移除 LIMIT 3，获取所有蓝图记录
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
                            
                            <!-- 悬停展开面板 (Progressive Disclosure) -->
                            <div class="bp-details">
                                <?php
                                $items_sql = "SELECT c.category_name, p.product_name 
                                              FROM build_items bi 
                                              JOIN products p ON bi.product_id = p.product_id 
                                              JOIN categories c ON p.category_id = c.category_id 
                                              WHERE bi.pc_build = $current_build_id";
                                $items_res = $conn->query($items_sql);
                                if ($items_res->num_rows > 0) {
                                    while ($item = $items_res->fetch_assoc()) {
                                        echo '<div class="bp-part-item">';
                                        echo '<span class="bp-part-cat">' . htmlspecialchars($item['category_name']) . '</span>';
                                        echo '<span class="bp-part-name" title="' . htmlspecialchars($item['product_name']) . '">' . htmlspecialchars($item['product_name']) . '</span>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<div style="color: #64748b; font-size: 0.75rem;">No components found.</div>';
                                }
                                ?>
                            </div>

                            <div style="margin-top: 15px; display: flex; gap: 10px;">
                                <a href="load_build.php?id=<?php echo $b['pc_build']; ?>" class="action-link" style="flex: 1; background: rgba(255,255,255,0.05); color:#fff; border-color: rgba(255,255,255,0.2);"><i class="fas fa-download"></i> Load to Builder</a>
                                <a href="delete_build.php?id=<?php echo $b['pc_build']; ?>" class="action-link" style="color: #ff4d4d; border-color: rgba(255,77,77,0.2);"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    <?php endwhile; 
                    else: ?>
                        <p style="color: #64748b; font-size: 0.85rem; text-align: center; padding: 30px; border: 1px dashed rgba(255,255,255,0.1); border-radius: 8px; font-family: 'JetBrains Mono', monospace;">NO BLUEPRINTS FOUND.</p>
                    <?php endif; ?>
                </div>
                <!-- 🌟 赛博滚动视窗包装结束 -->
                
                <a href="builder.php" class="tech-btn" style="display: block; text-align: center; margin-top: 15px; text-decoration: none; padding: 12px; font-size: 0.85rem;">Launch Builder System</a>
            </div>

            <!-- 订单入口卡片 -->
            <div class="tech-auth-card" style="padding: 25px 30px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h3 style="margin: 0 0 5px 0; font-size: 1rem; font-weight: 800;">Order History</h3>
                    <p style="margin: 0; font-size: 0.8rem; color: #64748b;">Track your shipments</p>
                </div>
                <a href="my_orders.php" style="color: #00f2fe; font-size: 1.5rem; transition: 0.3s;"><i class="fas fa-arrow-right-long"></i></a>
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>