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
    // 防呆检查：确保订单中没有使用这个 Package
    $check_stmt = $conn->prepare("SELECT package_id FROM order_details WHERE package_id = ? LIMIT 1");
    $check_stmt->bind_param("i", $delete_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $message = "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(255,77,77,0.3);'><i class='fas fa-lock'></i> Cannot delete! Linked to active orders.</div>";
    } else {
        // 先删除 package_items 里的关联组件
        $conn->query("DELETE FROM package_items WHERE package_id = $delete_id");
        // 再删除 package 本身
        $stmt_del = $conn->prepare("DELETE FROM packages WHERE package_id = ?");
        $stmt_del->bind_param("i", $delete_id);
        if ($stmt_del->execute()) { header("Location: manage_packages.php?deleted=1"); exit(); }
        $stmt_del->close();
    }
}
if (isset($_GET['deleted'])) $message = "<div style='color: #00e676; background: rgba(0,230,118,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px;'>✅ Package deleted.</div>";

// 🌟 捕获搜索和排序参数
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'default';

// 🌟 判断排序逻辑 (使用 real_price 别名进行价格排序)
$order_by = 'pk.package_id DESC'; 
if ($sort === 'price_desc') $order_by = 'real_price DESC';
elseif ($sort === 'price_asc') $order_by = 'real_price ASC';
elseif ($sort === 'name_asc') $order_by = 'pk.package_name ASC';
elseif ($sort === 'name_desc') $order_by = 'pk.package_name DESC';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Packages - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        body { font-family: 'Inter', 'JetBrains Mono', sans-serif !important; }

        /* 🌟 图片悬浮放大的赛博黑科技 CSS (紫钻专属版) */
        .zoom-img {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            z-index: 1;
            cursor: zoom-in;
        }
        .zoom-img:hover {
            transform: scale(3.5); 
            z-index: 999;
            box-shadow: 0 15px 35px rgba(0,0,0,0.9), 0 0 15px rgba(168,85,247,0.6); 
            border: 1px solid #a855f7; 
            border-radius: 8px !important;
        }

        /* 🌟 独立干净的搜索栏样式 (完美的对齐和紫色主题) */
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
        .search-form-clean button {
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
            border: 1px solid rgba(168, 85, 247, 0.3) !important; /* 紫色边框 */
            color: #fff !important;
        }
        .search-form-clean input:focus {
            border-color: #a855f7 !important;
            box-shadow: 0 0 8px rgba(168, 85, 247, 0.2) !important;
        }
        .search-form-clean select {
            width: 180px;
            background: rgba(0, 0, 0, 0.5) !important;
            border: 1px solid rgba(168, 85, 247, 0.3) !important; /* 紫色边框 */
            color: #fff !important;
            cursor: pointer;
        }
        .search-form-clean select option {
            background: #0a0a0a !important;
            color: #fff !important;
        }
        .search-form-clean button {
            background: linear-gradient(135deg, #a855f7, #00f2fe) !important;
            color: #000 !important;
            font-weight: bold !important;
            border: none !important;
            cursor: pointer;
            padding: 0 25px !important;
            transition: 0.2s !important;
        }
        .search-form-clean button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(168, 85, 247, 0.4) !important;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'admin_sidebar.php'; ?>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div><h2 style="color: #a855f7; margin:0;"><i class="fas fa-layer-group"></i> Master Blueprints</h2></div>
                <a href="add_package.php" class="btn-action" style="background: linear-gradient(135deg, #a855f7, #00f2fe); color:#fff; font-weight:900; border:none; padding:10px 20px; border-radius:6px; text-decoration:none;"><i class="fas fa-hammer"></i> Forge New Package</a>
            </header>
            
            <?php echo $message; ?>

            <div class="search-wrapper">
                <form method="GET" action="manage_packages.php" class="search-form-clean">
                    <input type="text" name="search" placeholder="Search by Blueprint Name..." value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select name="sort" onchange="this.form.submit()">
                        <option value="default" <?php echo $sort == 'default' ? 'selected' : ''; ?>>Sort: Default (Newest)</option>
                        <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                        <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                    </select>

                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    
                    <?php if(!empty($search) || $sort !== 'default'): ?>
                        <a href="manage_packages.php" style="color: #ff4d4d; border: 1px solid rgba(255,77,77,0.3); text-decoration: none; padding: 0 15px; border-radius: 6px; font-weight: bold; display: flex; align-items: center; height: 42px; transition: 0.3s; background: rgba(255,77,77,0.1);" onmouseover="this.style.background='rgba(255,77,77,0.2)'" onmouseout="this.style.background='rgba(255,77,77,0.1)'">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-container" style="background: rgba(0,0,0,0.4); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(168,85,247,0.2);">
                            <th style="padding:15px; color:#a855f7; text-align:left;">Visual</th>
                            <th style="padding:15px; color:#a855f7; text-align:left;">Name</th>
                            <th style="padding:15px; color:#a855f7; text-align:left;">Parts</th>
                            <th style="padding:15px; color:#a855f7; text-align:left;">Price</th>
                            <th style="padding:15px; color:#a855f7; text-align:left;">Status</th>
                            <th style="padding:15px; color:#a855f7; text-align:right;">Operations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 🌟 智能分词搜索逻辑 (Packages) + 排序逻辑
                        if ($search !== '') {
                            $keywords = explode(' ', trim($search));
                            $conditions = [];
                            $params = [];
                            $types = "";
                            
                            foreach ($keywords as $kw) {
                                if (trim($kw) !== '') {
                                    $conditions[] = "pk.package_name LIKE ?";
                                    $params[] = "%" . trim($kw) . "%";
                                    $types .= "s";
                                }
                            }
                            
                            $where_clause = implode(" AND ", $conditions);
                            $sql = "SELECT pk.*, 
                                    (SELECT COUNT(*) FROM package_items WHERE package_id = pk.package_id) as part_count,
                                    (SELECT COALESCE(SUM(p.price * pi.quantity), pk.price) FROM package_items pi JOIN products p ON pi.product_id = p.product_id WHERE pi.package_id = pk.package_id) AS real_price
                                    FROM packages pk WHERE $where_clause ORDER BY $order_by";
                            
                            $stmt = $conn->prepare($sql);
                            if (!empty($params)) {
                                $stmt->bind_param($types, ...$params);
                            }
                            $stmt->execute();
                            $res = $stmt->get_result();
                        } else {
                            $sql = "SELECT pk.*, 
                                    (SELECT COUNT(*) FROM package_items WHERE package_id = pk.package_id) as part_count,
                                    (SELECT COALESCE(SUM(p.price * pi.quantity), pk.price) FROM package_items pi JOIN products p ON pi.product_id = p.product_id WHERE pi.package_id = pk.package_id) AS real_price
                                    FROM packages pk ORDER BY $order_by";
                            $res = $conn->query($sql);
                        }

                        if ($res && $res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $img = htmlspecialchars($row['image_url']) ?: 'image/placeholder_pc.png';
                                echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05);'>";
                                
                                echo "<td style='padding:15px;'><img src='{$img}' class='zoom-img' style='height:40px; width:40px; object-fit:contain; border-radius:6px; background:#000;'></td>";
                                
                                echo "<td style='padding:15px; font-weight:900; color:#fff;'>".htmlspecialchars($row['package_name'])."</td>";
                                echo "<td style='padding:15px; color:#a855f7;'>{$row['part_count']} Parts</td>";
                                echo "<td style='padding:15px; color:#00e676;'>RM ".number_format($row['real_price'], 2)."</td>";
                                echo "<td style='padding:15px; color:#fff;'>".htmlspecialchars($row['stock_status'])."</td>";
                                
                                // 🌟 动作按钮也统一调整了间距和不换行属性
                                echo "<td style='padding:15px; text-align:right; white-space:nowrap;'>
                                        <a href='edit_package.php?package_id={$row['package_id']}' 
                                           style='background: transparent; color: #a855f7; border: 1px solid #a855f7; padding: 6px 18px; font-size: 14px; border-radius: 4px; text-decoration: none; margin-right: 8px; font-weight: 600; display: inline-block;'>
                                            Modify
                                        </a>
                                        <a href='manage_packages.php?delete_id={$row['package_id']}' 
                                           style='background: transparent; color: #ff4d4d; border: 1px solid #ff4d4d; padding: 6px 18px; font-size: 14px; border-radius: 4px; text-decoration: none; font-weight: 600; display: inline-block;' 
                                           onclick='return confirm(\"Delete package?\");'>
                                            Delete
                                        </a>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='padding:30px; text-align:center; color:#888; font-size: 16px;'>No blueprints match your search.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>