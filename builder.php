<?php 
ob_start(); 
session_start();
require_once 'config.php';
include 'includes/header.php'; 

// 🚨 强制清理旧版本格式的脏 Session
if (!empty($_SESSION['pc_build']) && is_array(reset($_SESSION['pc_build']))) {
    $_SESSION['pc_build'] = []; 
}
if (!isset($_SESSION['pc_build'])) { $_SESSION['pc_build'] = []; }

// ==========================================
// 1. 递归级联失效算法 (Cascade Invalidation)
// ==========================================
$dependency_map = [
    1 => [2, 8],    // CPU -> Motherboard, Cooler
    2 => [3, 4],    // Motherboard -> RAM, GPU
    4 => [6],       // GPU -> PSU
    7 => [10]       // PC Case -> Case Fans
];

function cascade_remove($cat_id, &$session_cart, $map) {
    if (isset($map[$cat_id])) {
        foreach ($map[$cat_id] as $child_id) {
            if (isset($session_cart[$child_id])) {
                unset($session_cart[$child_id]); 
                cascade_remove($child_id, $session_cart, $map); 
            }
        }
    }
}

// ==========================================
// 2. 动作拦截器 (Action Interceptor)
// ==========================================
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'remove' && isset($_GET['cat_id'])) {
        $remove_id = intval($_GET['cat_id']);
        cascade_remove($remove_id, $_SESSION['pc_build'], $dependency_map);
        unset($_SESSION['pc_build'][$remove_id]); 
        header("Location: builder.php"); 
        exit();
    }
    if ($_GET['action'] == 'clear') {
        unset($_SESSION['pc_build']); 
        header("Location: builder.php"); 
        exit();
    }
}

// ==========================================
// 🚀 3. 黑匣子恢复引擎 (安全加固版：防篡改与兼容性嗅探)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_backup_ids'])) {
    $backup_ids = json_decode($_POST['restore_backup_ids'], true);
    if (is_array($backup_ids) && !empty($backup_ids)) {
        $_SESSION['pc_build'] = []; 
        $id_list = implode(',', array_map('intval', $backup_ids));
        
        // 抓取所有配件及其兼容性属性
        $res = $conn->query("SELECT product_id, category_id, socket_type, ram_type FROM products WHERE product_id IN ($id_list) AND stock_quantity > 0 ORDER BY category_id ASC");
        
        $temp_build = [];
        $check_socket = "";
        $check_ram = "";
        $conflict_found = false;

        while ($row = $res->fetch_assoc()) {
            $cat_id = $row['category_id'];
            
            // 兼容性防呆检查核心
            if ($cat_id == 1 && !empty($row['socket_type'])) {
                $check_socket = $row['socket_type'];
            }
            if ($cat_id == 2) {
                if (!empty($check_socket) && !empty($row['socket_type']) && $check_socket !== $row['socket_type']) {
                    $conflict_found = true;
                    continue; // 发现主板与CPU不兼容，直接丢弃主板
                }
                if (!empty($row['ram_type'])) $check_ram = $row['ram_type'];
            }
            if ($cat_id == 3) {
                if (!empty($check_ram) && !empty($row['ram_type']) && $check_ram !== $row['ram_type']) {
                    $conflict_found = true;
                    continue; // 发现RAM与主板不兼容，直接丢弃RAM
                }
            }
            $temp_build[$cat_id] = (int)$row['product_id'];
        }
        
        $_SESSION['pc_build'] = $temp_build;

        if ($conflict_found) {
            $_SESSION['error_msg'] = "Session recovered partially. Some incompatible or modified parts were automatically removed for safety.";
        } else {
            $_SESSION['success_msg'] = "Session recovered! Valid blueprint has been restored.";
        }
        header("Location: builder.php");
        exit();
    }
}

// ==========================================
// 💎 4. 核心数据水合引擎 (Hydration Engine) - 后端真理原则
// ==========================================
$cart = []; 
$total_price = 0; 
$total_wattage = 0; 
$psu_wattage = 0; 
$socket_param = "";
$ram_type_param = "";
$stock_issue_detected = false;

if (!empty($_SESSION['pc_build'])) {
    $session_ids = implode(',', array_map('intval', $_SESSION['pc_build']));
    $sql = "SELECT product_id, category_id, product_name, price, tdp_wattage, stock_quantity, socket_type, ram_type, performance_tier FROM products WHERE product_id IN ($session_ids)";
    $res = $conn->query($sql);
    
    while ($row = $res->fetch_assoc()) {
        $cat_id = $row['category_id'];
        
        if ($row['stock_quantity'] <= 0) {
            cascade_remove($cat_id, $_SESSION['pc_build'], $dependency_map);
            unset($_SESSION['pc_build'][$cat_id]);
            $stock_issue_detected = true;
            continue; 
        }

        $cart[$cat_id] = [
            'product_id' => $row['product_id'],
            'name'       => $row['product_name'],
            'price'      => (float)$row['price'],
            'wattage'    => (int)$row['tdp_wattage'],
            'tier'       => (int)$row['performance_tier']
        ];

        $total_price += $row['price']; 
        if ($cat_id == 6) {
            $psu_wattage = $row['tdp_wattage']; 
        } else {
            $total_wattage += $row['tdp_wattage']; 
        }

        // 🛡️ 属性嗅探：完全废弃正则，直接读取物理字段！
        if ($cat_id == 1 && !empty($row['socket_type'])) $socket_param = $row['socket_type'];
        if ($cat_id == 2 && !empty($row['ram_type'])) $ram_type_param = $row['ram_type'];
    }
}

