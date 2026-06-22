<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['customer_id']) || !isset($_GET['id'])) {
    header("Location: profile.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$build_id = intval($_GET['id']); 


$check_stmt = $conn->prepare("SELECT pc_build FROM saved_builds WHERE pc_build = ? AND customer_id = ?");
$check_stmt->bind_param("ii", $build_id, $customer_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$check_stmt->close();

if ($result->num_rows > 0) {
  
    $conn->begin_transaction();
    try {
        
        $delete_items = $conn->prepare("DELETE FROM build_items WHERE pc_build = ?");
        $delete_items->bind_param("i", $build_id);
        $delete_items->execute();
        $delete_items->close();

        
        $delete_build = $conn->prepare("DELETE FROM saved_builds WHERE pc_build = ?");
        $delete_build->bind_param("i", $build_id);
        $delete_build->execute();
        $delete_build->close();

        
        $conn->commit();
        $_SESSION['success_msg'] = "Blueprint successfully wiped from the database.";
    } catch (Exception $e) {
     
        $conn->rollback();
        $_SESSION['error_msg'] = "System Error: Failed to delete blueprint cleanly.";
    }
} else {
    $_SESSION['error_msg'] = "Security Alert: Unauthorized deletion attempt intercepted.";
}

header("Location: profile.php");
exit();
?>