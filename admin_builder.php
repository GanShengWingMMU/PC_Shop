<?php 
session_start();
include 'db_connect.php'; 

// ✅ 顶部的聪明保安（你已经改对了）
if (!isset($_SESSION['role']) || (strtolower($_SESSION['role']) !== 'admin' && strtolower($_SESSION['role']) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

// 1. 级联删除逻辑
$dependency_map = [
    1 => [2, 8],    
    2 => [3, 4],    
    4 => [6]        
];

function cascade_remove($cat_id, &$cart, $map) {
    if (isset($map[$cat_id])) {
        foreach ($map[$cat_id] as $child_id) {
            if (isset($cart[$child_id])) {
                unset($cart[$child_id]); 
                cascade_remove($child_id, $cart, $map); 
            }
        }
    }
}

// 2. 动作拦截器
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'remove' && isset($_GET['cat_id'])) {
        $remove_id = intval($_GET['cat_id']);
        cascade_remove($remove_id, $_SESSION['admin_build'], $dependency_map);
        unset($_SESSION['admin_build'][$remove_id]); 
        header("Location: admin_builder.php"); 
        exit();
    }
    if ($_GET['action'] == 'clear') {
        unset($_SESSION['admin_build']); 
        header("Location: admin_builder.php"); 
        exit();
    }
}

if (!isset($_SESSION['admin_build'])) { $_SESSION['admin_build'] = []; }
$cart = $_SESSION['admin_build'];
$total_price = 0; $total_wattage = 0;
foreach ($cart as $p) { $total_price += $p['price']; $total_wattage += $p['wattage']; }

// 3. 智能兼容匹配
$socket_param = "";
if (isset($cart[1])) {
    $cpu_name = strtoupper($cart[1]['name']);
    if (preg_match('/(I3|I5|I7|I9|LGA1700)/', $cpu_name)) $socket_param = "LGA1700";
    elseif (preg_match('/(RYZEN|AM5|AM4)/', $cpu_name)) $socket_param = "AM5";
}

$ram_type_param = "";
if (isset($cart[2])) {
    $mb_name = strtoupper($cart[2]['name']);
    if (strpos($mb_name, 'DDR5') !== false) $ram_type_param = "DDR5";
    elseif (strpos($mb_name, 'DDR4') !== false) $ram_type_param = "DDR4";
}

$rec_psu = ceil(($total_wattage + 100) / 50) * 50;

// 4. 等级与瓶颈计算
$system_tier = "AWAITING PARTS";
$tier_color = "var(--text-muted)";
$bottleneck_warning = "";

if (isset($cart[1]) && isset($cart[2]) && isset($cart[4]) && isset($cart[6])) {
    if ($total_price >= 8000) { $system_tier = "GOD TIER (Enthusiast)"; $tier_color = "var(--accent-danger)"; } 
    elseif ($total_price >= 4000) { $system_tier = "HIGH-END (Pro Gaming)"; $tier_color = "#00e676"; } 
    else { $system_tier = "MAINSTREAM (Entry)"; $tier_color = "var(--accent-blue)"; }

    $cpu_price = $cart[1]['price'];
    $gpu_price = $cart[4]['price'];
    if ($cpu_price > ($gpu_price * 1.5)) { $bottleneck_warning = "⚠️ GPU Bottleneck detected."; } 
    elseif ($gpu_price > ($cpu_price * 2.5)) { $bottleneck_warning = "⚠️ CPU Bottleneck detected."; }
}

