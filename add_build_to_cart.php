<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) { header("Location: login.php"); exit(); }
if (empty($_SESSION['pc_build'])) { header("Location: builder.php"); exit(); }

$customer_id = $_SESSION['customer_id'];
$total_price = 0;

foreach ($_SESSION['pc_build'] as $part) {
    $total_price += $part['price'];
}

// 🌟 核心修改：接收前端傳來的主機名稱
// 如果顧客有填寫名字，就過濾並使用；如果沒填寫，就給個預設值
if (!empty($_POST['build_name'])) {
    $build_name = trim(mysqli_real_escape_string($conn, $_POST['build_name']));
} else {
    $build_name = "My Custom Build"; // 預設名稱
}

// 將整台主機存入 saved_builds
$stmt1 = $conn->prepare("INSERT INTO saved_builds (customer_id, build_name, total_price) VALUES (?, ?, ?)");
$stmt1->bind_param("isd", $customer_id, $build_name, $total_price);
$stmt1->execute();
$pc_build = $stmt1->insert_id;
$stmt1->close();

// 存入每個零件明細
$stmt2 = $conn->prepare("INSERT INTO build_items (pc_build, product_id, quantity) VALUES (?, ?, 1)");
foreach ($_SESSION['pc_build'] as $cat_id => $part) {
    $pid = $part['product_id']; 
    $stmt2->bind_param("ii", $pc_build, $pid);
    $stmt2->execute();
}
$stmt2->close();

// 加入購物車 (關聯 pc_build)
$stmt3 = $conn->prepare("INSERT INTO shopping_cart (customer_id, product_id, pc_build, quantity) VALUES (?, NULL, ?, 1)");
$stmt3->bind_param("ii", $customer_id, $pc_build);
$stmt3->execute();
$stmt3->close();

unset($_SESSION['pc_build']);
header("Location: cart.php");
exit();
?>