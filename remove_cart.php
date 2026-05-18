<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    $sql = "DELETE FROM shopping_cart WHERE customer_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $stmt->close();
    }
} 
else if (isset($_GET['id'])) {
    // 🌟 升级：强制转换为整数，比 is_numeric 更安全，防止隐性注入
    $cart_id = intval($_GET['id']); 
    if ($cart_id > 0) {
        $sql = "DELETE FROM shopping_cart WHERE cart_id = ? AND customer_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ii", $cart_id, $customer_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

header("Location: cart.php");
exit();
?>