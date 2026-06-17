<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || (strtolower($current_role) !== 'admin' && strtolower($current_role) !== 'superadmin')) {
    header("Location: admin_login.php"); exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $name = trim($_POST['product_name']);
    $category_id = intval($_POST['category_id']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock']);
    $specs = trim($_POST['specs']); // 这里的 specs 已经是 JS 自动拼接好的格式
    $description = trim($_POST['description'] ?? '');

    $image_url = 'image/placeholder_pc.png'; 
    $upload_ok = true;
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['product_image']['tmp_name'];
        $file_size = $_FILES['product_image']['size'];
        
        // 取得文件实际扩展名
        $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array($ext, $allowed_exts) || $file_size > 5 * 1024 * 1024) {
            $message = "<div class='alert-error'>⚠️ Upload Denied: Invalid format or exceeds 5MB.</div>";
            $upload_ok = false;
        } else {
            $new_filename = 'prod_' . uniqid() . '.' . $ext;
            $upload_dir = 'image/';
            $target_file = $upload_dir . $new_filename;
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            if (move_uploaded_file($file_tmp, $target_file)) { 
                $image_url = $target_file; 
            } else { 
                $message = "<div class='alert-error'>⚠️ System Error: Failed to save image.</div>"; 
                $upload_ok = false; 
            }
        }
    }

    if ($upload_ok) {
        $sql = "INSERT INTO products (product_name, category_id, price, stock_quantity, specifications, description, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sidssss", $name, $category_id, $price, $stock_quantity, $specs, $description, $image_url);
        
        if ($stmt->execute()) {
            
            // 🌟 记录动作到 Security Logs
            $log_admin_id = $_SESSION['admin_id'];
            $log_username = $_SESSION['admin_username'];
            $log_role = $_SESSION['admin_role'];
            $log_ip = $_SERVER['REMOTE_ADDR'];
            if ($log_ip == '::1') { $log_ip = '127.0.0.1'; }
            $action_event = "Added New Product: " . $name; 
            @$conn->query("INSERT INTO admin_logs (admin_id, username, role, action_event, ip_address) VALUES ('$log_admin_id', '$log_username', '$log_role', '$action_event', '$log_ip')");

            $message = "<div class='alert-success'>✅ Product added successfully!</div>";
        } else {
            $message = "<div class='alert-error'>⚠️ Database Error: " . htmlspecialchars($stmt->error) . "</div>";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .alert-success { color:#00e676; background:rgba(0,230,118,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(0,230,118,0.3); }
        .alert-error { color:#ff4d4d; background:rgba(255,77,77,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid rgba(255,77,77,0.3); }

        /* 🌟 终极滚动修复 (The Ultimate Scroll Fix) */
        html, body {
            height: auto; 
            min-height: 100vh;
            margin: 0;
            overflow-y: auto; 
            background-color: var(--bg-main); 
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh; 
            width: 100%;
        }

        .admin-sidebar {
            position: fixed; 
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 100;
        }

        .admin-content {
            margin-left: 250px; 
            flex: 1;
            padding: 30px !important;
            padding-bottom: 120px !important; /* 底部留白，防止按钮被吃掉 */
            min-height: 100vh;
            box-sizing: border-box;
        }
        
        .product-form {
            background: rgba(0,0,0,0.5); 
            padding: 30px; 
            border-radius: 12px; 
            border: 1px solid rgba(255,255,255,0.05);
            overflow: visible; 
            display: block;
        }

        .form-control {
            width: 100%;
            background: rgba(0,0,0,0.6);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            padding: 12px;
            border-radius: 6px;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #00f2fe;
            box-shadow: 0 0 10px rgba(0, 242, 254, 0.2);
        }
        
        .form-control::placeholder {
            color: rgba(255,255,255,0.3);
        }

        /* 🌟 动态规格行 CSS排版 */
        .spec-row {
            display: grid;
            grid-template-columns: 1fr 3fr 45px; 
            gap: 12px;
            margin-bottom: 12px;
            align-items: stretch;
        }
        .del-spec-btn {
            background: rgba(255,77,77,0.1);
            border: 1px solid rgba(255,77,77,0.3);
            color: #ff4d4d;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 45px;
        }
        .del-spec-btn:hover {
            background: rgba(255,77,77,0.8);
            color: #fff;
        }
        #add-spec-btn:hover {
            background: rgba(0,242,254,0.15);
            border-style: solid;
        }
        textarea.spec-val {
            resize: vertical;
            min-height: 45px; 
            padding: 12px;
            font-family: inherit;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <nav class="admin-sidebar">
            <div class="sidebar-header"><h3><i class="fas fa-shield-alt"></i> GridCity PC Admin</h3></div>
         <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                
                <?php 
                $role = strtolower($_SESSION['admin_role'] ?? $_SESSION['role'] ?? '');
                ?>

                <?php if ($role === 'superadmin'): ?>
                    <li><a href="manage_staff.php"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                <?php endif; ?>

                <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Customers</a></li>
                
                <li><a href="manage_categories.php">Categories</a></li>
                <li><a href="manage_products.php">Products</a></li> 
                <li><a href="manage_packages.php">Packages</a></li>
                <li><a href="manage_orders.php">Orders</a></li>
                <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 style="color: #00f2fe; margin: 0;"><i class="fas fa-plus-circle"></i> Inject New Hardware</h2>
                <a href="manage_products.php" class="btn-action" style="color: #888; border-color: #555; text-decoration:none;">&larr; Back to Registry</a>
            </header>

            <?php echo $message; ?>

            <form method="POST" enctype="multipart/form-data" class="product-form">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label style="color: #cbd5e1; font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block;">Product Name *</label>
                        <input type="text" name="product_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label style="color: #cbd5e1; font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block;">Category *</label>
                        <select name="category_id" class="form-control" required style="cursor: pointer;">
                            <option value="" disabled selected>-- Select a Category --</option>
                            <?php
                            $cat_query = "SELECT category_id, category_name FROM categories ORDER BY category_name ASC";
                            $cat_result = mysqli_query($conn, $cat_query);
                            while ($cat_row = mysqli_fetch_assoc($cat_result)) {
                                echo "<option value='{$cat_row['category_id']}'>" . htmlspecialchars($cat_row['category_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label style="color: #cbd5e1; font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block;">Price (RM) *</label>
                        <input type="number" step="0.01" name="price" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label style="color: #cbd5e1; font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block;">Stock Quantity *</label>
                        <input type="number" name="stock" class="form-control" required>
                    </div>
                    
                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label style="color: #00e676; font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block;"><i class="fas fa-image"></i> Product Image (Max 5MB) *</label>
                        <input type="file" name="product_image" accept=".jpg,.jpeg,.png,.webp" class="form-control" style="background: rgba(0,230,118,0.05); border: 1px dashed rgba(0,230,118,0.4); cursor: pointer;" required>
                    </div>

                    <div class="form-group full-width" style="grid-column: 1 / -1; margin-top: 10px;">
                        <label style="color: #00f2fe; font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block;"><i class="fas fa-list-ul"></i> Detailed Specifications *</label>
                        <p style="font-size: 11px; color: #888; margin-top: -5px; margin-bottom: 15px;">Add specifications row by row. If the details are long, the box will automatically adjust.</p>
                        
                        <textarea name="specs" id="hidden-specs" style="display: none;"></textarea>
                        
                        <div id="specs-builder" style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            </div>
                        
                        <button type="button" id="add-spec-btn" style="margin-top: 10px; width: 100%; background: rgba(0,242,254,0.05); border: 1px dashed rgba(0,242,254,0.4); color: #00f2fe; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s;">
                            <i class="fas fa-plus"></i> Add New Specification
                        </button>
                    </div>
                    
                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label style="color: #cbd5e1; font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block;">Marketing Description (Optional)</label>
                        <textarea name="description" class="form-control" rows="3" style="resize: vertical;"></textarea>
                    </div>
                </div>
                
                <div class="form-group full-width" style="margin-top: 40px; margin-bottom: 20px;">
                    <button type="submit" name="add_product" style="width: 100%; background: linear-gradient(135deg, #00f2fe, #4facfe); color: #000; border: none; padding: 18px; border-radius: 8px; font-weight: 900; font-size: 16px; cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 10px 20px rgba(0,242,254,0.2);">
                        <i class="fas fa-upload"></i> Upload Node to Matrix
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const specsContainer = document.getElementById('specs-builder');
            const hiddenSpecs = document.getElementById('hidden-specs');
            const addBtn = document.getElementById('add-spec-btn');

            // 刚进 Add 页面，默认给一个空行
            addRow('', '');

            function addRow(key = '', val = '') {
                const row = document.createElement('div');
                row.className = 'spec-row';
                
                // 将右侧参数的输入框换成了 <textarea>，让它可以自由拉长
                row.innerHTML = `
                    <input type="text" class="spec-key form-control" placeholder="e.g. Memory Type" value="${key}" style="margin:0; height: 45px;" required>
                    <textarea class="spec-val form-control" placeholder="e.g. DDR5, Maximum Capacity 192GB..." style="margin:0;" required>${val}</textarea>
                    <button type="button" class="del-spec-btn" title="Remove"><i class="fas fa-trash-alt"></i></button>
                `;
                specsContainer.appendChild(row);

                row.querySelector('.del-spec-btn').addEventListener('click', () => {
                    if(specsContainer.children.length > 1) {
                        row.remove();
                        syncSpecs(); 
                    } else {
                        alert("You must have at least one specification line.");
                    }
                });

                // 绑定输入事件，实时更新
                row.querySelectorAll('input, textarea').forEach(inp => {
                    inp.addEventListener('input', syncSpecs);
                });
            }

            addBtn.addEventListener('click', () => {
                addRow();
                syncSpecs();
            });

            function syncSpecs() {
                let lines = [];
                specsContainer.querySelectorAll('.spec-row').forEach(row => {
                    let k = row.querySelector('.spec-key').value.trim();
                    let v = row.querySelector('.spec-val').value.trim();
                    
                    // 把可能含有的多余回车符换掉，确保拼成一行
                    v = v.replace(/[\r\n]+/g, ' '); 
                    
                    if (k || v) {
                        lines.push(`- ${k || 'Spec'}: ${v || 'N/A'}`);
                    }
                });
                hiddenSpecs.value = lines.join('\n');
            }
        });
    </script>
</body>
</html>