if ($stock_issue_detected) {
    $_SESSION['error_msg'] = "Some items went out of stock and were automatically removed.";
}

if ($total_wattage > 0) $total_wattage += 50; 
$rec_psu = ceil(($total_wattage + 100) / 50) * 50;

// ==========================================
// 3.5 实时库存雷达 (精确拦截)
// ==========================================
$inventory_radar = [];
$stock_check_sql = "SELECT category_id, COUNT(product_id) as available_count FROM products WHERE stock_quantity > 0 AND status = 'Available' GROUP BY category_id";
$stock_res = mysqli_query($conn, $stock_check_sql);
if ($stock_res) {
    while ($row = mysqli_fetch_assoc($stock_res)) {
        $inventory_radar[$row['category_id']] = $row['available_count'];
    }
}

// ==========================================
// 🚀 AI 瓶颈预警与商业 Upsell 引擎
// ==========================================
$system_tier = "AWAITING CORE PARTS";
$tier_color = "#555"; 
$bottleneck_warning = "";
$bottleneck_color = "";

if (isset($cart[1], $cart[2], $cart[4], $cart[6])) {
    if ($total_price >= 8000) { $system_tier = "GOD TIER (Enthusiast)"; $tier_color = "#ff007f"; } 
    elseif ($total_price >= 4000) { $system_tier = "HIGH-END (Pro Gaming)"; $tier_color = "#00e676"; } 
    else { $system_tier = "MAINSTREAM (Entry)"; $tier_color = "#00f2fe"; }
}

if (isset($cart[1]) && isset($cart[4])) {
    $cpu_tier = $cart[1]['tier'] ?? 1;
    $gpu_tier = $cart[4]['tier'] ?? 1;

    if (($gpu_tier - $cpu_tier) >= 3) {
        $bottleneck_color = "#ff4d4d"; 
        $bottleneck_warning = "<strong><i class='fas fa-exclamation-triangle'></i> Severe CPU Bottleneck:</strong><br> Your GPU is heavily throttled by the processor.<br><a href='select_part.php?category_id=1&socket=$socket_param' style='color:#00f2fe; text-decoration:none; display:inline-block; margin-top:8px; font-weight:900;'><i class='fas fa-arrow-up'></i> UPGRADE CPU TO FIX</a>";
    } elseif (($cpu_tier - $gpu_tier) >= 3) {
        $bottleneck_color = "#f97316"; 
        $bottleneck_warning = "<strong><i class='fas fa-info-circle'></i> Unbalanced Build:</strong><br> High-end CPU with a weak Graphics Card. Great for rendering, poor for gaming.<br><a href='select_part.php?category_id=4' style='color:#00f2fe; text-decoration:none; display:inline-block; margin-top:8px; font-weight:900;'><i class='fas fa-arrow-up'></i> UPGRADE GPU TO FIX</a>";
    }
}

// ==========================================
// 5. DAG 有向无环图数据结构 (Workflow Matrix)
// ==========================================
$workflow = [
    'Phase 1: Core Foundation' => [
        ['id' => 1, 'name' => 'Processor (CPU)', 'icon' => 'fa-microchip', 'req' => [], 'params' => '', 'desc' => 'The brain of your workstation.'],
        ['id' => 2, 'name' => 'Motherboard', 'icon' => 'fa-chess-board', 'req' => [1], 'params' => "&socket=$socket_param", 'lock_msg' => 'Select a CPU to unlock compatible boards.', 'desc' => $socket_param ? "Locked to $socket_param platform." : "Awaiting CPU platform..."],
    ],
    'Phase 2: Performance' => [
        ['id' => 4, 'name' => 'Graphics Card (GPU)', 'icon' => 'fa-tv', 'req' => [2], 'params' => '', 'lock_msg' => 'Requires Motherboard foundation.', 'desc' => 'Defines your gaming limits.'],
        ['id' => 6, 'name' => 'Power Supply (PSU)', 'icon' => 'fa-plug', 'req' => [4], 'params' => "&min_w=$rec_psu", 'lock_msg' => 'Select GPU for power calculation.', 'desc' => $total_wattage > 0 ? "Recommended minimum: {$rec_psu}W" : "Awaiting system load..."]
    ],
    'Phase 3: Storage & Aesthetics' => [
        ['id' => 3, 'name' => 'Memory (RAM)', 'icon' => 'fa-memory', 'req' => [2], 'params' => ($ram_type_param ? "&ram_type=$ram_type_param" : ""), 'lock_msg' => 'Requires Motherboard.', 'desc' => $ram_type_param ? "Locked to $ram_type_param memory standard." : 'Awaiting Motherboard platform...'],
        ['id' => 5, 'name' => 'Storage (SSD)', 'icon' => 'fa-hdd', 'req' => [], 'params' => '', 'lock_msg' => '', 'desc' => 'Ultra-fast NVMe recommended.'],
        ['id' => 8, 'name' => 'Cooling System', 'icon' => 'fa-fan', 'req' => [1], 'params' => '', 'lock_msg' => 'Requires CPU for socket fit.', 'desc' => 'Keep your CPU temperatures low.'],
        ['id' => 7, 'name' => 'PC Case', 'icon' => 'fa-box', 'req' => [], 'params' => '', 'lock_msg' => '', 'desc' => 'The house for your components.']
    ],
    'Phase 4: Software & Peripherals' => [
        ['id' => 9, 'name' => 'Operating System', 'icon' => 'fa-windows', 'req' => [], 'params' => '', 'lock_msg' => '', 'desc' => 'Essential for running your PC.'],
        ['id' => 10, 'name' => 'Case Fans', 'icon' => 'fa-dharmachakra', 'req' => [7], 'params' => '', 'lock_msg' => 'Select a PC Case first.', 'desc' => 'Extra airflow and RGB aesthetics.'],
        ['id' => 11, 'name' => 'Monitor', 'icon' => 'fa-desktop', 'req' => [], 'params' => '', 'lock_msg' => '', 'desc' => 'Complete your setup with a display.']
    ]
];

