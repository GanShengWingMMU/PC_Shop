<?php
session_start();
require_once 'config.php';

// 安全檢查：確保玩家有登入
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// ==========================================
// 🚀 模式 1：一鍵清空整個購物車 (Remove All)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    $sql = "DELETE FROM shopping_cart WHERE customer_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $stmt->close();
    }
} 
// ==========================================
// 🗑️ 模式 2：刪除單一商品 (Delete Single Item)
// ==========================================
else if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $cart_id = $_GET['id'];
    $sql = "DELETE FROM shopping_cart WHERE cart_id = ? AND customer_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $cart_id, $customer_id);
        $stmt->execute();
        $stmt->close();
    }
}

// 執行完畢，0.1 秒無縫跳轉回購物車頁面
header("Location: cart.php");
exit();
?>