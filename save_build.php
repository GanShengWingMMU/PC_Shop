<?php
session_start();
require_once 'config.php';

// 🚨 真正的安全拦截：没登录就不准保存，并提示他去登录
if (!isset($_SESSION['customer_id'])) {
    echo "<script>alert('Authentication required! Please login to save your build to the Armory.'); window.location.href='login.php';</script>";
    exit();
}

$customer_id = $_SESSION['customer_id'];
$cart = $_SESSION['pc_build'] ?? [];

// 1. 防御性编程：没选东西不准保存
if (empty($cart)) {
    die("<script>alert('Your build is empty! Cannot save.'); window.location.href='builder.php';</script>");
}

// 算出总价
$total_price = 0;
foreach ($cart as $item) {
    $total_price += $item['price'];
}

// ==========================================
// 🧠 核心考点：ACID 数据库事务 (Database Transaction)
// ==========================================
try {
    // 开启事务：接下来的所有 SQL 操作，要么全部成功，要么全部撤销！
    $conn->begin_transaction();

    // 步骤 A：生成一个主配置单 (Insert into saved_builds)
    $build_name = "My Dream Rig - " . date('M d, Y'); 
    $stmt_build = $conn->prepare("INSERT INTO saved_builds (customer_id, build_name, total_price) VALUES (?, ?, ?)");
    $stmt_build->bind_param("isd", $customer_id, $build_name, $total_price);
    $stmt_build->execute();
    
    // 获取刚才刚插入的那个配置单的 ID！(非常重要)
    $build_id = $conn->insert_id;
    $stmt_build->close();

    // 步骤 B：把购物车里的零件，一个个挂在这个配置单下面 (Insert into build_items)
    $stmt_item = $conn->prepare("INSERT INTO build_items (build_id, product_id, quantity) VALUES (?, ?, 1)");
    
    foreach ($cart as $category_id => $part) {
        $product_id = $part['product_id'];
        $stmt_item->bind_param("ii", $build_id, $product_id);
        $stmt_item->execute();
    }
    $stmt_item->close();

    // 步骤 C：确认提交事务 (Commit)
    $conn->commit();

    // 存档成功后，跳转到个人中心看结果
    echo "<script>alert('Build saved successfully! Welcome to your Armory.'); window.location.href='profile.php';</script>";
    exit();

} catch (Exception $e) {
    // 🚨 如果中间任何一步报错（比如断网、数据库炸了），立刻回滚！(Rollback)
    $conn->rollback();
    die("Database Error: Saved failed. Everything has been rolled back. Error: " . $e->getMessage());
}
?>