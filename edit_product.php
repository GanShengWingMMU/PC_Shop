<?php
session_start();
include 'db_connect.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$message = "";
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ==========================================
// 1. 获取当前商品原本的数据，用来填入表格
// ==========================================
$sql_get = "SELECT * FROM products WHERE product_id = $product_id";
$res_get = mysqli_query($conn, $sql_get);
$product = mysqli_fetch_assoc($res_get);

if (!$product) {
    echo "Product not found!";
    exit();
}

// ==========================================
// 2. 处理表单提交 (Update Logic)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    
    $name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $category_id = intval($_POST['category']); 
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock']);
    $specs = mysqli_real_escape_string($conn, $_POST['specs']);
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : '';

    // 默认使用原本的旧图片
    $image_url = $_POST['existing_image']; 
    
    // 如果管理员上传了新图片，就替换掉旧的
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "uploads/"; 
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_name = time() . "_" . basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $image_url = $target_file; 
        }
    }

    try {
        // 💡 使用 UPDATE 语句而不是 INSERT
        $sql_update = "UPDATE products SET 
                        name = '$name', 
                        category_id = '$category_id', 
                        price = '$price', 
                        stock_quantity = '$stock_quantity', 
                        specs = '$specs', 
                        description = '$description', 
                        image_url = '$image_url' 
                       WHERE product_id = $product_id";
                       
        if (mysqli_query($conn, $sql_update)) {
            // 更新成功，跳回列表并显示蓝色提示
            header("Location: manage_products.php?updated=1");
            exit();
        } else {
            throw new Exception("Error updating data.");
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
    <title>Edit Product - PC Shop Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lora:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* 直接复用 add_product.php 的所有优美样式 */
        body { margin: 0; font-family: 'Inter', sans-serif; background-color: #f4f4f9; display: flex; height: 100vh; }
        .sidebar { width: 250px; background-color: #2c2c2c; color: white; display: flex; flex-direction: column; }
        .sidebar h2 { display: flex; align-items: center; justify-content: center; gap: 10px; font-family: 'Inter', serif; color: #8a2be2; padding: 20px 0; border-bottom: 1px solid #444; margin: 0; }
        .sidebar-logo { width: 50px; height: auto; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar ul li a { display: block; padding: 15px 20px; color: #ddd; text-decoration: none; border-bottom: 1px solid #3a3a3a; transition: 0.3s; }
        .sidebar ul li a:hover, .sidebar ul li a.active { background-color: #8a2be2; color: white; font-weight: bold; }
        .logout-btn { margin-top: auto; background-color: #1a1a1a !important; }

        .main-content { flex: 1; padding: 20px 40px; overflow-y: auto; }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header-top h1 { font-family: 'Inter', serif; color: #333; margin: 0; }
        
        .btn-back { background-color: #8a2be2; color: white; text-decoration: none; font-weight: bold; font-size: 14px; border: 2px solid #8a2be2; padding: 8px 15px; border-radius: 6px; transition: all 0.3s ease; }
        .btn-back:hover { background-color: white; color: #8a2be2; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(138,43,226,0.3); }

        .content-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); border-top: 4px solid #8a2be2; /* Edit页面用橘紫色边框区分 */ }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group.full-width { grid-column: span 2; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-family: 'Inter', serif; }
        
        .btn-submit { padding: 12px 20px; background-color: #8a2be2; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 16px; width: 100%; transition: 0.2s; }
        .btn-submit:hover { background-color: #8a2be2; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(243,156,18,0.3); }
        .file-input-wrapper { background: #f9f9fc; border: 2px dashed #ccc; padding: 20px; text-align: center; border-radius: 4px; }
        .error-msg { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
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
            <h1>Edit Product #<?php echo $product_id; ?></h1>
            <a href="manage_products.php" class="btn-back">&larr; Back to Products List</a>
        </div>

        <?php echo $message; ?>

        <div class="content-card">
            <form method="POST" action="" enctype="multipart/form-data">
                
                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($product['image_url']); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="product_name" class="form-control" required value="<?php echo htmlspecialchars($product['name']); ?>">
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
                        <label>Product Image <span style="font-weight: normal; color: #888; font-size: 13px;">(Leave blank to keep existing photo)</span></label>
                        <div class="file-input-wrapper">
                            <div style="margin-bottom: 10px;">
                                <img src="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'https://via.placeholder.com/70'; ?>" alt="Current Image" style="height: 60px; border-radius: 4px; border: 1px solid #ccc;">
                            </div>
                            <input type="file" name="product_image" accept="image/*" style="cursor: pointer;">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Specifications <span style="color: red;">*</span></label>
                        <textarea name="specs" class="form-control" rows="3" required><?php echo htmlspecialchars($product['specs']); ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label>Description <span style="font-weight: normal; color: #888; font-size: 13px;">(Optional)</span></label>
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