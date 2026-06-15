<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

// 🌟 只有老板才能进入
$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || strtolower($current_role) !== 'superadmin') {
    die("<div style='background:#000; color:#ff4d4d; padding:50px; text-align:center; font-family:monospace;'>ACCESS DENIED: ALPHA REQUIRED.</div>");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_staff'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = trim($_POST['role']);

    if (empty($username) || empty($password) || empty($role)) {
        $error = "Please fill in all required fields.";
    } else {
        // 🌟 后端二次严格验证 (防止黑客绕过前端直接发包)
        if (strlen($password) < 12 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
            $error = "⚠️ Password does not meet the high-security requirements.";
        } else {
            // 检查用户名是否重复
            $check_stmt = $conn->prepare("SELECT admin_id FROM admins WHERE username = ?");
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = "⚠️ Username already exists. Please choose another.";
            } else {
                // 新增员工
                $stmt = $conn->prepare("INSERT INTO admins (username, password, email, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $password, $email, $role);
                if ($stmt->execute()) {
                    header("Location: manage_staff.php?msg=added");
                    exit();
                } else {
                    $error = "Database Error: " . htmlspecialchars($stmt->error);
                }
                $stmt->close();
            }
            $check_stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Staff - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        /* 🌟 动态密码提示框的专属 CSS */
        .pwd-rules {
            display: none;
            background: rgba(0,0,0,0.8);
            border: 1px solid rgba(255, 77, 77, 0.5);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            box-shadow: 0 5px 15px rgba(255, 77, 77, 0.1);
            transition: 0.3s;
        }
        .pwd-rules p {
            margin: 6px 0;
            color: #ff4d4d;
            font-size: 13px;
            font-weight: 600;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pwd-rules p i { font-size: 14px; }
        .pwd-rules p.valid { color: #00e676; }
        .pwd-rules.all-valid { border-color: #00e676; box-shadow: 0 5px 15px rgba(0, 230, 118, 0.1); }
        
        #submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #555 !important;
            color: #888 !important;
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
                $sidebar_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
                if (strtolower($sidebar_role) === 'superadmin'): 
                ?>
                    <li><a href="manage_staff.php" class="active" style="color: var(--accent-warning); border-left-color: var(--accent-warning);"><i class="fas fa-user-tie"></i> Manage Staff</a></li>
                    <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Customers</a></li>
                <?php endif; ?>
                <li><a href="manage_categories.php">Categories</a></li>
                <li><a href="manage_products.php">Products</a></li> 
                <li><a href="manage_packages.php">Packages</a></li>
                <li><a href="manage_orders.php">Orders</a></li>
                <li><a href="admin_logout.php" class="logout-btn">Log out</a></li> 
            </ul>
        </nav>

        <div class="admin-content">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <h2 style="color: #ff4d4d; margin: 0;"><i class="fas fa-user-plus"></i> Recruit Security Personnel</h2>
                <a href="manage_staff.php" class="btn-action" style="color: #888; border-color: #555; text-decoration:none;">&larr; Abort</a>
            </header>

            <div class="form-card">
                <?php if ($error) echo "<div style='color:#ff4d4d; background:rgba(255,77,77,0.1); padding:15px; border-radius:6px; margin-bottom:20px;'>$error</div>"; ?>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label style="color: #ff4d4d;">Username *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label style="color: #ff4d4d;">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>

                        <div class="form-group full-width">
                            <label style="color: #ff4d4d;">High-Security Password *</label>
                            <div style="position: relative;">
                                <input type="password" name="password" id="pwd-input" class="form-control" required style="padding-right: 40px; margin-bottom:0;">
                                <i class="fas fa-eye" id="toggle-pwd" style="position: absolute; right: 15px; top: 15px; color: #888; cursor: pointer; transition: 0.2s;"></i>
                            </div>

                            <div id="pwd-rules" class="pwd-rules">
                                <p id="req-len"><i class="fas fa-times-circle"></i> At least 12 characters</p>
                                <p id="req-up"><i class="fas fa-times-circle"></i> 1 Uppercase letter</p>
                                <p id="req-low"><i class="fas fa-times-circle"></i> 1 Lowercase letter</p>
                                <p id="req-num"><i class="fas fa-times-circle"></i> 1 Number</p>
                                <p id="req-spc"><i class="fas fa-times-circle"></i> 1 Special character (e.g. @$!%*?&)</p>
                            </div>
                        </div>

                        <div class="form-group full-width" style="margin-top: 15px;">
                            <label style="color: #ff4d4d;">Role Level *</label>
                            <select name="role" class="form-control" required style="cursor:pointer;">
                                <option value="Admin">Admin (Standard)</option>
                                <option value="SuperAdmin">SuperAdmin (Alpha)</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="add_staff" id="submit-btn" disabled style="width: 100%; margin-top:30px; background: linear-gradient(135deg, #ff4d4d, #f39c12); color: #000; border: none; padding: 15px; border-radius: 8px; font-weight: 900; font-size: 16px; transition: 0.3s;">
                        <i class="fas fa-lock"></i> Authorize Clearance
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const pwdInput = document.getElementById('pwd-input');
        const togglePwd = document.getElementById('toggle-pwd');
        const rulesBox = document.getElementById('pwd-rules');
        const submitBtn = document.getElementById('submit-btn');

        const rules = {
            len: { el: document.getElementById('req-len'), regex: /.{12,}/ },
            up: { el: document.getElementById('req-up'), regex: /[A-Z]/ },
            low: { el: document.getElementById('req-low'), regex: /[a-z]/ },
            num: { el: document.getElementById('req-num'), regex: /[0-9]/ },
            spc: { el: document.getElementById('req-spc'), regex: /[\W_]/ }
        };

        // 点击眼睛显示/隐藏密码
        togglePwd.addEventListener('click', function () {
            const type = pwdInput.getAttribute('type') === 'password' ? 'text' : 'password';
            pwdInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
            this.style.color = type === 'text' ? '#00f2fe' : '#888';
        });

        // 聚焦时显示规则框
        pwdInput.addEventListener('focus', () => {
            rulesBox.style.display = 'block';
        });

        // 实时打字验证逻辑
        pwdInput.addEventListener('input', function () {
            const val = this.value;
            let allValid = true;

            for (const key in rules) {
                const rule = rules[key];
                if (rule.regex.test(val)) {
                    rule.el.classList.add('valid');
                    rule.el.innerHTML = rule.el.innerHTML.replace('fa-times-circle', 'fa-check-circle');
                } else {
                    rule.el.classList.remove('valid');
                    rule.el.innerHTML = rule.el.innerHTML.replace('fa-check-circle', 'fa-times-circle');
                    allValid = false;
                }
            }

            if (allValid) {
                rulesBox.classList.add('all-valid');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Authorize Clearance';
            } else {
                rulesBox.classList.remove('all-valid');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-lock"></i> Requirements Not Met';
            }
        });
    </script>
</body>
</html>