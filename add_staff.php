<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }
$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
if (empty($current_role) || strtolower($current_role) !== 'superadmin') {
    die("<div style='background:#000; color:#ff4d4d; padding:50px; text-align:center; font-family:monospace;'>ACCESS DENIED: ALPHA REQUIRED.</div>");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_staff'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = trim($_POST['role']);

    if (empty($username) || empty($password) || empty($confirm_password) || empty($role)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "⚠️ Passwords do not match.";
    } else {
    
        if (strlen($password) < 12 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
            $error = "⚠️ Password does not meet the high-security requirements.";
        } else {
           
            $check_stmt = $conn->prepare("SELECT admin_id FROM admins WHERE username = ?");
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = "⚠️ Username already exists. Please choose another.";
            } else {
                
                $stmt = $conn->prepare("INSERT INTO admins (username, password, email, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $password, $email, $role);
                if ($stmt->execute()) {
                    
                    
                    $log_admin_id = $_SESSION['admin_id'];
                    $log_username = $_SESSION['admin_username'];
                    $log_role = $_SESSION['admin_role'];
                    $log_ip = $_SERVER['REMOTE_ADDR'];
                    if ($log_ip == '::1') { $log_ip = '127.0.0.1'; }
                    $action_event = "Added New Staff: " . $username; 
                    @$conn->query("INSERT INTO admin_logs (admin_id, username, role, action_event, ip_address) VALUES ('$log_admin_id', '$log_username', '$log_role', '$action_event', '$log_ip')");

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
            padding-bottom: 120px !important;
            min-height: 100vh;
            box-sizing: border-box;
        }
        
      
        .form-card {
            background: rgba(0,0,0,0.5); 
            padding: 30px; 
            border-radius: 12px; 
            border: 1px solid rgba(255,255,255,0.05);
            overflow: visible; 
            display: block;
            margin-bottom: 30px;
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
            border-color: #ff4d4d;
            box-shadow: 0 0 10px rgba(255, 77, 77, 0.2);
        }

        /*Dynamic password rules CSS */
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
    <?php include 'admin_sidebar.php'; ?>

        <div class="admin-content">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <h2 style="color: #ff4d4d; margin: 0;"><i class="fas fa-user-plus"></i> Recruit Security Personnel</h2>
                <a href="manage_staff.php" class="btn-action" style="color: #888; border-color: #555; text-decoration:none;">&larr; Abort</a>
            </header>

            <div class="form-card">
                <?php if ($error) echo "<div style='color:#ff4d4d; background:rgba(255,77,77,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid #ff4d4d;'>$error</div>"; ?>

                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                        <div class="form-group full-width">
                            <label style="color: #ff4d4d; font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block;">Username *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label style="color: #ff4d4d; font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block;">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>

                        <!-- 🌟 密码栏位 -->
                        <div class="form-group full-width">
                            <label style="color: #ff4d4d; font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block;">Password *</label>
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

                        <!-- 🌟 确认密码栏位 -->
                        <div class="form-group full-width" style="margin-top: 5px;">
                            <label style="color: #ff4d4d; font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block;">Confirm Password *</label>
                            <div style="position: relative;">
                                <input type="password" name="confirm_password" id="confirm-pwd-input" class="form-control" required style="padding-right: 40px; margin-bottom:0;">
                                <i class="fas fa-eye" id="toggle-confirm-pwd" style="position: absolute; right: 15px; top: 15px; color: #888; cursor: pointer; transition: 0.2s;"></i>
                            </div>
                            <div id="pwd-match-msg" style="font-size: 12px; margin-top: 8px; font-weight: bold; display: none;"></div>
                        </div>

                        <div class="form-group full-width" style="margin-top: 15px;">
                            <label style="color: #ff4d4d; font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block;">Role Level *</label>
                            <select name="role" class="form-control" required style="cursor:pointer;">
                                <option value="Admin">Admin (Standard)</option>
                                <option value="SuperAdmin">SuperAdmin (Alpha)</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="add_staff" id="submit-btn" disabled style="width: 100%; margin-top:40px; background: linear-gradient(135deg, #ff4d4d, #f39c12); color: #000; border: none; padding: 18px; border-radius: 8px; font-weight: 900; font-size: 16px; cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px;">
                        <i class="fas fa-lock"></i> Requirements Not Met
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const pwdInput = document.getElementById('pwd-input');
        const confirmPwdInput = document.getElementById('confirm-pwd-input');
        const togglePwd = document.getElementById('toggle-pwd');
        const toggleConfirmPwd = document.getElementById('toggle-confirm-pwd');
        const rulesBox = document.getElementById('pwd-rules');
        const submitBtn = document.getElementById('submit-btn');
        const pwdMatchMsg = document.getElementById('pwd-match-msg');

        const rules = {
            len: { el: document.getElementById('req-len'), regex: /.{12,}/ },
            up: { el: document.getElementById('req-up'), regex: /[A-Z]/ },
            low: { el: document.getElementById('req-low'), regex: /[a-z]/ },
            num: { el: document.getElementById('req-num'), regex: /[0-9]/ },
            spc: { el: document.getElementById('req-spc'), regex: /[\W_]/ }
        };

        let isPwdValid = false;
        let isMatch = false;

        // Toggle Password Visibility (Password Field)
        togglePwd.addEventListener('click', function () {
            const type = pwdInput.getAttribute('type') === 'password' ? 'text' : 'password';
            pwdInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
            this.style.color = type === 'text' ? '#ff4d4d' : '#888';
        });

        // Toggle Password Visibility (Confirm Password Field)
        toggleConfirmPwd.addEventListener('click', function () {
            const type = confirmPwdInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPwdInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
            this.style.color = type === 'text' ? '#ff4d4d' : '#888';
        });

        // Show rules on focus
        pwdInput.addEventListener('focus', () => {
            rulesBox.style.display = 'block';
        });

        // Master function to validate entire form state
        function validateForm() {
            // Check matching
            if (confirmPwdInput.value.length > 0) {
                pwdMatchMsg.style.display = 'block';
                if (pwdInput.value === confirmPwdInput.value) {
                    isMatch = true;
                    pwdMatchMsg.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
                    pwdMatchMsg.style.color = '#00e676';
                } else {
                    isMatch = false;
                    pwdMatchMsg.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
                    pwdMatchMsg.style.color = '#ff4d4d';
                }
            } else {
                pwdMatchMsg.style.display = 'none';
                isMatch = false;
            }

            // Unlock button only if rules met AND passwords match
            if (isPwdValid && isMatch) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Authorize Clearance';
            } else {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-lock"></i> Requirements Not Met';
            }
        }

        // Validate complex rules on password input
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

            isPwdValid = allValid;
            if (allValid) {
                rulesBox.classList.add('all-valid');
            } else {
                rulesBox.classList.remove('all-valid');
            }

            validateForm();
        });

        // Validate matching on confirm input
        confirmPwdInput.addEventListener('input', validateForm);
    </script>
</body>
</html>