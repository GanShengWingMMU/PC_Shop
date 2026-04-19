<?php
session_start();
include 'db_connect.php'; 

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

$message = ""; 

// 处理删除逻辑
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $sql_delete = "DELETE FROM products WHERE product_id = $delete_id";
    if (mysqli_query($conn, $sql_delete)) {
        header("Location: manage_products.php?deleted=1");
        exit();
    } else {
        $message = "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px;'>⚠️ Failed to delete product.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products - GridCity PC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lora:wght@400;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="sidebar">
        <h2>
            <img src="image/Admin_dashboard_logo.jpg" alt="ROG Logo" class="sidebar-logo">
            <span>PC SHOP</span>
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
        
        <div class="header-top" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h1 style="margin: 0; font-size: 28px; color: var(--text-main);">Product Inventory</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Manage your store's products, prices, and stock levels.</p>
            </div>
            <a href="add_product.php" class="btn-primary" style="padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: bold;">
                <i class="fas fa-plus"></i> Add New Product
            </a>
        </div>

        <?php 
        // 提示信息
        if(!empty($message)) echo $message;
        
        if(isset($_GET['success']) && $_GET['success'] == 1) {
            echo "<div style='color: #00e676; background: rgba(0,230,118,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(0,230,118,0.3);'>✅ Product added successfully!</div>";
        }
        if(isset($_GET['updated']) && $_GET['updated'] == 1) {
            echo "<div style='color: #00f2fe; background: rgba(0,242,254,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(0,242,254,0.3);'>🔄 Product updated successfully!</div>";
        }
        if(isset($_GET['deleted']) && $_GET['deleted'] == 1) {
            echo "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(255,77,77,0.3);'>🗑️ Product deleted successfully!</div>";
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
                                        <div class='product-info-cell' style='display: flex; gap: 15px; align-items: center;'>
                                            <img src='{$img_src}' class='product-thumb' style='width: 60px; height: 60px; object-fit: contain; background: rgba(0,0,0,0.3); border-radius: 6px; padding: 5px;'>
                                            <div>
                                                <h4 class='product-title' style='margin: 0 0 5px 0; color: var(--text-main); font-size: 15px;'>" . htmlspecialchars($row['product_name']) . "</h4>
                                                <p class='product-desc' style='margin: 0; color: var(--text-muted); font-size: 13px;'>" . htmlspecialchars($desc_text) . "</p>
                                            </div>
                                        </div>
                                      </td>";

                                echo "<td>Cat: " . $row['category_id'] . "</td>";
                                echo "<td><strong style='color: var(--accent-blue);'>RM " . number_format($row['price'], 2) . "</strong></td>";
                                
                                $stock = $row['stock_quantity'];
                                $stock_color = ($stock <= 2) ? "color: var(--accent-danger); font-weight: bold;" : "color: #00e676;";
                                echo "<td style='{$stock_color}'>" . $stock . "</td>";
                                echo "<td>
                                        <div style='display:flex; gap:8px;'>
                                            <a href='edit_product.php?id=" . $row['product_id'] . "' class='btn-action'>Edit</a>
                                            <a href='manage_products.php?delete_id=" . $row['product_id'] . "' class='btn-action' style='color: var(--accent-danger); border-color: var(--accent-danger);' onclick='return confirm(\"⚠️ Are you SURE you want to delete this product?\");'>Del</a>
                                        </div>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding: 30px; color: var(--text-muted);'>No products available yet. Click '+ Add New Product' to start.</td></tr>";
                        }
                    } catch (Exception $e) {
                        echo "<tr><td colspan='6' style='color: var(--accent-danger);'>Database Error.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>