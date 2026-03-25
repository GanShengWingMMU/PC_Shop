<?php
session_start();
include 'db_connect.php'; 

// 安全门禁：确保是管理员
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// 获取 URL 上的分类 ID 数组，如果没有选择，就是一个空数组
$selected_cat_ids = isset($_GET['category_ids']) ? $_GET['category_ids'] : [];

// 安全过滤：确保数组里的每一个值都是纯数字，防止 SQL 注入
$safe_cat_ids = array_map('intval', $selected_cat_ids);
// 移除里面的 0 或空值
$safe_cat_ids = array_filter($safe_cat_ids); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories - PC Shop Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lora:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* ====== 基础和侧边栏样式 ====== */
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

        /* ====== 多选分类卡片网格样式 ====== */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        /* 隐藏真实的 Checkbox */
        .cat-checkbox { display: none; }

        /* 把 Label 伪装成卡片 */
        .cat-card {
            background: white;
            padding: 30px 20px;
            border-radius: 8px;
            text-align: center;
            border-top: 4px solid #8a2be2;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
        }
        .cat-card h3 { margin: 0; font-size: 18px; color: #8a2be2; transition: 0.3s; }
        .cat-card p { margin: 10px 0 0 0; font-size: 13px; color: #777; transition: 0.3s; }
        
        /* 鼠标悬浮特效 */
        .cat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 12px rgba(138,43,226,0.2); }

        /* 🌟 核心魔法：当 Checkbox 被选中时，相邻的 .cat-card 改变样式！ */
        .cat-checkbox:checked + .cat-card {
            background-color: #8a2be2;
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(138,43,226,0.4);
            border-color: #fff; /* 边框变白增加对比 */
        }
        .cat-checkbox:checked + .cat-card h3, 
        .cat-checkbox:checked + .cat-card p {
            color: white;
        }

        /* Continue 按钮样式 */
        .btn-continue {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 30px;
            background-color: #8a2be2;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(138,43,226,0.3);
        }
        .btn-continue:hover { background-color: #7a1fd1; transform: translateY(-2px); }

        /* ====== 商品表格样式 ====== */
        .content-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); border-top: 4px solid #8a2be2; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        table th, table td { padding: 15px 12px; text-align: left; border-bottom: 1px solid #eee; vertical-align: top; }
        table th { background-color: #f9f9f9; color: #333; }
        
        .product-info-cell { display: flex; gap: 15px; align-items: flex-start; }
        .product-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; background: #fff; }
        .product-title { margin: 0 0 5px 0; font-size: 14px; color: #2c2c2c; }
        
        .btn-back { background-color: #8a2be2; color: white; text-decoration: none; font-weight: bold; font-size: 14px; border: 2px solid #8a2be2; padding: 8px 15px; border-radius: 6px; transition: all 0.3s ease; }
        .btn-back:hover { background-color: white; color: #8a2be2; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(138,43,226,0.3); }
        .btn-edit { background-color: #f39c12; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 13px; }
        
        .cat-badge { display: inline-block; padding: 3px 8px; background-color: #eee; border-radius: 4px; font-size: 12px; color: #555; margin-bottom: 5px; }
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
            <li><a href="manage_products.php">Products</a></li> 
            <li><a href="manage_categories.php" class="active">Categories</a></li>
            <li><a href="manage_orders.php">Orders</a></li>
            <li><a href="manage_users.php">Users</a></li>
            <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
        </ul>
    </div>

    <div class="main-content">

        <?php if (empty($safe_cat_ids)): ?>
            <div class="header-top">
                <h1>Select Categories</h1>
            </div>
            <p style="color: #666; margin-top: -10px; margin-bottom: 20px;">Select one or multiple categories below, then click Continue.</p>

            <form action="manage_categories.php" method="GET">
                <div class="category-grid">
                    <?php
                    $sql_cats = "SELECT * FROM categories ORDER BY category_id ASC";
                    $res_cats = mysqli_query($conn, $sql_cats);

                    if ($res_cats && mysqli_num_rows($res_cats) > 0) {
                        while($cat = mysqli_fetch_assoc($res_cats)) {
                            $cid = $cat['category_id'];
                            // 动态生成隐藏的 Checkbox 和可见的 Label 卡片
                            echo "<div>
                                    <input type='checkbox' name='category_ids[]' value='{$cid}' id='cat_{$cid}' class='cat-checkbox'>
                                    <label for='cat_{$cid}' class='cat-card'>
                                        <h3>{$cid}. " . htmlspecialchars($cat['category_name']) . "</h3>
                                        <p>" . htmlspecialchars($cat['description']) . "</p>
                                    </label>
                                  </div>";
                        }
                    } else {
                        echo "<p style='color:red;'>No categories found in database!</p>";
                    }
                    ?>
                </div>
                
                <button type="submit" class="btn-continue">Continue &rarr;</button>
            </form>

        <?php else: ?>
            <?php
            // 把数组变成逗号分隔的字符串，比如 "1,2,7"
            $ids_string = implode(',', $safe_cat_ids);

            // 拿到这些分类的名字，显示在标题上
            $cat_names = [];
            $cat_name_sql = "SELECT category_name FROM categories WHERE category_id IN ($ids_string)";
            $cat_name_res = mysqli_query($conn, $cat_name_sql);
            while($row = mysqli_fetch_assoc($cat_name_res)) {
                $cat_names[] = $row['category_name'];
            }
            $display_title = implode(' & ', $cat_names); // 把名字拼起来，比如 "Processors & Case"
            ?>

            <div class="header-top">
                <h1 style="font-size: 24px;">Products in: <span style="color: #8a2be2;"><?php echo htmlspecialchars($display_title); ?></span></h1>
                <a href="manage_categories.php" class="btn-back">&larr; Back to Selection</a>
            </div>

            <div class="content-card">
                <table>
                    <thead>
                        <tr>
                            <th width="10%">ID</th>
                            <th width="45%">Product Name</th> 
                            <th width="15%">Category</th>
                            <th width="15%">Price</th>
                            <th width="10%">Stock</th>
                            <th width="5%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 💡 重点：使用 IN() 语法来查询多个分类的商品
                        $sql_products = "SELECT p.*, c.category_name 
                                         FROM products p 
                                         LEFT JOIN categories c ON p.category_id = c.category_id 
                                         WHERE p.category_id IN ($ids_string) 
                                         ORDER BY p.category_id ASC, p.product_id DESC"; 
                        $res_products = mysqli_query($conn, $sql_products);

                        if ($res_products && mysqli_num_rows($res_products) > 0) {
                            while($prod = mysqli_fetch_assoc($res_products)) {
                                $img_src = !empty($prod['image_url']) ? $prod['image_url'] : 'https://via.placeholder.com/60?text=No+Img';
                                
                                echo "<tr>";
                                echo "<td>#" . $prod['product_id'] . "</td>";
                                
                                echo "<td>
                                        <div class='product-info-cell'>
                                            <img src='{$img_src}' class='product-thumb'>
                                            <div>
                                                <h4 class='product-title'>" . htmlspecialchars($prod['name']) . "</h4>
                                            </div>
                                        </div>
                                      </td>";

                                // 显示这个商品到底是属于哪个选中的分类的
                                echo "<td><span class='cat-badge'>" . htmlspecialchars($prod['category_name']) . "</span></td>";

                                echo "<td><strong style='color:#8a2be2;'>RM " . number_format($prod['price'], 2) . "</strong></td>";
                                
                                $stock = $prod['stock_quantity'];
                                $stock_color = ($stock <= 2) ? "color: red; font-weight: bold;" : "color: green;";
                                echo "<td style='{$stock_color}'>" . $stock . "</td>";
                                
                                echo "<td><a href='edit_product.php?id=" . $prod['product_id'] . "' class='btn-edit'>Edit</a></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding: 30px; color:#888;'>No products found in the selected categories.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>