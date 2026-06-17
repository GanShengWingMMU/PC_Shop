<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

// 🌟 权限验证
$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
$logged_in_id = $_SESSION['admin_id'] ?? 0;
$staff_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (empty($current_role) || (strtolower($current_role) !== 'superadmin' && $logged_in_id !== $staff_id)) {
    die("<div style='background:#000; color:#ff4d4d; padding:50px; text-align:center; font-family:monospace;'>ACCESS DENIED: ALPHA REQUIRED OR OWNER ONLY.</div>");
}

$error = "";
if ($staff_id <= 0) { header("Location: admin_dashboard.php"); exit(); }

$stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$staff) { header("Location: admin_dashboard.php"); exit(); }

// 🌟 智能路由：如果是大老板，退回员工列表；如果是普通员工，退回 Dashboard
$back_link = (strtolower($current_role) === 'superadmin') ? 'manage_staff.php' : 'admin_dashboard.php';
$back_text = (strtolower($current_role) === 'superadmin') ? 'Back to Roster' : 'Back to Dashboard';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_staff'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $password = $_POST['password'] ?? ''; 

    if (empty($username) || empty($role)) {
        $error = "Username and Role are required.";
    } else {
        $password_ok = true;
        if (!empty($password)) {
            if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
                $password_ok = false;
                $error = "⚠️ New password must be at least 8 chars with uppercase, number, and symbol.";
            }
        }

        if ($password_ok) {
            $check_stmt = $conn->prepare("SELECT admin_id FROM admins WHERE username = ? AND admin_id != ?");
            $check_stmt->bind_param("si", $username, $staff_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = "⚠️ Username already exists. Please choose another.";
            } else {
                if (!empty($password)) {
                    $hashed_pwd = password_hash($password, PASSWORD_DEFAULT);
                    $update_sql = "UPDATE admins SET username=?, email=?, role=?, password=? WHERE admin_id=?";
                    $stmt = $conn->prepare($update_sql);
                    $stmt->bind_param("ssssi", $username, $email, $role, $hashed_pwd, $staff_id);
                } else {
                    $update_sql = "UPDATE admins SET username=?, email=?, role=? WHERE admin_id=?";
                    $stmt = $conn->prepare($update_sql);
                    $stmt->bind_param("sssi", $username, $email, $role, $staff_id);
                }
                
                if ($stmt->execute()) {
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    if ($ip_address == '::1') $ip_address = '127.0.0.1';
                    $action = "Modified Staff Profile ID: " . $staff_id;
                    @$conn->query("INSERT INTO admin_logs (admin_id, username, role, action_event, ip_address) VALUES ('$logged_in_id', '{$_SESSION['admin_username']}', '$current_role', '$action', '$ip_address')");

                    if ($logged_in_id === $staff_id) {
                        $_SESSION['admin_username'] = $username;
                        $_SESSION['username'] = $username;
                    }
                    
                    // 🌟 成功后自动判断退回哪里
                    if (strtolower($current_role) === 'superadmin') {
                        header("Location: manage_staff.php?msg=updated");
                    } else {
                        echo "<script>alert('Profile updated successfully!'); window.location.href='admin_dashboard.php';</script>";
                    }
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

$avatar_letter = strtoupper(substr($staff['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Staff Profile - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .profile-container { display: grid; grid-template-columns: 300px 1fr; gap: 30px; align-items: start; }
        .profile-sidebar { background: rgba(11,11,18,0.6); border: 1px solid rgba(255,255,255,0.05); border-top: 2px solid var(--accent-purple); padding: 30px 20px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); backdrop-filter: blur(10px); text-align: center; }
        .profile-avatar { width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #a855f7, #00f2fe); display: flex; align-items: center; justify-content: center; font-size: 40px; font-weight: 900; color: #000; margin: 0 auto 20px; box-shadow: 0 0 20px rgba(0,242,254,0.4); border: 3px solid rgba(255,255,255,0.1); }
        .profile-name { font-size: 20px; font-weight: 800; color: #fff; margin: 0 0 5px; }
        .profile-role { font-size: 12px; color: #a855f7; background: rgba(168, 85, 247, 0.1); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(168, 85, 247, 0.3); display: inline-block; margin-bottom: 20px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; }
        .profile-detail-item { display: flex; align-items: center; justify-content: center; gap: 10px; color: #cbd5e1; font-size: 13px; margin-bottom: 10px; }
        .profile-form-area { background: rgba(11,11,18,0.6); border: 1px solid rgba(255,255,255,0.05); padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); backdrop-filter: blur(10px); }
        .profile-form-area h3 { color: #00f2fe; margin-top: 0; margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; font-size: 18px; display: flex; align-items: center; gap: 10px; }
        .pwd-rules { display: none; background: rgba(0,0,0,0.8); border: 1px solid rgba(255, 77, 77, 0.5); padding: 15px; border-radius: 8px; margin-top: 10px; box-shadow: 0 5px 15px rgba(255, 77, 77, 0.1); transition: 0.3s; }
        .pwd-rules p { margin: 6px 0; color: #ff4d4d; font-size: 13px; font-weight: 600; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .pwd-rules p i { font-size: 14px; }
        .pwd-rules p.valid { color: #00e676; }
        .pwd-rules.all-valid { border-color: #00e676; box-shadow: 0 5px 15px rgba(0, 230, 118, 0.1); }
        #submit-btn:disabled { opacity: 0.5; cursor: not-allowed; background: #555 !important; color: #888 !important; }
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

        <div class="admin-content" style="padding: 30px;">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 style="color: #fff; margin: 0;"><i class="fas fa-user-cog" style="color: #a855f7;"></i> Personnel Profile</h2>
                <a href="<?php echo $back_link; ?>" class="btn-action" style="color: #888; border-color: #555; text-decoration:none;"><i class="fas fa-arrow-left"></i> <?php echo $back_text; ?></a>
            </header>

            <?php if ($error) echo "<div style='color:#ff4d4d; background:rgba(255,77,77,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border: 1px solid rgba(255,77,77,0.3);'><i class='fas fa-exclamation-triangle'></i> $error</div>"; ?>

            <div class="profile-container">
                <div class="profile-sidebar">
                    <div class="profile-avatar"><?php echo $avatar_letter; ?></div>
                    <h2 class="profile-name"><?php echo htmlspecialchars($staff['username']); ?></h2>
                    <div class="profile-role"><i class="fas fa-star"></i> <?php echo htmlspecialchars($staff['role']); ?></div>
                    
                    <div style="border-top: 1px dashed rgba(255,255,255,0.1); margin-top: 15px; padding-top: 20px;">
                        <div class="profile-detail-item">
                            <i class="fas fa-envelope" style="color: #64748b; width: 20px;"></i>
                            <span><?php echo htmlspecialchars($staff['email'] ?: 'No email provided'); ?></span>
                        </div>
                        <div class="profile-detail-item">
                            <i class="fas fa-id-badge" style="color: #64748b; width: 20px;"></i>
                            <span>ID: ADM-<?php echo str_pad($staff['admin_id'], 4, '0', STR_PAD_LEFT); ?></span>
                        </div>
                    </div>
                </div>

                <div class="profile-form-area">
                    <h3><i class="fas fa-sliders-h"></i> Update Security Credentials</h3>
                    
                    <form method="POST">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group full-width" style="grid-column: 1 / -1;">
                                <label style="color: #cbd5e1; font-size: 13px; display:block; margin-bottom:8px;">Username</label>
                                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($staff['username']); ?>" required style="width: 100%; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); color:#fff; padding: 12px; border-radius: 6px;">
                            </div>
                            
                            <div class="form-group full-width" style="grid-column: 1 / -1;">
                                <label style="color: #cbd5e1; font-size: 13px; display:block; margin-bottom:8px;">Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($staff['email'] ?? ''); ?>" style="width: 100%; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); color:#fff; padding: 12px; border-radius: 6px;">
                            </div>

                            <div class="form-group full-width" style="grid-column: 1 / -1;">
                                <label style="color: #cbd5e1; font-size: 13px; display:block; margin-bottom:8px;">Clearance Level (Role)</label>
                                <?php if (strtolower($current_role) === 'superadmin'): ?>
                                    <select name="role" class="form-control" required style="width: 100%; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); color:#fff; padding: 12px; border-radius: 6px; cursor:pointer;">
                                        <option value="Admin" <?php if(strtolower($staff['role']) == 'admin') echo 'selected'; ?>>Admin (Standard Access Control)</option>
                                        <option value="SuperAdmin" <?php if(strtolower($staff['role']) == 'superadmin') echo 'selected'; ?>>SuperAdmin (Full System Control)</option>
                                    </select>
                                <?php else: ?>
                                    <input type="text" class="form-control" value="<?php echo strtoupper($staff['role']); ?> (Standard Access)" disabled style="width: 100%; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); color:#64748b; padding: 12px; border-radius: 6px; cursor:not-allowed;">
                                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($staff['role']); ?>">
                                    <p style="font-size: 11px; color: #888; margin-top: 8px;"><i class="fas fa-lock"></i> Role modification requires Alpha (SuperAdmin) clearance.</p>
                                <?php endif; ?>
                            </div>

                            <?php if (strtolower($current_role) === 'superadmin'): ?>
                                <div class="form-group full-width" style="grid-column: 1 / -1;">
                                    <label style="color: #ff4d4d; font-size: 13px; display:block; margin-bottom:8px;">Change Password (Optional)</label>
                                    <div style="position: relative;">
                                        <input type="password" name="password" id="pwd-input" class="form-control" placeholder="Leave blank to maintain current password" style="width: 100%; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,77,77,0.3); color:#fff; padding: 12px 40px 12px 12px; border-radius: 6px; margin-bottom:0;">
                                        <i class="fas fa-eye" id="toggle-pwd" style="position: absolute; right: 15px; top: 15px; color: #888; cursor: pointer; transition: 0.2s;"></i>
                                    </div>
                                    <div id="pwd-rules" class="pwd-rules">
                                        <p id="req-len"><i class="fas fa-times-circle"></i> At least 8 characters</p>
                                        <p id="req-up"><i class="fas fa-times-circle"></i> 1 Uppercase letter</p>
                                        <p id="req-low"><i class="fas fa-times-circle"></i> 1 Lowercase letter</p>
                                        <p id="req-num"><i class="fas fa-times-circle"></i> 1 Number</p>
                                        <p id="req-spc"><i class="fas fa-times-circle"></i> 1 Special character (e.g. @$!%*?&)</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="form-group full-width" style="grid-column: 1 / -1;">
                                    <label style="color: #64748b; font-size: 13px; display:block; margin-bottom:8px;">Change Password</label>
                                    <input type="password" class="form-control" placeholder="Locked. Only Alpha (SuperAdmin) can reset passwords." disabled style="width: 100%; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); color:#64748b; padding: 12px; border-radius: 6px; cursor:not-allowed;">
                                </div>
                            <?php endif; ?>

                        </div>

                        <button type="submit" name="update_staff" id="submit-btn" style="width: 100%; margin-top:30px; background: linear-gradient(135deg, #a855f7, #00f2fe); color: #fff; border: none; padding: 15px; border-radius: 8px; font-weight: 800; font-size: 15px; cursor: pointer; transition: 0.3s; box-shadow: 0 5px 15px rgba(0,242,254,0.2);">
                            <i class="fas fa-save"></i> Save Profile Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const pwdInput = document.getElementById('pwd-input');
        const togglePwd = document.getElementById('toggle-pwd');
        const rulesBox = document.getElementById('pwd-rules');
        const submitBtn = document.getElementById('submit-btn');
        const btnOriginalText = '<i class="fas fa-save"></i> Save Profile Changes';

        if (pwdInput) {
            const rules = {
                len: { el: document.getElementById('req-len'), regex: /.{8,}/ },
                up: { el: document.getElementById('req-up'), regex: /[A-Z]/ },
                low: { el: document.getElementById('req-low'), regex: /[a-z]/ },
                num: { el: document.getElementById('req-num'), regex: /[0-9]/ },
                spc: { el: document.getElementById('req-spc'), regex: /[\W_]/ }
            };

            togglePwd.addEventListener('click', function () {
                const type = pwdInput.getAttribute('type') === 'password' ? 'text' : 'password';
                pwdInput.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
                this.style.color = type === 'text' ? '#00f2fe' : '#888';
            });

            pwdInput.addEventListener('focus', () => {
                if(pwdInput.value.length > 0) rulesBox.style.display = 'block';
            });

            pwdInput.addEventListener('input', function () {
                const val = this.value;
                
                if (val.length === 0) {
                    rulesBox.style.display = 'none';
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.innerHTML = btnOriginalText;
                    return;
                }

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
                    submitBtn.style.opacity = '1';
                    submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Password Verified. Update Ready.';
                } else {
                    rulesBox.classList.remove('all-valid');
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.5';
                    submitBtn.innerHTML = '<i class="fas fa-lock"></i> Requirements Not Met';
                }
            });
        }
    </script>
</body>
</html>