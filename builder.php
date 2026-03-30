<?php 
ob_start(); 
session_start();
require_once 'config.php';
include 'includes/header.php'; 

// ==========================================
// 1. 递归级联失效算法 (Cascade Invalidation)
// ==========================================
$dependency_map = [
    1 => [2, 8],    // 删 CPU(1) -> 连带删除 主板(2) 和 散热(8)
    2 => [3, 4],    // 删 主板(2) -> 连带删除 RAM(3) 和 GPU(4)
    4 => [6]        // 删 GPU(4) -> 连带删除 电源(6)
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

// 初始化系统状态
if (!isset($_SESSION['pc_build'])) { $_SESSION['pc_build'] = []; }
$cart = $_SESSION['pc_build'];
$total_price = 0; $total_wattage = 0;
foreach ($cart as $p) { $total_price += $p['price']; $total_wattage += $p['wattage']; }

// ==========================================
// 3. 🧠 动态属性嗅探器 (Transitive Property Sniffer)
// ==========================================
// [第 1 层提取]：从 CPU 提取 Socket
$socket_param = "";
if (isset($cart[1])) {
    $cpu_name = strtoupper($cart[1]['name']);
    if (preg_match('/(I3|I5|I7|I9|LGA1700)/', $cpu_name)) $socket_param = "LGA1700";
    elseif (preg_match('/(RYZEN|AM5|AM4)/', $cpu_name)) $socket_param = "AM5";
}

// 🌟 [第 2 层提取 - 传递性依赖]：从主板提取 RAM 世代
$ram_type_param = "";
if (isset($cart[2])) {
    $mb_name = strtoupper($cart[2]['name']);
    if (strpos($mb_name, 'DDR5') !== false) $ram_type_param = "DDR5";
    elseif (strpos($mb_name, 'DDR4') !== false) $ram_type_param = "DDR4";
}

// 建议电源瓦数
$rec_psu = ceil(($total_wattage + 100) / 50) * 50;

// ==========================================
// 🌟 4. 性能评级与木桶效应算法 (Tier & Bottleneck AI)
// ==========================================
$system_tier = "AWAITING CORE PARTS";
$tier_color = "#555";
$bottleneck_warning = "";

if (isset($cart[1]) && isset($cart[2]) && isset($cart[4]) && isset($cart[6])) {
    if ($total_price >= 8000) {
        $system_tier = "GOD TIER (Enthusiast)";
        $tier_color = "#ff007f"; 
    } elseif ($total_price >= 4000) {
        $system_tier = "HIGH-END (Pro Gaming)";
        $tier_color = "#00e676"; 
    } else {
        $system_tier = "MAINSTREAM (Entry)";
        $tier_color = "#00f2fe"; 
    }

    $cpu_price = $cart[1]['price'];
    $gpu_price = $cart[4]['price'];
    if ($cpu_price > ($gpu_price * 1.5)) {
        $bottleneck_warning = "⚠️ GPU Bottleneck: Your CPU outpaces your Graphics Card.";
    } elseif ($gpu_price > ($cpu_price * 2.5)) {
        $bottleneck_warning = "⚠️ CPU Bottleneck: Your GPU might be held back by the Processor.";
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
    ]
];

$flat_slots = [];
foreach($workflow as $s) foreach($s as $item) $flat_slots[] = $item;
$progress = (count($flat_slots) > 0) ? round((count($cart) / count($flat_slots)) * 100) : 0;
?>

<link rel="stylesheet" href="css/builder.css">
<style>
    :root { --accent: #00f2fe; --dark-card: rgba(255,255,255,0.03); }
    .builder-body { max-width: 1000px; margin: 2rem auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
    
    .phase-title { margin: 40px 0 15px; color: var(--accent); font-weight: 800; letter-spacing: 2px; text-transform: uppercase; font-size: 0.85rem; border-bottom: 1px solid rgba(0,242,254,0.2); padding-bottom: 8px; }
    
    .slot-card { background: var(--dark-card); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 18px 25px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; transition: 0.3s; }
    
    .slot-locked { opacity: 0.35; filter: grayscale(1) blur(1px); pointer-events: none; user-select: none; background: rgba(0,0,0,0.2); }
    .slot-filled { border-color: var(--accent) !important; background: rgba(0,242,254,0.04); box-shadow: 0 4px 15px rgba(0,242,254,0.05); }

    .btn-action { padding: 8px 20px; border-radius: 6px; font-weight: 700; font-size: 0.85rem; text-decoration: none; transition: 0.2s; cursor: pointer; border: 1px solid transparent; }
    .btn-select { background: var(--accent); color: #000; }
    .btn-select:hover { background: #fff; box-shadow: 0 0 10px var(--accent); }
    .btn-change { border-color: var(--accent); color: var(--accent); background: transparent; }
    .btn-change:hover { background: var(--accent); color: #000; }
    .lock-badge { background: #ff4d4d; color: #fff; font-size: 0.7rem; padding: 3px 8px; border-radius: 4px; font-weight: 800; letter-spacing: 1px; }

    .sticky-footer { display: flex; justify-content: space-between; align-items: center; background: rgba(10, 10, 10, 0.95); backdrop-filter: blur(10px); padding: 15px 30px; border-top: 1px solid rgba(255,255,255,0.1); position: sticky; bottom: 0; z-index: 100; box-shadow: 0 -5px 20px rgba(0,0,0,0.5); }
    .summary-stats { display: flex; gap: 30px; }
    .stat-box { display: flex; flex-direction: column; }
    .stat-label { font-size: 0.75rem; color: #888; font-weight: 800; letter-spacing: 1px; margin-bottom: 4px; }
    .stat-value { font-size: 1.2rem; font-weight: 900; color: #fff; }
</style>

<div class="builder-body">
    <div style="margin-bottom: 40px;">
        <h1 style="font-size: 2.8rem; font-weight: 900; margin: 0; letter-spacing: -1px;">SYSTEM <span style="color:var(--accent)">ARCHITECT</span></h1>
        <p style="color: #888; font-size: 1.1rem;">Smart topological dependency engine & bottleneck AI active.</p>
        
        <div style="display: flex; justify-content: space-between; margin-top: 25px; font-size: 0.85rem; color: #aaa; font-weight: 600;">
            <span>BUILD PROGRESS</span>
            <span style="color: var(--accent);"><?php echo $progress; ?>%</span>
        </div>
        <div style="background: rgba(255,255,255,0.1); height: 6px; margin-top: 8px; border-radius: 3px; overflow: hidden;">
            <div style="width: <?php echo $progress; ?>%; background: var(--accent); height: 100%; box-shadow: 0 0 10px var(--accent); transition: width 0.5s ease;"></div>
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
                    <div style="width: 40px; text-align: center; font-size: 1.8rem; color: <?php echo $is_filled ? 'var(--accent)' : '#555'; ?>;">
                        <i class="fas <?php echo $slot['icon']; ?>"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0 0 4px 0; font-size: 1.1rem; color: #fff;"><?php echo $slot['name']; ?></h3>
                        
                        <?php if ($is_locked): ?>
                            <span class="lock-badge"><i class="fas fa-lock"></i> LOCKED</span>
                            <span style="color: #ff4d4d; font-size: 0.8rem; margin-left: 8px;"><?php echo $slot['lock_msg']; ?></span>
                        <?php elseif ($is_filled): ?>
                            <div style="color: var(--accent); font-weight: 700; font-size: 1rem;"><?php echo htmlspecialchars($cart[$cid]['name']); ?></div>
                            <div style="color: #00e676; font-size: 0.85rem; font-weight: 600; margin-top: 3px;">RM <?php echo number_format($cart[$cid]['price'], 2); ?></div>
                        <?php else: ?>
                            <div style="color: #666; font-size: 0.85rem;"><?php echo $slot['desc']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display: flex; align-items: center;">
                    <?php if ($is_locked): ?>
                        <div style="color: #555; font-weight: 800; letter-spacing: 2px;">LOCKED</div>
                    <?php elseif ($is_filled): ?>
                        <a href="select_part.php?category_id=<?php echo $cid . $slot['params']; ?>" class="btn-action btn-change">REPLACE</a>
                        <a href="builder.php?action=remove&cat_id=<?php echo $cid; ?>" style="color: #ff4d4d; margin-left: 15px; font-size: 1.2rem; transition: 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#ff4d4d'"><i class="fas fa-trash-alt"></i></a>
                    <?php else: ?>
                        <a href="select_part.php?category_id=<?php echo $cid . $slot['params']; ?>" class="btn-action btn-select">SELECT <i class="fas fa-plus" style="margin-left: 5px; font-size: 0.75rem;"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>

<div class="sticky-footer">
    <div class="summary-stats">
        <div class="stat-box" style="min-width: 150px;">
            <span class="stat-label">SYSTEM TIER</span>
            <span class="stat-value" style="color: <?php echo $tier_color; ?>; text-shadow: 0 0 10px <?php echo $tier_color; ?>88;">
                <?php echo $system_tier; ?>
            </span>
            <?php if($bottleneck_warning): ?>
                <div style="color: #ffc107; font-size: 0.7rem; font-weight: bold; margin-top: 5px; max-width: 220px; line-height: 1.2;">
                    <?php echo $bottleneck_warning; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="stat-box">
            <span class="stat-label">SYSTEM LOAD</span>
            <span class="stat-value"><i class="fas fa-bolt" style="color:#ffc107"></i> <?php echo $total_wattage; ?> <small>W</small></span>
        </div>
        <div class="stat-box">
            <span class="stat-label">TOTAL PAYABLE</span>
            <span class="stat-value" style="color: var(--accent); font-size: 1.4rem;">RM <?php echo number_format($total_price, 2); ?></span>
        </div>
    </div>
    
    <div style="display: flex; gap: 15px;">
        <button class="btn btn-outline" style="border-color: var(--text-main); color: var(--text-main);">
            <i class="fas fa-save"></i> Save Build
        </button>
<?php if ($total_price > 0): ?>
    <a href="add_build_to_cart.php" class="btn btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
        Checkout <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
    </a>
<?php else: ?>
    <button class="btn btn-primary" disabled style="opacity:0.5; cursor:not-allowed;">
        Checkout <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
    </button>
<?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>