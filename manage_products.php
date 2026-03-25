<?php
session_start();
include 'db_connect.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$message = ""; // 初始化信息变量，防止报错

// 处理删除 (Delete) 逻辑 ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // 执行删除 SQL
    $sql_delete = "DELETE FROM products WHERE product_id = $delete_id";
    if (mysqli_query($conn, $sql_delete)) {
        // 删除成功后刷新页面，并带上 deleted 提示
        header("Location: manage_products.php?deleted=1");
        exit();
    } else {
        $message = "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb;'>⚠️ Failed to delete product.</div>";
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
        body { margin: 0; font-family: 'Inter', sans-serif; background-color: #f4f4f9; display: flex; height: 100vh; }
        .sidebar { width: 250px; background-color: #2c2c2c; color: white; display: flex; flex-direction: column; }
        .sidebar h2 { display: flex; align-items: center; justify-content: center; gap: 10px; font-family: 'Inter', serif; color: #8a2be2; padding: 20px 0; border-bottom: 1px solid #444; margin: 0; }
        .sidebar-logo { width: 50px; height: auto; background-color: transparent; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar ul li a { display: block; padding: 15px 20px; color: #ddd; text-decoration: none; border-bottom: 1px solid #3a3a3a; transition: 0.3s; }
        .sidebar ul li a:hover, .sidebar ul li a.active { background-color: #8a2be2; color: white; font-weight: bold; }
        .logout-btn { margin-top: auto; background-color: #1a1a1a !important; }

        .main-content { flex: 1; padding: 20px 40px; overflow-y: auto; }
        
        /* 顶部排版：左边标题，右边添加按钮 */
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header-top h1 { font-family: 'Inter', serif; color: #333; margin: 0; }
     
        /* + Add New Product 按钮基础样式 */
        .btn-add-new { 
            color: #8a2be2; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 14px; 
            border: 2px solid #8a2be2; 
            padding: 8px 15px; 
            border-radius: 6px; 
            transition: 0.2s; /* 统一改为 0.2 秒顺滑动画 */
        }
        /* 🌟 悬浮效果：背景变白，文字变紫 */
        .btn-add-new:hover { 
            background-color: #8a2be2; 
            color: white; 
            transform: translateY(-2px); /* 向上浮动 2px */
            box-shadow: 0 4px 8px rgba(138, 43, 226, 0.3); /* 统一的紫色发光阴影 */
        }

        .content-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); border-top: 4px solid #8a2be2; }

        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        table th, table td { padding: 15px 12px; text-align: left; border-bottom: 1px solid #eee; vertical-align: top; }
        table th { background-color: #f9f9f9; color: #333; }
        
        .product-info-cell { display: flex; gap: 15px; align-items: flex-start; }
        .product-thumb { width: 70px; height: 70px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; background: #fff; }
        .product-title { margin: 0 0 5px 0; font-size: 15px; color: #2c2c2c; }
        .product-desc { margin: 0; font-size: 12px; color: #777; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        .btn-edit { background-color: #f39c12; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 13px; }
        .btn-delete { background-color: #e74c3c; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 13px; margin-left: 5px; cursor: pointer; }
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
            <h1>Product Inventory</h1>
            <a href="add_product.php" class="btn-add-new">+ Add New Product</a>
        </div>

        <?php 
        // 🌟 新增：处理所有的提示信息 (增加、修改、删除)
        if(!empty($message)) echo $message;
        
        if(isset($_GET['success']) && $_GET['success'] == 1) {
            echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb;'>✅ Product added successfully!</div>";
        }
        if(isset($_GET['updated']) && $_GET['updated'] == 1) {
            echo "<div style='background-color: #cce5ff; color: #004085; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #b8daff;'>🔄 Product updated successfully!</div>";
        }
        if(isset($_GET['deleted']) && $_GET['deleted'] == 1) {
            echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb;'>🗑️ Product deleted successfully!</div>";
        }
        ?>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="45%">Product Details</th> 
                        <th width="15%">Category ID</th>
                        <th width="15%">Price</th>
                        <th width="10%">Stock</th>
                        <th width="10%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $sql_products = "SELECT * FROM products ORDER BY product_id DESC"; 
                        $res_products = mysqli_query($conn, $sql_products);

                        if ($res_products && mysqli_num_rows($res_products) > 0) {
                            while($row = mysqli_fetch_assoc($res_products)) {
                                echo "<tr>";
                                echo "<td>#" . $row['product_id'] . "</td>";
                                
                                $img_src = !empty($row['image_url']) ? $row['image_url'] : 'https://via.placeholder.com/70x70?text=No+Image';
                                $desc_text = !empty($row['description']) ? $row['description'] : 'No description available.';
                                
                                echo "<td>
                                        <div class='product-info-cell'>
                                            <img src='{$img_src}' class='product-thumb'>
                                            <div>
                                                <h4 class='product-title'>" . htmlspecialchars($row['name']) . "</h4>
                                                <p class='product-desc'>" . htmlspecialchars($desc_text) . "</p>
                                            </div>
                                        </div>
                                      </td>";

                                echo "<td>Category: " . $row['category_id'] . "</td>";
                                echo "<td><strong style='color:#8a2be2;'>RM " . number_format($row['price'], 2) . "</strong></td>";
                                
                                $stock = $row['stock_quantity'];
                                $stock_color = ($stock <= 2) ? "color: red; font-weight: bold;" : "color: green;";
                                echo "<td style='{$stock_color}'>" . $stock . "</td>";
                                
                                // 🌟 重点修改区：加入了真实的 ID 和 JavaScript 删除弹窗确认
                                echo "<td>
                                        <div style='display:flex; gap:5px;'>
                                            <a href='edit_product.php?id=" . $row['product_id'] . "' class='btn-edit'>Edit</a>
                                            <a href='manage_products.php?delete_id=" . $row['product_id'] . "' class='btn-delete' onclick='return confirm(\"⚠️ Are you SURE you want to delete this product? This action cannot be undone!\");'>Del</a>
                                        </div>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding: 30px; color:#888;'>No products available yet. Click '+ Add New Product' to start.</td></tr>";
                        }
                    } catch (Exception $e) {
                        echo "<tr><td colspan='6' style='color:red;'>Database Error.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>