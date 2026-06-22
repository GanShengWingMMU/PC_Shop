<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['pc_build'])) {
    header("Location: builder.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$action = $_POST['process_action'] ?? 'save';
$build_name = trim($conn->real_escape_string($_POST['build_name']));

if (empty($build_name)) {
    $build_name = "Custom Rig (" . date('M d, Y') . ")";
}

try {
    $conn->begin_transaction();


    $product_ids = array_values($_SESSION['pc_build']);
    $id_list = implode(',', array_map('intval', $product_ids));
    
    $sql = "SELECT product_id, category_id, price FROM products WHERE product_id IN ($id_list)";
    $res = $conn->query($sql);
    
    $total_price = 0;
    $gpu_spend = 0; 
    $cpu_spend = 0; 
    $ram_spend = 0;
    $valid_parts = [];

    while ($row = $res->fetch_assoc()) {
        $cid = $row['category_id'];
        $price = (float)$row['price'];
        
        $total_price += $price;
        $valid_parts[] = $row['product_id'];

        if ($cid == 4) $gpu_spend = $price;
        if ($cid == 1) $cpu_spend = $price;
        if ($cid == 3) $ram_spend = $price;
    }

    if (empty($valid_parts)) {
        throw new Exception("[SECURITY FAULT] Invalid payload detected. No structural components found.");


    $stmt_build = $conn->prepare("INSERT INTO saved_builds (customer_id, build_name, total_price) VALUES (?, ?, ?)");
    $stmt_build->bind_param("isd", $customer_id, $build_name, $total_price);
    $stmt_build->execute();
    $build_id = $stmt_build->insert_id;
    $stmt_build->close();

    $stmt_items = $conn->prepare("INSERT INTO build_items (pc_build, product_id, quantity) VALUES (?, ?, 1)");
    foreach ($valid_parts as $pid) {
        $stmt_items->bind_param("ii", $build_id, $pid);
        $stmt_items->execute();
    }
    $stmt_items->close();

    $add_gamer = 0; 
    $add_creator = 0; 
    $add_student = 0;
    $add_enthusiast = 0; 

    $gpu_ratio = $total_price > 0 ? ($gpu_spend / $total_price) : 0;
    $cpu_ratio = $total_price > 0 ? ($cpu_spend / $total_price) : 0;

  
    if ($total_price >= 8000) {
        $add_enthusiast = 5; $add_gamer = 2; $add_creator = 2;
    } elseif ($gpu_ratio >= 0.35) {
        $add_gamer = 5; $add_creator = 1; 
    } elseif ($cpu_ratio >= 0.25 || $ram_spend >= 600) {
        $add_creator = 5; $add_gamer = 2; $add_student = 1;
    } else {
        $add_student = 5; $add_creator = 2; $add_gamer = 1;
    }


    $stmt_dna = $conn->prepare("UPDATE customers SET pref_gamer = pref_gamer + ?, pref_creator = pref_creator + ?, pref_student = pref_student + ?, pref_enthusiast = pref_enthusiast + ? WHERE customer_id = ?");
    $stmt_dna->bind_param("iiiii", $add_gamer, $add_creator, $add_student, $add_enthusiast, $customer_id);


    if ($action === 'checkout') {

        $stmt_cart = $conn->prepare("INSERT INTO shopping_cart (customer_id, pc_build, quantity) VALUES (?, ?, 1)");
        $stmt_cart->bind_param("ii", $customer_id, $build_id);
        $stmt_cart->execute();
        $stmt_cart->close();


        unset($_SESSION['pc_build']); 
        
        $conn->commit();
        header("Location: cart.php");
        exit();
        
    } else {

        $conn->commit();
        $_SESSION['success_msg'] = "[SUCCESS] Blueprint '{$build_name}' has been encrypted and secured in your armory.";
        header("Location: builder.php");
        exit();
    }

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_msg'] = "[SYSTEM ERROR] Blueprint compilation failed: " . $e->getMessage();
    header("Location: builder.php");
    exit();
}
?>