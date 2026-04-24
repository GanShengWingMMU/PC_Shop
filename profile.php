<?php
ob_start();
session_start();
require_once 'config.php';

// ==========================================
// 1. 门卫拦截 (Auth Guard)
// ==========================================
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$update_msg = "";
$update_err = "";

// ==========================================
// 🌟 2. 处理表单提交 (Update Profile Logic)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_username) || empty($new_email)) {
        $update_err = "Username and Email cannot be empty.";
    } else {
        // 检查邮箱或用户名是否被别人抢占了
        $check_stmt = $conn->prepare("SELECT customer_id FROM customers WHERE (email = ? OR username = ?) AND customer_id != ?");
        $check_stmt->bind_param("ssi", $new_email, $new_username, $customer_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $update_err = "Username or Email is already taken by another user.";
        } else {
            if (!empty($new_password)) {
                // 如果用户输入了新密码，检查密码一致性
                if ($new_password !== $confirm_password) {
                    $update_err = "New passwords do not match.";
                } elseif (strlen($new_password) < 6) {
                    $update_err = "Password must be at least 6 characters long.";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE customers SET username = ?, email = ?, password = ? WHERE customer_id = ?");
                    $update_stmt->bind_param("sssi", $new_username, $new_email, $hashed_password, $customer_id);
                    if ($update_stmt->execute()) {
                        $update_msg = "Profile and password updated successfully!";
                        $_SESSION['username'] = $new_username; // 更新右上角的显示
                    } else {
                        $update_err = "Failed to update profile.";
                    }
                    $update_stmt->close();
                }
            } else {
                // 如果密码为空，只更新用户名和邮箱
                $update_stmt = $conn->prepare("UPDATE customers SET username = ?, email = ? WHERE customer_id = ?");
                $update_stmt->bind_param("ssi", $new_username, $new_email, $customer_id);
                if ($update_stmt->execute()) {
                    $update_msg = "Profile updated successfully!";
                    $_SESSION['username'] = $new_username; // 更新右上角的显示
                } else {
                    $update_err = "Failed to update profile.";
                }
                $update_stmt->close();
            }
        }
        $check_stmt->close();
    }
}

