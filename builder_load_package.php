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

$_SESSION['pc_build'] = [];


$sql = "SELECT p.product_id, p.product_name, p.price, p.tdp_wattage, p.category_id, p.stock_quantity, p.status 
        FROM package_items pi
        JOIN products p ON pi.product_id = p.product_id
        WHERE pi.package_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pkg_id);
$stmt->execute();
$result = $stmt->get_result();

$loaded_count = 0;
$out_of_stock_items = [];

while ($row = $result->fetch_assoc()) {
    if ($row['stock_quantity'] > 0 && $row['status'] === 'Available') {
        $cat_id = $row['category_id'];
     
        $_SESSION['pc_build'][$cat_id] = (int)$row['product_id'];
        $loaded_count++;
    } else {
        $out_of_stock_items[] = $row['product_name'];
    }
}
$stmt->close();

if (!empty($out_of_stock_items)) {
    $_SESSION['error_msg'] = "Package loaded, but some parts are out of stock: " . implode(", ", $out_of_stock_items) . ". Please select alternatives manually.";
} else {
    $_SESSION['success_msg'] = "Package '" . htmlspecialchars($package_name) . "' successfully loaded into the builder!";
}

header("Location: builder.php");
exit();
?>