$flat_slots = [];
foreach($workflow as $s) foreach($s as $item) $flat_slots[] = $item;
$progress = (count($flat_slots) > 0) ? round((count($cart) / count($flat_slots)) * 100) : 0;
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/builder.css">

<style>
    :root { --accent: #00f2fe; --dark-card: rgba(255,255,255,0.03); }
    body { background-color: #030305; color: #fff; font-family: 'Inter', sans-serif; margin: 0; padding: 0; overflow-x: hidden; }
    
    .builder-dashboard { max-width: 1400px; margin: 40px auto 80px; padding: 0 20px; display: grid; grid-template-columns: 1fr 360px; gap: 40px; align-items: start; }
    .builder-main-column { display: flex; flex-direction: column; }
    .builder-sidebar-column { position: sticky; top: 100px; background: rgba(10, 10, 10, 0.8); backdrop-filter: blur(20px); border: 1px solid rgba(0, 242, 254, 0.2); border-radius: 16px; padding: 30px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }

    @media (max-width: 1024px) {
        .builder-dashboard { grid-template-columns: 1fr; }
        .builder-sidebar-column { position: static; }
    }

    .phase-title { margin: 40px 0 15px; color: var(--accent); font-weight: 800; letter-spacing: 2px; text-transform: uppercase; font-size: 0.85rem; border-bottom: 1px solid rgba(0,242,254,0.2); padding-bottom: 8px; }
    .slot-card { background: var(--dark-card); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 18px 25px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s; }
    .slot-card:hover { transform: translateY(-3px); }
    .slot-locked { opacity: 0.35; filter: grayscale(1) blur(1px); pointer-events: none; user-select: none; background: rgba(0,0,0,0.2); }
    .slot-filled { border-color: var(--accent) !important; background: rgba(0,242,254,0.04); box-shadow: 0 4px 15px rgba(0,242,254,0.05); }
    
    .btn-action { padding: 8px 20px; border-radius: 6px; font-weight: 700; font-size: 0.85rem; text-decoration: none; transition: 0.3s; cursor: pointer; display: inline-flex; justify-content: center; align-items: center; box-sizing: border-box; }
    .btn-select { background: transparent !important; color: #00f2fe !important; border: 1px solid #00f2fe !important; font-family: 'Inter', sans-serif; }
    .btn-select:hover { background: #00f2fe !important; color: #000 !important; box-shadow: 0 0 15px rgba(0, 242, 254, 0.4) !important; }
    .btn-change { background: rgba(255,255,255,0.03) !important; color: #cbd5e1 !important; border: 1px solid rgba(255,255,255,0.08) !important; font-family: 'Inter', sans-serif; }
    .btn-change:hover { background: rgba(255,255,255,0.08) !important; color: #fff !important; border-color: rgba(255,255,255,0.3) !important; }
    .btn-out-of-stock { background: rgba(239, 68, 68, 0.05); color: #ef4444; border: 1px dashed #ef4444; cursor: not-allowed; user-select: none; }
    .lock-badge { background: #ff4d4d; color: #fff; font-size: 0.7rem; padding: 3px 8px; border-radius: 4px; font-weight: 800; letter-spacing: 1px; font-family: 'JetBrains Mono', monospace;}

    .perf-hub { width: 100%; margin: 40px 0 0; background: rgba(10,10,10,0.8); border: 1px solid rgba(0,242,254,0.2); border-radius: 12px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    .hub-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 20px; }
    .hub-title { font-size: 1.1rem; color: #fff; font-weight: 900; letter-spacing: 1px; display: flex; align-items: center; gap: 10px; }
    .bot-badge { padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; font-family: 'Inter', sans-serif; }
    .persona-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
    .persona-col { background: rgba(255,255,255,0.02); border-radius: 8px; padding: 15px; border: 1px solid rgba(255,255,255,0.05); }
    .p-title { font-size: 0.85rem; color: #888; font-weight: 800; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 1px; display: flex; align-items: center; gap: 8px; }
    .metric-row { margin-bottom: 12px; }
    .metric-label { display: flex; justify-content: space-between; font-size: 0.8rem; color: #cbd5e1; margin-bottom: 4px; font-weight: 600; }
    .metric-bar-bg { width: 100%; height: 5px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden; }
    .metric-bar-fill { height: 100%; border-radius: 3px; transition: 1s cubic-bezier(0.4, 0, 0.2, 1); }

    /* ==============================================================
       🌟 全息透视装机线框图 (Holographic Wireframe) V4.0 精准防切断版 🌟
       ============================================================== */
    .blueprint-wrapper {
        position: relative; width: 100%; height: 320px;
        background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(0, 242, 254, 0.2);
        border-radius: 12px; margin-bottom: 25px; display: flex;
        align-items: center; justify-content: center; overflow: hidden;
        box-shadow: inset 0 0 40px rgba(0,0,0,0.9);
    }
    .blueprint-wrapper::before {
        content: ''; position: absolute; width: 100%; height: 100%;
        background-image: linear-gradient(rgba(0, 242, 254, 0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 242, 254, 0.04) 1px, transparent 1px);
        background-size: 20px 20px; z-index: 0; pointer-events: none;
    }
    
    .bp-canvas { position: relative; width: 280px; height: 280px; z-index: 1; }
    .bp-node, .bp-container { box-sizing: border-box; }

    .bp-node {
        position: absolute; border: 1px solid rgba(255,255,255,0.08); background: rgba(0,0,0,0.6);
        display: flex; flex-direction: column; justify-content: center; align-items: center;
        border-radius: 6px; transition: all 0.4s ease; backdrop-filter: blur(4px);
    }
    .bp-node i { font-size: 1rem; color: #555; transition: 0.4s ease; }
    .bp-node span { font-size: 8px; font-weight: 800; margin-top: 4px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; transition: 0.4s ease; text-align: center; white-space: nowrap; }

    .bp-container {
        position: absolute; border: 1px dashed rgba(255,255,255,0.15); background: rgba(0,0,0,0.1);
        border-radius: 8px; transition: all 0.4s ease;
    }
    .bp-container > .cont-label {
        position: absolute; top: -7px; left: 10px; font-size: 9px; font-weight: 900; color: #777;
        text-transform: uppercase; letter-spacing: 1px; transition: 0.4s ease;
        background: #0b0c10; padding: 0 4px;
    }

    .bp-node.active {
        border-color: var(--bp-color); background: rgba(var(--bp-rgb), 0.15);
        box-shadow: 0 0 12px rgba(var(--bp-rgb), 0.25), inset 0 0 6px rgba(var(--bp-rgb), 0.15);
    }
    .bp-node.active i { color: var(--bp-color); text-shadow: 0 0 10px var(--bp-color); transform: scale(1.1); }
    .bp-node.active span { color: #fff; text-shadow: 0 0 5px var(--bp-color); }

    .bp-container.active {
        border-color: var(--bp-color); background: rgba(var(--bp-rgb), 0.03);
        box-shadow: 0 0 20px rgba(var(--bp-rgb), 0.1) inset;
    }
    .bp-container.active > .cont-label { color: var(--bp-color); text-shadow: 0 0 8px var(--bp-color); }

    .bp-monitor { left: 0px; top: 40px; width: 60px; height: 70px; --bp-color: #facc15; --bp-rgb: 250, 204, 21; }
    .bp-os { left: 0px; top: 130px; width: 60px; height: 50px; --bp-color: #00e676; --bp-rgb: 0, 230, 118; border-radius: 8px; }
    .bp-case { left: 75px; top: 10px; width: 205px; height: 260px; --bp-color: #00f2fe; --bp-rgb: 0, 242, 254; }
    .bp-fans { right: 5px; top: 15px; width: 25px; height: 230px; --bp-color: #00f2fe; --bp-rgb: 0, 242, 254; gap: 15px; }
    .bp-fans i { font-size: 0.9rem; animation: spin 4s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
    .bp-psu { left: 10px; bottom: 10px; width: 145px; height: 45px; --bp-color: #ff007f; --bp-rgb: 255, 0, 127; flex-direction: row; gap: 8px; }
    .bp-mobo { left: 10px; top: 15px; width: 145px; height: 180px; --bp-color: #a855f7; --bp-rgb: 168, 85, 247; }
    .bp-cooler { left: 35px; top: 10px; width: 65px; height: 30px; --bp-color: #00e676; --bp-rgb: 0, 230, 118; flex-direction: row; gap: 5px; }
    .bp-cooler i { font-size: 0.8rem; }
    .bp-cpu { left: 40px; top: 50px; width: 55px; height: 50px; --bp-color: #ff007f; --bp-rgb: 255, 0, 127; }
    .bp-cpu i { font-size: 1.4rem; }
    .bp-ram { left: 105px; top: 15px; width: 30px; height: 85px; --bp-color: #facc15; --bp-rgb: 250, 204, 21; }
    .bp-ssd { left: 5px; top: 50px; width: 25px; height: 50px; --bp-color: #00e676; --bp-rgb: 0, 230, 118; }
    .bp-gpu { left: 5px; top: 120px; width: 130px; height: 45px; --bp-color: #f97316; --bp-rgb: 249, 115, 22; flex-direction: row; gap: 8px;}
    .bp-psu span, .bp-gpu span, .bp-cooler span { margin-top: 0; }
</style>

<div class="builder-dashboard">
    <div class="builder-main-column">
        
        <?php if (isset($_SESSION['error_msg'])): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; line-height: 1.5; font-family: 'Inter', sans-serif;">
                <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div style="background: rgba(0, 230, 118, 0.1); border: 1px solid #00e676; color: #00e676; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; font-family: 'Inter', sans-serif;">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
            </div>
        <?php endif; ?>

        <div>
            <h1 style="font-size: 2.8rem; font-weight: 900; margin: 0; letter-spacing: -1px; color: #fff;">SYSTEM <span style="color:var(--accent); text-shadow: 0 0 20px rgba(0,242,254,0.4);">ARCHITECT</span></h1>
            <p style="color: #888; font-size: 1.1rem; margin-top: 5px;">Smart topological dependency engine & bottleneck AI active.</p>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 25px; font-size: 0.85rem; color: #aaa; font-weight: 600;">
                <span>BUILD PROGRESS</span>
                <div>
                    <?php if ($progress > 0): ?>
                        <a href="builder.php?action=clear" onclick="return confirm('WARNING: This will obliterate your current blueprint. Are you absolute sure?');" style="color: #ff4d4d; text-decoration: none; margin-right: 15px; padding: 5px 10px; border: 1px dashed rgba(255,77,77,0.3); border-radius: 5px; transition: 0.3s;" onmouseover="this.style.background='rgba(255,77,77,0.1)'; this.style.boxShadow='0 0 10px rgba(255,77,77,0.2)';" onmouseout="this.style.background='transparent'; this.style.boxShadow='none';">
                            <i class="fas fa-trash-alt"></i> WIPE LOADOUT
                        </a>
                    <?php endif; ?>
                    <span style="color: var(--accent); font-size: 1.4rem; font-family: 'JetBrains Mono', monospace; text-shadow: 0 0 10px rgba(0,242,254,0.5);"><?php echo $progress; ?>%</span>
                </div>
            </div>
            <div style="background: rgba(255,255,255,0.05); height: 8px; margin-top: 8px; border-radius: 4px; overflow: hidden; border: 1px solid rgba(0,242,254,0.1);">
                <div style="width: <?php echo $progress; ?>%; background: linear-gradient(90deg, #00f2fe, #4facfe); height: 100%; box-shadow: 0 0 15px var(--accent); transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);"></div>
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

                $has_stock = isset($inventory_radar[$cid]) && $inventory_radar[$cid] > 0;
            ?>
                <div class="slot-card <?php echo $is_filled ? 'slot-filled' : ''; ?> <?php echo $is_locked ? 'slot-locked' : ''; ?>">
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <div style="width: 40px; text-align: center; font-size: 1.8rem; color: <?php echo $is_filled ? 'var(--accent)' : '#475569'; ?>;">
                            <i class="fas <?php echo $slot['icon']; ?>"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0 0 4px 0; font-size: 1.1rem; color: #fff; font-weight: 800;"><?php echo $slot['name']; ?></h3>
                            
                            <?php if ($is_locked): ?>
                                <span class="lock-badge"><i class="fas fa-lock"></i> LOCKED</span>
                                <span style="color: #ff4d4d; font-size: 0.8rem; margin-left: 8px;"><?php echo $slot['lock_msg']; ?></span>
                            <?php elseif ($is_filled): ?>
                                <div style="color: var(--accent); font-weight: 700; font-size: 1rem;"><?php echo htmlspecialchars($cart[$cid]['name']); ?></div>
                                <div style="color: #00e676; font-size: 0.85rem; font-weight: 600; margin-top: 3px; font-family: 'JetBrains Mono', monospace;">RM <?php echo number_format($cart[$cid]['price'], 2); ?></div>
                            <?php elseif (!$has_stock): ?>
                                <div style="color: #ef4444; font-size: 0.85rem; font-weight: bold;"><i class="fas fa-times-circle"></i> Currently depleted from database.</div>
                            <?php else: ?>
                                <div style="color: #64748b; font-size: 0.85rem;"><?php echo $slot['desc']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center;">
                        <?php if ($is_locked): ?>
                            <div style="color: #475569; font-weight: 800; letter-spacing: 2px;">LOCKED</div>
                        <?php elseif ($is_filled): ?>
                            <a href="select_part.php?category_id=<?php echo $cid . $slot['params']; ?>" class="btn-action btn-change">REPLACE</a>
                            <a href="builder.php?action=remove&cat_id=<?php echo $cid; ?>" style="color: #ef4444; margin-left: 15px; font-size: 1.2rem; transition: 0.2s;" onmouseover="this.style.color='#fff'; this.style.textShadow='0 0 10px #ef4444';" onmouseout="this.style.color='#ef4444'; this.style.textShadow='none';"><i class="fas fa-times-circle"></i></a>
                        <?php elseif (!$has_stock): ?>
                            <span class="btn-action btn-out-of-stock">NO STOCK</span>
                        <?php else: ?>
                            <a href="select_part.php?category_id=<?php echo $cid . $slot['params']; ?>" class="btn-action btn-select">SELECT <i class="fas fa-crosshairs" style="margin-left: 5px; font-size: 0.75rem;"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <?php if (isset($cart[1]) && isset($cart[4])): 
            $cpu_p = $cart[1]['price'];
            $gpu_p = $cart[4]['price'];
            
            $cpu_index = pow($cpu_p / 3000, 0.6) * 100; 
            $gpu_index = pow($gpu_p / 6000, 0.6) * 100; 

            $fps_cyberpunk = round(30 + ($gpu_index * 0.85) + ($cpu_index * 0.15)); 
            $fps_csgo      = round(120 + ($gpu_index * 1.2) + ($cpu_index * 2.8)); 

            $score_pr = round(($cpu_index * 0.65 + $gpu_index * 0.35) * 10); 
            $score_blender = round(($gpu_index * 0.8 + $cpu_index * 0.2) * 10);  

            $score_code  = round($cpu_index * 0.85 + $gpu_index * 0.15); 
            $score_multi = round(($cpu_index * 0.6 + $gpu_index * 0.4)); 

            $score_obs_4k = round($cpu_index * 0.4 + $gpu_index * 0.6); 
            $score_vtube  = round($cpu_index * 0.7 + $gpu_index * 0.3); 
        ?>
        <div class="perf-hub">
            <div class="hub-header">
                <div class="hub-title"><i class="fas fa-satellite-dish" style="color: var(--accent);"></i> MULTI-SCENARIO AI PREDICTOR</div>
                <div class="bot-badge" style="border: 1px solid <?php echo $bottleneck_color ?: '#00e676'; ?>; color: <?php echo $bottleneck_color ?: '#00e676'; ?>;">
                    <i class="fas <?php echo empty($bottleneck_color) ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i> 
                    <?php echo empty($bottleneck_warning) ? 'OPTIMAL PAIRING: Balanced components.' : strip_tags($bottleneck_warning); ?>
                </div>
            </div>
            
            <div class="persona-grid">
                <div class="persona-col">
                    <div class="p-title" style="color: #00f2fe;"><i class="fas fa-gamepad"></i> Gaming (1440p)</div>
                    <div class="metric-row">
                        <div class="metric-label"><span>Cyberpunk 2077</span> <span style="font-family: 'JetBrains Mono', monospace;"><?php echo $fps_cyberpunk; ?> FPS</span></div>
                        <div class="metric-bar-bg"><div class="metric-bar-fill" style="width: <?php echo min(($fps_cyberpunk/144)*100, 100); ?>%; background: #00f2fe;"></div></div>
                    </div>
                    <div class="metric-row">
                        <div class="metric-label"><span>CS:GO 2</span> <span style="font-family: 'JetBrains Mono', monospace;"><?php echo $fps_csgo; ?> FPS</span></div>
                        <div class="metric-bar-bg"><div class="metric-bar-fill" style="width: <?php echo min(($fps_csgo/360)*100, 100); ?>%; background: #00f2fe;"></div></div>
                    </div>
                </div>

                <div class="persona-col">
                    <div class="p-title" style="color: #ff007f;"><i class="fas fa-palette"></i> Content Creation</div>
                    <div class="metric-row">
                        <div class="metric-label"><span>Premiere Pro</span> <span style="font-family: 'JetBrains Mono', monospace;"><?php echo $score_pr; ?> pts</span></div>
                        <div class="metric-bar-bg"><div class="metric-bar-fill" style="width: <?php echo min(($score_pr/2000)*100, 100); ?>%; background: #ff007f;"></div></div>
                    </div>
                    <div class="metric-row">
                        <div class="metric-label"><span>Blender 3D</span> <span style="font-family: 'JetBrains Mono', monospace;"><?php echo $score_blender; ?> pts</span></div>
                        <div class="metric-bar-bg"><div class="metric-bar-fill" style="width: <?php echo min(($score_blender/2000)*100, 100); ?>%; background: #ff007f;"></div></div>
                    </div>
                </div>

                <div class="persona-col">
                    <div class="p-title" style="color: #facc15;"><i class="fas fa-code"></i> Student / Dev</div>
                    <div class="metric-row">
                        <div class="metric-label"><span>Code Compilation</span> <span style="font-family: 'JetBrains Mono', monospace;"><?php echo $score_code; ?> idx</span></div>
                        <div class="metric-bar-bg"><div class="metric-bar-fill" style="width: <?php echo min(($score_code/150)*100, 100); ?>%; background: #facc15;"></div></div>
                    </div>
                    <div class="metric-row">
                        <div class="metric-label"><span>Multitasking</span> <span style="font-family: 'JetBrains Mono', monospace;"><?php echo $score_multi; ?> idx</span></div>
                        <div class="metric-bar-bg"><div class="metric-bar-fill" style="width: <?php echo min(($score_multi/150)*100, 100); ?>%; background: #facc15;"></div></div>
                    </div>
                </div>

                <div class="persona-col">
                    <div class="p-title" style="color: #a855f7;"><i class="fas fa-broadcast-tower"></i> Streaming (OBS)</div>
                    <div class="metric-row">
                        <div class="metric-label"><span>4K Stream Stability</span> <span style="font-family: 'JetBrains Mono', monospace;"><?php echo $score_obs_4k; ?>%</span></div>
                        <div class="metric-bar-bg"><div class="metric-bar-fill" style="width: <?php echo min(($score_obs_4k/120)*100, 100); ?>%; background: #a855f7;"></div></div>
                    </div>
                    <div class="metric-row">
                        <div class="metric-label"><span>V-Tube / Face Tracking</span> <span style="font-family: 'JetBrains Mono', monospace;"><?php echo $score_vtube; ?> idx</span></div>
                        <div class="metric-bar-bg"><div class="metric-bar-fill" style="width: <?php echo min(($score_vtube/120)*100, 100); ?>%; background: #a855f7;"></div></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div> 
        
    <div class="builder-sidebar-column">
        <h3 style="margin: 0; color: #fff; font-size: 1.2rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 10px;">
            <i class="fas fa-receipt" style="color: var(--accent);"></i> SYSTEM SUMMARY
        </h3>

        <div class="blueprint-wrapper">
            <div class="bp-canvas">
                <div class="bp-node bp-monitor <?= isset($cart[11]) ? 'active' : '' ?>">
                    <i class="fas fa-desktop"></i><span>Monitor</span>
                </div>
                
                <div class="bp-node bp-os <?= isset($cart[9]) ? 'active' : '' ?>">
                    <i class="fab fa-windows"></i><span>System</span>
                </div>
                
                <div class="bp-container bp-case <?= isset($cart[7]) ? 'active' : '' ?>">
                    <span class="cont-label">PC Case</span>
                    
                    <div class="bp-node bp-fans <?= isset($cart[10]) ? 'active' : '' ?>">
                        <i class="fas fa-fan"></i><i class="fas fa-fan"></i><i class="fas fa-fan"></i>
                    </div>
                    
                    <div class="bp-node bp-psu <?= isset($cart[6]) ? 'active' : '' ?>">
                        <i class="fas fa-plug"></i><span>Power Unit</span>
                    </div>
                    
                    <div class="bp-container bp-mobo <?= isset($cart[2]) ? 'active' : '' ?>">
                        <span class="cont-label">Motherboard</span>
                        
                        <div class="bp-node bp-cooler <?= isset($cart[8]) ? 'active' : '' ?>">
                            <i class="fas fa-snowflake"></i><span>Cooler</span>
                        </div>
                        
                        <div class="bp-node bp-cpu <?= isset($cart[1]) ? 'active' : '' ?>">
                            <i class="fas fa-microchip"></i><span>CPU</span>
                        </div>
                        
                        <div class="bp-node bp-ram <?= isset($cart[3]) ? 'active' : '' ?>">
                            <i class="fas fa-memory"></i><span>RAM</span>
                        </div>
                        
                        <div class="bp-node bp-ssd <?= isset($cart[5]) ? 'active' : '' ?>">
                            <i class="fas fa-hdd"></i><span>SSD</span>
                        </div>
                        
                        <div class="bp-node bp-gpu <?= isset($cart[4]) ? 'active' : '' ?>">
                            <i class="fas fa-tv"></i><span>Graphics Card</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div>
                <div style="font-size: 0.8rem; color: #888; text-transform: uppercase; margin-bottom: 5px; font-weight: 800; letter-spacing: 1px;">System Tier</div>
                <div style="font-size: 1.2rem; font-weight: 900; color: <?php echo $tier_color; ?>; text-shadow: 0 0 15px <?php echo $tier_color; ?>88;">
                    <?php echo $system_tier; ?>
                </div>
                
                <?php if($bottleneck_warning): ?>
                    <div style="color: <?php echo $bottleneck_color; ?>; font-size: 0.8rem; font-weight: normal; margin-top: 10px; line-height: 1.5; background: rgba(0,0,0,0.4); padding: 12px; border-radius: 8px; border-left: 4px solid <?php echo $bottleneck_color; ?>;">
                        <?php echo $bottleneck_warning; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <div style="font-size: 0.8rem; color: #888; text-transform: uppercase; margin-bottom: 5px; font-weight: 800; letter-spacing: 1px;">Power / Upgrade Headroom</div>
                <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.5rem; color: #facc15; font-weight: 900; margin-bottom: 5px;"><i class="fas fa-bolt" style="text-shadow: 0 0 10px rgba(251,191,36,0.4);"></i> Load: <?php echo $total_wattage; ?> W</div>
                
                <?php if (isset($cart[6])): ?>
                    <?php if ($psu_wattage < $total_wattage): ?>
                        <div style="color: #ff4d4d; font-size: 0.8rem; line-height: 1.4; background: rgba(255,77,77,0.1); padding: 10px; border-radius: 6px; border: 1px dashed #ff4d4d;">
                            <i class="fas fa-radiation"></i> <strong>CRITICAL:</strong> Your PSU (<?php echo $psu_wattage; ?>W) cannot support this system. PC will shut down under load!
                        </div>
                    <?php elseif ($psu_wattage < ($total_wattage * 1.3)): ?>
                        <div style="color: #f97316; font-size: 0.8rem; line-height: 1.4; background: rgba(249,115,22,0.1); padding: 10px; border-radius: 6px; border: 1px solid #f97316;">
                            <i class="fas fa-battery-half"></i> <strong>LOW HEADROOM:</strong> Only <?php echo round((($psu_wattage - $total_wattage) / $psu_wattage) * 100); ?>% upgrade margin. Consider a larger PSU for future-proofing.
                        </div>
                    <?php else: ?>
                        <div style="color: #00e676; font-size: 0.8rem; line-height: 1.4; background: rgba(0,230,118,0.05); padding: 10px; border-radius: 6px; border: 1px solid rgba(0,230,118,0.3);">
                            <i class="fas fa-battery-full"></i> <strong>SAFE:</strong> <?php echo round((($psu_wattage - $total_wattage) / $psu_wattage) * 100); ?>% capacity remaining. Excellent upgrade headroom.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="color: #64748b; font-size: 0.8rem;"><i class="fas fa-plug"></i> Select a Power Supply to calculate headroom.</div>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 5px; padding-top: 20px; border-top: 1px dashed rgba(255,255,255,0.1);">
                <div style="font-size: 0.8rem; color: #888; text-transform: uppercase; margin-bottom: 5px; font-weight: 800; letter-spacing: 1px;">Raw Component Value</div>
                <div style="font-family: 'JetBrains Mono', monospace; font-size: 2.2rem; color: var(--accent); font-weight: 900; text-shadow: 0 0 20px rgba(0,242,254,0.3);">RM <?php echo number_format($total_price, 2); ?></div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 10px;">
                <?php if ($progress == 100): ?>
                    <button type="button" onclick="openProcessModal('checkout')" class="btn-action btn-select" style="text-align: center; font-size: 1.1rem; padding: 15px; width: 100%; box-sizing: border-box; border: none; cursor: pointer;">
                        CHECKOUT <i class="fas fa-shopping-cart" style="margin-left: 8px;"></i>
                    </button>
                <?php else: ?>
                    <span class="btn-action" style="background: rgba(255,255,255,0.05); color: #64748b; cursor: not-allowed; padding: 15px; border: 1px dashed rgba(255,255,255,0.1); text-align: center; width: 100%; box-sizing: border-box;">
                        Complete Build to Checkout
                    </span>
                <?php endif; ?>
                
                <?php if ($progress > 0): ?>
                    <button type="button" onclick="openProcessModal('save')" class="btn-action btn-change" style="text-align: center; padding: 12px; width: 100%; box-sizing: border-box; border: 1px solid rgba(255,255,255,0.08); cursor: pointer; background: transparent;">
                        <i class="fas fa-save" style="margin-right: 8px;"></i> Save Draft
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="processBuildModal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); backdrop-filter: blur(8px);">
    <div style="background: rgba(10, 10, 15, 0.95); margin: 10% auto; padding: 0; width: 90%; max-width: 480px; border-radius: 12px; border: 1px solid #00f2fe; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.7), inset 0 0 20px rgba(0, 242, 254, 0.05); transform: translateY(-20px); animation: modalSlideIn 0.3s forwards;">
        
        <div style="padding: 20px 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
            <h3 style="margin: 0; color: #fff; font-weight: 900; letter-spacing: -0.5px;" id="processModalTitle"><i class="fa-solid fa-server" style="color: #00f2fe;"></i> Process Configuration</h3>
            <span onclick="closeProcessModal()" style="color: #64748b; cursor: pointer; font-size: 1.5rem; transition: 0.3s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#64748b'">&times;</span>
        </div>

        <div style="padding: 30px 25px;">
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="font-size: 0.85rem; color: #888; text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Total Build Value</div>
                <div style="font-family: 'JetBrains Mono', monospace; font-size: 2rem; color: #00f2fe; font-weight: 900; text-shadow: 0 0 15px rgba(0,242,254,0.3);">RM <?php echo number_format($total_price, 2); ?></div>
            </div>

            <form action="process_build.php" method="POST">
                <input type="hidden" name="process_action" id="processActionInput" value="save">
                
                <div style="margin-bottom: 25px;">
                    <label style="color: #00f2fe; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: block;">Name Your Rig (Optional)</label>
                    <input type="text" name="build_name" placeholder="e.g. Project Midnight, Titan V..." style="width: 100%; background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; padding: 14px; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; transition: 0.3s;" onfocus="this.style.borderColor='#00f2fe'; this.style.boxShadow='0 0 15px rgba(0,242,254,0.1)';" onblur="this.style.borderColor='rgba(255,255,255,0.1)'; this.style.boxShadow='none';">
                </div>

                <button type="submit" id="processSubmitBtn" style="background: #ffffff; color: #000; font-weight: 800; padding: 15px; width: 100%; border-radius: 8px; border: none; cursor: pointer; transition: 0.3s; font-size: 1.05rem; box-shadow: 0 4px 15px rgba(255,255,255,0.1);">
                    Confirm Action
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    @keyframes modalSlideIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
    function openProcessModal(action) {
        document.getElementById('processBuildModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
        document.getElementById('processActionInput').value = action;
        
        const title = document.getElementById('processModalTitle');
        const btn = document.getElementById('processSubmitBtn');
        
        if (action === 'checkout') {
            title.innerHTML = '<i class="fa-solid fa-cart-arrow-down" style="color: #ffd700;"></i> Checkout Build';
            btn.innerHTML = 'Add to Cart & Checkout <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>';
            btn.style.background = '#ffd700';
            btn.style.color = '#000';
        } else {
            title.innerHTML = '<i class="fa-solid fa-save" style="color: #00e676;"></i> Save Draft';
            btn.innerHTML = 'Secure to Armory <i class="fa-solid fa-shield-halved" style="margin-left: 8px;"></i>';
            btn.style.background = '#00e676';
            btn.style.color = '#000';
        }
    }

    function closeProcessModal() {
        document.getElementById('processBuildModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
</script>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const phpCartCount = <?php echo count($cart); ?>;
    const currentCartIds = <?php echo json_encode(array_values(array_map(function($item) { return $item['product_id']; }, $cart))); ?>;

    if (phpCartCount > 0) {
        localStorage.setItem('gridcity_backup_build', JSON.stringify(currentCartIds));
    }

    if (phpCartCount === 0) {
        const backup = localStorage.getItem('gridcity_backup_build');
        if (backup) {
            const backupIds = JSON.parse(backup);
            if (backupIds && backupIds.length > 0) {
                if (confirm("⚠️ SYSTEM ALERT: We detected an unsaved build from your previous session! Do you want to restore your hard work?")) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'builder.php';
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'restore_backup_ids';
                    input.value = JSON.stringify(backupIds);
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                } else {
                    localStorage.removeItem('gridcity_backup_build'); 
                }
            }
        }
    }
    
    const clearBtn = document.querySelector('a[href="builder.php?action=clear"]');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            localStorage.removeItem('gridcity_backup_build');
        });
    }
});
</script>
</body>
</html>