<?php
session_start();
require_once 'config.php';

// 1. 门卫拦截：必须登录，且必须传入了蓝图 ID
if (!isset($_SESSION['customer_id']) || !isset($_GET['id'])) {
    header("Location: profile.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$build_id = intval($_GET['id']); // 强制转换为整数，防注入

// 2. A+ 级安全校验：这真的是这个用户的蓝图吗？(防止越权删除)
$check_stmt = $conn->prepare("SELECT pc_build FROM saved_builds WHERE pc_build = ? AND customer_id = ?");
$check_stmt->bind_param("ii", $build_id, $customer_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$check_stmt->close();

if ($result->num_rows > 0) {
    // 🌟 核心升级：开启 ACID 事务，确保子表和主表同生共死
    $conn->begin_transaction();
    try {
        // 第一步：清空子表零件
        $delete_items = $conn->prepare("DELETE FROM build_items WHERE pc_build = ?");
        $delete_items->bind_param("i", $build_id);
        $delete_items->execute();
        $delete_items->close();

        // 第二步：销毁主表蓝图
        $delete_build = $conn->prepare("DELETE FROM saved_builds WHERE pc_build = ?");
        $delete_build->bind_param("i", $build_id);
        $delete_build->execute();
        $delete_build->close();

        // 完美执行，提交事务
        $conn->commit();
        $_SESSION['success_msg'] = "Blueprint successfully wiped from the database.";
    } catch (Exception $e) {
        // 发生意外，回滚撤销
        $conn->rollback();
        $_SESSION['error_msg'] = "System Error: Failed to delete blueprint cleanly.";
    }
} else {
    $_SESSION['error_msg'] = "Security Alert: Unauthorized deletion attempt intercepted.";
}

header("Location: profile.php");
exit();
?>