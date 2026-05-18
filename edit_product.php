<?php
session_start();
require_once 'config.php'; 

if (!isset($_SESSION['role']) || (strtolower($_SESSION['role']) !== 'admin' && strtolower($_SESSION['role']) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

$message = "";
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql_get = "SELECT * FROM products WHERE product_id = ?";
$stmt_get = $conn->prepare($sql_get);
$stmt_get->bind_param("i", $product_id);
$stmt_get->execute();
$product = $stmt_get->get_result()->fetch_assoc();
$stmt_get->close();

if (!$product) { die("Product not found!"); }

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    
    $name = trim($_POST['product_name']);
    $category_id = intval($_POST['category']); 
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock']);
    $specs = trim($_POST['specs']);
    $description = trim($_POST['description'] ?? '');

    $image_url = $_POST['existing_image']; 
    $upload_ok = true;
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['product_image']['tmp_name'];
        $file_size = $_FILES['product_image']['size'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($mime_type, $allowed_mimes)) {
            $message = "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(255,77,77,0.3);'>⚠️ Upload Denied: Invalid image format!</div>";
            $upload_ok = false;
        } elseif ($file_size > 5 * 1024 * 1024) { 
            $message = "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(255,77,77,0.3);'>⚠️ Upload Denied: Max 5MB allowed.</div>";
            $upload_ok = false;
        } else {
            $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) $ext = 'jpg'; 
            $new_filename = uniqid('prod_') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $target_file)) {
                $image_url = $target_file; 
            } else {
                $message = "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(255,77,77,0.3);'>⚠️ System Error: Failed to save image.</div>";
                $upload_ok = false;
            }
        }
    }

    if ($upload_ok) {
        $stmt = $conn->prepare("UPDATE products SET product_name=?, category_id=?, price=?, stock_quantity=?, specifications=?, description=?, image_url=? WHERE product_id=?");
        $stmt->bind_param("sidisssi", $name, $category_id, $price, $stock_quantity, $specs, $description, $image_url, $product_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Product updated successfully!";
            header("Location: manage_products.php");
            exit();
        } else {
            $message = "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(255,77,77,0.3);'>⚠️ Database Error.</div>";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product - GridCity PC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lora:wght@400;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

    <div class="sidebar">
        <h2>
            <img src="image/Admin_dashboard_logo.jpg" alt="ROG Logo" class="sidebar-logo">
            <span>GridCity PC</span>
        </h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_products.php">Products</a></li> 
            <li><a href="manage_categories.php">Categories</a></li>
            <li><a href="manage_orders.php">Orders</a></li>
            <li><a href="admin_builder.php">Build System</a></li>
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                <li><a href="manage_staff.php" style="color: var(--accent-warning);"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                <li><a href="manage_users.php">Manage Customers</a></li>
            <?php endif; ?>
            
            <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
        </ul>
    </div>

    <div class="main-content">
        
        <div class="header-top">
            <div>
                <h1>Edit Product #<?php echo $product_id; ?></h1>
            </div>
            <a href="manage_products.php" class="btn-back">&larr; Back to Products</a>
        </div>

        <?php echo $message; ?>

        <div class="content-card">
            <form method="POST" action="" enctype="multipart/form-data">
                
                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($product['image_url']); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="product_name" class="form-control" required value="<?php echo htmlspecialchars($product['product_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" class="form-control" required>
                            <option value="">Select Category...</option>
                            <option value="1" <?php if($product['category_id'] == 1) echo 'selected'; ?>>Processors</option>
                            <option value="2" <?php if($product['category_id'] == 2) echo 'selected'; ?>>Graphics Cards</option>
                            <option value="3" <?php if($product['category_id'] == 3) echo 'selected'; ?>>Motherboards</option>
                            <option value="4" <?php if($product['category_id'] == 4) echo 'selected'; ?>>RAM</option>
                            <option value="5" <?php if($product['category_id'] == 5) echo 'selected'; ?>>Storage</option>
                            <option value="6" <?php if($product['category_id'] == 6) echo 'selected'; ?>>Power Supply</option>
                            <option value="7" <?php if($product['category_id'] == 7) echo 'selected'; ?>>Case</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Price (RM)</label>
                        <input type="number" step="0.01" name="price" class="form-control" required value="<?php echo $product['price']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock" class="form-control" required value="<?php echo $product['stock_quantity']; ?>">
                    </div>

                    <div class="form-group full-width">
                        <label>Product Image <span>(Leave blank to keep existing photo)</span></label>
                        <div class="file-input-wrapper">
                            <div style="margin-bottom: 15px;">
                                <img src="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'https://via.placeholder.com/70'; ?>" alt="Current Image" style="height: 80px; border-radius: 6px; border: 1px solid var(--border-color);">
                            </div>
                            <input type="file" name="product_image" accept="image/*" style="cursor: pointer; color: var(--text-muted);">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Specifications <span style="color: #ff4d4d;">*</span></label>
                        <textarea name="specs" class="form-control" rows="3" required><?php echo htmlspecialchars($product['specifications']); ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label>Description <span>(Optional)</span></label>
                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <button type="submit" name="update_product" class="btn-submit">💾 Save Changes</button>
                    </div>
                </div>
            </form>
        </div>

    </div>
</body>
</html>