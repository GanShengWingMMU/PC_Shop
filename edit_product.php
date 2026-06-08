<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php"); exit();
}

$error = "";
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id <= 0) { header("Location: manage_products.php"); exit(); }

$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$prod) { header("Location: manage_products.php"); exit(); }

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    $name = trim($_POST['product_name']);
    $category_id = intval($_POST['category_id']); 
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock']);
    $specs = trim($_POST['specs']);
    $description = trim($_POST['description'] ?? '');

    $tdp_wattage = intval($_POST['tdp_wattage'] ?? 0);
    $socket_type = trim($_POST['socket_type'] ?? '');
    $ram_type = trim($_POST['ram_type'] ?? '');
    $performance_tier = intval($_POST['performance_tier'] ?? 1);

    // 🌟 回歸最穩定的實體檔案上傳！
    $image_url = $prod['image_url']; 
    $upload_ok = true;
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['product_image']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $valid_extensions = ["jpg", "jpeg", "png", "gif", "webp"];
        
        if (!in_array($ext, $valid_extensions)) {
            $error = "⚠️ Upload Denied: Invalid format. Only JPG, PNG, WEBP allowed.";
            $upload_ok = false;
        } else {
            // 生成隨機檔名，存入 image/ 資料夾
            $new_filename = uniqid('prod_') . '.' . $ext;
            $target_dir = "image/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $target_file)) {
                $image_url = $target_file; // 資料庫只存路徑 "image/prod_xxx.jpg"
            } else {
                $error = "⚠️ Failed to save image to folder.";
                $upload_ok = false;
            }
        }
    }

    if ($upload_ok) {
        $sql = "UPDATE products SET product_name=?, category_id=?, price=?, stock_quantity=?, specifications=?, description=?, image_url=?, tdp_wattage=?, socket_type=?, ram_type=?, performance_tier=? WHERE product_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sidisssissii", $name, $category_id, $price, $stock_quantity, $specs, $description, $image_url, $tdp_wattage, $socket_type, $ram_type, $performance_tier, $product_id);
        if ($stmt->execute()) {
            header("Location: manage_products.php?msg=updated");
            exit();
        } else {
            $error = "Database Error: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <li><a href="manage_products.php" class="active"><i class="fas fa-box"></i> Products</a></li> 
                <li><a href="manage_packages.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_packages.php') echo 'class="active"'; ?>>Packages</a></li>
                <li><a href="manage_orders.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_orders.php') echo 'class="active"'; ?>>Orders</a></li>
                
                <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 style="color: #00f2fe; margin: 0;"><i class="fas fa-edit"></i> Modify Hardware Node</h2>
                <a href="manage_products.php" class="btn-action" style="color: #888; border-color: #555; text-decoration:none;">&larr; Abort</a>
            </header>

            <?php if ($error) echo "<div style='color:#ff4d4d; background:rgba(255,77,77,0.1); padding:15px; border-radius:6px; margin-bottom:20px;'>$error</div>"; ?>

            <form method="POST" enctype="multipart/form-data" style="background: rgba(0,0,0,0.5); padding: 30px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label>Product Name</label>
                        <input type="text" name="product_name" class="form-control" value="<?php echo htmlspecialchars($prod['product_name']); ?>" required style="width: 100%;">
                    </div>
                    
                    <div class="form-group">
                        <label style="color: var(--text-muted); font-weight: bold; margin-bottom: 8px; display:block;">Category *</label>
                        <select name="category_id" class="form-control" required style="background: var(--bg-main); color: var(--text-main); border: 1px solid var(--border-color); padding: 10px; border-radius: 6px;">
                            <option value="" disabled>-- Select a Category --</option>
                            <?php
                            $cat_query = "SELECT category_id, category_name FROM categories ORDER BY category_name ASC";
                            $cat_result = mysqli_query($conn, $cat_query);
                            if ($cat_result && mysqli_num_rows($cat_result) > 0) {
                                while ($cat_row = mysqli_fetch_assoc($cat_result)) {
                                    $selected = (isset($prod['category_id']) && $prod['category_id'] == $cat_row['category_id']) ? 'selected' : '';
                                    echo "<option value='{$cat_row['category_id']}' {$selected}>" . htmlspecialchars($cat_row['category_name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Price (RM)</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $prod['price']; ?>" required style="width: 100%;">
                    </div>

                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock" class="form-control" value="<?php echo $prod['stock_quantity']; ?>" required style="width: 100%;">
                    </div>

                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label style="color: #00e676;"><i class="fas fa-image"></i> Update Photo</label>
                        <input type="file" name="product_image" accept=".jpg,.jpeg,.png,.webp" class="form-control" style="width: 100%; padding: 10px; background: rgba(0,230,118,0.05); color: #fff; border: 1px dashed rgba(0,230,118,0.4); cursor: pointer;">
                        <p style="color: #888; font-size: 11px; margin-top: 5px;">* Leave empty to keep current image. Saved securely in the local image folder.</p>
                    </div>

                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label>Specifications</label>
                        <textarea name="specs" class="form-control" rows="2" required style="width: 100%;"><?php echo htmlspecialchars($prod['specifications']); ?></textarea>
                    </div>

                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label>Marketing Description</label>
                        <textarea name="description" class="form-control" rows="2" style="width: 100%;"><?php echo htmlspecialchars($prod['description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div style="background: rgba(138, 43, 226, 0.1); padding: 20px; border-radius: 8px; border: 1px solid rgba(138, 43, 226, 0.3); margin-top: 30px; margin-bottom: 20px;">
                    <h3 style="color: #a855f7; margin-top: 0; margin-bottom: 10px;"><i class="fas fa-robot"></i> AI Builder Attributes</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label style="font-size: 13px; color: var(--text-muted);">TDP Wattage (W)</label>
                            <input type="number" name="tdp_wattage" class="form-control" value="<?php echo $prod['tdp_wattage']; ?>" style="width: 100%;">
                        </div>
                        <div>
                            <label style="font-size: 13px; color: var(--text-muted);">Socket Type</label>
                            <input type="text" name="socket_type" class="form-control" value="<?php echo htmlspecialchars($prod['socket_type'] ?? ''); ?>" style="width: 100%;">
                        </div>
                        <div>
                            <label style="font-size: 13px; color: var(--text-muted);">RAM Type</label>
                            <input type="text" name="ram_type" class="form-control" value="<?php echo htmlspecialchars($prod['ram_type'] ?? ''); ?>" style="width: 100%;">
                        </div>
                        <div>
                            <label style="font-size: 13px; color: var(--text-muted);">Performance Tier (1-10)</label>
                            <input type="number" min="1" max="10" name="performance_tier" class="form-control" value="<?php echo $prod['performance_tier']; ?>" style="width: 100%;">
                        </div>
                    </div>
                </div>

                <button type="submit" name="update_product" style="width: 100%; background: linear-gradient(135deg, #00f2fe, #4facfe); color: #000; border: none; padding: 15px; border-radius: 8px; font-weight: 900; font-size: 16px; cursor: pointer; transition: 0.3s;">
                    <i class="fas fa-sync"></i> Save & Re-align Data
                </button>
            </form>
        </div>
    </div>
</body>
</html>