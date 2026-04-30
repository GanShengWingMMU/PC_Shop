<?php 
ob_start(); 
session_start();
require_once 'config.php';
include 'includes/header.php'; 

// ==========================================
// 1. 递归级联失效算法 (Cascade Invalidation)
// ==========================================
$dependency_map = [
    1 => [2, 8],    // CPU -> Motherboard, Cooler
    2 => [3, 4],    // Motherboard -> RAM, GPU
    4 => [6],       // GPU -> PSU
    7 => [10]       // 修复：PC Case (7) 没了，Case Fans (10) 才应该跟着掉！
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

if (!isset($_SESSION['pc_build'])) { $_SESSION['pc_build'] = []; }
$cart = $_SESSION['pc_build'];

$total_price = 0; $total_wattage = 0;
foreach ($cart as $p) { $total_price += $p['price']; $total_wattage += $p['wattage']; }

// ==========================================
// 3. 动态属性嗅探 (Transitive Property Sniffer)
// ==========================================
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

// ==========================================
// 🚀 新增：3.5 实时库存雷达 (Inventory Radar)
// 一次性查出每个分类下还有多少个【有库存】的商品
// ==========================================
$stock_check_sql = "SELECT category_id, COUNT(product_id) as available_count FROM products WHERE stock_quantity > 0 GROUP BY category_id";
$stock_res = mysqli_query($conn, $stock_check_sql);
$inventory_radar = [];
if ($stock_res) {
    while ($row = mysqli_fetch_assoc($stock_res)) {
        $inventory_radar[$row['category_id']] = $row['available_count'];
    }
}

// ==========================================
// 4. 木桶效应与 AI 评级 (Tier & Bottleneck AI)
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

    if ($gpu_price > ($cpu_price * 3.5)) {
        $bottleneck_warning = "⚠️ CPU Bottleneck: Your GPU might be held back by the Processor.";
    } elseif ($cpu_price > ($gpu_price * 2.5)) {
        $bottleneck_warning = "⚠️ GPU Bottleneck: Your CPU outpaces your Graphics Card.";
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

<!-- 🌟 植入 Login 的核心字体 -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/builder.css">

<style>
    :root { --accent: #00f2fe; --dark-card: rgba(255,255,255,0.03); }
    
    /* 🌟 全局对齐 Login 的排版体系 */
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
    
    /* 🌟 修复隐形 BUG：加入 !important 强行覆盖全局样式 */
    .btn-select { background: transparent !important; color: #00f2fe !important; border: 1px solid #00f2fe !important; font-family: 'Inter', sans-serif; }
    .btn-select:hover { background: #00f2fe !important; color: #000 !important; box-shadow: 0 0 15px rgba(0, 242, 254, 0.4) !important; }
    
    .btn-change { background: rgba(255,255,255,0.03) !important; color: #cbd5e1 !important; border: 1px solid rgba(255,255,255,0.08) !important; font-family: 'Inter', sans-serif; }
    .btn-change:hover { background: rgba(255,255,255,0.08) !important; color: #fff !important; border-color: rgba(255,255,255,0.3) !important; }
    
    /* 无库存按钮专用样式 */
    .btn-out-of-stock { background: rgba(239, 68, 68, 0.05); color: #ef4444; border: 1px dashed #ef4444; cursor: not-allowed; user-select: none; }
    
    .lock-badge { background: #ff4d4d; color: #fff; font-size: 0.7rem; padding: 3px 8px; border-radius: 4px; font-weight: 800; letter-spacing: 1px; font-family: 'JetBrains Mono', monospace;}

    /* AI Predictor Styles */
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
                    <!-- 🌟 极客字体用于百分比 -->
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
                                <!-- 🌟 金额应用极客字体 -->
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

            $adv_bottleneck = "";
            $bot_color = "";
            if ($gpu_p > ($cpu_p * 3.5)) {
                $adv_bottleneck = "SEVERE CPU BOTTLENECK: Processor will throttle GPU performance.";
                $bot_color = "#ef4444";
            } elseif ($cpu_p > ($gpu_p * 2.5)) {
                $adv_bottleneck = "GPU BOTTLENECK: Graphics card holding back the system.";
                $bot_color = "#f97316";
            } else {
                $adv_bottleneck = "OPTIMAL PAIRING: Balanced components.";
                $bot_color = "#00e676";
            }
        ?>
        <div class="perf-hub">
            <div class="hub-header">
                <div class="hub-title"><i class="fas fa-satellite-dish" style="color: var(--accent);"></i> MULTI-SCENARIO AI PREDICTOR</div>
                <div class="bot-badge" style="border: 1px solid <?php echo $bot_color; ?>; color: <?php echo $bot_color; ?>;">
                    <i class="fas <?php echo $bot_color == '#00e676' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i> <?php echo $adv_bottleneck; ?>
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
        
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div>
                <div style="font-size: 0.8rem; color: #888; text-transform: uppercase; margin-bottom: 5px; font-weight: 800; letter-spacing: 1px;">System Tier</div>
                <div style="font-size: 1.2rem; font-weight: 900; color: <?php echo $tier_color; ?>; text-shadow: 0 0 15px <?php echo $tier_color; ?>88;">
                    <?php echo $system_tier; ?>
                </div>
                <?php if($bottleneck_warning): ?>
                    <div style="color: #ffc107; font-size: 0.75rem; font-weight: bold; margin-top: 8px; line-height: 1.4; background: rgba(255,193,7,0.1); padding: 8px 10px; border-radius: 6px; border-left: 3px solid #ffc107;">
                        <?php echo $bottleneck_warning; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <div style="font-size: 0.8rem; color: #888; text-transform: uppercase; margin-bottom: 5px; font-weight: 800; letter-spacing: 1px;">Estimated Load</div>
                <!-- 🌟 数值应用极客字体 -->
                <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.5rem; color: #facc15; font-weight: 900;"><i class="fas fa-bolt" style="text-shadow: 0 0 10px rgba(251,191,36,0.4);"></i> <?php echo $total_wattage; ?> W</div>
            </div>
            
            <div style="margin-top: 5px; padding-top: 20px; border-top: 1px dashed rgba(255,255,255,0.1);">
                <div style="font-size: 0.8rem; color: #888; text-transform: uppercase; margin-bottom: 5px; font-weight: 800; letter-spacing: 1px;">Raw Component Value</div>
                <!-- 🌟 数值应用极客字体 -->
                <div style="font-family: 'JetBrains Mono', monospace; font-size: 2.2rem; color: var(--accent); font-weight: 900; text-shadow: 0 0 20px rgba(0,242,254,0.3);">RM <?php echo number_format($total_price, 2); ?></div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 10px;">
                <?php if ($progress == 100): ?>
                    <a href="name_build.php" class="btn-action btn-select" style="text-align: center; font-size: 1.1rem; padding: 15px; width: 100%; box-sizing: border-box;" title="Discounts applied at checkout!">
                        CHECKOUT <i class="fas fa-shopping-cart" style="margin-left: 8px;"></i>
                    </a>
                <?php else: ?>
                    <span class="btn-action" style="background: rgba(255,255,255,0.05); color: #64748b; cursor: not-allowed; padding: 15px; border: 1px dashed rgba(255,255,255,0.1); text-align: center; width: 100%; box-sizing: border-box;">
                        Complete Build to Checkout
                    </span>
                <?php endif; ?>
                
                <a href="save_build.php" class="btn-action btn-change" style="text-align: center; padding: 12px; width: 100%; box-sizing: border-box;">
                    <i class="fas fa-save" style="margin-right: 8px;"></i> Save Draft
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>