// 5. 组装流程结构
$workflow = [
    'Phase 1: Core Foundation' => [
        ['id' => 1, 'name' => 'Processor (CPU)', 'icon' => 'fa-microchip', 'req' => [], 'params' => '', 'desc' => 'The brain of the workstation.'],
        ['id' => 2, 'name' => 'Motherboard', 'icon' => 'fa-chess-board', 'req' => [1], 'params' => "&socket=$socket_param", 'lock_msg' => 'Select a CPU first.', 'desc' => $socket_param ? "Locked to $socket_param platform." : "Awaiting CPU platform..."],
    ],
    'Phase 2: Performance' => [
        ['id' => 4, 'name' => 'Graphics Card (GPU)', 'icon' => 'fa-tv', 'req' => [2], 'params' => '', 'lock_msg' => 'Requires Motherboard.', 'desc' => 'Defines gaming limits.'],
        ['id' => 6, 'name' => 'Power Supply (PSU)', 'icon' => 'fa-plug', 'req' => [4], 'params' => "&min_w=$rec_psu", 'lock_msg' => 'Select GPU for power calculation.', 'desc' => $total_wattage > 0 ? "Recommended min: {$rec_psu}W" : "Awaiting system load..."]
    ],
    'Phase 3: Storage & Aesthetics' => [
        ['id' => 3, 'name' => 'Memory (RAM)', 'icon' => 'fa-memory', 'req' => [2], 'params' => ($ram_type_param ? "&ram_type=$ram_type_param" : ""), 'lock_msg' => 'Requires Motherboard.', 'desc' => $ram_type_param ? "Locked to $ram_type_param memory." : 'Awaiting Motherboard...'],
        ['id' => 5, 'name' => 'Storage (SSD)', 'icon' => 'fa-hdd', 'req' => [], 'params' => '', 'lock_msg' => '', 'desc' => 'Ultra-fast NVMe recommended.'],
        ['id' => 8, 'name' => 'Cooling System', 'icon' => 'fa-fan', 'req' => [1], 'params' => '', 'lock_msg' => 'Requires CPU.', 'desc' => 'Keep temperatures low.'],
        ['id' => 7, 'name' => 'PC Case', 'icon' => 'fa-box', 'req' => [], 'params' => '', 'lock_msg' => '', 'desc' => 'The house for the components.']
    ]
];

