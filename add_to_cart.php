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

// 🌟 全局底层零件需求拆解器 (Global Cart Resolution)
function get_global_cart_requirements($conn, $customer_id) {
    $reqs = [];
    $query = "SELECT c.quantity, c.product_id, c.package_id, c.pc_build 
              FROM shopping_cart c WHERE c.customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    while ($row = $res->fetch_assoc()) {
        $qty = $row['quantity'];
        if ($row['product_id']) {
            $pid = $row['product_id'];
            $reqs[$pid] = ($reqs[$pid] ?? 0) + $qty;
        } elseif ($row['package_id']) {
            $pst = $conn->prepare("SELECT product_id, quantity FROM package_items WHERE package_id = ?");
            $pst->bind_param("i", $row['package_id']);
            $pst->execute();
            $pres = $pst->get_result();
            while ($pr = $pres->fetch_assoc()) {
                $pid = $pr['product_id'];
                $reqs[$pid] = ($reqs[$pid] ?? 0) + ($qty * $pr['quantity']);
            }
            $pst->close();
        } elseif ($row['pc_build']) {
            $bst = $conn->prepare("SELECT product_id, quantity FROM build_items WHERE pc_build = ?");
            $bst->bind_param("i", $row['pc_build']);
            $bst->execute();
            $bres = $bst->get_result();
            while ($br = $bres->fetch_assoc()) {
                $pid = $br['product_id'];
                $reqs[$pid] = ($reqs[$pid] ?? 0) + ($qty * $br['quantity']);
            }
            $bst->close();
        }
    }
    $stmt->close();
    return $reqs;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    if ($quantity <= 0) $quantity = 1;

    $is_buy_now = (isset($_POST['action']) && $_POST['action'] === 'buy_now');
    
    // 🌟 获取当前购物车内所有零件的全局真实占用量
    $global_cart = get_global_cart_requirements($conn, $customer_id);

    // ==========================================
    // 🌟 情境 A：加入的是「單一零件 (Components)」
    // ==========================================
    if (isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);

        $stock_query = "SELECT stock_quantity, product_name FROM products WHERE product_id = ?";
        $stmt_stock = $conn->prepare($stock_query);
        $stmt_stock->bind_param("i", $product_id);
        $stmt_stock->execute();
        $stock_result = $stmt_stock->get_result();

        if ($stock_result->num_rows > 0) {
            $product_data = $stock_result->fetch_assoc();
            $available_stock = $product_data['stock_quantity'];
            $product_name = $product_data['product_name'];

            $check_cart = "SELECT cart_id, quantity FROM shopping_cart WHERE customer_id = ? AND product_id = ?";
            $stmt_check = $conn->prepare($check_cart);
            $stmt_check->bind_param("ii", $customer_id, $product_id);
            $stmt_check->execute();
            $cart_result = $stmt_check->get_result();
            
            $cart_row = ($cart_result->num_rows > 0) ? $cart_result->fetch_assoc() : null;
            
            // 🌟 核心修复 1：对比全局总需求
            $current_in_cart = $global_cart[$product_id] ?? 0;

            if (($current_in_cart + $quantity) > $available_stock) {
                if ($current_in_cart > 0) {
                    $_SESSION['error_msg'] = "Sorry, you already have $current_in_cart units across your cart builds/packages. Only $available_stock total available for '$product_name'.";
                } else {
                    $_SESSION['error_msg'] = "Sorry, only $available_stock units of '$product_name' available.";
                }
            } else {
                if ($cart_row) {
                    $new_qty = $cart_row['quantity'] + $quantity;
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

        $pkg_query = "SELECT package_name, stock_status FROM packages WHERE package_id = ?";
        $stmt_pkg = $conn->prepare($pkg_query);
        $stmt_pkg->bind_param("i", $package_id);
        $stmt_pkg->execute();
        $pkg_result = $stmt_pkg->get_result();

        if ($pkg_result->num_rows > 0) {
            $pkg_data = $pkg_result->fetch_assoc();
            $package_name = $pkg_data['package_name'];

            if ($pkg_data['stock_status'] !== 'Available') {
                $_SESSION['error_msg'] = "Sorry, '$package_name' is currently marked as out of stock.";
            } else {
                $check_cart = "SELECT cart_id, quantity FROM shopping_cart WHERE customer_id = ? AND package_id = ?";
                $stmt_check = $conn->prepare($check_cart);
                $stmt_check->bind_param("ii", $customer_id, $package_id);
                $stmt_check->execute();
                $cart_result = $stmt_check->get_result();
                
                $cart_row = ($cart_result->num_rows > 0) ? $cart_result->fetch_assoc() : null;

                $parts_ok = true;
                $short_part_name = "";
                
                $check_parts_sql = "SELECT p.product_id, p.product_name, p.stock_quantity, pi.quantity as req_qty 
                                    FROM package_items pi 
                                    JOIN products p ON pi.product_id = p.product_id 
                                    WHERE pi.package_id = ?";
                $stmt_parts = $conn->prepare($check_parts_sql);
                $stmt_parts->bind_param("i", $package_id);
                $stmt_parts->execute();
                $parts_res = $stmt_parts->get_result();
                
                // 🌟 核心修复 2：将套餐内的零件与全局购物车内的零件进行交叉比对
                while ($part = $parts_res->fetch_assoc()) {
                    $pid = $part['product_id'];
                    $current_in_cart = $global_cart[$pid] ?? 0;
                    $total_future_req = $current_in_cart + ($part['req_qty'] * $quantity);
                    
                    if ($total_future_req > $part['stock_quantity']) {
                        $parts_ok = false;
                        $short_part_name = $part['product_name'];
                        break;
                    }
                }
                $stmt_parts->close();

                if (!$parts_ok) {
                    $_SESSION['error_msg'] = "Cannot add '$package_name'. An underlying part ('$short_part_name') lacks sufficient stock in global inventory.";
                } else {
                    if ($cart_row) {
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
    }

    if ($is_buy_now && !isset($_SESSION['error_msg'])) {
        header("Location: checkout.php");
    } else {
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
        $parsed_url = parse_url($referer);
        $safe_host = $_SERVER['HTTP_HOST'];
        
        if (isset($parsed_url['host']) && $parsed_url['host'] !== $safe_host) {
            $referer = 'index.php';
        }
        header("Location: $referer");
    }
    exit();
}
?>