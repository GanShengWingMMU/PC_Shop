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


$search = $_GET['search'] ?? '';
$current_sort = $_GET['sort'] ?? 'default';

$order_by = 'c.category_id ASC'; 
if ($current_sort === 'az') {
    $order_by = 'c.category_name ASC';
} elseif ($current_sort === 'za') {
    $order_by = 'c.category_name DESC';
}


if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    $check_stmt = $conn->prepare("SELECT product_id FROM products WHERE category_id = ? LIMIT 1");
    $check_stmt->bind_param("i", $delete_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $message = "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(255,77,77,0.3);'><i class='fas fa-lock'></i> Deletion Blocked! There are still hardware components linked to this category. Please reassign or delete them first.</div>";
    } else {
        $stmt_del = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt_del->bind_param("i", $delete_id);
        if ($stmt_del->execute()) {
            header("Location: manage_categories.php?deleted=1");
            exit();
        } else {
            $message = "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px;'>⚠️ System Error: Failed to purge category.</div>";
        }
        $stmt_del->close();
    }
    $check_stmt->close();
}

if (isset($_GET['deleted'])) $message = "<div style='color: #00e676; background: rgba(0,230,118,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(0,230,118,0.3);'><i class='fas fa-trash'></i> Category successfully purged from the matrix.</div>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        body { font-family: 'Inter', sans-serif !important; }

        
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
            height: 42px !important; 
            padding: 0 15px !important;
            font-size: 14px !important;
            line-height: normal !important;
            border-radius: 6px !important;
            outline: none !important;
            box-sizing: border-box !important;
            margin: 0 !important;
        }
        .search-form-clean input {
            flex: 1;
            min-width: 250px;
            background: rgba(0, 0, 0, 0.5) !important;
            border: 1px solid rgba(0, 242, 254, 0.3) !important;
            color: #fff !important;
        }
        .search-form-clean select {
            width: 200px;
            background: rgba(0, 0, 0, 0.5) !important;
            border: 1px solid rgba(0, 242, 254, 0.3) !important;
            color: #fff !important;
            cursor: pointer;
        }
        .search-form-clean button {
            background: linear-gradient(135deg, #00f2fe, #4facfe) !important;
            color: #000 !important;
            font-weight: bold !important;
            border: none !important;
            cursor: pointer;
            padding: 0 25px !important;
        }
        .btn-clear {
            display: flex;
            align-items: center;
            background: rgba(255,77,77,0.1) !important;
            color: #ff4d4d !important;
            border: 1px solid rgba(255,77,77,0.3) !important;
            font-weight: bold !important;
            text-decoration: none !important;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'admin_sidebar.php'; ?>

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div><h2 style="color: #00f2fe; margin:0;"><i class="fas fa-network-wired"></i> Ontology & Categories</h2></div>
                <a href="add_category.php" class="btn-action" style="background: linear-gradient(135deg, #a855f7, #00f2fe); color:#fff; font-weight:900; border:none; padding:10px 20px; border-radius:6px; text-decoration:none;"><i class="fas fa-plus"></i> Define New Category</a>
            </header>
            
            <?php echo $message; ?>

            <div class="search-wrapper">
                <form method="GET" action="manage_categories.php" class="search-form-clean">
                    <input type="text" name="search" placeholder="Search by Category Name..." value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select name="sort" onchange="this.form.submit();">
                        <option value="default" <?php echo $current_sort == 'default' ? 'selected' : ''; ?>>Sort: ID (Default)</option>
                        <option value="az" <?php echo $current_sort == 'az' ? 'selected' : ''; ?>>Sort: Name (A - Z)</option>
                        <option value="za" <?php echo $current_sort == 'za' ? 'selected' : ''; ?>>Sort: Name (Z - A)</option>
                    </select>

                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    
                    <?php if(!empty($search) || $current_sort !== 'default'): ?>
                        <a href="manage_categories.php" class="btn-clear">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-container" style="background: rgba(0,0,0,0.4); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(0,242,254,0.2);">
                            <th style="padding:15px; color:#00f2fe; text-align:left;">ID</th>
                            <th style="padding:15px; color:#00f2fe; text-align:left;">Category Name</th>
                            <th style="padding:15px; color:#00f2fe; text-align:left;">Description</th>
                            <th style="padding:15px; color:#00f2fe; text-align:center;">Active Components</th>
                            <th style="padding:15px; color:#00f2fe; text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT c.*, COUNT(p.product_id) as item_count 
                                FROM categories c 
                                LEFT JOIN products p ON c.category_id = p.category_id ";
                        if ($search !== '') {
                            $sql .= " WHERE c.category_name LIKE '%" . $conn->real_escape_string($search) . "%' ";
                        }
                        $sql .= " GROUP BY c.category_id ORDER BY $order_by";
                        
                        $res = $conn->query($sql);
                        if ($res && $res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $cid = $row['category_id'];
                                echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05);'>";
                                echo "<td style='padding:15px; color:#888;'>#{$row['category_id']}</td>";
                                echo "<td style='padding:15px; font-weight:600; color:#fff;'>".htmlspecialchars($row['category_name'])."</td>";
                                echo "<td style='padding:15px; color:#94a3b8;'>".htmlspecialchars($row['description'])."</td>";
                                echo "<td style='padding:15px; text-align:center;'><span style='background:rgba(0,242,254,0.1); color:#00f2fe; padding:4px 12px; border-radius:20px; font-size:12px;'>{$row['item_count']} linked</span></td>";
                                echo "<td style='padding:15px; text-align:right; white-space:nowrap;'>
                                        <a href='edit_category.php?id=$cid' class='btn-action' style='color:#00f2fe; border:1px solid #00f2fe; padding:6px 12px; text-decoration:none; margin-right:8px;'>Modify</a>
                                        <a href='manage_categories.php?delete_id=$cid' class='btn-action' style='color:#ff4d4d; border:1px solid #ff4d4d; padding:6px 12px; text-decoration:none;' onclick='return confirm(\"Are you sure?\");'>Delete</a>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='padding:30px; text-align:center; color:#888;'>No categories found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>