<?php
session_start();
require_once 'config.php';

// 1. 權限與邊界防護
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['pc_build'])) {
    header("Location: builder.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$action = $_POST['process_action'] ?? 'save'; // 決定是 'save' 還是 'checkout'
$build_name = trim($conn->real_escape_string($_POST['build_name']));

if (empty($build_name)) {
    $build_name = "Custom Rig (" . date('M d, Y') . ")";
}

try {
    $conn->begin_transaction();

    // ==========================================
    // 🛡️ 後端真理水合引擎 (Hydration Engine)
    // 杜絕讀取 Session 中殘留的死數據，強制從數據庫抓取實時價格
    // ==========================================
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
        throw new Exception("Security Alert: No valid components found in your payload.");
    }

    // ==========================================
    // 💾 統一寫入裝機庫 (Single Source of Truth)
    // ==========================================
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

    // ==========================================
    // 🧠 核心黑科技 V2.0：基於花銷比例的防偏見追踪 (Customer DNA)
    // ==========================================
    $add_gamer = 0; 
    $add_creator = 0; 
    $add_student = 0;

    $gpu_ratio = $total_price > 0 ? ($gpu_spend / $total_price) : 0;
    $cpu_ratio = $total_price > 0 ? ($cpu_spend / $total_price) : 0;

    if ($gpu_ratio >= 0.35) {
        $add_gamer = 5; $add_creator = 1; 
    } elseif ($cpu_ratio >= 0.25 || $ram_spend >= 600) {
        $add_creator = 5; $add_gamer = 2; $add_student = 1;
    } else {
        $add_student = 5; $add_creator = 2; $add_gamer = 1;
    }

    $stmt_dna = $conn->prepare("UPDATE customers SET pref_gamer = pref_gamer + ?, pref_creator = pref_creator + ?, pref_student = pref_student + ? WHERE customer_id = ?");
    $stmt_dna->bind_param("iiii", $add_gamer, $add_creator, $add_student, $customer_id);
    $stmt_dna->execute();
    $stmt_dna->close();

    // ==========================================
    // 🚦 業務路由分流 (Routing based on Action)
    // ==========================================
    if ($action === 'checkout') {
        // 加入購物車
        $stmt_cart = $conn->prepare("INSERT INTO shopping_cart (customer_id, pc_build, quantity) VALUES (?, ?, 1)");
        $stmt_cart->bind_param("ii", $customer_id, $build_id);
        $stmt_cart->execute();
        $stmt_cart->close();

        // 結帳動作必須清除 Session，防止重複提交
        unset($_SESSION['pc_build']); 
        
        $conn->commit();
        header("Location: cart.php");
        exit();
        
    } else {
        // 單純儲存草稿
        $conn->commit();
        $_SESSION['success_msg'] = "Blueprint '{$build_name}' successfully secured to your armory.";
        header("Location: builder.php");
        exit();
    }

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_msg'] = "Failed to process blueprint. " . $e->getMessage();
    header("Location: builder.php");
    exit();
}
?>