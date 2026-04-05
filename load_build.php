<?php
session_start();
require_once 'config.php';

// 1. 安全检查：必须登录且必须有 ID
if (!isset($_SESSION['customer_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$build_id = intval($_GET['id']);

// 2. 验证这个 build 是否真的属于当前登录用户（防止越权漏洞）
$stmt_check = $conn->prepare("SELECT pc_build FROM saved_builds WHERE pc_build = ? AND customer_id = ?");
$stmt_check->bind_param("ii", $build_id, $customer_id);
$stmt_check->execute();
$res = $stmt_check->get_result();

if ($res->num_rows === 0) {
    // 如果找不到或者不属于该用户，踢回 profile
    header("Location: profile.php");
    exit();
}
$stmt_check->close();

// 3. 【核心步骤】清空当前 Builder 缓存，准备装载
unset($_SESSION['pc_build']);
$_SESSION['pc_build'] = [];

// 4. 从数据库查询该配置的所有零件详情
// 对应你在 profile.php 里展示零件的那个逻辑
$stmt_items = $conn->prepare("
    SELECT p.product_id, p.product_name, p.price, p.tdp_wattage, p.category_id 
    FROM build_items bi 
    JOIN products p ON bi.product_id = p.product_id 
    WHERE bi.pc_build = ?
");
$stmt_items->bind_param("i", $build_id);
$stmt_items->execute();
$items_res = $stmt_items->get_result();

while ($row = $items_res->fetch_assoc()) {
    $cat_id = $row['category_id'];
    
    // 5. 按照 builder.php 识别的数组格式进行压栈
    $_SESSION['pc_build'][$cat_id] = [
        'product_id' => $row['product_id'],
        'name'       => $row['product_name'],
        'price'      => $row['price'],
        'wattage'    => $row['tdp_wattage'] ?? 0
    ];
}

$stmt_items->close();

// 6. 装载完毕，直接送回 Builder 页面
header("Location: builder.php");
exit();