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

// 🌟 删除逻辑
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
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

// 🌟 获取搜索和排序参数
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'default';

// 🌟 判断排序逻辑
$order_by = 'p.product_id DESC'; 
if ($sort === 'price_desc') $order_by = 'p.price DESC';
elseif ($sort === 'price_asc') $order_by = 'p.price ASC';
elseif ($sort === 'name_asc') $order_by = 'p.product_name ASC';
elseif ($sort === 'name_desc') $order_by = 'p.product_name DESC';
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
    body { font-family: 'Inter', 'JetBrains Mono', sans-serif !important; }

    .zoom-img {
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative; z-index: 1; cursor: zoom-in;
    }
    .zoom-img:hover {
        transform: scale(3.5); z-index: 999;
        box-shadow: 0 15px 35px rgba(0,0,0,0.9), 0 0 15px rgba(0,242,254,0.6);
        border: 1px solid #00f2fe; border-radius: 8px !important;
    }

    /* 🌟 独立干净的搜索栏样式 */
    .search-form-clean {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        background: rgba(15, 15, 20, 0.6);
        padding: 15px 20px;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.05);
        align-items: center;
        margin-bottom: 25px;
    }
    .search-form-clean input,
    .search-form-clean select,
    .search-form-clean button,
    .btn-clear {
        height: 42px !important; /* 统一小巧的高度 */
        padding: 0 15px !important;
        font-size: 14px !important;
        line-height: normal !important;
        font-family: 'Inter', sans-serif !important;
        border-radius: 6px !important;
        outline: none !important;
        box-sizing: border-box !important;
        margin: 0 !important;
    }
    .search-form-clean input {
        flex: 1;
        min-width: 200px;
        background: rgba(0, 0, 0, 0.5) !important;
        border: 1px solid rgba(0, 242, 254, 0.3) !important;
        color: #fff !important;
    }
    .search-form-clean input:focus {
        border-color: #00f2fe !important;
        box-shadow: 0 0 8px rgba(0, 242, 254, 0.2) !important;
    }
    .search-form-clean select {
        width: 180px;
        background: rgba(0, 0, 0, 0.5) !important;
        border: 1px solid rgba(0, 242, 254, 0.3) !important;
        color: #fff !important;
        cursor: pointer;
    }
    .search-form-clean select option {
        background: #0a0a0a !important;
        color: #fff !important;
    }
    .search-form-clean button {
        background: linear-gradient(135deg, #00f2fe, #4facfe) !important;
        color: #000 !important;
        font-weight: bold !important;
        border: none !important;
        cursor: pointer;
        padding: 0 25px !important;
        transition: 0.2s !important;
    }
    .search-form-clean button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 242, 254, 0.4) !important;
    }
    
    /* 🌟 新增的 Clear 按钮样式 */
    .btn-clear {
        display: flex;
        align-items: center;
        background: rgba(255,77,77,0.1) !important;
        color: #ff4d4d !important;
        border: 1px solid rgba(255,77,77,0.3) !important;
        font-weight: bold !important;
        text-decoration: none !important;
        padding: 0 20px !important;
        transition: 0.3s !important;
    }
    .btn-clear:hover {
        background: rgba(255,77,77,0.2) !important;
    }
</style>
</head>
<body>
    <div class="admin-container">
         <?php include 'admin_sidebar.php'; ?>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div><h2 style="color: #00f2fe; margin:0;"><i class="fas fa-microchip"></i> Hardware Registry</h2></div>
                <a href="add_product.php" class="btn-action" style="background: linear-gradient(135deg, #00f2fe, #4facfe); color:#000; font-weight:900; border:none; padding:10px 20px; border-radius:6px; text-decoration:none;"><i class="fas fa-plus"></i> New Node</a>
            </header>
            
            <?php echo $message; ?>

            <div class="search-wrapper">
                <form method="GET" action="manage_products.php" class="search-form-clean">
                    <input type="text" name="search" placeholder="Search by Product Name..." value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select name="sort" onchange="this.form.submit()">
                        <option value="default" <?php echo $sort == 'default' ? 'selected' : ''; ?>>Sort: Default (Newest)</option>
                        <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                        <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                    </select>

                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    
                    <?php if(!empty($search) || $sort !== 'default'): ?>
                        <a href="manage_products.php" class="btn-clear">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

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
                        $sql = "SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id";
                        if ($search !== '') {
                            $sql .= " WHERE p.product_name LIKE '%" . $conn->real_escape_string($search) . "%'";
                        }
                        
                        $sql .= " ORDER BY " . $order_by;
                        
                        $res = $conn->query($sql);
                        if ($res && $res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $img = htmlspecialchars($row['image_url']) ?: 'image/placeholder_pc.png';
                                
                                echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05);'>";
                                echo "<td style='padding:15px;'><img src='{$img}' class='zoom-img' style='height:40px; width:40px; object-fit:contain; border-radius:6px; background:#000;'></td>";
                                echo "<td style='padding:15px; font-weight:600; color:#fff;'>".htmlspecialchars($row['product_name'])."</td>";
                                echo "<td style='padding:15px; color:#00e676;'>RM ".number_format($row['price'], 2)."</td>";
                                echo "<td style='padding:15px;'>{$row['stock_quantity']} UNITS</td>";
                                
                                echo "<td style='padding:15px; text-align:right; white-space:nowrap;'>
                                        <a href='edit_product.php?product_id={$row['product_id']}' 
                                           style='background: transparent; color: #00f2fe; border: 1px solid #00f2fe; padding: 6px 18px; font-size: 14px; border-radius: 4px; text-decoration: none; margin-right: 8px; font-weight: 600; display: inline-block;'>
                                            Modify
                                        </a>
                                        <a href='manage_products.php?delete_id={$row['product_id']}' 
                                           style='background: transparent; color: #ff4d4d; border: 1px solid #ff4d4d; padding: 6px 18px; font-size: 14px; border-radius: 4px; text-decoration: none; font-weight: 600; display: inline-block;' 
                                           onclick='return confirm(\"Delete node?\");'>
                                            Delete
                                        </a>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='padding:30px; text-align:center; color:#888; font-size: 16px;'>No products match your search.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>