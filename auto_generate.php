<?php
ob_start();
session_start();
require_once 'config.php';

// 1. 拦截非法访问
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['budget'])) {
    header("Location: packages.php");
    exit();
}

$budget = floatval($_POST['budget']);
$persona = $_POST['target_persona'];

// 确保最低预算能买得起电脑
if ($budget < 1500) { $budget = 1500; }

// ==========================================
// 🧠 核心算法第一步：启发式预算划分 (Budget Partitioning)
// 根据不同的用户画像，决定钱花在刀刃上
// ==========================================
$allocation = [];
if ($persona == 'Gamer') {
    // 游戏玩家：显卡占大头 40%，CPU 20%
    $allocation = [1 => 0.20, 4 => 0.40, 2 => 0.12, 3 => 0.08, 5 => 0.08, 7 => 0.05]; 
} elseif ($persona == 'Creator') {
    // 创作者：CPU和内存更重要
    $allocation = [1 => 0.30, 4 => 0.30, 2 => 0.12, 3 => 0.12, 5 => 0.10, 7 => 0.06];
} else {
    // 学生/办公：相对均衡
    $allocation = [1 => 0.25, 4 => 0.20, 2 => 0.15, 3 => 0.15, 5 => 0.15, 7 => 0.10];
}

// 准备一个空数组来装 AI 挑选的零件
$ai_build = [];
$total_wattage = 0;
$socket_req = "";
$ram_req = "";

// ==========================================
// 🧠 核心算法第二步：贪心寻优与 DAG 约束 (Greedy Selection)
// 严格按照依赖顺序挑选：CPU -> 主板 -> RAM -> 显卡 -> 硬盘 -> 机箱 -> 电源
// ==========================================

// 挑选函数：在给定预算下找最贵的（性能最强）
function pick_best_part($conn, $cat_id, $max_price, $extra_sql = "") {
    $sql = "SELECT * FROM products WHERE category_id = $cat_id AND price <= $max_price AND status = 'Available' $extra_sql ORDER BY price DESC LIMIT 1";
    $res = mysqli_query($conn, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        return mysqli_fetch_assoc($res);
    }
    // 如果这个预算买不到，就强行拿该分类下最便宜的一个（保底机制）
    $sql_fallback = "SELECT * FROM products WHERE category_id = $cat_id AND status = 'Available' $extra_sql ORDER BY price ASC LIMIT 1";
    $res_fallback = mysqli_query($conn, $sql_fallback);
    return ($res_fallback && mysqli_num_rows($res_fallback) > 0) ? mysqli_fetch_assoc($res_fallback) : null;
}

// 1. 选 CPU
$cpu = pick_best_part($conn, 1, $budget * $allocation[1]);
if ($cpu) {
    $ai_build[1] = $cpu;
    $total_wattage += $cpu['tdp_wattage'];
    // 嗅探 Socket
    $cpu_name = strtoupper($cpu['product_name']);
    if (preg_match('/(I3|I5|I7|I9|LGA1700)/', $cpu_name)) $socket_req = "LGA1700";
    elseif (preg_match('/(RYZEN|AM5|AM4)/', $cpu_name)) $socket_req = "AM5";
}

// 2. 选 主板 (必须符合 CPU Socket)
$mobo_sql = $socket_req ? "AND (product_name LIKE '%$socket_req%' OR description LIKE '%$socket_req%')" : "";
$mobo = pick_best_part($conn, 2, $budget * $allocation[2], $mobo_sql);
if ($mobo) {
    $ai_build[2] = $mobo;
    $total_wattage += $mobo['tdp_wattage'];
    // 嗅探 RAM DDR 类型
    $mb_name = strtoupper($mobo['product_name']);
    if (strpos($mb_name, 'DDR5') !== false) $ram_req = "DDR5";
    elseif (strpos($mb_name, 'DDR4') !== false) $ram_req = "DDR4";
}

// 3. 选 RAM (必须符合主板 DDR)
$ram_sql = $ram_req ? "AND (product_name LIKE '%$ram_req%' OR description LIKE '%$ram_req%')" : "";
$ram = pick_best_part($conn, 3, $budget * $allocation[3], $ram_sql);
if ($ram) { $ai_build[3] = $ram; $total_wattage += $ram['tdp_wattage']; }

// 4. 选 显卡 GPU
$gpu = pick_best_part($conn, 4, $budget * $allocation[4]);
if ($gpu) { $ai_build[4] = $gpu; $total_wattage += $gpu['tdp_wattage']; }

// 5. 选 硬盘 SSD & 机箱 Case
$ssd = pick_best_part($conn, 5, $budget * $allocation[5]);
if ($ssd) { $ai_build[5] = $ssd; $total_wattage += $ssd['tdp_wattage']; }

$case = pick_best_part($conn, 7, $budget * $allocation[7]);
if ($case) { $ai_build[7] = $case; }

// 6. 终极计算：选 电源 PSU (基于真实的 Total Wattage + 100W 冗余)
$req_psu_watt = ceil(($total_wattage + 100) / 50) * 50;
$psu_sql = "AND tdp_wattage >= $req_psu_watt";
// 电源的预算是剩下的钱
$psu_budget = max($budget * 0.10, 300); 
$psu = pick_best_part($conn, 6, $psu_budget, $psu_sql);
if ($psu) { $ai_build[6] = $psu; }

// ==========================================
// 🚀 核心算法第三步：覆写 Session，完美闭环
// ==========================================
unset($_SESSION['pc_build']);
$_SESSION['pc_build'] = [];

foreach ($ai_build as $cat_id => $part) {
    $_SESSION['pc_build'][$cat_id] = [
        'product_id' => $part['product_id'],
        'name'       => $part['product_name'],
        'price'      => $part['price'],
        'wattage'    => $part['tdp_wattage'] ?? 0
    ];
}

// 瞬间传送回装机台！
header("Location: builder.php");
exit();
?>