<?php
session_start();
require_once 'config.php';

// 如果没有登录，跳去登录页面（带货必须登录才能买）
if (!isset($_SESSION['customer_id'])) {
    $_SESSION['error_msg'] = "Please connect to the neural network (login) to load this blueprint.";
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: community.php");
    exit();
}

$current_user = $_SESSION['customer_id'];
$build_id = intval($_GET['id']);

// 1. 验证这个 Build 是否存在，并查出它的原作者是谁
$stmt_check = $conn->prepare("SELECT customer_id, build_name FROM saved_builds WHERE pc_build = ?");
$stmt_check->bind_param("i", $build_id);
$stmt_check->execute();
$res_check = $stmt_check->get_result();

if ($res_check->num_rows === 0) {
    $_SESSION['error_msg'] = "Data corrupted: Blueprint not found in the archives.";
    header("Location: community.php"); 
    exit();
}

$build_data = $res_check->fetch_assoc();
$original_author_id = $build_data['customer_id'];
$stmt_check->close();

// 🌟 社交带货逻辑：如果这不是我自己的配置单，就将原作者标记为 affiliate (带货人)
$affiliate_id = ($original_author_id != $current_user) ? $original_author_id : 'NULL';

// =========================================================
// 🌟 核心分流机制：
// 如果 URL 里带了 action=cart，说明是从社区点击的 "一键加入购物车"
// 否则，走原来的逻辑：加载到 Builder 里修改。
// =========================================================

if (isset($_GET['action']) && $_GET['action'] == 'cart') {
    
    // -- 模式 A：一键加入购物车 (带货模式) --
    
    // 🌟 统一架构：全量使用 Prepared Statement 防御
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
        // Affiliate ID 可能是 NULL，我们需要正确处理绑定
        $aff_bind = ($affiliate_id === 'NULL') ? null : $affiliate_id;
        $insert_stmt->bind_param("iii", $current_user, $build_id, $aff_bind);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    $check_stmt->close();
    
    $_SESSION['success_msg'] = "Blueprint [{$build_data['build_name']}] successfully injected into your cart!";
    header("Location: cart.php");
    exit();

} else {
    
    // -- 模式 B：传统的 Load 到 Builder 模式 (保持你原有的优秀逻辑) --
    
    unset($_SESSION['pc_build']);
    $_SESSION['pc_build'] = [];

    // 检查原作者是不是自己，不是自己不准 load 到 builder (防止随意篡改别人的心血)
    // 但作为演示，我们可以允许。如果你想严格控制，取消下面两行的注释：
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
            // 🌟 终极修复：只存整数 ID！
            $_SESSION['pc_build'][$cat_id] = (int)$row['product_id'];
        } else {
            $out_of_stock_items[] = $row['product_name'];
        }
    }
    $stmt_items->close();

    if (!empty($out_of_stock_items)) {
        $_SESSION['error_msg'] = "Blueprint loaded, but some parts are OUT OF STOCK and were skipped: <br>• " . implode("<br>• ", $out_of_stock_items);
    } else {
        $_SESSION['success_msg'] = "Blueprint injected into Builder successfully!";
    }

    header("Location: builder.php");
    exit();
}
?>