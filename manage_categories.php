<?php
session_start();
include 'db_connect.php'; 


if (!isset($_SESSION['role']) || (strtolower($_SESSION['role']) !== 'admin' && strtolower($_SESSION['role']) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

$selected_cat_ids = isset($_GET['category_ids']) ? $_GET['category_ids'] : [];
$safe_cat_ids = array_filter(array_map('intval', $selected_cat_ids)); 

// 🌟 核心更改：现在只有一个 filter_price (最高预算)，默认是 50000
$filter_price = (isset($_GET['filter_price']) && is_numeric($_GET['filter_price'])) ? floatval($_GET['filter_price']) : 50000;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories - GridCity PC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lora:wght@400;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css?v=<?php echo time(); ?>">
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

        <?php if (empty($safe_cat_ids)): ?>
            <div class="header-top">
                <div>
                    <h1>Select Categories</h1>
                    <p>Select categories and drag the slider to set your maximum budget.</p>
                </div>
            </div>

            <form action="manage_categories.php" method="GET">
                <div class="category-grid">
                    <?php
                    $sql_cats = "SELECT * FROM categories ORDER BY category_id ASC";
                    $res_cats = mysqli_query($conn, $sql_cats);

                    if ($res_cats && mysqli_num_rows($res_cats) > 0) {
                        while($cat = mysqli_fetch_assoc($res_cats)) {
                            $cid = $cat['category_id'];
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
                
                <div class="price-slider-wrapper">
                    <label>Max Price Filter</label>
                    
                    <div class="slider-container">
                        <div class="slider-track"></div>
                        <div class="slider-fill" id="slider-fill"></div>
                        <input type="range" name="filter_price" id="price-slider" min="0" max="50000" value="<?php echo $filter_price; ?>" step="100">
                    </div>
                    
                    <div class="price-display" style="justify-content: center; font-size: 16px;">
                        <div>Up to: <span>RM <span id="price-val"><?php echo number_format($filter_price); ?></span></span></div>
                    </div>
                </div>
                
                <button type="submit" class="btn-continue" style="margin-top: 30px;">Continue &rarr;</button>
            </form>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const priceSlider = document.getElementById("price-slider");
                    const priceVal = document.getElementById("price-val");
                    const sliderFill = document.getElementById("slider-fill");
                    const maxLimit = 50000;

                    function updateSlider() {
                        let currentPrice = parseInt(priceSlider.value);
                        
                        // 更新显示的文字 (加上千位逗号)
                        priceVal.textContent = currentPrice.toLocaleString();

                        // 更新发光进度条的宽度
                        const percent = (currentPrice / maxLimit) * 100;
                        sliderFill.style.left = "0%";
                        sliderFill.style.width = percent + "%";
                    }

                    // 监听滑动事件
                    priceSlider.addEventListener("input", updateSlider);
                    // 页面加载时执行一次初始化
                    updateSlider();
                });
            </script>

        <?php else: ?>
            <?php
            $ids_string = implode(',', $safe_cat_ids);
            
            $cat_names = [];
            $cat_name_sql = "SELECT category_name FROM categories WHERE category_id IN ($ids_string)";
            $cat_name_res = mysqli_query($conn, $cat_name_sql);
            while($row = mysqli_fetch_assoc($cat_name_res)) {
                $cat_names[] = $row['category_name'];
            }
            $display_title = implode(' & ', $cat_names); 
            ?>

            <div class="header-top">
                <div>
                    <h1 style="font-size: 24px;">
                        Products in: <span style="color: var(--accent-blue);"><?php echo htmlspecialchars($display_title); ?></span>
                    </h1>
                    <p style="color: var(--text-muted); font-weight: bold; margin-top: 5px;">
                        Filter: Up to RM <?php echo number_format($filter_price); ?>
                    </p>
                </div>
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
                        // 🌟 核心更改：SQL 查询只过滤 <= 设定好的最高价
                        $sql_products = "SELECT p.*, c.category_name 
                                         FROM products p 
                                         LEFT JOIN categories c ON p.category_id = c.category_id 
                                         WHERE p.category_id IN ($ids_string) 
                                         AND p.price <= $filter_price 
                                         ORDER BY p.category_id ASC, p.price ASC"; 
                        
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
                                                <h4 class='product-title'>" . htmlspecialchars($prod['product_name']) . "</h4>
                                            </div>
                                        </div>
                                      </td>";

                                echo "<td><span class='cat-badge'>" . htmlspecialchars($prod['category_name']) . "</span></td>";
                                echo "<td><strong style='color: var(--accent-blue);'>RM " . number_format($prod['price'], 2) . "</strong></td>";
                                
                                $stock = $prod['stock_quantity'];
                                $stock_color = ($stock <= 2) ? "color: var(--accent-danger); font-weight: bold;" : "color: #00e676;";
                                echo "<td style='{$stock_color}'>" . $stock . "</td>";
                                
                                echo "<td><a href='edit_product.php?id=" . $prod['product_id'] . "' class='btn-edit'>Edit</a></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding: 30px; color: var(--text-muted);'>No products match your selected categories under RM " . number_format($filter_price) . ".</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>