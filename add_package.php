<?php
session_start();

// 🌟 智慧相容資料庫連線
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

// 🌟 安全准入
$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

$error = "";

// ==========================================
// 1. 抓取所有零件，並依照分類分組 (給下拉選單使用)
// ==========================================
$components_by_category = [];
$sql_prod = "SELECT p.product_id, p.product_name, p.price, c.category_name 
             FROM products p 
             JOIN categories c ON p.category_id = c.category_id 
             ORDER BY c.category_id ASC, p.price DESC";
$res_prod = $conn->query($sql_prod);
if ($res_prod) {
    while($row = $res_prod->fetch_assoc()){
        $components_by_category[$row['category_name']][] = $row;
    }
}

// ==========================================
// 2. 處理表單提交 (ACID Transaction 關聯寫入 + 實體圖片上傳)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $package_name = trim($_POST['package_name']);
    $description = trim($_POST['description']);
    $target_persona = trim($_POST['target_persona']);
    $stock_status = trim($_POST['stock_status']);
    
    // 🌟 核心：回歸最穩定的實體檔案上傳
    $image_url = "image/placeholder_pc.png"; // 預設圖片
    $upload_ok = true;
    
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES["image_file"]["name"], PATHINFO_EXTENSION));
        $valid_extensions = array("jpg", "jpeg", "png", "gif", "webp");
        
        if (!in_array($ext, $valid_extensions)) {
            $error = "Error: Invalid image format. Only JPG, PNG, GIF, WEBP are allowed.";
            $upload_ok = false;
        } else {
            // 生成獨一無二的檔名防止覆蓋，存入本地 image/ 資料夾
            $new_filename = uniqid("pkg_") . "." . $ext;
            $target_dir = "image/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $target_file)) {
                $image_url = $target_file; // 資料庫只存路徑
            } else {
                $error = "Error: Failed to save image to local folder.";
                $upload_ok = false;
            }
        }
    }
    
    $score_gamer = intval($_POST['score_gamer']);
    $score_creator = intval($_POST['score_creator']);
    $score_student = intval($_POST['score_student']);
    $score_enthusiast = intval($_POST['score_enthusiast']);

    if ($upload_ok && empty($error)) {
        // 防呆：攔截非法的 AI 分數
        if ($score_gamer < 0 || $score_gamer > 10 || $score_creator < 0 || $score_creator > 10 || $score_student < 0 || $score_student > 10 || $score_enthusiast < 0 || $score_enthusiast > 10) {
            $error = "System Error: AI Scores must be strictly between 0 and 10.";
        } else {
            // 🌟 開啟資料庫事務
            $conn->begin_transaction();
            
            try {
                $base_price = 0;
                $insert_pkg = $conn->prepare("INSERT INTO packages (package_name, description, price, image_url, target_persona, stock_status, score_gamer, score_creator, score_student, score_enthusiast) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insert_pkg->bind_param("ssdsssiiii", $package_name, $description, $base_price, $image_url, $target_persona, $stock_status, $score_gamer, $score_creator, $score_student, $score_enthusiast);
                $insert_pkg->execute();
                
                $new_package_id = $conn->insert_id;
                $insert_pkg->close();

                if (isset($_POST['components']) && is_array($_POST['components'])) {
                    $insert_item = $conn->prepare("INSERT INTO package_items (package_id, product_id, quantity) VALUES (?, ?, 1)");
                    foreach ($_POST['components'] as $prod_id) {
                        if (!empty($prod_id)) {
                            $pid = intval($prod_id);
                            $insert_item->bind_param("ii", $new_package_id, $pid);
                            $insert_item->execute();
                        }
                    }
                    $insert_item->close();
                }

                $conn->commit();
                header("Location: manage_packages.php?msg=added");
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                $error = "Database Error: Could not save package. " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Build Package - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>
    <div class="admin-container">
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-shield-alt"></i> GridCity Admin</h3>
                <p style="color: #888; font-size: 12px; margin-top: 5px;">Build Architecture V4.0</p>
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
                <li><a href="manage_packages.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_packages.php') echo 'class="active"'; ?>>Packages</a></li>
                <li><a href="manage_orders.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_orders.php') echo 'class="active"'; ?>>Orders</a></li>
                <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 style="color: #00f2fe; margin: 0;"><i class="fas fa-hammer"></i> Forge New Package</h2>
                <a href="manage_packages.php" class="btn-action" style="color: #888; border-color: #555; text-decoration:none;">&larr; Back to Packages</a>
            </header>

            <?php if ($error): ?>
                <div style="background: rgba(255,77,77,0.1); border: 1px solid rgba(255,77,77,0.3); color: #ff4d4d; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" style="background: rgba(0,0,0,0.5); padding: 30px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <div style="background: rgba(0,242,254,0.05); padding: 20px; border-radius: 8px; border: 1px solid rgba(0,242,254,0.2); margin-bottom: 30px;">
                    <h3 style="color: #00f2fe; margin-top: 0; margin-bottom: 10px;"><i class="fas fa-microchip"></i> Select Package Components</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                        <?php foreach ($components_by_category as $cat_name => $products): ?>
                            <div>
                                <label style="display: block; color: var(--text-muted); font-size: 12px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase;"><?php echo htmlspecialchars($cat_name); ?></label>
                                <select name="components[]" class="form-control" style="width: 100%; padding: 10px; font-size: 13px; background: rgba(0,0,0,0.6);">
                                    <option value="">-- Skip / None --</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?php echo $p['product_id']; ?>"><?php echo htmlspecialchars($p['product_name']); ?> (+RM <?php echo number_format($p['price'], 2); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label>Package Name</label>
                        <input type="text" name="package_name" class="form-control" required style="width: 100%;">
                    </div>
                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label>Description & Marketing Pitch</label>
                        <textarea name="description" class="form-control" rows="3" required style="width: 100%;"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Target Persona</label>
                        <input type="text" name="target_persona" class="form-control" style="width: 100%;">
                    </div>
                    <div class="form-group">
                        <label>Stock Status</label>
                        <select name="stock_status" class="form-control" style="width: 100%;">
                            <option value="Available">Available</option>
                            <option value="Pre-order">Pre-order</option>
                            <option value="Out of Stock">Out of Stock</option>
                        </select>
                    </div>

                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label style="color: #00e676;"><i class="fas fa-image"></i> Insert Photo (Upload Package Image)</label>
                        <input type="file" name="image_file" accept="image/*" class="form-control" style="width: 100%; padding: 10px; background: rgba(0,230,118,0.05); color: #fff; border: 1px dashed rgba(0,230,118,0.4); cursor: pointer;">
                        <p style="color: #888; font-size: 11px; margin-top: 5px;">* Saved securely in local folder. Formats: JPG, PNG, GIF, WEBP.</p>
                    </div>

                    <div class="form-group full-width" style="grid-column: 1 / -1; margin-top: 10px;">
                        <label style="color: #a855f7;"><i class="fas fa-brain"></i> AI Recommendation Radar (Scores 0-10)</label>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <div>
                                <label style="font-size: 12px; color: var(--text-muted);">Gamer Score</label>
                                <input type="number" min="0" max="10" name="score_gamer" class="form-control" value="0" style="width: 100%;">
                            </div>
                            <div>
                                <label style="font-size: 12px; color: var(--text-muted);">Creator Score</label>
                                <input type="number" min="0" max="10" name="score_creator" class="form-control" value="0" style="width: 100%;">
                            </div>
                            <div>
                                <label style="font-size: 12px; color: var(--text-muted);">Student Score</label>
                                <input type="number" min="0" max="10" name="score_student" class="form-control" value="0" style="width: 100%;">
                            </div>
                            <div>
                                <label style="font-size: 12px; color: var(--text-muted);">Enthusiast Score</label>
                                <input type="number" min="0" max="10" name="score_enthusiast" class="form-control" value="0" style="width: 100%;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group full-width" style="margin-top: 30px;">
                    <button type="submit" style="width: 100%; background: linear-gradient(135deg, #8a2be2, #00f2fe); color: #fff; border: none; padding: 15px; border-radius: 8px; font-weight: bold; font-size: 16px; cursor: pointer; transition: 0.3s;">
                        <i class="fas fa-save"></i> Forge Package & Link Components
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>