<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

// 🌟 统一安全准入
$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php"); exit();
}

$error = "";
$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($category_id <= 0) { header("Location: manage_categories.php"); exit(); }

// 🌟 获取现有分类资料
$stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$cat = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cat) { header("Location: manage_categories.php"); exit(); }

// 🌟 处理表单更新
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_category'])) {
    $name = trim($_POST['category_name']);
    $description = trim($_POST['description']);

    if (!empty($name)) {
        $sql = "UPDATE categories SET category_name=?, description=? WHERE category_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $name, $description, $category_id);
        
        if ($stmt->execute()) {
            header("Location: manage_categories.php?msg=updated");
            exit();
        } else {
            $error = "Database Error: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $error = "⚠️ Category Name cannot be empty.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Category - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        /* 🌟 高级居中卡片样式，加入了 width:100% 防止被挤压 */
        .form-card { background: rgba(0,0,0,0.4); padding: 40px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); max-width: 800px; margin: 0 auto; box-shadow: 0 10px 30px rgba(0,0,0,0.5); width: 100%; box-sizing: border-box; }
        .form-control { background: rgba(0,0,0,0.5); color: #fff; border: 1px solid rgba(255,255,255,0.1); padding: 12px; border-radius: 6px; width: 100%; box-sizing: border-box; font-family: 'Inter', sans-serif; transition: 0.3s; }
        .form-control:focus { border-color: #00f2fe; outline: none; box-shadow: 0 0 10px rgba(0,242,254,0.2); }
        label { color: #00f2fe; font-size: 13px; font-weight: bold; margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px; }
    </style>
</head>
<body>
    <div class="admin-container" style="display: flex; min-height: 100vh; width: 100%;">
        
        <nav class="admin-sidebar">
            <div class="sidebar-header"><h3><i class="fas fa-shield-alt"></i> GridCity Admin</h3></div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <?php 
                $sidebar_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
                if (strtolower($sidebar_role) === 'superadmin'): 
                ?>
                    <li><a href="manage_staff.php" style="color: var(--accent-warning);"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                    <li><a href="manage_users.php">Manage Customers</a></li>
                <?php endif; ?>
                <li><a href="manage_categories.php" class="active">Categories</a></li>
                <li><a href="manage_products.php">Products</a></li> 
                <li><a href="manage_packages.php">Packages</a></li>
                <li><a href="manage_orders.php">Orders</a></li>
                <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content" style="padding: 30px; flex: 1; width: 100%; box-sizing: border-box;">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <h2 style="color: #00f2fe; margin: 0;"><i class="fas fa-edit"></i> Modify Category Definition</h2>
                <a href="manage_categories.php" class="btn-action" style="color: #888; border-color: #555; text-decoration:none; padding: 8px 15px; border: 1px solid #555; border-radius: 6px;">&larr; Abort</a>
            </header>

            <div class="form-card">
                <?php if ($error) echo "<div style='color:#ff4d4d; background:rgba(255,77,77,0.1); padding:15px; border-radius:6px; margin-bottom:20px;'>$error</div>"; ?>

                <form method="POST">
                    <div style="margin-bottom: 20px;">
                        <label>Category Name *</label>
                        <input type="text" name="category_name" class="form-control" value="<?php echo htmlspecialchars($cat['category_name']); ?>" required>
                    </div>
                    
                    <div style="margin-bottom: 30px;">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="5"><?php echo htmlspecialchars($cat['description']); ?></textarea>
                    </div>

                    <button type="submit" name="update_category" style="width: 100%; background: linear-gradient(135deg, #00f2fe, #4facfe); color: #000; border: none; padding: 15px; border-radius: 8px; font-weight: 900; font-size: 16px; cursor: pointer; transition: 0.3s;">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
            
        </div>
    </div>
</body>
</html>