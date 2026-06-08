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

if ($package_id <= 0) {
    header("Location: manage_packages.php");
    exit();
}

// 🌟 1. 抓取套餐原本的資料
$stmt = $conn->prepare("SELECT * FROM packages WHERE package_id = ?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$pkg = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$pkg) { header("Location: manage_packages.php"); exit(); }

// 🌟 2. 抓取這個套餐「目前包含」了哪些零件
$current_components = [];
$stmt_items = $conn->prepare("SELECT product_id FROM package_items WHERE package_id = ?");
$stmt_items->bind_param("i", $package_id);
$stmt_items->execute();
$res_items = $stmt_items->get_result();
while($row = $res_items->fetch_assoc()){
    $current_components[] = $row['product_id'];
}
$stmt_items->close();

// 🌟 3. 抓取全庫零件字典
$components_by_category = [];
$sql_prod = "SELECT p.product_id, p.product_name, p.price, c.category_name 
             FROM products p JOIN categories c ON p.category_id = c.category_id 
             ORDER BY c.category_id ASC, p.price DESC";
$res_prod = $conn->query($sql_prod);
while($row = $res_prod->fetch_assoc()){
    $components_by_category[$row['category_name']][] = $row;
}

// 🌟 4. 處理更新邏輯 (ACID Transaction + 實體檔案上傳)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $package_name = trim($_POST['package_name']);
    $description = trim($_POST['description']);
    $target_persona = trim($_POST['target_persona']);
    $stock_status = trim($_POST['stock_status']);
    
    $score_gamer = intval($_POST['score_gamer']);
    $score_creator = intval($_POST['score_creator']);
    $score_student = intval($_POST['score_student']);
    $score_enthusiast = intval($_POST['score_enthusiast']);

    // 🌟 預設保留資料庫裡原有的舊圖片路徑
    $image_url = $pkg['image_url']; 
    $upload_ok = true;

    // 如果管理員有選取「新圖片」才進行本地存檔
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image_file']['tmp_name'];
        $file_extension = strtolower(pathinfo($_FILES["image_file"]["name"], PATHINFO_EXTENSION));
        $valid_extensions = array("jpg", "jpeg", "png", "gif", "webp");
        
        if (!in_array($file_extension, $valid_extensions)) {
            $error = "Error: Invalid image format. Only JPG, PNG, GIF, WEBP are allowed.";
            $upload_ok = false;
        } else {
            // 生成獨一無二的檔名，移入 image/ 資料夾
            $new_filename = uniqid("pkg_") . "." . $file_extension;
            $target_dir = "image/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $target_file)) {
                $image_url = $target_file;
            } else {
                $error = "Error: Failed to save image to server folder.";
                $upload_ok = false;
            }
        }
    }

    if ($upload_ok && empty($error)) {
        $conn->begin_transaction();
        try {
            $update_pkg = $conn->prepare("UPDATE packages SET package_name=?, description=?, image_url=?, target_persona=?, stock_status=?, score_gamer=?, score_creator=?, score_student=?, score_enthusiast=? WHERE package_id=?");
            $update_pkg->bind_param("sssssiiiii", $package_name, $description, $image_url, $target_persona, $stock_status, $score_gamer, $score_creator, $score_student, $score_enthusiast, $package_id);
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
            header("Location: manage_packages.php?msg=updated");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Transaction Failed: " . $e->getMessage();
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
</head>
<body>
    <div class="admin-container">
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-shield-alt"></i> GridCity Admin</h3>
                <p style="color:#555; font-size:11px; font-family:'JetBrains Mono';">Unified Architecture v4.0</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php" <?php if(basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php') echo 'class="active"'; ?>>Dashboard</a></li>
                <?php 
                $sidebar_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
                if (strtolower($sidebar_role) === 'superadmin'): 
                ?>
                    <li><a href="manage_staff.php" style="color: var(--accent-warning);" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_staff.php') echo 'class="active"'; ?>><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                    <li><a href="manage_users.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_users.php') echo 'class="active"'; ?>>Manage Customers</a></li>
                <?php endif; ?>
                <li><a href="manage_categories.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_categories.php') echo 'class="active"'; ?>>Categories</a></li>
                <li><a href="manage_products.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_products.php') echo 'class="active"'; ?>>Products</a></li> 
                <li><a href="manage_packages.php" class="active"><i class="fas fa-layer-group"></i> Packages</a></li>
                <li><a href="manage_orders.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_orders.php') echo 'class="active"'; ?>>Orders</a></li>
                <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 style="color: #a855f7; margin: 0;"><i class="fas fa-wrench"></i> Configure Blueprint: #<?php echo $package_id; ?></h2>
                <a href="manage_packages.php" class="btn-action" style="color: #888; border-color: #555; text-decoration:none;">&larr; Abort</a>
            </header>

            <?php if ($error): ?>
                <div style="background: rgba(255,77,77,0.1); color: #ff4d4d; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" style="background: rgba(0,0,0,0.5); padding: 30px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <div style="background: rgba(168,85,247,0.05); padding: 20px; border-radius: 8px; border: 1px solid rgba(168,85,247,0.2); margin-bottom: 30px;">
                    <h3 style="color: #a855f7; margin-top: 0; margin-bottom: 10px;"><i class="fas fa-microchip"></i> Package Configuration Matrix</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                        <?php foreach ($components_by_category as $cat_name => $products): ?>
                            <div>
                                <label style="display: block; color: #a855f7; font-size: 12px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase;"><?php echo htmlspecialchars($cat_name); ?></label>
                                <select name="components[]" class="form-control" style="width: 100%; padding: 10px; font-size: 13px; background: rgba(0,0,0,0.6);">
                                    <option value="">-- Skip / Remove --</option>
                                    <?php foreach ($products as $p): 
                                        $is_selected = in_array($p['product_id'], $current_components) ? "selected" : "";
                                    ?>
                                        <option value="<?php echo $p['product_id']; ?>" <?php echo $is_selected; ?>><?php echo htmlspecialchars($p['product_name']); ?> (+RM <?php echo number_format($p['price'], 2); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label>Package Name</label>
                        <input type="text" name="package_name" class="form-control" value="<?php echo htmlspecialchars($pkg['package_name']); ?>" required style="width: 100%;">
                    </div>
                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label>Description & Marketing Pitch</label>
                        <textarea name="description" class="form-control" rows="3" required style="width: 100%;"><?php echo htmlspecialchars($pkg['description']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Target Persona</label>
                        <input type="text" name="target_persona" class="form-control" value="<?php echo htmlspecialchars($pkg['target_persona']); ?>" style="width: 100%;">
                    </div>
                    <div class="form-group">
                        <label>Stock Status</label>
                        <select name="stock_status" class="form-control" style="width: 100%;">
                            <option value="Available" <?php if($pkg['stock_status']=='Available') echo 'selected';?>>Available</option>
                            <option value="Pre-order" <?php if($pkg['stock_status']=='Pre-order') echo 'selected';?>>Pre-order</option>
                            <option value="Out of Stock" <?php if($pkg['stock_status']=='Out of Stock') echo 'selected';?>>Out of Stock</option>
                        </select>
                    </div>

                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label style="color: #00e676;"><i class="fas fa-image"></i> Update Photo (Local Secure Upload)</label>
                        <input type="file" name="image_file" accept="image/*" class="form-control" style="width: 100%; padding: 10px; background: rgba(0,230,118,0.05); color: #fff; border: 1px dashed rgba(0,230,118,0.4); cursor: pointer;">
                        <p style="color: #888; font-size: 11px; margin-top: 5px;">* Leave empty to keep current image.</p>
                    </div>

                    <div class="form-group full-width" style="grid-column: 1 / -1; margin-top: 10px;">
                        <label style="color: #a855f7;"><i class="fas fa-brain"></i> AI Recommendation Radar (Scores 0-10)</label>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <div>
                                <label style="font-size: 12px; color: var(--text-muted);">Gamer Score</label>
                                <input type="number" min="0" max="10" name="score_gamer" class="form-control" value="<?php echo $pkg['score_gamer']; ?>" style="width: 100%;">
                            </div>
                            <div>
                                <label style="font-size: 12px; color: var(--text-muted);">Creator Score</label>
                                <input type="number" min="0" max="10" name="score_creator" class="form-control" value="<?php echo $pkg['score_creator']; ?>" style="width: 100%;">
                            </div>
                            <div>
                                <label style="font-size: 12px; color: var(--text-muted);">Student Score</label>
                                <input type="number" min="0" max="10" name="score_student" class="form-control" value="<?php echo $pkg['score_student']; ?>" style="width: 100%;">
                            </div>
                            <div>
                                <label style="font-size: 12px; color: var(--text-muted);">Enthusiast Score</label>
                                <input type="number" min="0" max="10" name="score_enthusiast" class="form-control" value="<?php echo $pkg['score_enthusiast']; ?>" style="width: 100%;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group full-width" style="margin-top: 30px;">
                    <button type="submit" style="width: 100%; background: linear-gradient(135deg, #a855f7, #00f2fe); color: #fff; border: none; padding: 15px; border-radius: 8px; font-weight: bold; font-size: 16px; cursor: pointer; transition: 0.3s;">
                        <i class="fas fa-sync-alt"></i> Update Blueprint & Real-time Price
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>