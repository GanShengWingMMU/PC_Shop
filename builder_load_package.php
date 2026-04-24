<?php
ob_start();
session_start();
require_once 'config.php';

if (!isset($_GET['pkg_id']) || empty($_GET['pkg_id'])) {
    header("Location: packages.php");
    exit();
}

$pkg_id = intval($_GET['pkg_id']);

$stmt_pkg = $conn->prepare("SELECT package_name FROM packages WHERE package_id = ?");
$stmt_pkg->bind_param("i", $pkg_id);
$stmt_pkg->execute();
$pkg_result = $stmt_pkg->get_result();
if ($pkg_result->num_rows === 0) {
    header("Location: packages.php");
    exit();
}
$package_name = $pkg_result->fetch_assoc()['package_name'];
$stmt_pkg->close();

// 清空并初始化装机台
$_SESSION['pc_build'] = [];

// 获取底层真实零件
$sql = "SELECT p.product_id, p.product_name, p.price, p.tdp_wattage, p.category_id 
        FROM package_items pi
        JOIN products p ON pi.product_id = p.product_id
        WHERE pi.package_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pkg_id);
$stmt->execute();
$result = $stmt->get_result();

$loaded_count = 0;

while ($row = $result->fetch_assoc()) {
    $cat_id = $row['category_id'];
    $_SESSION['pc_build'][$cat_id] = [
        'product_id' => $row['product_id'],
        'name'       => $row['product_name'],
        'price'      => $row['price'], // 绝对的底层原价
        'wattage'    => $row['tdp_wattage'] ?? 0
    ];
    $loaded_count++;
}
$stmt->close();

if ($loaded_count === 0) {
    $_SESSION['error_msg'] = "This package has no linked components yet.";
    header("Location: packages.php");
    exit();
}

// 🌟 剥离之前的花哨打折逻辑，恢复统一标价的纯洁性
if (isset($_SESSION['customer_id'])) {
    $_SESSION['success_msg'] = "Loaded <strong>$package_name</strong>! Base components loaded at unified retail prices.";
}

header("Location: builder.php");
exit();