$flat_slots = [];
foreach($workflow as $s) foreach($s as $item) $flat_slots[] = $item;
$progress = (count($flat_slots) > 0) ? round((count($cart) / count($flat_slots)) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin PC Builder - GridCity PC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lora:wght@400;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

    <div class="sidebar">
        <h2>
            <img src="image/Admin_dashboard_logo.jpg" alt="ROG Logo" class="sidebar-logo">
            <span>GridCity PC</span>
        </h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_products.php">Products</a></li> 
            <li><a href="manage_packages.php">Packages</a></li>
             <li><a href="manage_categories.php">Categories</a></li>
            <li><a href="manage_orders.php">Orders</a></li>
            <li><a href="admin_builder.php" class="active">Build System</a></li> <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'superadmin'): ?>
                <li><a href="manage_staff.php" style="color: var(--accent-warning);"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                <li><a href="manage_users.php">Manage Customers</a></li>
            <?php endif; ?>
            
            <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
        </ul>
    </div>

    <div class="main-content">
        
        <div class="header-top">
            <div>
                <h1>Admin PC Builder Tool</h1>
                <p>Create custom quotations or pre-built systems.</p>
            </div>
            <div>
                <a href="admin_builder.php?action=clear" class="btn-delete" style="padding: 10px 20px; font-size: 14px;"><i class="fas fa-trash"></i> Clear All</a>
            </div>
        </div>

        <div class="content-card" style="padding: 20px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--text-muted); font-weight: bold; letter-spacing: 1px;">
                <span>BUILD PROGRESS</span>
                <span style="color: var(--accent-blue);"><?php echo $progress; ?>%</span>
            </div>
            <div style="background: rgba(255,255,255,0.05); height: 8px; margin-top: 10px; border-radius: 4px; overflow: hidden; border: 1px solid var(--border-color);">
                <div style="width: <?php echo $progress; ?>%; background: linear-gradient(to right, var(--accent-purple), var(--accent-blue)); height: 100%; transition: width 0.5s ease; box-shadow: 0 0 10px rgba(0,242,254,0.5);"></div>
            </div>
        </div>

        <?php foreach ($workflow as $phase_name => $slots): ?>
            <div class="phase-title"><?php echo $phase_name; ?></div>
            
            <?php foreach ($slots as $slot): 
                $cid = $slot['id'];
                $is_filled = isset($cart[$cid]);
                $is_locked = false;
                foreach ($slot['req'] as $req_id) {
                    if (!isset($cart[$req_id])) { $is_locked = true; break; }
                }
            ?>
                <div class="slot-card <?php echo $is_filled ? 'slot-filled' : ''; ?> <?php echo $is_locked ? 'slot-locked' : ''; ?>">
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <div style="width: 40px; text-align: center; font-size: 1.8rem; color: <?php echo $is_filled ? 'var(--accent-blue)' : 'var(--text-muted)'; ?>;">
                            <i class="fas <?php echo $slot['icon']; ?>"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0 0 4px 0; font-size: 1.1rem; color: var(--text-main);"><?php echo $slot['name']; ?></h3>
                            
                            <?php if ($is_locked): ?>
                                <span class="lock-badge"><i class="fas fa-lock"></i> LOCKED</span>
                                <span style="color: var(--accent-danger); font-size: 0.85rem; margin-left: 8px;"><?php echo $slot['lock_msg']; ?></span>
                            <?php elseif ($is_filled): ?>
                                <div style="color: var(--accent-blue); font-weight: bold; font-size: 0.95rem;"><?php echo htmlspecialchars($cart[$cid]['name']); ?></div>
                                <div style="color: #00e676; font-size: 0.85rem; font-weight: bold; margin-top: 3px;">RM <?php echo number_format($cart[$cid]['price'], 2); ?></div>
                            <?php else: ?>
                                <div style="color: var(--text-muted); font-size: 0.85rem;"><?php echo $slot['desc']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center;">
                        <?php if ($is_locked): ?>
                            <div style="color: var(--text-muted); font-weight: bold; letter-spacing: 1px;">LOCKED</div>
                        <?php elseif ($is_filled): ?>
                            <a href="admin_select_part.php?category_id=<?php echo $cid . $slot['params']; ?>" class="btn-action">CHANGE</a>
                            <a href="admin_builder.php?action=remove&cat_id=<?php echo $cid; ?>" style="color: var(--accent-danger); margin-left: 20px; font-size: 1.3rem; transition: 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--accent-danger)'"><i class="fas fa-trash-alt"></i></a>
                        <?php else: ?>
                            <a href="admin_select_part.php?category_id=<?php echo $cid . $slot['params']; ?>" class="btn-primary">SELECT</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
        
        <div style="height: 100px;"></div> <div class="sticky-footer">
            <div style="display: flex; gap: 40px;">
                <div class="stat-box">
                    <span class="stat-label">SYSTEM TIER</span>
                    <span class="stat-value" style="color: <?php echo $tier_color; ?>; text-shadow: 0 0 10px <?php echo $tier_color; ?>88;"><?php echo $system_tier; ?></span>
                    <?php if($bottleneck_warning): ?>
                        <div style="color: var(--accent-warning); font-size: 0.75rem; font-weight: bold; margin-top: 5px;"><?php echo $bottleneck_warning; ?></div>
                    <?php endif; ?>
                </div>
                <div class="stat-box">
                    <span class="stat-label">ESTIMATED POWER</span>
                    <span class="stat-value"><i class="fas fa-bolt" style="color: var(--accent-warning);"></i> <?php echo $total_wattage; ?> W</span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">TOTAL COST</span>
                    <span class="stat-value" style="color: var(--accent-blue); font-size: 1.6rem;">RM <?php echo number_format($total_price, 2); ?></span>
                </div>
            </div>
            
            <button class="btn-primary" <?php echo ($total_price == 0) ? 'style="opacity:0.3; cursor:not-allowed;" disabled' : ''; ?>>
                <i class="fas fa-file-invoice"></i> Create Quotation
            </button>
        </div>

    </div>
</body>
</html>