<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['customer_id']) || !isset($_GET['id'])) {
    header("Location: profile.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$build_id = intval($_GET['id']);

$stmt_check = $conn->prepare("SELECT pc_build FROM saved_builds WHERE pc_build = ? AND customer_id = ?");
$stmt_check->bind_param("ii", $build_id, $customer_id);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows === 0) {
    header("Location: profile.php"); exit();
}
$stmt_check->close();

unset($_SESSION['pc_build']);
$_SESSION['pc_build'] = [];

// 🌟 A+ 级修复：提取数据时加入 stock_quantity 字段
$stmt_items = $conn->prepare("
    SELECT p.product_id, p.product_name, p.price, p.tdp_wattage, p.category_id, p.stock_quantity 
    FROM build_items bi 
    JOIN products p ON bi.product_id = p.product_id 
    WHERE bi.pc_build = ?
");
$stmt_items->bind_param("i", $build_id);
$stmt_items->execute();
$items_res = $stmt_items->get_result();

$out_of_stock_items = []; // 记录缺货被踢出的零件

while ($row = $items_res->fetch_assoc()) {
    // 🌟 核心拦截逻辑：只有库存 > 0 才能被重新注入！
    if ($row['stock_quantity'] > 0) {
        $cat_id = $row['category_id'];
        $_SESSION['pc_build'][$cat_id] = [
            'product_id' => $row['product_id'],
            'name'       => $row['product_name'],
            'price'      => $row['price'],
            'wattage'    => $row['tdp_wattage'] ?? 0
        ];
    } else {
        // 如果缺货，记录它的名字
        $out_of_stock_items[] = $row['product_name'];
    }
}
$stmt_items->close();

// 🌟 发送智能系统通告给装机台
if (!empty($out_of_stock_items)) {
    $_SESSION['error_msg'] = "Blueprint loaded, but some parts are currently OUT OF STOCK and were skipped: <br>• " . implode("<br>• ", $out_of_stock_items);
} else {
    $_SESSION['success_msg'] = "Blueprint injected into Builder successfully!";
}

header("Location: builder.php");
exit();