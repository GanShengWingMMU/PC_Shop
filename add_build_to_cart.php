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

if (!empty($_POST['build_name'])) {
    $build_name = trim(mysqli_real_escape_string($conn, $_POST['build_name']));
} else {
    $build_name = "My Custom Build";
}

$stmt1 = $conn->prepare("INSERT INTO saved_builds (customer_id, build_name, total_price) VALUES (?, ?, ?)");
$stmt1->bind_param("isd", $customer_id, $build_name, $total_price);
$stmt1->execute();
$pc_build = $stmt1->insert_id;
$stmt1->close();

$stmt2 = $conn->prepare("INSERT INTO build_items (pc_build, product_id, quantity) VALUES (?, ?, 1)");
foreach ($_SESSION['pc_build'] as $cat_id => $part) {
    $pid = $part['product_id']; 
    $stmt2->bind_param("ii", $pc_build, $pid);
    $stmt2->execute();
}
$stmt2->close();

$stmt3 = $conn->prepare("INSERT INTO shopping_cart (customer_id, product_id, pc_build, quantity) VALUES (?, NULL, ?, 1)");
$stmt3->bind_param("ii", $customer_id, $pc_build);
$stmt3->execute();
$stmt3->close();

unset($_SESSION['pc_build']);
header("Location: cart.php");
exit();
?>