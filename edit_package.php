<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

$error = "";
$package_id = isset($_GET['package_id']) ? intval($_GET['package_id']) : 0;
if ($package_id <= 0) { header("Location: manage_packages.php"); exit(); }

// 1. 抓取套餐資料
$stmt = $conn->prepare("SELECT * FROM packages WHERE package_id = ?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$pkg = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$pkg) { header("Location: manage_packages.php"); exit(); }

// 2. 抓取目前包含的零件
$current_components = [];
$stmt_items = $conn->prepare("SELECT product_id FROM package_items WHERE package_id = ?");
$stmt_items->bind_param("i", $package_id);
$stmt_items->execute();
$res_items = $stmt_items->get_result();
while($row = $res_items->fetch_assoc()){
    $current_components[] = $row['product_id'];
}
$stmt_items->close();

// 3. 抓取全庫零件字典
$components_by_category = [];
$sql_prod = "SELECT p.product_id, p.product_name, p.price, c.category_name 
             FROM products p JOIN categories c ON p.category_id = c.category_id 
             ORDER BY c.category_id ASC, p.price DESC";
$res_prod = $conn->query($sql_prod);
while($row = $res_prod->fetch_assoc()){
    $components_by_category[$row['category_name']][] = $row;
}

// 4. 處理更新邏輯
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $package_name = trim($_POST['package_name']);
    $description = trim($_POST['description']);
    $target_persona = trim($_POST['target_persona']);
    $stock_status = trim($_POST['stock_status']);

    $image_url = $pkg['image_url']; 
    $upload_ok = true;

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES["image_file"]["name"], PATHINFO_EXTENSION));
        $valid_extensions = array("jpg", "jpeg", "png", "gif", "webp");
        
        if (!in_array($ext, $valid_extensions)) {
            $error = "Error: Invalid image format.";
            $upload_ok = false;
        } else {
            $new_filename = uniqid("pkg_") . "." . $ext;
            $target_dir = "image/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            $target_file = $target_dir . $new_filename;
            if (move_uploaded_file($file_tmp, $target_file)) { $image_url = $target_file; } 
            else { $error = "Error: Failed to save image."; $upload_ok = false; }
        }
    }

    if ($upload_ok && empty($error)) {
        // 🌟 接收 4 个雷达分数
        $score_gamer = intval($_POST['score_gamer'] ?? 0);
        $score_creator = intval($_POST['score_creator'] ?? 0);
        $score_student = intval($_POST['score_student'] ?? 0);
        $score_enthusiast = intval($_POST['score_enthusiast'] ?? 0);

        // 自動計算更新後的所有選中零件總價格
        $total_price = 0;
        if (isset($_POST['components']) && is_array($_POST['components'])) {
            foreach ($_POST['components'] as $prod_id) {
                if (!empty($prod_id)) {
                    $pid = intval($prod_id);
                    $res = $conn->query("SELECT price FROM products WHERE product_id = $pid");
                    if ($r = $res->fetch_assoc()) {
                        $total_price += floatval($r['price']);
                    }
                }
            }
        }

        $conn->begin_transaction();
        try {
            // 🌟 包含 4 个 score 字段的更新
            $update_query = "UPDATE packages SET package_name=?, description=?, price=?, image_url=?, target_persona=?, stock_status=?, score_gamer=?, score_creator=?, score_student=?, score_enthusiast=? WHERE package_id=?";
            $update_pkg = $conn->prepare($update_query);
            
            if (!$update_pkg) {
                throw new Exception("SQL Error: " . $conn->error);
            }

            $update_pkg->bind_param("ssdsssiiiii", $package_name, $description, $total_price, $image_url, $target_persona, $stock_status, $score_gamer, $score_creator, $score_student, $score_enthusiast, $package_id);
            $update_pkg->execute();
            $update_pkg->close();

            $conn->query("DELETE FROM package_items WHERE package_id = $package_id");
            
            if (isset($_POST['components']) && is_array($_POST['components'])) {
                $insert_item = $conn->prepare("INSERT INTO package_items (package_id, product_id, quantity) VALUES (?, ?, 1)");
                foreach ($_POST['components'] as $prod_id) {
                    if (!empty($prod_id)) { 
                        $pid = intval($prod_id);
                        $insert_item->bind_param("ii", $package_id, $pid);
                        $insert_item->execute();
                    }
                }
                $insert_item->close();
            }

            $conn->commit();

            // 记录动作到 Security Logs
            $log_admin_id = $_SESSION['admin_id'];
            $log_username = $_SESSION['admin_username'];
            $log_role = $_SESSION['admin_role'];
            $log_ip = $_SERVER['REMOTE_ADDR'];
            if ($log_ip == '::1') { $log_ip = '127.0.0.1'; }
            $action_event = "Modified Blueprint ID: " . $package_id; 
            @$conn->query("INSERT INTO admin_logs (admin_id, username, role, action_event, ip_address) VALUES ('$log_admin_id', '$log_username', '$log_role', '$action_event', '$log_ip')");

            header("Location: manage_packages.php?msg=updated");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Update Failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Configure Blueprint - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        html, body {
            height: auto; 
            min-height: 100vh;
            margin: 0;
            overflow-y: auto; 
            background-color: var(--bg-main); 
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh; 
            width: 100%;
        }

        .admin-sidebar {
            position: fixed; 
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 100;
        }

        .admin-content {
            margin-left: 250px; 
            flex: 1;
            padding: 30px !important;
            padding-bottom: 120px !important; 
            min-height: 100vh;
            box-sizing: border-box;
        }
        
        .blueprint-form {
            background: rgba(0,0,0,0.5); 
            padding: 30px; 
            border-radius: 12px; 
            border: 1px solid rgba(255,255,255,0.05);
            overflow: visible; 
            display: block;
        }
    </style>
</head>
<body>
    <div class="admin-container">
         <?php include 'admin_sidebar.php'; ?>

        <div class="admin-content">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 style="color: #a855f7; margin: 0;"><i class="fas fa-wrench"></i> Configure Blueprint: #<?php echo $package_id; ?></h2>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="background: rgba(168,85,247,0.1); border: 1px solid rgba(168,85,247,0.3); padding: 8px 15px; border-radius: 6px; color: #a855f7; font-weight: bold; font-size: 18px;">
                        Total: <span id="live-price">RM <?php echo number_format($pkg['price'], 2); ?></span>
                    </div>
                    <a href="manage_packages.php" class="btn-action" style="color: #888; border-color: #555; text-decoration:none;">&larr; Abort</a>
                </div>
            </header>

            <?php if ($error): ?>
                <div style="background: rgba(255,77,77,0.1); border: 1px solid #ff4d4d; color: #ff4d4d; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="blueprint-form">
                
                <div style="background: rgba(168,85,247,0.05); padding: 20px; border-radius: 8px; border: 1px solid rgba(168,85,247,0.2); margin-bottom: 30px;">
                    <h3 style="color: #a855f7; margin-top: 0; margin-bottom: 15px;"><i class="fas fa-microchip"></i> Package Configuration Matrix</h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                        <?php foreach ($components_by_category as $cat_name => $products): ?>
                            <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.03);">
                                <label style="display: block; color: #a855f7; font-size: 12px; font-weight: bold; margin-bottom: 8px; text-transform: uppercase;">
                                    <i class="fas fa-caret-right" style="margin-right: 5px; opacity: 0.5;"></i> <?php echo htmlspecialchars($cat_name); ?>
                                </label>
                                <select name="components[]" class="form-control component-select" style="width: 100%; padding: 10px; font-size: 13px; background: rgba(0,0,0,0.8); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                                    <option value="" data-price="0">-- Skip / Remove --</option>
                                    <?php foreach ($products as $p): 
                                        $is_selected = in_array($p['product_id'], $current_components) ? "selected" : "";
                                    ?>
                                        <option value="<?php echo $p['product_id']; ?>" data-price="<?php echo $p['price']; ?>" <?php echo $is_selected; ?>>
                                            <?php echo htmlspecialchars($p['product_name']); ?> (+RM <?php echo number_format($p['price'], 2); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label>Package Name</label>
                        <input type="text" name="package_name" class="form-control" value="<?php echo htmlspecialchars($pkg['package_name']); ?>" required style="width: 100%; background: rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                    </div>
                    
                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label>Description & Marketing Pitch</label>
                        <textarea name="description" class="form-control" rows="4" required style="width: 100%; background: rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.1); color: #fff; resize: vertical;"><?php echo htmlspecialchars($pkg['description']); ?></textarea>
                    </div>
                    
<div class="form-group">
    <label>Target Persona</label>
    <select name="target_persona" class="form-control" style="width: 100%; background: rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
        <option value="Gamer" <?php if($pkg['target_persona'] == 'Gamer') echo 'selected'; ?>>Gamer</option>
        <option value="Creator" <?php if($pkg['target_persona'] == 'Creator') echo 'selected'; ?>>Creator</option>
        <option value="Student" <?php if($pkg['target_persona'] == 'Student') echo 'selected'; ?>>Student / Dev</option>
        <option value="Enthusiast" <?php if($pkg['target_persona'] == 'Enthusiast') echo 'selected'; ?>>Enthusiast</option>
    </select>
</div>
                    
                    <div class="form-group">
                        <label>Stock Status</label>
                        <select name="stock_status" class="form-control" style="width: 100%; background: rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                            <option value="Available" <?php if($pkg['stock_status']=='Available') echo 'selected';?>>Available</option>
                            <option value="Pre-order" <?php if($pkg['stock_status']=='Pre-order') echo 'selected';?>>Pre-order</option>
                            <option value="Out of Stock" <?php if($pkg['stock_status']=='Out of Stock') echo 'selected';?>>Out of Stock</option>
                        </select>
                    </div>

                    <div class="form-group full-width" style="grid-column: 1 / -1; background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); margin-top: 10px;">
                        <label style="color: #a855f7; font-weight: bold; font-size: 13px; margin-bottom: 12px; display: block;"><i class="fas fa-chart-pie"></i> DNA Radar Scores (1-10)</label>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                            <div>
                                <label style="font-size: 11px; color: #cbd5e1;">Gamer Score</label>
                                <input type="number" name="score_gamer" min="0" max="10" class="form-control" value="<?php echo $pkg['score_gamer'] ?? 0; ?>" required>
                            </div>
                            <div>
                                <label style="font-size: 11px; color: #cbd5e1;">Creator Score</label>
                                <input type="number" name="score_creator" min="0" max="10" class="form-control" value="<?php echo $pkg['score_creator'] ?? 0; ?>" required>
                            </div>
                            <div>
                                <label style="font-size: 11px; color: #cbd5e1;">Student Score</label>
                                <input type="number" name="score_student" min="0" max="10" class="form-control" value="<?php echo $pkg['score_student'] ?? 0; ?>" required>
                            </div>
                            <div>
                                <label style="font-size: 11px; color: #cbd5e1;">Enthusiast Score</label>
                                <input type="number" name="score_enthusiast" min="0" max="10" class="form-control" value="<?php echo $pkg['score_enthusiast'] ?? 0; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label style="color: #00e676;"><i class="fas fa-image"></i> Update Photo</label>
                        <input type="file" name="image_file" accept="image/*" class="form-control" style="width: 100%; padding: 12px; background: rgba(0,230,118,0.05); color: #fff; border: 1px dashed rgba(0,230,118,0.4); cursor: pointer;">
                    </div>
                </div>

                <div class="form-group full-width" style="margin-top: 40px; margin-bottom: 20px;">
                    <button type="submit" style="width: 100%; background: linear-gradient(135deg, #a855f7, #00f2fe); color: #fff; border: none; padding: 18px; border-radius: 8px; font-weight: 900; font-size: 16px; cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 10px 20px rgba(0,242,254,0.2);">
                        <i class="fas fa-sync-alt" style="margin-right: 8px;"></i> Execute Blueprint Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('.component-select').forEach(select => {
                let selectedOption = select.options[select.selectedIndex];
                if(selectedOption.value !== "") {
                    total += parseFloat(selectedOption.getAttribute('data-price') || 0);
                }
            });
            document.getElementById('live-price').innerText = 'RM ' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        document.querySelectorAll('.component-select').forEach(select => {
            select.addEventListener('change', calculateTotal);
        });

        window.addEventListener('load', calculateTotal);
    </script>
</body>
</html>