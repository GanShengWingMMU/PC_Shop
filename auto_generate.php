<?php
ob_start();
session_start();
require_once 'config.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['budget'])) {
    header("Location: packages.php");
    exit();
}

$budget = floatval($_POST['budget']);
$persona = $_POST['target_persona'] ?? 'Gamer';

$_SESSION['pc_build'] = [];

if ($persona == 'Gamer') {
    $weights = [ 1 => 0.16, 8 => 0.04, 2 => 0.08, 3 => 0.07, 4 => 0.32, 5 => 0.06, 7 => 0.05, 10=> 0.02, 11=> 0.13, 9 => 0.02, 6 => 0.05 ]; 
} elseif ($persona == 'Creator') {
    $weights = [ 1 => 0.22, 8 => 0.05, 2 => 0.10, 3 => 0.10, 4 => 0.20, 5 => 0.08, 7 => 0.05, 10=> 0.02, 11=> 0.11, 9 => 0.02, 6 => 0.05 ];
} else { 
    $weights = [ 1 => 0.22, 8 => 0.04, 2 => 0.10, 3 => 0.08, 4 => 0.12, 5 => 0.10, 7 => 0.06, 10=> 0.02, 11=> 0.16, 9 => 0.05, 6 => 0.05 ];
}

$remaining_budget = $budget;
$remaining_weight = array_sum($weights);

$ai_build = [];
$total_wattage = 0;
$socket_req = "";
$ram_req = "";

$execution_order = [1, 8, 2, 3, 4, 5, 7, 10, 11, 9, 6];

foreach ($execution_order as $cat_id) {
    if ($remaining_weight <= 0) break;

    $current_weight = $weights[$cat_id];
    $target_budget = $remaining_budget * ($current_weight / $remaining_weight);

    
    $params = [];
    $types = "";
    $extra_sql = "";

    if ($cat_id == 2 && $socket_req) {
        $extra_sql = " AND (product_name LIKE ? OR description LIKE ?)";
        $search_str = "%$socket_req%";
        $params[] = $search_str; $params[] = $search_str;
        $types .= "ss";
    } elseif ($cat_id == 3 && $ram_req) {
        $extra_sql = " AND (product_name LIKE ? OR description LIKE ?)";
        $search_str = "%$ram_req%";
        $params[] = $search_str; $params[] = $search_str;
        $types .= "ss";
    } elseif ($cat_id == 6) {
        $req_psu_watt = ceil(($total_wattage + 100) / 50) * 50;
        $extra_sql = " AND tdp_wattage >= ?";
        $params[] = $req_psu_watt;
        $types .= "i";
        $target_budget = max($target_budget, 150); 
    }

    $sql_find = "SELECT * FROM products WHERE category_id = ? AND stock_quantity > 0 AND price <= ? $extra_sql ORDER BY price DESC LIMIT 1";
    $stmt_find = $conn->prepare($sql_find);
    $bind_params_find = array_merge([$cat_id, $target_budget], $params);
    $stmt_find->bind_param("id" . $types, ...$bind_params_find);
    $stmt_find->execute();
    $res = $stmt_find->get_result();

    if ($res && $res->num_rows > 0) {
        $part = $res->fetch_assoc();
    } else {
   
        $sql_fb = "SELECT * FROM products WHERE category_id = ? AND stock_quantity > 0 $extra_sql ORDER BY price ASC LIMIT 1";
        $stmt_fb = $conn->prepare($sql_fb);
        $bind_params_fb = array_merge([$cat_id], $params);
        $stmt_fb->bind_param("i" . $types, ...$bind_params_fb);
        $stmt_fb->execute();
        $res_fb = $stmt_fb->get_result();
        
        if ($res_fb && $res_fb->num_rows > 0) {
            $part = $res_fb->fetch_assoc();
        } else {
            $stmt_fb->close();
            continue; 
        }
        $stmt_fb->close();
    }
    $stmt_find->close();

    $ai_build[$cat_id] = [
        'id'      => $part['product_id'],
        'name'    => $part['product_name'],
        'price'   => $part['price'],
        'wattage' => $part['tdp_wattage'] ?? 0
    ];

    if (in_array($cat_id, [1, 2, 3, 4, 5, 8, 10])) {
        $total_wattage += $part['tdp_wattage'] ?? 20;
    }

    if ($cat_id == 1) {
        $cpu_name = strtoupper($part['product_name']);
        if (preg_match('/(I3|I5|I7|I9|LGA1700)/', $cpu_name)) $socket_req = "LGA1700";
        elseif (preg_match('/(RYZEN|AM5|AM4)/', $cpu_name)) $socket_req = "AM5";
    } elseif ($cat_id == 2) {
        $mb_name = strtoupper($part['product_name']);
        if (strpos($mb_name, 'DDR5') !== false) $ram_req = "DDR5";
        elseif (strpos($mb_name, 'DDR4') !== false) $ram_req = "DDR4";
    }

    $remaining_budget -= $part['price'];
    $remaining_weight -= $current_weight;
}
 
$actual_total = 0;
foreach ($ai_build as $p) { $actual_total += $p['price']; }

$_SESSION['pc_build'] = [];
foreach ($ai_build as $cat_id => $part) {
    $_SESSION['pc_build'][$cat_id] = (int)$part['id'];
}

$error_messages = [];

if (count($ai_build) < 11) {
    $error_messages[] = "<b>INCOMPLETE BUILD:</b> Inventory couldn't fulfill all slots (Filled: " . count($ai_build) . "/11). Manual component selection required.";
}

if ($actual_total > $budget) {
    $deficit = $actual_total - $budget;
    $error_messages[] = "<b>SURVIVAL MODE ENGAGED:</b> RM " . number_format($budget, 2) . " is mathematically impossible for this setup. AI secured the absolute cheapest viable parts at RM " . number_format($actual_total, 2) . " (RM " . number_format($deficit, 2) . " over budget).";
}

if (!empty($error_messages)) {
    $_SESSION['error_msg'] = implode("<br><br>", $error_messages);
} else {
    $_SESSION['success_msg'] = "AI Blueprint Fully Deployed: All 11 slots optimally filled for " . strtoupper($persona) . ". Target Budget: RM " . number_format($budget, 2) . " | Actual Cost: RM " . number_format($actual_total, 2) . ".";
}

header("Location: builder.php");
exit();
?>