<?php
session_start();
require_once 'config.php'; // 统一使用 config.php

// 权限拦截：必须是 admin 或 superadmin
if (!isset($_SESSION['role']) || (strtolower($_SESSION['role']) !== 'admin' && strtolower($_SESSION['role']) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    
    $name = trim($_POST['product_name']);
    $category_id = intval($_POST['category']); 
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock']);
    $specs = trim($_POST['specs']);
    $description = trim($_POST['description'] ?? '');

    $image_url = 'image/placeholder_pc.png'; 
    $upload_ok = true;
    
    // 🌟 企业级防木马上传引擎
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['product_image']['tmp_name'];
        $file_size = $_FILES['product_image']['size'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
        
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($mime_type, $allowed_mimes)) {
            $message = "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(255,77,77,0.3);'>⚠️ Upload Denied: Only JPG, PNG, WEBP allowed!</div>";
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
        $stmt = $conn->prepare("INSERT INTO products (product_name, category_id, price, stock_quantity, specifications, description, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sidisss", $name, $category_id, $price, $stock_quantity, $specs, $description, $image_url);
        
        if ($stmt->execute()) {
            $message = "<div style='color: #00e676; background: rgba(0,230,118,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(0,230,118,0.3);'>✅ Product added successfully!</div>";
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
    <title>Manage Products - GridCity PC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lora:wght@400;700&display=swap" rel="stylesheet">
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
            
            <li><a href="manage_packages.php">Packages</a></li>
            
            <li><a href="manage_categories.php">Categories</a></li>
            <li><a href="manage_orders.php">Orders</a></li>
            <li><a href="admin_builder.php">Build System</a></li>
            
            <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'superadmin'): ?>
                <li><a href="manage_staff.php" style="color: var(--accent-warning);"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                <li><a href="manage_users.php">Manage Customers</a></li>
            <?php endif; ?>
            
            <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
        </ul>
    </div>

    <div class="main-content">
        
        <div class="header-top">
            <h1>Add New Product</h1>
            <a href="manage_products.php" class="btn-back">&larr; Back to Products List</a>
        </div>

        <?php echo $message; ?>

        <div class="content-card">
            <h3>+ Product Details</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="product_name" class="form-control" required placeholder="e.g. ASUS ROG Strix RTX 4090">
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" class="form-control" required>
                            <option value="">Select Category...</option>
                            <option value="1">Processors</option>
                            <option value="2">Graphics Cards</option>
                            <option value="3">Motherboards</option>
                            <option value="4">RAM</option>
                            <option value="5">Storage</option>
                            <option value="6">Power Supply</option>
                            <option value="7">Case</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Price (RM)</label>
                        <input type="number" step="0.01" name="price" class="form-control" required placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock" class="form-control" required placeholder="0">
                    </div>

                    <div class="form-group full-width">
                        <label>Product Image <span style="font-weight: normal; color: #888; font-size: 13px;">(Format: JPG, PNG)</span></label>
                        <div class="file-input-wrapper">
                            <input type="file" name="product_image" accept="image/*" style="cursor: pointer;">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Specifications <span style="color: red;">*</span></label>
                        <textarea name="specs" class="form-control" rows="3" required placeholder="e.g. Intel Core i9, 32GB DDR5 RAM, 1TB NVMe SSD..."></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label>Description <span style="font-weight: normal; color: #888; font-size: 13px;">(Optional)</span></label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Enter extra product details, warranty info, or marketing text here..."></textarea>
                    </div>

                    <div class="form-group full-width">
                        <button type="submit" name="add_product" class="btn-submit">Upload & Add Product</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="content-card">
            <h3>Product Inventory Details</h3>
            <table>
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="45%">Product Details</th> <th width="15%">Category ID</th>
                        <th width="15%">Price</th>
                        <th width="10%">Stock</th>
                        <th width="10%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $sql_products = "SELECT * FROM products ORDER BY product_id DESC LIMIT 10"; 
                        $res_products = mysqli_query($conn, $sql_products);

                        if ($res_products && mysqli_num_rows($res_products) > 0) {
                            while($row = mysqli_fetch_assoc($res_products)) {
                                echo "<tr>";
                                echo "<td>#" . (isset($row['product_id']) ? $row['product_id'] : '') . "</td>";
                                
                                // 🌟 重点：在这里组合显示图片 + 名字 + 简介
                                $img_src = !empty($row['image_url']) ? $row['image_url'] : 'https://via.placeholder.com/70x70?text=No+Image';
                                $desc_text = !empty($row['description']) ? $row['description'] : 'No description available for this product.';
                                
                                echo "<td>
                                        <div class='product-info-cell'>
                                            <img src='{$img_src}' class='product-thumb' alt='Product Image'>
                                            <div>
                                                <h4 class='product-title'>" . htmlspecialchars($row['name']) . "</h4>
                                                <p class='product-desc'>" . htmlspecialchars($desc_text) . "</p>
                                            </div>
                                        </div>
                                      </td>";

                                echo "<td>Cat: " . (isset($row['category_id']) ? $row['category_id'] : 'N/A') . "</td>";
                                echo "<td><strong style='color:#8a2be2;'>RM " . (isset($row['price']) ? number_format($row['price'], 2) : '0.00') . "</strong></td>";
                                
                                // 库存警告颜色
                                $stock = isset($row['stock_quantity']) ? $row['stock_quantity'] : 0;
                                $stock_color = ($stock <= 2) ? "color: red; font-weight: bold;" : "color: green;";
                                echo "<td style='{$stock_color}'>" . $stock . "</td>";
                                
                                echo "<td>
                                        <div style='display:flex; gap:5px;'>
                                            <a href='#' class='btn-edit'>Edit</a>
                                            <a href='#' class='btn-delete'>Del</a>
                                        </div>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding: 30px; color:#888;'>No products added yet. Add your first product with photo above!</td></tr>";
                        }
                    } catch (Exception $e) {
                        echo "<tr><td colspan='6' style='color:red;'>Database Error: {$e->getMessage()}</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>
