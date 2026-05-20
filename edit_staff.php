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
$staff_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($staff_id <= 0) { header("Location: manage_staff.php"); exit(); }

// 获取当前员工资料
$stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$staff) { header("Location: manage_staff.php"); exit(); }

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_staff'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $password = $_POST['password']; // 密码选填

    if (empty($username) || empty($role)) {
        $error = "Username and Role are required.";
    } else {
        // 🌟 后端严格验证：如果有填密码，必须符合安全标准
        $password_ok = true;
        if (!empty($password)) {
            if (strlen($password) < 12 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
                $password_ok = false;
                $error = "⚠️ New password does not meet the high-security requirements.";
            }
        }

        if ($password_ok) {
            // 检查用户名是否重复 (要排除自己原本的名字)
            $check_stmt = $conn->prepare("SELECT admin_id FROM admins WHERE username = ? AND admin_id != ?");
            $check_stmt->bind_param("si", $username, $staff_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = "⚠️ Username already exists. Please choose another.";
            } else {
                if (!empty($password)) {
                    // 如果填写了新密码，就一起更新密码
                    $update_sql = "UPDATE admins SET username=?, email=?, role=?, password=? WHERE admin_id=?";
                    $stmt = $conn->prepare($update_sql);
                    $stmt->bind_param("ssssi", $username, $email, $role, $password, $staff_id);
                } else {
                    // 如果没填密码，就不更新密码字段
                    $update_sql = "UPDATE admins SET username=?, email=?, role=? WHERE admin_id=?";
                    $stmt = $conn->prepare($update_sql);
                    $stmt->bind_param("sssi", $username, $email, $role, $staff_id);
                }
                
                if ($stmt->execute()) {
                    header("Location: manage_staff.php?msg=updated");
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
    <title>Edit Staff - Admin</title>
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
            <div class="sidebar-header"><h3><i class="fas fa-shield-alt"></i> GridCity Admin</h3></div>
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
                <h2 style="color: #ff4d4d; margin: 0;"><i class="fas fa-user-edit"></i> Modify Personnel File</h2>
                <a href="manage_staff.php" class="btn-action" style="color: #888; border-color: #555; text-decoration:none;">&larr; Abort</a>
            </header>

            <div class="form-card">
                <?php if ($error) echo "<div style='color:#ff4d4d; background:rgba(255,77,77,0.1); padding:15px; border-radius:6px; margin-bottom:20px;'>$error</div>"; ?>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label style="color: #ff4d4d;">Username *</label>
                            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($staff['username']); ?>" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label style="color: #ff4d4d;">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($staff['email'] ?? ''); ?>">
                        </div>

                        <div class="form-group full-width">
                            <label style="color: #ff4d4d;">New High-Security Password (Optional)</label>
                            <div style="position: relative;">
                                <input type="password" name="password" id="pwd-input" class="form-control" placeholder="Leave blank to keep current password" style="padding-right: 40px; margin-bottom:0;">
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
                                <option value="Admin" <?php if($staff['role'] == 'Admin') echo 'selected'; ?>>Admin (Standard Access Control)</option>
                                <option value="SuperAdmin" <?php if($staff['role'] == 'SuperAdmin') echo 'selected'; ?>>SuperAdmin (Full Access Control)</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="update_staff" id="submit-btn" style="width: 100%; margin-top:30px; background: linear-gradient(135deg, #ff4d4d, #f39c12); color: #000; border: none; padding: 15px; border-radius: 8px; font-weight: 900; font-size: 16px; transition: 0.3s;">
                        <i class="fas fa-sync"></i> Update Clearance
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
        const btnOriginalText = '<i class="fas fa-sync"></i> Update Clearance';

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

        // 聚焦时显示规则框 (如果是空的)
        pwdInput.addEventListener('focus', () => {
            if(pwdInput.value.length > 0) rulesBox.style.display = 'block';
        });

        // 实时打字验证逻辑
        pwdInput.addEventListener('input', function () {
            const val = this.value;
            
            // 如果删光了密码（不想改了），就关掉提示框，允许直接保存
            if (val.length === 0) {
                rulesBox.style.display = 'none';
                submitBtn.disabled = false;
                submitBtn.innerHTML = btnOriginalText;
                return;
            }

            // 只要有打字，就显示提示框并开始严格验证
            rulesBox.style.display = 'block';
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
                submitBtn.innerHTML = btnOriginalText;
            } else {
                rulesBox.classList.remove('all-valid');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-lock"></i> Requirements Not Met';
            }
        });
    </script>
</body>
</html>