<?php
session_start();
include 'db_connect.php'; 

// ✅ 聪明的保安：不管大小写都能进
if (!isset($_SESSION['role']) || (strtolower($_SESSION['role']) !== 'admin' && strtolower($_SESSION['role']) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

// 处理删除套餐的逻辑
$message = "";
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $sql_delete = "DELETE FROM packages WHERE package_id = $delete_id";
    if (mysqli_query($conn, $sql_delete)) {
        header("Location: manage_packages.php?deleted=1");
        exit();
    } else {
        $message = "<div class='error-msg'>⚠️ Failed to delete package.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Packages - GridCity PC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="sidebar">
        <h2>
            <img src="image/Admin_dashboard_logo.jpg" alt="GridCity PC Logo" class="sidebar-logo">
            <span>GridCity PC</span>
        </h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_products.php">Products</a></li> 
            
            <li><a href="manage_packages.php" class="active">Packages</a></li>
            
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
        
        <div class="header-top" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h1 style="margin: 0; font-size: 28px; color: var(--text-main);">PC Packages</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Manage pre-built PC packages and custom bundles.</p>
            </div>
            <a href="add_package.php" class="btn-primary" style="padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: bold;">
                <i class="fas fa-plus"></i> Add New Package
            </a>
        </div>

        <?php 
        if(!empty($message)) echo $message;
        
        // ✅ 帮你把 删除成功 和 添加成功 的提示语都放好了！
        if(isset($_GET['deleted']) && $_GET['deleted'] == 1) {
            echo "<div style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(255,77,77,0.3);'>🗑️ Package deleted successfully!</div>";
        }
        if(isset($_GET['success']) && $_GET['success'] == 1) {
            echo "<div style='color: #00e676; background: rgba(0,230,118,0.1); padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(0,230,118,0.3);'>✅ New package created successfully!</div>";
        }
        ?>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th width="10%">ID</th>
                        <th width="45%">Package Details</th> 
                        <th width="20%">Price</th>
                        <th width="25%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // 从数据库里抓取 Packages
                    $sql_pkgs = "SELECT * FROM packages ORDER BY package_id DESC"; 
                    $res_pkgs = mysqli_query($conn, $sql_pkgs);

                    if ($res_pkgs && mysqli_num_rows($res_pkgs) > 0) {
                        while($row = mysqli_fetch_assoc($res_pkgs)) {
                            echo "<tr>";
                            echo "<td>#" . $row['package_id'] . "</td>";
                            
                            $img_src = !empty($row['image_url']) ? $row['image_url'] : 'https://via.placeholder.com/70x70?text=Bundle';
                            $desc_text = !empty($row['description']) ? $row['description'] : 'No description.';
                            
                            echo "<td>
                                    <div class='product-info-cell' style='display: flex; gap: 15px; align-items: center;'>
                                        <img src='{$img_src}' class='product-thumb' style='width: 60px; height: 60px; object-fit: cover; background: rgba(0,0,0,0.3); border-radius: 6px; border: 1px solid var(--accent-purple);'>
                                        <div>
                                            <h4 class='product-title' style='margin: 0 0 5px 0; color: var(--text-main); font-size: 16px;'>" . htmlspecialchars($row['package_name']) . "</h4>
                                            <p class='product-desc' style='margin: 0; color: var(--text-muted); font-size: 13px;'>" . htmlspecialchars($desc_text) . "</p>
                                        </div>
                                    </div>
                                  </td>";

                            echo "<td><strong style='color: var(--accent-purple); font-size: 1.1rem;'>RM " . number_format($row['price'], 2) . "</strong></td>";
                            
                            echo "<td>
                                    <div style='display:flex; gap:8px;'>
                                        <a href='edit_package.php?id=" . $row['package_id'] . "' class='btn-action'>Edit</a>
                                        <a href='manage_packages.php?delete_id=" . $row['package_id'] . "' class='btn-action' style='color: var(--accent-danger); border-color: var(--accent-danger);' onclick='return confirm(\"⚠️ Are you sure you want to delete this package?\");'>Del</a>
                                    </div>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center; padding: 30px; color: var(--text-muted);'>No packages found. Click '+ Add New Package' to create one.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>