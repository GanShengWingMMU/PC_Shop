<?php
session_start();
include 'db_connect.php'; 

// ✅ 聪明的保安：允许 admin 和 superadmin 进入
if (!isset($_SESSION['role']) || (strtolower($_SESSION['role']) !== 'admin' && strtolower($_SESSION['role']) !== 'superadmin')) {
    header("Location: admin_login.php");
    exit();
}

$error = "";

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $package_name = mysqli_real_escape_string($conn, $_POST['package_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = floatval($_POST['price']);
    $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
    $target_persona = mysqli_real_escape_string($conn, $_POST['target_persona']);
    $stock_status = mysqli_real_escape_string($conn, $_POST['stock_status']);
    
    // 抓取评分数据
    $score_gamer = intval($_POST['score_gamer']);
    $score_creator = intval($_POST['score_creator']);
    $score_student = intval($_POST['score_student']);
    $score_enthusiast = intval($_POST['score_enthusiast']);

    // 插入数据库
    $insert_sql = "INSERT INTO packages 
                   (package_name, description, price, image_url, target_persona, stock_status, score_gamer, score_creator, score_student, score_enthusiast) 
                   VALUES 
                   ('$package_name', '$description', $price, '$image_url', '$target_persona', '$stock_status', $score_gamer, $score_creator, $score_student, $score_enthusiast)";
                   
    if (mysqli_query($conn, $insert_sql)) {
        // ✅ 已经帮你改成 manage_packages.php 了
        header("Location: manage_packages.php?success=1");
        exit();
    } else {
        $error = "Database Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Package - GridCity PC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css?v=<?php echo time(); ?>">
    <style>
        .form-card {
            background: var(--bg-surface); padding: 40px; border-radius: 12px;
            border: 1px solid var(--border-color); box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            max-width: 800px; margin: 0 auto; position: relative; overflow: hidden;
        }
        .form-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(to right, #8a2be2, #00f2fe); /* 套餐专属紫蓝渐变 */
        }
        .form-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
        }
        .full-width { grid-column: span 2; }
    </style>
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
        
        <div class="header-top" style="margin-bottom: 30px;">
            <a href="manage_packages.php" class="btn-action" style="display: inline-block; margin-bottom: 15px; border:none; color: var(--text-muted);">&larr; Back to Packages</a>
            <h1 style="margin: 0; font-size: 28px; color: var(--text-main);">Add New PC Package</h1>
            <p style="color: var(--text-muted); margin-top: 5px;">Create a new pre-built system bundle for your customers.</p>
        </div>

        <div class="form-card">
            <?php 
            if(!empty($error)) {
                echo "<div class='error-msg' style='color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 12px; border-radius: 6px; border: 1px solid rgba(255,77,77,0.3); margin-bottom: 20px; font-weight: bold;'>⚠️ $error</div>";
            }
            ?>

            <form action="" method="POST">
                <div class="form-grid">
                    
                    <div class="form-group full-width">
                        <label style="color: var(--text-muted); font-weight: bold; margin-bottom: 8px; display:block;">Package Name *</label>
                        <input type="text" name="package_name" class="form-control" required placeholder="e.g., Esports Predator V2">
                    </div>

                    <div class="form-group full-width">
                        <label style="color: var(--text-muted); font-weight: bold; margin-bottom: 8px; display:block;">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Enter hardware details and target audience..."></textarea>
                    </div>

                    <div class="form-group">
                        <label style="color: var(--text-muted); font-weight: bold; margin-bottom: 8px; display:block;">Price (RM) *</label>
                        <input type="number" step="0.01" name="price" class="form-control" required placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label style="color: var(--text-muted); font-weight: bold; margin-bottom: 8px; display:block;">Stock Status</label>
                        <select name="stock_status" class="form-control">
                            <option value="Available">Available</option>
                            <option value="Out of Stock">Out of Stock</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="color: var(--text-muted); font-weight: bold; margin-bottom: 8px; display:block;">Target Persona</label>
                        <select name="target_persona" class="form-control">
                            <option value="Gamer">Gamer</option>
                            <option value="Creator">Creator</option>
                            <option value="Student">Student</option>
                            <option value="Enthusiast">Enthusiast</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="color: var(--text-muted); font-weight: bold; margin-bottom: 8px; display:block;">Image URL</label>
                        <input type="text" name="image_url" class="form-control" placeholder="https://...">
                    </div>

                    <div class="full-width" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color);">
                        <h4 style="color: var(--accent-purple); margin-bottom: 15px; margin-top: 0;"><i class="fas fa-chart-line"></i> Persona Suitability Scores (0-10)</h4>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                            <div>
                                <label style="font-size: 12px; color: var(--text-muted);">Gamer Score</label>
                                <input type="number" min="0" max="10" name="score_gamer" class="form-control" value="0">
                            </div>
                            <div>
                                <label style="font-size: 12px; color: var(--text-muted);">Creator Score</label>
                                <input type="number" min="0" max="10" name="score_creator" class="form-control" value="0">
                            </div>
                            <div>
                                <label style="font-size: 12px; color: var(--text-muted);">Student Score</label>
                                <input type="number" min="0" max="10" name="score_student" class="form-control" value="0">
                            </div>
                            <div>
                                <label style="font-size: 12px; color: var(--text-muted);">Enthusiast Score</label>
                                <input type="number" min="0" max="10" name="score_enthusiast" class="form-control" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width" style="margin-top: 20px;">
                        <button type="submit" class="quick-action-btn" style="width: 100%; background: linear-gradient(135deg, #8a2be2, #00f2fe); font-size: 16px; border:none;">
                            <i class="fas fa-save"></i> Save Package
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>
</body>
</html>