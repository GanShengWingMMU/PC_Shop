<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

$message = ""; 

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // 🌟 product_id 防呆检查
    $check_stmt = $conn->prepare("SELECT product_id FROM order_details WHERE product_id = ? LIMIT 1");
    $check_stmt->bind_param("i", $delete_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $message = "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(255,77,77,0.3);'><i class='fas fa-lock'></i> Cannot delete! Linked to active orders.</div>";
    } else {
        $stmt_del = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt_del->bind_param("i", $delete_id);
        if ($stmt_del->execute()) { header("Location: manage_products.php?deleted=1"); exit(); }
        $stmt_del->close();
    }
}
if (isset($_GET['deleted'])) $message = "<div style='color: #00e676; background: rgba(0,230,118,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px;'>✅ Node deleted.</div>";

// 🌟 捕获搜索关键词
$search = $_GET['search'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        /* 🌟 图片悬浮放大的赛博黑科技 CSS */
        .zoom-img {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            z-index: 1;
            cursor: zoom-in;
        }
        .zoom-img:hover {
            transform: scale(3.5); /* 放大 3.5 倍 */
            z-index: 999;
            box-shadow: 0 15px 35px rgba(0,0,0,0.9), 0 0 15px rgba(0,242,254,0.6);
            border: 1px solid #00f2fe;
            border-radius: 8px !important;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-shield-alt"></i> GridCity Admin</h3>
                <p style="color:#555; font-size:11px; font-family:'JetBrains Mono';">Unified Architecture v4.0</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php" <?php if(basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php') echo 'class="active"'; ?>>Dashboard</a></li>
                
                <?php 
                $sidebar_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
                if (strtolower($sidebar_role) === 'superadmin'): 
                ?>
                    <li><a href="manage_staff.php" style="color: var(--accent-warning);" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_staff.php') echo 'class="active"'; ?>><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                    <li><a href="manage_users.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_users.php') echo 'class="active"'; ?>>Manage Customers</a></li>
                <?php endif; ?>
                
                <li><a href="manage_categories.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_categories.php') echo 'class="active"'; ?>>Categories</a></li>
                <li><a href="manage_products.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_products.php') echo 'class="active"'; ?>>Products</a></li> 
                <li><a href="manage_packages.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_packages.php') echo 'class="active"'; ?>>Packages</a></li>
                <li><a href="manage_orders.php" <?php if(basename($_SERVER['PHP_SELF']) == 'manage_orders.php') echo 'class="active"'; ?>>Orders</a></li>
                
                <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div><h2 style="color: #00f2fe; margin:0;"><i class="fas fa-microchip"></i> Hardware Registry</h2></div>
                <a href="add_product.php" class="btn-action" style="background: linear-gradient(135deg, #00f2fe, #4facfe); color:#000; font-weight:900; border:none; padding:10px 20px; border-radius:6px; text-decoration:none;"><i class="fas fa-plus"></i> New Node</a>
            </header>
            
            <?php echo $message; ?>

            <form method="GET" action="manage_products.php" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; background: rgba(0,0,0,0.4); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                <input type="text" name="search" placeholder="Search by Product Name..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; min-width: 250px; background: rgba(0,0,0,0.6); border: 1px solid rgba(0,242,254,0.3); color: #fff; padding: 10px 15px; border-radius: 6px; outline: none; box-sizing: border-box;">
                
                <button type="submit" style="width: auto; flex-shrink: 0; white-space: nowrap; background: #00f2fe; color: #000; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s;" onmouseover="this.style.boxShadow='0 0 15px rgba(0,242,254,0.4)'" onmouseout="this.style.boxShadow='none'"><i class="fas fa-search"></i> Search</button>
                
                <?php if(!empty($search)): ?>
                    <a href="manage_products.php" style="width: auto; flex-shrink: 0; white-space: nowrap; background: rgba(255,77,77,0.1); color: #ff4d4d; border: 1px solid rgba(255,77,77,0.3); text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; display: flex; align-items: center; transition: 0.3s;" onmouseover="this.style.background='rgba(255,77,77,0.2)'" onmouseout="this.style.background='rgba(255,77,77,0.1)'">Clear</a>
                <?php endif; ?>
            </form>

            <div class="table-container" style="background: rgba(0,0,0,0.4); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(0,242,254,0.2);">
                            <th style="padding:15px; color:#00f2fe; text-align:left;">Visual</th>
                            <th style="padding:15px; color:#00f2fe; text-align:left;">Product Name</th>
                            <th style="padding:15px; color:#00f2fe; text-align:left;">Price</th>
                            <th style="padding:15px; color:#00f2fe; text-align:left;">Stock</th>
                            <th style="padding:15px; color:#00f2fe; text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 🌟 终极智能分词搜索逻辑
                        if ($search !== '') {
                            $keywords = explode(' ', trim($search));
                            $conditions = [];
                            $params = [];
                            $types = "";
                            
                            foreach ($keywords as $kw) {
                                if (trim($kw) !== '') {
                                    $conditions[] = "p.product_name LIKE ?";
                                    $params[] = "%" . trim($kw) . "%";
                                    $types .= "s";
                                }
                            }
                            
                            $where_clause = implode(" AND ", $conditions);
                            $sql = "SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id WHERE $where_clause ORDER BY p.product_id DESC";
                            
                            $stmt = $conn->prepare($sql);
                            if (!empty($params)) {
                                $stmt->bind_param($types, ...$params);
                            }
                            $stmt->execute();
                            $res = $stmt->get_result();
                        } else {
                            $sql = "SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id ORDER BY p.product_id DESC";
                            $res = $conn->query($sql);
                        }

                        if ($res && $res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $img = htmlspecialchars($row['image_url']) ?: 'image/placeholder_pc.png';
                                echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05);'>";
                                
                                // 🌟 核心：给 img 标签加上 class='zoom-img'
                                echo "<td style='padding:15px;'><img src='{$img}' class='zoom-img' style='height:40px; width:40px; object-fit:contain; border-radius:6px; background:#000;'></td>";
                                
                                echo "<td style='padding:15px; font-weight:600; color:#fff;'>".htmlspecialchars($row['product_name'])."</td>";
                                echo "<td style='padding:15px; color:#00e676;'>RM ".number_format($row['price'], 2)."</td>";
                                echo "<td style='padding:15px;'>{$row['stock_quantity']} UNITS</td>";
                                echo "<td style='padding:15px; text-align:right;'>
                                        <a href='edit_product.php?product_id={$row['product_id']}' class='btn-action' style='color:#00f2fe; border-color:#00f2fe; padding:6px 12px; font-size:12px; text-decoration:none; margin-right:8px;'>Modify</a>
                                        <a href='manage_products.php?delete_id={$row['product_id']}' class='btn-action' style='color:#ff4d4d; border-color:#ff4d4d; padding:6px 12px; font-size:12px; text-decoration:none;' onclick='return confirm(\"Delete node?\");'>Delete</a>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='padding:20px; text-align:center; color:#888;'>No products match your search.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 