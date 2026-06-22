<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['customer_id'])) {
    $_SESSION['error_msg'] = "[ACCESS DENIED] Neural Network connection required. Please authenticate to load this blueprint.";
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: community.php");
    exit();
}

$current_user = $_SESSION['customer_id'];
$build_id = intval($_GET['id']);


$stmt_check = $conn->prepare("SELECT customer_id, build_name FROM saved_builds WHERE pc_build = ?");
$stmt_check->bind_param("i", $build_id);
$stmt_check->execute();
$res_check = $stmt_check->get_result();

if ($res_check->num_rows === 0) {
    $_SESSION['error_msg'] = "[DATA CORRUPTION] Blueprint entity not found in the archives.";
    header("Location: community.php"); 
    exit();
}

$build_data = $res_check->fetch_assoc();
$original_author_id = $build_data['customer_id'];
$stmt_check->close();

$affiliate_id = ($original_author_id != $current_user) ? $original_author_id : 'NULL';



if (isset($_GET['action']) && $_GET['action'] == 'cart') {
    

   
    $check_stmt = $conn->prepare("SELECT cart_id FROM shopping_cart WHERE customer_id = ? AND pc_build = ?");
    $check_stmt->bind_param("ii", $current_user, $build_id);
    $check_stmt->execute();
    $check_cart = $check_stmt->get_result();
    
    if ($check_cart->num_rows > 0) {
        $update_stmt = $conn->prepare("UPDATE shopping_cart SET quantity = quantity + 1 WHERE customer_id = ? AND pc_build = ?");
        $update_stmt->bind_param("ii", $current_user, $build_id);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        $insert_stmt = $conn->prepare("INSERT INTO shopping_cart (customer_id, pc_build, affiliate_id, quantity) VALUES (?, ?, ?, 1)");

        $aff_bind = ($affiliate_id === 'NULL') ? null : $affiliate_id;
        $insert_stmt->bind_param("iii", $current_user, $build_id, $aff_bind);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    $check_stmt->close();
    
    $_SESSION['success_msg'] = "[SUCCESS] Blueprint [{$build_data['build_name']}] successfully injected into your cart payload!";
    header("Location: cart.php");
    exit();

} else {
    

    
    unset($_SESSION['pc_build']);
    $_SESSION['pc_build'] = [];


    // if ($original_author_id != $current_user) { $_SESSION['error_msg'] = "You can only load your own blueprints into the builder."; header("Location: community.php"); exit(); }

    $stmt_items = $conn->prepare("
        SELECT p.product_id, p.product_name, p.price, p.tdp_wattage, p.category_id, p.stock_quantity 
        FROM build_items bi 
        JOIN products p ON bi.product_id = p.product_id 
        WHERE bi.pc_build = ?
    ");
    $stmt_items->bind_param("i", $build_id);
    $stmt_items->execute();
    $items_res = $stmt_items->get_result();

    $out_of_stock_items = [];

    while ($row = $items_res->fetch_assoc()) {
        if ($row['stock_quantity'] > 0) {
            $cat_id = $row['category_id'];
            
            $_SESSION['pc_build'][$cat_id] = (int)$row['product_id'];
        } else {
            $out_of_stock_items[] = $row['product_name'];
        }
    }
    $stmt_items->close();

    if (!empty($out_of_stock_items)) {
        $_SESSION['error_msg'] = "[WARNING] Blueprint loaded with modifications. The following components are out of stock and bypassed: <br>• " . implode("<br>• ", $out_of_stock_items);
    } else {
        $_SESSION['success_msg'] = "[SUCCESS] Blueprint sequence injected into Builder.";

    header("Location: builder.php");
    exit();
    }
}
?>