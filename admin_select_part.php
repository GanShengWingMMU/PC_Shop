<?php
session_start();
include 'db_connect.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// ==========================================
// 1. 处理选择零件的动作 (加入 Build Session)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'add' && isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $sql_p = "SELECT * FROM products WHERE product_id = $product_id";
    $res_p = mysqli_query($conn, $sql_p);
    
    if ($res_p && mysqli_num_rows($res_p) > 0) {
        $p = mysqli_fetch_assoc($res_p);
        
        // 🌟 将数据存入 session (这里把 product_name 转换成 session 需要的 name)
        $_SESSION['admin_build'][$category_id] = [
            'id' => $p['product_id'],
            'name' => $p['product_name'], // <- 这里用对真实的数据库列名
            'price' => $p['price'],
            'wattage' => isset($p['wattage']) ? $p['wattage'] : 0 
        ];
        
        // 选好后自动跳回组装台
        header("Location: admin_builder.php");
        exit();
    }
}

// ==========================================
// 2. 获取当前分类的名字 (显示在标题)
// ==========================================
$cat_name = "Select Part";
$sql_c = "SELECT category_name FROM categories WHERE category_id = $category_id";
$res_c = mysqli_query($conn, $sql_c);
if ($res_c && mysqli_num_rows($res_c) > 0) {
    $c = mysqli_fetch_assoc($res_c);
    $cat_name = "Select: " . $c['category_name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $cat_name; ?> - PC Shop Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
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
            <li><a href="admin_builder.php" class="active">Build System</a></li>
            <li><a href="manage_users.php">Users</a></li>
            <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
        </ul>
    </div>

    <div class="main-content">
        <div class="header-top" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1 style="margin: 0; font-size: 24px; color: var(--accent-blue);"><?php echo $cat_name; ?></h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Choose a component for your build.</p>
            </div>
            <a href="admin_builder.php" class="btn-outline" style="padding: 8px 15px; border-radius: 6px;">&larr; Back to Builder</a>
        </div>

        <div class="card-grid">
            <?php
            // 处理智能兼容性过滤 (比如只显示 DDR5 或 AM5)
            $extra_sql = "";
            if (isset($_GET['socket']) && $_GET['socket'] != '') {
                $socket = mysqli_real_escape_string($conn, $_GET['socket']);
                $extra_sql .= " AND specifications LIKE '%$socket%'";
            }
            if (isset($_GET['ram_type']) && $_GET['ram_type'] != '') {
                $ram_type = mysqli_real_escape_string($conn, $_GET['ram_type']);
                $extra_sql .= " AND specifications LIKE '%$ram_type%'";
            }

            // 查询该分类下的所有商品
            $sql = "SELECT * FROM products WHERE category_id = $category_id $extra_sql ORDER BY price ASC";
            $res = mysqli_query($conn, $sql);

            if ($res && mysqli_num_rows($res) > 0) {
                while($row = mysqli_fetch_assoc($res)) {
                    // 🌟 这里完美修复了报错！使用 product_name 和 specifications
                    $p_name = htmlspecialchars($row['product_name']);
                    $p_specs = htmlspecialchars($row['specifications']);
                    $p_price = number_format($row['price'], 2);
                    $p_id = $row['product_id'];
                    $img = !empty($row['image_url']) ? $row['image_url'] : 'https://via.placeholder.com/150';

                    // 渲染暗黑发光科技卡片
                    echo "
                    <div class='tech-card' style='display: flex; flex-direction: column; justify-content: space-between;'>
                        <div>
                            <div style='text-align: center; margin-bottom: 15px; background: rgba(0,0,0,0.3); padding: 10px; border-radius: 8px;'>
                                <img src='{$img}' style='max-width: 100%; height: 120px; object-fit: contain;' alt='Product Image'>
                            </div>
                            <h3 style='font-size: 1.1rem;'>{$p_name}</h3>
                            <p class='specs' style='font-size: 0.85rem; color: var(--text-muted);'>{$p_specs}</p>
                        </div>
                        <div style='margin-top: 20px; text-align: center;'>
                            <div class='price' style='color: var(--accent-blue); font-size: 1.4rem; font-weight: bold; margin-bottom: 15px;'>RM {$p_price}</div>
                            
                            <a href='admin_select_part.php?category_id={$category_id}&action=add&product_id={$p_id}' class='quick-action-btn' style='color:#000;'>Select Part</a>
                        </div>
                    </div>";
                }
            } else {
                echo "<div style='grid-column: 1 / -1; background: var(--bg-surface); padding: 40px; text-align: center; border-radius: 8px; border: 1px solid var(--border-color);'>
                        <i class='fas fa-box-open' style='font-size: 3rem; color: var(--text-muted); margin-bottom: 15px;'></i>
                        <h3 style='color: var(--text-main);'>No components found</h3>
                        <p style='color: var(--text-muted);'>There are no parts in this category that match your current build compatibility.</p>
                      </div>";
            }
            ?>
        </div>

    </div>
</body>
</html>