// ==========================================
// 3. 抓取玩家基础资料 (Fetch User Info)
// ==========================================
$stmt_user = $conn->prepare("SELECT username, email FROM customers WHERE customer_id = ?");
$stmt_user->bind_param("i", $customer_id);
$stmt_user->execute();
$user_info = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// ==========================================
// 4. 抓取草稿箱主单 (Fetch Saved Builds)
// ==========================================
$stmt_builds = $conn->prepare("SELECT pc_build, build_name, total_price, created_at FROM saved_builds WHERE customer_id = ? ORDER BY created_at DESC");
$stmt_builds->bind_param("i", $customer_id);
$stmt_builds->execute();
$builds_result = $stmt_builds->get_result();
$saved_builds = [];
while ($row = $builds_result->fetch_assoc()) {
    $saved_builds[] = $row;
}
$stmt_builds->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GridCitY PC - My Armory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .page-header { background: linear-gradient(90deg, rgba(10,10,10,1) 0%, rgba(0,242,254,0.1) 100%); padding: 40px; border-radius: 12px; margin-bottom: 30px; border: 1px solid rgba(0,242,254,0.2); }
        .builds-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .build-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 20px; transition: 0.3s; position: relative; overflow: hidden; }
        .build-card:hover { transform: translateY(-3px); border-color: var(--accent); box-shadow: 0 5px 20px rgba(0,242,254,0.1); }
        .build-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--accent); opacity: 0; transition: 0.3s; }
        .build-card:hover::before { opacity: 1; }
        .part-list { margin-top: 15px; padding-top: 15px; border-top: 1px dashed rgba(255,255,255,0.1); }
        .part-item { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 8px; color: #aaa; }
        .part-cat { color: var(--accent); font-weight: bold; width: 80px; flex-shrink: 0; }
        .part-name { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        /* Account Settings Form Styles */
        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .settings-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="main-container" style="padding: 40px 20px;">
    
    <div class="page-header">
        <h1 style="font-size: 2.5rem; margin: 0; color: #fff;">COMMAND <span style="color: var(--accent);">CENTER</span></h1>
        <p style="color: #aaa; margin-top: 10px; font-size: 1.1rem;">Manage your profile and custom blueprints.</p>
    </div>

    <div class="auth-container" style="max-width: 100%; margin: 0 0 40px 0; padding: 30px;">
        <h2 style="color: #fff; margin-bottom: 20px; font-size: 1.5rem;"><i class="fas fa-user-cog" style="color: var(--accent);"></i> Account Settings</h2>

        <?php if (!empty($update_msg)): ?>
            <div style="color: #00e676; padding: 12px; border: 1px solid #00e676; border-radius: 6px; background: rgba(0,230,118,0.1); margin-bottom: 20px; font-weight: bold;">
                <i class="fas fa-check-circle"></i> <?php echo $update_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($update_err)): ?>
            <div style="color: #ff4d4d; padding: 12px; border: 1px solid #ff4d4d; border-radius: 6px; background: rgba(255,77,77,0.1); margin-bottom: 20px; font-weight: bold;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $update_err; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="settings-grid">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user_info['username'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">New Password <span style="font-size: 0.8rem; color: #888;">(Leave blank to keep current)</span></label>
                <div style="position: relative;">
                    <input type="password" name="new_password" class="form-control" placeholder="New Password" style="padding-right: 40px;">
                    <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; transition: 0.2s;"></i>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <div style="position: relative;">
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" style="padding-right: 40px;">
                    <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; transition: 0.2s;"></i>
                </div>
            </div>

            <div style="grid-column: 1 / -1; margin-top: 10px;">
                <button type="submit" name="update_profile" class="btn btn-primary" style="padding: 12px 30px;"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>

    <h2 style="color: #fff; margin-bottom: 20px; font-size: 1.5rem;"><i class="fas fa-microchip" style="color: var(--accent);"></i> My Saved Blueprints</h2>
    
    <div class="builds-grid">
        <?php if (count($saved_builds) > 0): ?>
            <?php foreach ($saved_builds as $build): ?>
                <div class="build-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                        <div>
                            <h3 style="color: #fff; font-size: 1.2rem; margin-bottom: 5px;"><?php echo htmlspecialchars($build['build_name']); ?></h3>
                            <div style="color: #666; font-size: 0.8rem;"><i class="far fa-calendar-alt"></i> <?php echo date('d M Y, H:i', strtotime($build['created_at'])); ?></div>
                        </div>
                        <div style="color: #00e676; font-size: 1.2rem; font-weight: 900;">RM <?php echo number_format($build['total_price'], 2); ?></div>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <a href="load_build.php?id=<?php echo $build['pc_build']; ?>" class="btn-action btn-select" style="flex: 1; padding: 8px;"><i class="fas fa-upload"></i> Load to Builder</a>
                    </div>

                    <div class="part-list">
                        <?php
                            $stmt_items = $conn->prepare("SELECT c.category_name, p.product_name FROM build_items bi JOIN products p ON bi.product_id = p.product_id JOIN categories c ON p.category_id = c.category_id WHERE bi.pc_build = ?");
                            $stmt_items->bind_param("i", $build['pc_build']);
                            $stmt_items->execute();
                            $items_res = $stmt_items->get_result();
                            
                            while ($item = $items_res->fetch_assoc()):
                        ?>
                            <div class="part-item">
                                <div class="part-cat"><?php echo htmlspecialchars($item['category_name']); ?></div>
                                <div class="part-name" title="<?php echo htmlspecialchars($item['product_name']); ?>">
                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                </div>
                            </div>
                        <?php 
                            endwhile; 
                            $stmt_items->close();
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; background: rgba(0,0,0,0.2); padding: 40px; border-radius: 12px; border: 1px dashed rgba(255,255,255,0.1);">
                <i class="fas fa-box-open" style="font-size: 3rem; color: #444; margin-bottom: 15px;"></i>
                <h3 style="color: #fff; margin-bottom: 5px;">Armory is Empty</h3>
                <p style="color: #888; font-size: 0.95rem;">You haven't saved any custom PC blueprints yet.</p>
                <a href="builder.php" class="btn btn-primary" style="margin-top: 20px; display: inline-block;"><i class="fas fa-tools"></i> Start Building</a>
            </div>
        <?php endif; ?>
    </div>

</main>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleIcons = document.querySelectorAll('.toggle-password');
    toggleIcons.forEach(function(icon) {
        icon.addEventListener('click', function() {
            const inputField = this.previousElementSibling;
            if (inputField.type === 'password') {
                inputField.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
                this.style.color = 'var(--accent-blue)';
            } else {
                inputField.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
                this.style.color = '#888';
            }
        });
    });
});
</script>

</body>
</html>