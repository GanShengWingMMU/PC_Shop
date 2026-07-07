<?php
session_start();
if (file_exists('config.php')) { require_once 'config.php'; } 
else { include 'db_connect.php'; }

// 引入 PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$current_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';
$logged_in_id = $_SESSION['admin_id'] ?? 0;
$staff_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (empty($current_role) || (strtolower($current_role) !== 'superadmin' && $logged_in_id !== $staff_id)) {
    die("<div style='background:#000; color:#ff4d4d; padding:50px; text-align:center; font-family:monospace;'>ACCESS DENIED: ALPHA REQUIRED OR OWNER ONLY.</div>");
}

$error = "";
$success_msg = "";
if ($staff_id <= 0) { header("Location: admin_dashboard.php"); exit(); }

$stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$staff) { header("Location: admin_dashboard.php"); exit(); }

$back_link = (strtolower($current_role) === 'superadmin') ? 'manage_staff.php' : 'admin_dashboard.php';
$back_text = (strtolower($current_role) === 'superadmin') ? 'Back to Command Roster' : 'Back to Dashboard';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_staff'])) {
    $email = trim($_POST['email']);
    
    
    // Check if new password is provided
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($email)) {
        $error = "Email is required.";
    } elseif (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            $error = "⚠️ Passwords do not match.";
        } elseif (strlen($new_password) < 12 || !preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password) || !preg_match('/[^A-Za-z0-9]/', $new_password)) {
            $error = "⚠️ Password does not meet security protocols.";
        }
    }

    if (empty($error)) {
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE admins SET email = ?, password = ? WHERE admin_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssi", $email, $hashed_password, $staff_id);
        } else {
            $update_sql = "UPDATE admins SET email = ? WHERE admin_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("si", $email, $staff_id);
        }

        if ($stmt->execute()) {
            
            if (!empty($new_password)) {
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'ahaa3153@gmail.com'; 
                    $mail->Password   = 'ojhnofgqawsvclvq'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    // Recipients
                    $mail->setFrom('no-reply@gridcitypc.com', 'GridCity Command Center');
                    $mail->addAddress($email, $staff['username']); 

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = '[GridCity PC] Administrative Security Protocol Update';
                    

                    $mail->Body = "
                    <div style='background-color:#030305; padding:40px; font-family:\"Courier New\", Courier, monospace; color:#cbd5e1; border: 1px solid #333;'>
                        <div style='text-align:center; margin-bottom: 30px;'>
                            <h1 style='color:#00f2fe; margin:0; text-transform:uppercase; letter-spacing: 2px;'>GridCity Network</h1>
                            <div style='color:#a855f7; font-weight:bold; font-size:12px; letter-spacing: 1px;'>CLEARANCE UPDATE DETECTED</div>
                        </div>
                        
                        <p>Greetings, <strong>" . htmlspecialchars($staff['username']) . "</strong>,</p>
                        
                        <p>This is an automated notification from the GridCity PC Command Center. Your administrative access credentials have been modified.</p>
                        
                        <div style='background:rgba(0,242,254,0.05); border-left:4px solid #00f2fe; padding:20px; margin: 25px 0;'>
                            <p style='margin:0 0 10px 0; color:#fff;'><strong>Access Data:</strong></p>
                            <p style='margin:0; font-size:16px;'><span style='color:#64748b;'>Role:</span> <strong style='color:#a855f7; text-transform:uppercase;'>" . htmlspecialchars($staff['role']) . " (Locked)</strong></p>
                            <p style='margin:10px 0 0 0; font-size:16px;'><span style='color:#64748b;'>New Password:</span> <strong style='color:#00f2fe; background:#000; padding:5px 10px; border-radius:4px;'>" . htmlspecialchars($new_password) . "</strong></p>
                        </div>
                        
                        <p style='color:#ff4d4d; font-size:12px; font-weight:bold;'>WARNING: DO NOT SHARE THIS TRANSMISSION. Destroy this log after updating your personal memory bank.</p>
                        
                        <p>End of transmission.</p>
                    </div>";

                    $mail->send();
                    $success_msg = "✅ Target credentials updated & Security protocol transmitted (Email Sent).";
                } catch (Exception $e) {
                    $success_msg = "⚠️ Credentials updated, but email transmission failed: {$mail->ErrorInfo}";
                }
            } else {
                $success_msg = "✅ Personnel profile updated successfully.";
            }


            if ($logged_in_id === $staff_id && !empty($new_password)) {
                session_destroy();
                header("Location: admin_login.php?msg=password_changed");
                exit();
            }


            $stmt_refresh = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
            $stmt_refresh->bind_param("i", $staff_id);
            $stmt_refresh->execute();
            $staff = $stmt_refresh->get_result()->fetch_assoc();
            $stmt_refresh->close();

        } else {
            $error = "Database Error: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Personnel - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        html, body { height: auto; min-height: 100vh; margin: 0; overflow-y: auto; background-color: var(--bg-main); }
        .admin-container { display: flex; min-height: 100vh; width: 100%; }
        .admin-sidebar { position: fixed; top: 0; left: 0; height: 100vh; z-index: 100; }
        .admin-content { margin-left: 250px; flex: 1; padding: 30px !important; padding-bottom: 120px !important; min-height: 100vh; box-sizing: border-box; }
        
        .profile-card { background: rgba(0,0,0,0.4); padding: 40px; border-radius: 12px; border: 1px dashed rgba(255,255,255,0.1); margin-bottom: 30px; display: flex; align-items: center; gap: 30px;}
        .avatar-lg { width: 80px; height: 80px; border-radius: 20px; background: linear-gradient(135deg, #a855f7, #6b21a8); display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 2.5rem; color: #fff; box-shadow: 0 0 30px rgba(168,85,247,0.3);}
        
        .product-form { background: rgba(0,0,0,0.5); padding: 30px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); overflow: visible; display: block; }
        .form-control { width: 100%; background: rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 12px; border-radius: 6px; box-sizing: border-box; font-family: 'JetBrains Mono', monospace;}
        .form-control:focus { outline: none; border-color: #a855f7; box-shadow: 0 0 10px rgba(168, 85, 247, 0.2); }
        
        .pwd-rules-container { margin-top: 15px; padding: 15px; background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1); border-radius: 8px; display: none; }
        .rule-item { color: #64748b; font-size: 12px; margin-bottom: 5px; font-family: 'JetBrains Mono', monospace; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .rule-item.valid { color: #00e676; text-shadow: 0 0 5px rgba(0,230,118,0.5); }
        .pwd-rules-container.all-valid { border-color: #00e676; background: rgba(0,230,118,0.05); }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'admin_sidebar.php'; ?>

        <div class="admin-content">
            <header class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 style="color: #a855f7; margin: 0;"><i class="fas fa-id-card"></i> Modify Personnel Profile</h2>
                <a href="<?php echo $back_link; ?>" class="btn-action" style="color: #888; border-color: #555; text-decoration:none;">&larr; <?php echo $back_text; ?></a>
            </header>

            <?php if ($error) echo "<div style='color:#ff4d4d; background:rgba(255,77,77,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid #ff4d4d;'><i class='fas fa-exclamation-triangle'></i> $error</div>"; ?>
            <?php if ($success_msg) echo "<div style='color:#00e676; background:rgba(0,230,118,0.1); padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid #00e676;'><i class='fas fa-check-circle'></i> $success_msg</div>"; ?>

            <div class="profile-card">
                <div class="avatar-lg"><?php echo strtoupper(substr($staff['username'], 0, 1)); ?></div>
                <div>
                    <h1 style="margin:0 0 5px 0; color:#fff; font-size: 2rem;"><?php echo htmlspecialchars($staff['username']); ?></h1>
                    <span style="background: rgba(168, 85, 247, 0.2); color: #a855f7; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; border: 1px solid rgba(168, 85, 247, 0.5); text-transform: uppercase;">
                        <i class="fas fa-shield-alt"></i> CLEARANCE: <?php echo htmlspecialchars($staff['role']); ?>
                    </span>
                    <span style="color: #64748b; font-size: 12px; margin-left: 15px; font-family:'JetBrains Mono';">ID: #<?php echo str_pad($staff['admin_id'], 4, '0', STR_PAD_LEFT); ?></span>
                </div>
            </div>

            <form method="POST" class="product-form" id="staffForm">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label style="color: #cbd5e1; font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block;">Target Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($staff['email']); ?>" required>
                    </div>

                   
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label style="color: #cbd5e1; font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block;">Authorization Level (Role)</label>
                        <input type="text" class="form-control" value="<?php echo strtoupper($staff['role']); ?>" disabled style="background: rgba(255,255,255,0.05); color: #888; border-color: rgba(255,255,255,0.05); cursor: not-allowed; font-weight: bold;">
                        

                    <div class="form-group" style="grid-column: 1 / -1; margin-top: 20px; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 20px;">
                        <label style="color: #00f2fe; font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block;"><i class="fas fa-key"></i> Modify Authentication Key </label>

                        
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div style="position: relative;">
        <input type="password" name="password" id="new_pwd" class="form-control" placeholder="New Password" style="padding-right: 40px;">
        <i class="fas fa-eye toggle-password" data-target="new_pwd" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #64748b; cursor: pointer; transition: 0.3s;"></i>
    </div>
    <div style="position: relative;">
        <input type="password" name="confirm_password" id="cfm_pwd" class="form-control" placeholder="Verify New Password" style="padding-right: 40px;">
        <i class="fas fa-eye toggle-password" data-target="cfm_pwd" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #64748b; cursor: pointer; transition: 0.3s;"></i>
    </div>
</div>

                        <div class="pwd-rules-container" id="rulesBox">
                            <div class="rule-item" id="rule-length"><i class="fas fa-times-circle"></i> Minimum 12 characters</div>
                            <div class="rule-item" id="rule-upper"><i class="fas fa-times-circle"></i> At least one uppercase letter (A-Z)</div>
                            <div class="rule-item" id="rule-lower"><i class="fas fa-times-circle"></i> At least one lowercase letter (a-z)</div>
                            <div class="rule-item" id="rule-number"><i class="fas fa-times-circle"></i> At least one number (0-9)</div>
                            <div class="rule-item" id="rule-special"><i class="fas fa-times-circle"></i> At least one special character (!@#$%^&*)</div>
                            <div class="rule-item" id="rule-match" style="margin-top:10px; border-top:1px dashed rgba(255,255,255,0.1); padding-top:5px;"><i class="fas fa-times-circle"></i> Passwords must match</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group full-width" style="margin-top: 30px;">
                    <button type="submit" name="update_staff" id="submitBtn" style="width: 100%; background: linear-gradient(135deg, #a855f7, #6b21a8); color: #fff; border: none; padding: 18px; border-radius: 8px; font-weight: 900; font-size: 14px; cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 2px;">
                        <i class="fas fa-user-edit"></i> Override Profile Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const pwdInput = document.getElementById('new_pwd');
        const confirmPwdInput = document.getElementById('cfm_pwd');
        const rulesBox = document.getElementById('rulesBox');
        const submitBtn = document.getElementById('submitBtn');
        const btnOriginalText = submitBtn.innerHTML;

        const rules = {
            length: { regex: /.{12,}/, el: document.getElementById('rule-length') },
            upper: { regex: /[A-Z]/, el: document.getElementById('rule-upper') },
            lower: { regex: /[a-z]/, el: document.getElementById('rule-lower') },
            number: { regex: /[0-9]/, el: document.getElementById('rule-number') },
            special: { regex: /[^A-Za-z0-9]/, el: document.getElementById('rule-special') }
        };

        const matchRule = document.getElementById('rule-match');

        if (pwdInput && confirmPwdInput) {
            pwdInput.addEventListener('input', function () {
                const val = this.value;
                const confirmVal = confirmPwdInput.value;
                
                // If emptied, hide rules and allow submit (optional change mode)
                if (val === '') {
                    rulesBox.style.display = 'none';
                    confirmPwdInput.value = '';
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

                // Check match dynamically
                if (val === confirmVal && val !== '') {
                    matchRule.classList.add('valid');
                    matchRule.innerHTML = '<i class="fas fa-check-circle"></i> Passwords must match';
                } else {
                    matchRule.classList.remove('valid');
                    matchRule.innerHTML = '<i class="fas fa-times-circle"></i> Passwords must match';
                    allValid = false;
                }

                if (allValid) {
                    rulesBox.classList.add('all-valid');
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Security Cleared. Ready to Execute.';
                } else {
                    rulesBox.classList.remove('all-valid');
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.5';
                    submitBtn.innerHTML = '<i class="fas fa-lock"></i> Requirements Not Met';
                }
            });

            confirmPwdInput.addEventListener('input', function() {
                // Trigger the pwdInput event to re-evaluate the matching rule
                const event = new Event('input');
                pwdInput.dispatchEvent(event);
            });
        }
        //show password toggle
        const togglePasswordIcons = document.querySelectorAll('.toggle-password');
        togglePasswordIcons.forEach(icon => {
            icon.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const targetInput = document.getElementById(targetId);
                
                if (targetInput.type === 'password') {
                    targetInput.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                    this.style.color = '#00f2fe'; 
                } else {
                    targetInput.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                    this.style.color = '#64748b'; 
                }
            });
        });
    </script>
</body>
</html>