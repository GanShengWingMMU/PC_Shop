<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) { header("Location: login.php"); exit(); }
if (empty($_SESSION['pc_build'])) { header("Location: builder.php"); exit(); }

$customer_id = $_SESSION['customer_id'];
$total_price = 0;

// 計算總價
foreach ($_SESSION['pc_build'] as $part) {
    $total_price += $part['price'];
}

// 1. 建立一張新菜單 (Saved Build)
$build_name = "Custom Gaming PC #" . rand(1000, 9999); // 給它一個帥氣的隨機編號
$stmt1 = $conn->prepare("INSERT INTO saved_builds (customer_id, build_name, total_price) VALUES (?, ?, ?)");
$stmt1->bind_param("isd", $customer_id, $build_name, $total_price);
$stmt1->execute();
$build_id = $stmt1->insert_id; // 取得剛剛建立的菜單 ID
$stmt1->close();

// 2. 把 Session 裡的零件一一塞進 build_items
$stmt2 = $conn->prepare("INSERT INTO build_items (build_id, product_id, quantity) VALUES (?, ?, 1)");
foreach ($_SESSION['pc_build'] as $cat_id => $part) {
    // ⚠️ 請確保你在 builder 存 Session 時，商品ID的 key 叫做 product_id
    $pid = $part['product_id']; 
    $stmt2->bind_param("ii", $build_id, $pid);
    $stmt2->execute();
}
$stmt2->close();

// 3. ⭐️ 關鍵：把這台「主機」塞進購物車 (product_id 設為 NULL，填入 build_id)
$stmt3 = $conn->prepare("INSERT INTO shopping_cart (customer_id, product_id, build_id, quantity) VALUES (?, NULL, ?, 1)");
$stmt3->bind_param("ii", $customer_id, $build_id);
$stmt3->execute();
$stmt3->close();

// 4. 清空 Builder 暫存，飛去購物車
unset($_SESSION['pc_build']);
header("Location: cart.php");
exit();
?>