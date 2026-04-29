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
$persona = $_POST['target_persona'] ?? 'Gamer';

// 强制清空 Builder 中的旧配置
$_SESSION['pc_build'] = [];

// ==========================================
// 🧠 核心机制 1：十一槽位动态全量分配 (11-Slot Weights)
// 包含外设与软件，总和必须等于 1.0
// ==========================================
if ($persona == 'Gamer') {
    // 游戏玩家：重仓显卡和显示器
    $weights = [
        1 => 0.16,  // CPU
        8 => 0.04,  // 散热 Cooler
        2 => 0.08,  // 主板 Mobo
        3 => 0.07,  // 内存 RAM
        4 => 0.32,  // 显卡 GPU
        5 => 0.06,  // 固态 SSD
        7 => 0.05,  // 机箱 Case
        10=> 0.02,  // 风扇 Fans
        11=> 0.13,  // 显示器 Monitor
        9 => 0.02,  // 操作系统 OS
        6 => 0.05   // 电源 PSU
    ]; 
} elseif ($persona == 'Creator') {
    // 创作者：重仓 CPU、内存和优秀显示器
    $weights = [
        1 => 0.22, 8 => 0.05, 2 => 0.10, 3 => 0.10, 4 => 0.20, 
        5 => 0.08, 7 => 0.05, 10=> 0.02, 11=> 0.11, 9 => 0.02, 6 => 0.05
    ];
} else { 
    // 学生/开发：均衡配置
    $weights = [
        1 => 0.22, 8 => 0.04, 2 => 0.10, 3 => 0.08, 4 => 0.12, 
        5 => 0.10, 7 => 0.06, 10=> 0.02, 11=> 0.16, 9 => 0.05, 6 => 0.05
    ];
}

$remaining_budget = $budget;
$remaining_weight = array_sum($weights);

$ai_build = [];
$total_wattage = 0;
$socket_req = "";
$ram_req = "";

// ==========================================
// 🧠 核心机制 2：十一重拓扑装机队列 (Topological Order)
// 必须按此逻辑顺序挑选，确保所有兼容性条件生效
// ==========================================
$execution_order = [1, 8, 2, 3, 4, 5, 7, 10, 11, 9, 6]; // 最后选电源

foreach ($execution_order as $cat_id) {
    if ($remaining_weight <= 0) break;

    $current_weight = $weights[$cat_id];
    
    // 💡 动态算账：当前可用资金
    $target_budget = $remaining_budget * ($current_weight / $remaining_weight);

    // 构建兼容性 SQL (与 builder.php 嗅探器完全一致)
    $extra_sql = "";
    if ($cat_id == 2 && $socket_req) {
        $extra_sql = "AND (product_name LIKE '%$socket_req%' OR description LIKE '%$socket_req%')";
    } elseif ($cat_id == 3 && $ram_req) {
        $extra_sql = "AND (product_name LIKE '%$ram_req%' OR description LIKE '%$ram_req%')";
    } elseif ($cat_id == 6) {
        // 电源：必须满足系统总功耗 + 100W 冗余
        $req_psu_watt = ceil(($total_wattage + 100) / 50) * 50;
        $extra_sql = "AND tdp_wattage >= $req_psu_watt";
        $target_budget = max($target_budget, 150); // 强制电源底线
    }

    // 🎯 尝试 1：预算内找最强的
    $sql = "SELECT * FROM products WHERE category_id = $cat_id AND stock_quantity > 0 AND price <= $target_budget $extra_sql ORDER BY price DESC LIMIT 1";
    $res = mysqli_query($conn, $sql);

    if ($res && mysqli_num_rows($res) > 0) {
        $part = mysqli_fetch_assoc($res);
    } else {
        // ⚠️ 尝试 2：保底生存模式 (拿兼容的最便宜货)
        $sql_fb = "SELECT * FROM products WHERE category_id = $cat_id AND stock_quantity > 0 $extra_sql ORDER BY price ASC LIMIT 1";
        $res_fb = mysqli_query($conn, $sql_fb);
        if ($res_fb && mysqli_num_rows($res_fb) > 0) {
            $part = mysqli_fetch_assoc($res_fb);
        } else {
            continue; // 如果真的没货，只能跳过
        }
    }

    // 装入蓝图
    $ai_build[$cat_id] = [
        'id'      => $part['product_id'],
        'name'    => $part['product_name'],
        'price'   => $part['price'],
        'wattage' => $part['tdp_wattage'] ?? 0
    ];

    // 只有核心硬件计算功耗 (排除显示器、系统、机箱等)
    if (in_array($cat_id, [1, 2, 3, 4, 5, 8, 10])) {
        $total_wattage += $part['tdp_wattage'] ?? 20;
    }

    // 🔍 兼容性规则探针设置
    if ($cat_id == 1) {
        $cpu_name = strtoupper($part['product_name']);
        if (preg_match('/(I3|I5|I7|I9|LGA1700)/', $cpu_name)) $socket_req = "LGA1700";
        elseif (preg_match('/(RYZEN|AM5|AM4)/', $cpu_name)) $socket_req = "AM5";
    } elseif ($cat_id == 2) {
        $mb_name = strtoupper($part['product_name']);
        if (strpos($mb_name, 'DDR5') !== false) $ram_req = "DDR5";
        elseif (strpos($mb_name, 'DDR4') !== false) $ram_req = "DDR4";
    }

    // 扣款 (结转至下一轮)
    $remaining_budget -= $part['price'];
    $remaining_weight -= $current_weight;
}
 
// ==========================================
// 🚀 结算与 Builder 闭环通信
// ==========================================
$actual_total = 0;
foreach ($ai_build as $p) { $actual_total += $p['price']; }

// 写入 Session
$_SESSION['pc_build'] = [];
foreach ($ai_build as $cat_id => $part) {
    $_SESSION['pc_build'][$cat_id] = [
        'product_id' => $part['id'],
        'name'       => $part['name'],
        'price'      => $part['price'],
        'wattage'    => $part['wattage']
    ];
}

// 🌟 预警与预期管理反馈机制 (修复多重错误叠加显示)
$error_messages = [];

// 独立检测 1：零件是否缺失
if (count($ai_build) < 11) {
    $error_messages[] = "<b>INCOMPLETE BUILD:</b> Inventory couldn't fulfill all slots (Filled: " . count($ai_build) . "/11). Manual component selection required.";
}

// 独立检测 2：预算是否超支
if ($actual_total > $budget) {
    $deficit = $actual_total - $budget;
    $error_messages[] = "<b>SURVIVAL MODE ENGAGED:</b> RM " . number_format($budget, 2) . " is mathematically impossible for this setup. AI secured the absolute cheapest viable parts at RM " . number_format($actual_total, 2) . " (RM " . number_format($deficit, 2) . " over budget).";
}

// 统合输出机制
if (!empty($error_messages)) {
    // 如果存在一个或多个错误，使用 <br><br> 将它们拼接成多行警告
    $_SESSION['error_msg'] = implode("<br><br>", $error_messages);
} else {
    // 完美状态
    $_SESSION['success_msg'] = "✅ AI Blueprint Fully Deployed: All 11 slots optimally filled for " . strtoupper($persona) . ". Target Budget: RM " . number_format($budget, 2) . " | Actual Cost: RM " . number_format($actual_total, 2) . ".";
}

header("Location: builder.php");
exit();
?>