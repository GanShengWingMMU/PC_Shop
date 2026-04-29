<?php
session_start();
require_once 'config.php';

// 1. 權限防護
if (!isset($_SESSION['customer_id'])) {
    $_SESSION['error_msg'] = "Please login to add items to your cart.";
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    if ($quantity <= 0) $quantity = 1;

    // 判斷是否為 Buy Now 動作
    $is_buy_now = (isset($_POST['action']) && $_POST['action'] === 'buy_now');

    // ==========================================
    // 🌟 情境 A：加入的是「單一零件 (Components)」
    // ==========================================
    if (isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);

        // 庫存檢查
        $stock_query = "SELECT stock_quantity, product_name FROM products WHERE product_id = ?";
        $stmt_stock = $conn->prepare($stock_query);
        $stmt_stock->bind_param("i", $product_id);
        $stmt_stock->execute();
        $stock_result = $stmt_stock->get_result();

        if ($stock_result->num_rows > 0) {
            $product_data = $stock_result->fetch_assoc();
            $available_stock = $product_data['stock_quantity'];
            $product_name = $product_data['product_name'];

            if ($quantity > $available_stock) {
                $_SESSION['error_msg'] = "Sorry, only $available_stock units available.";
            } else {
                // 檢查購物車是否已有
                $check_cart = "SELECT cart_id, quantity FROM shopping_cart WHERE customer_id = ? AND product_id = ?";
                $stmt_check = $conn->prepare($check_cart);
                $stmt_check->bind_param("ii", $customer_id, $product_id);
                $stmt_check->execute();
                $cart_result = $stmt_check->get_result();

                if ($cart_result->num_rows > 0) {
                    $cart_row = $cart_result->fetch_assoc();
                    $new_qty = min($cart_row['quantity'] + $quantity, $available_stock);
                    $update_cart = "UPDATE shopping_cart SET quantity = ? WHERE cart_id = ?";
                    $stmt_update = $conn->prepare($update_cart);
                    $stmt_update->bind_param("ii", $new_qty, $cart_row['cart_id']);
                    $stmt_update->execute();
                    $_SESSION['success_msg'] = "Quantity updated for '$product_name'!";
                } else {
                    $insert_cart = "INSERT INTO shopping_cart (customer_id, product_id, quantity) VALUES (?, ?, ?)";
                    $stmt_insert = $conn->prepare($insert_cart);
                    $stmt_insert->bind_param("iii", $customer_id, $product_id, $quantity);
                    $stmt_insert->execute();
                    $_SESSION['success_msg'] = "'$product_name' added to cart!";
                }
            }
        }
    }
    // ==========================================
    // 🌟 情境 B：加入的是「整機套餐 (Packages)」
    // ==========================================
    elseif (isset($_POST['package_id'])) {
        $package_id = intval($_POST['package_id']);

        // 檢查套餐狀態
        $pkg_query = "SELECT package_name, stock_status FROM packages WHERE package_id = ?";
        $stmt_pkg = $conn->prepare($pkg_query);
        $stmt_pkg->bind_param("i", $package_id);
        $stmt_pkg->execute();
        $pkg_result = $stmt_pkg->get_result();

        if ($pkg_result->num_rows > 0) {
            $pkg_data = $pkg_result->fetch_assoc();
            $package_name = $pkg_data['package_name'];

            if ($pkg_data['stock_status'] !== 'Available') {
                $_SESSION['error_msg'] = "Sorry, '$package_name' is currently out of stock.";
            } else {
                // 檢查購物車是否已有
                $check_cart = "SELECT cart_id, quantity FROM shopping_cart WHERE customer_id = ? AND package_id = ?";
                $stmt_check = $conn->prepare($check_cart);
                $stmt_check->bind_param("ii", $customer_id, $package_id);
                $stmt_check->execute();
                $cart_result = $stmt_check->get_result();

                if ($cart_result->num_rows > 0) {
                    $cart_row = $cart_result->fetch_assoc();
                    $new_qty = $cart_row['quantity'] + $quantity;
                    $update_cart = "UPDATE shopping_cart SET quantity = ? WHERE cart_id = ?";
                    $stmt_update = $conn->prepare($update_cart);
                    $stmt_update->bind_param("ii", $new_qty, $cart_row['cart_id']);
                    $stmt_update->execute();
                    $_SESSION['success_msg'] = "Quantity updated for Package: '$package_name'!";
                } else {
                    $insert_cart = "INSERT INTO shopping_cart (customer_id, package_id, quantity) VALUES (?, ?, ?)";
                    $stmt_insert = $conn->prepare($insert_cart);
                    $stmt_insert->bind_param("iii", $customer_id, $package_id, $quantity);
                    $stmt_insert->execute();
                    $_SESSION['success_msg'] = "Package '$package_name' added to cart!";
                }
            }
        }
    }

    // ==========================================
    // 🌟 智慧路由 (UX 極致體驗)
    // ==========================================
    if ($is_buy_now && !isset($_SESSION['error_msg'])) {
        // 如果是按 Buy Now 且沒報錯，直接跳去結帳畫面
        header("Location: checkout.php");
    } else {
        // 否則留在原本的頁面
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
        header("Location: $referer");
    }
    exit();
}
?>