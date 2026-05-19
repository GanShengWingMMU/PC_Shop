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

// 🌟 安全防呆刪除邏輯
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // 檢查這個分類底下是不是還有商品
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
                
                <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'superadmin'): ?>
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
                <div>
                    <h2 style="color: #00f2fe; margin:0;"><i class="fas fa-network-wired"></i> Ontology & Categories</h2>
                </div>
                <a href="add_category.php" class="btn-action" style="background: linear-gradient(135deg, #a855f7, #00f2fe); color:#fff; font-weight:bold; border:none; padding:10px 20px; border-radius:6px; text-decoration:none;"><i class="fas fa-plus"></i> Define New Category</a>
            </header>
            <?php echo $message; ?>
            <div class="table-container" style="background: rgba(0,0,0,0.4); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(0,242,254,0.2); text-align: left;">
                            <th style="padding:15px; color:#00f2fe;">ID</th>
                            <th style="padding:15px; color:#00f2fe;">Category Name</th>
                            <th style="padding:15px; color:#00f2fe;">Description</th>
                            <th style="padding:15px; color:#00f2fe; text-align:center;">Active Components</th>
                            <th style="padding:15px; color:#00f2fe; text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT c.*, COUNT(p.product_id) as item_count FROM categories c LEFT JOIN products p ON c.category_id = p.category_id GROUP BY c.category_id";
                        $res = $conn->query($sql);
                        if ($res && $res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $cid = $row['category_id'];
                                $count = $row['item_count'];
                                echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05);'>";
                                echo "<td style='padding:15px; font-family:\"JetBrains Mono\"; color:#888;'>#$cid</td>";
                                echo "<td style='padding:15px; font-weight:bold; color:#fff;'>".htmlspecialchars($row['category_name'])."</td>";
                                echo "<td style='padding:15px; color:#94a3b8; font-size:13px;'>".htmlspecialchars($row['description'])."</td>";
                                echo "<td style='padding:15px; text-align:center;'><span style='background:rgba(0,242,254,0.1); color:#00f2fe; padding:4px 12px; border-radius:20px; font-family:\"JetBrains Mono\"; font-size:12px;'>$count linked</span></td>";
                                echo "<td style='padding:15px; text-align:right;'>
                                        <a href='manage_categories.php?delete_id=$cid' class='btn-action' style='color:#ff4d4d; border-color:#ff4d4d; padding:6px 12px; font-size:12px; text-decoration:none;' onclick='return confirm(\"Are you sure?\");'>Delete</a>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding:30px; color:#888;'>No categories defined.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>