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

if ($result->num_rows > 0) {
    // 权限确认无误，执行销毁协议
    // 注意：如果你的数据库没有设置 ON DELETE CASCADE (级联删除)，我们需要先删子表，再删主表
    $delete_items = $conn->prepare("DELETE FROM build_items WHERE pc_build = ?");
    $delete_items->bind_param("i", $build_id);
    $delete_items->execute();
    $delete_items->close();

    $delete_build = $conn->prepare("DELETE FROM saved_builds WHERE pc_build = ?");
    $delete_build->bind_param("i", $build_id);
    $delete_build->execute();
    $delete_build->close();
}
$check_stmt->close();

// 3. 销毁完成，无缝跳回指挥中心
header("Location: profile.php");
exit();
?>