<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php"); exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $name = trim($_POST['product_name']);
    $category_id = intval($_POST['category_id']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock']);
    $specs = trim($_POST['specs']);
    $description = trim($_POST['description'] ?? '');

    $image_url = 'image/placeholder_pc.png'; 
    $upload_ok = true;
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['product_image']['tmp_name'];
        $file_size = $_FILES['product_image']['size'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type_check = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
        
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($mime_type_check, $allowed_mimes) || $file_size > 5 * 1024 * 1024) {
            $message = "<div class='alert-error'>⚠️ Upload Denied: Invalid format or exceeds 5MB.</div>";
            $upload_ok = false;
        } else {
            $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
            $new_filename = 'prod_' . uniqid() . '.' . $ext;
            $upload_dir = 'image/';
            $target_file = $upload_dir . $new_filename;
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            if (move_uploaded_file($file_tmp, $target_file)) { $image_url = $target_file; }
            else { $message = "<div class='alert-error'>⚠️ System Error: Failed to save image.</div>"; $upload_ok = false; }
        }
    }

    if ($upload_ok) {
        // 彻底移除 AI 相关字段的 SQL
        $sql = "INSERT INTO products (product_name, category_id, price, stock_quantity, specifications, description, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sidssss", $name, $category_id, $price, $stock_quantity, $specs, $description, $image_url);
        if ($stmt->execute()) {
            $message = "<div class='alert-success'>✅ Product added successfully!</div>";
        } else {
            $message = "<div class='alert-error'>⚠️ Database Error: " . htmlspecialchars($stmt->error) . "</div>";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .alert-success { color:#00e676; background:rgba(0,230,118,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(0,230,118,0.3); }
        .alert-error { color:#ff4d4d; background:rgba(255,77,77,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(255,77,77,0.3); }
    </style>
</head>
<body>
    <div class="admin-container">
        <nav class="admin-sidebar">
            <div class="sidebar-header"><h3><i class="fas fa-shield-alt"></i> GridCity Admin</h3></div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <?php 
                $sidebar_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
                if (strtolower($sidebar_role) === 'superadmin'): 
                ?>
                    <li><a href="manage_staff.php" style="color: var(--accent-warning);"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                    <li><a href="manage_users.php">Manage Customers</a></li>
                <?php endif; ?>
                <li><a href="manage_categories.php">Categories</a></li>
                <li><a href="manage_products.php" class="active">Products</a></li> 
                <li><a href="manage_packages.php">Packages</a></li>
                <li><a href="manage_orders.php">Orders</a></li>
                <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 style="color: #00f2fe; margin: 0;"><i class="fas fa-plus-circle"></i> Inject New Hardware</h2>
                <a href="manage_products.php" class="btn-action" style="color: #888; border-color: #555; text-decoration:none;">&larr; Back to Registry</a>
            </header>

            <?php echo $message; ?>

            <form method="POST" enctype="multipart/form-data" style="background: rgba(0,0,0,0.5); padding: 30px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label>Product Name *</label>
                        <input type="text" name="product_name" class="form-control" required style="width: 100%;">
                    </div>
                    <div class="form-group">
                        <label style="color: var(--text-muted); font-weight: bold; margin-bottom: 8px; display:block;">Category *</label>
                        <select name="category_id" class="form-control" required style="background: var(--bg-main); color: var(--text-main); border: 1px solid var(--border-color); padding: 10px; border-radius: 6px;">
                            <option value="" disabled selected>-- Select a Category --</option>
                            <?php
                            $cat_query = "SELECT category_id, category_name FROM categories ORDER BY category_name ASC";
                            $cat_result = mysqli_query($conn, $cat_query);
                            while ($cat_row = mysqli_fetch_assoc($cat_result)) {
                                echo "<option value='{$cat_row['category_id']}'>" . htmlspecialchars($cat_row['category_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Price (RM) *</label>
                        <input type="number" step="0.01" name="price" class="form-control" required style="width: 100%;">
                    </div>
                    <div class="form-group">
                        <label>Stock Quantity *</label>
                        <input type="number" name="stock" class="form-control" required style="width: 100%;">
                    </div>
                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label style="color: #00e676;"><i class="fas fa-image"></i> Product Image (Max 5MB) *</label>
                        <input type="file" name="product_image" class="form-control" accept=".jpg,.jpeg,.png,.webp" style="width: 100%; padding: 10px; background: rgba(0,230,118,0.05); border: 1px dashed rgba(0,230,118,0.4); cursor: pointer;">
                    </div>
                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label>Specifications *</label>
                        <textarea name="specs" class="form-control" rows="2" required style="width: 100%;"></textarea>
                    </div>
                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label>Marketing Description</label>
                        <textarea name="description" class="form-control" rows="2" style="width: 100%;"></textarea>
                    </div>
                </div>
                <button type="submit" name="add_product" style="width: 100%; margin-top: 20px; background: linear-gradient(135deg, #00f2fe, #4facfe); color: #000; border: none; padding: 15px; border-radius: 8px; font-weight: 900; font-size: 16px; cursor: pointer; transition: 0.3s;">
                    <i class="fas fa-upload"></i> Upload Node to Matrix
                </button>
            </form>
        </div>
    </div>
</body>
</html>