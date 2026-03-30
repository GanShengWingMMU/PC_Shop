<?php
session_start();
include 'db_connect.php'; 

// 安全门禁：确保是管理员
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$message = "";

// ==========================================
// 1. 处理表单提交 (Add New Product & Photo)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    
    $name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $category_id = intval($_POST['category']); 
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock']);
    $specs = mysqli_real_escape_string($conn, $_POST['specs']);
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : '';

    // --- 图片上传逻辑开始 ---
    $image_url = 'default-product.png'; // 如果没上传图片，给一个默认的占位图
    
    // 检查是否有上传文件，并且没有错误
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "uploads/"; // 图片要保存的文件夹
        
        // 如果 uploads 文件夹不存在，PHP 会自动帮你建一个
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // 给图片重新命名 (加上时间戳防止名字重复覆盖)
        $file_name = time() . "_" . basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . $file_name;

        // 把图片从临时区域移动到 uploads 文件夹
        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $image_url = $target_file; // 如果成功，就把路径存进数据库
        }
    }
    // --- 图片上传逻辑结束 ---

    try {
        // 💡 SQL 加入了 image_url
        $sql_insert = "INSERT INTO products (name, category_id, price, stock_quantity, specs, description, image_url) 
                       VALUES ('$name', '$category_id', '$price', '$stock_quantity', '$specs', '$description', '$image_url')";
                       
        if (mysqli_query($conn, $sql_insert)) {
            $message = "<div class='success-msg'>✅ Product and Photo added successfully!</div>";
        } else {
            throw new Exception("Error inserting data: " . mysqli_error($conn));
        }
    } catch (Exception $e) {
        $message = "<div class='error-msg'>⚠️ Database Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products - PC Shop Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lora:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* ====== 基础样式 ====== */
        body { margin: 0; font-family: 'Inter', sans-serif; background-color: #f4f4f9; display: flex; height: 100vh; }
        .sidebar { width: 250px; background-color: #2c2c2c; color: white; display: flex; flex-direction: column; }
        .sidebar h2 { display: flex; align-items: center; justify-content: center; gap: 10px; font-family: 'Inter', serif; color: #8a2be2; padding: 20px 0; border-bottom: 1px solid #444; margin: 0; }
        .sidebar-logo { width: 50px; height: auto; background-color: transparent; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar ul li a { display: block; padding: 15px 20px; color: #ddd; text-decoration: none; border-bottom: 1px solid #3a3a3a; transition: 0.3s; }
        .sidebar ul li a:hover, .sidebar ul li a.active { background-color: #8a2be2; color: white; font-weight: bold; }
        .logout-btn { margin-top: auto; background-color: #1a1a1a !important; }

        .main-content { flex: 1; padding: 20px 40px; overflow-y: auto; }
        
        /* 🌟 新增：顶部标题和返回按钮的排版 */
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header-top h1 { font-family: 'Inter', serif; color: #333; margin: 0; }
        
        /* 返回按钮的基础样式 */
        .btn-back { 
            color: #8a2be2; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 14px; 
            border: 2px solid #8a2be2; 
            padding: 8px 15px; 
            border-radius: 6px; 
            transition: 0.2s; /* 统一改为 0.2 秒顺滑动画 */
        }
        
        /* 🌟 修改这里：鼠标悬浮时的动态效果（上浮 + 阴影） */
        .btn-back:hover { 
            background-color: #8a2be2; 
            color: white; 
            transform: translateY(-2px); /* 向上浮动 2px */
            box-shadow: 0 4px 8px rgba(138, 43, 226, 0.3); /* 统一的紫色发光阴影 */
        }
        
        /* 🌟 修改这里：鼠标悬浮时的动态效果（上浮 + 阴影） */
/* ====== 大提交按钮的基础样式 ====== */
        .btn-submit { 
            padding: 12px 20px; 
            background-color: #8a2be2; /* 默认紫色背景 */
            color: white;              /* 默认白色文字 */
            border: 2px solid #8a2be2; /* 💡 必须加这个边框，不然悬浮时大小会跳动 */
            border-radius: 4px; 
            font-weight: bold; 
            cursor: pointer; 
            font-size: 16px; 
            width: 100%; 
            transition: all 0.3s ease; 
        }

        /* ====== 大提交按钮的悬浮 (Hover) 效果 ====== */
        .btn-submit:hover { 
            background-color: white;   /* 1. 背景变成白色 */
            color: #8a2be2;            /* 2. 💡 确保字体变成显眼的紫色！ */
            transform: translateY(-2px); /* 3. 微微上浮 */
            box-shadow: 0 4px 8px rgba(138, 43, 226, 0.3); /* 4. 紫色发光阴影 */
        }

        
        .content-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); margin-bottom: 30px; border-top: 4px solid #8a2be2; }
        .content-card h3 { margin-top: 0; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }

        /* ====== 表单样式 ====== */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group.full-width { grid-column: span 2; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-family: 'Inter', serif; }
        .btn-submit { padding: 12px 20px; background-color: #8a2be2; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 16px; width: 100%; }
        .btn-submit:hover { opacity: 1.2; }

        /* 图片上传按钮美化 */
        .file-input-wrapper { background: #f9f9fc; border: 2px dashed #ccc; padding: 20px; text-align: center; border-radius: 4px; }
        
        .success-msg { background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .error-msg { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; }

        /* ====== 包含图片的表格样式 ====== */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { padding: 15px 12px; text-align: left; border-bottom: 1px solid #eee; vertical-align: top; }
        table th { background-color: #f9f9f9; color: #333; }
        
        /* 商品图片和文字排版 */
        .product-info-cell { display: flex; gap: 15px; align-items: flex-start; }
        .product-thumb { width: 70px; height: 70px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; background: #fff; }
        .product-title { margin: 0 0 5px 0; font-size: 15px; color: #2c2c2c; }
        .product-desc { margin: 0; font-size: 12px; color: #777; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        .btn-edit { background-color: #f39c12; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 13px; }
        .btn-delete { background-color: #e74c3c; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 13px; margin-left: 5px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>
            <img src="photo/Admin_dashboard_logo.jpg" alt="ROG Logo" class="sidebar-logo">
            <span>PC SHOP</span>
        </h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_products.php" class="active">Products</a></li> 
            <li><a href="manage_categories.php">Categories</a></li>
            <li><a href="manage_orders.php">Orders</a></li>
            <li><a href="manage_users.php">Users</a></li>
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