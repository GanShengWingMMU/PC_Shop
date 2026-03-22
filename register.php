<?php
// 1. 在输出任何画面之前，先开启 Session 和数据库连接
session_start();
require_once 'config.php';

// 如果用户已经登录过了，直接踢回主页，不需要再注册
if (isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit();
}

$error_msg = "";
$success_msg = "";

// 2. 处理表单提交 (核心逻辑全部放在上面)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error_msg = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } else {
        // 🚨 注意：这里已经改成了查询你真实的 customers 表
        $check_stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $error_msg = "This email is already registered. Please log in.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 🚨 注意：这里插入的是 customers 表，密码字段名为 password
            $insert_stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $first_name, $last_name, $email, $hashed_password);

            if ($insert_stmt->execute()) {
                $success_msg = "Account created successfully! You can now log in.";
            } else {
                $error_msg = "Oops! Something went wrong. Please try again later.";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

// ==========================================
// 3. 后端逻辑走完后，现在才开始加载前台画面！
// ==========================================
include 'includes/header.php'; 
?>

<div class="auth-container">
    <h2 class="auth-title">Create Account</h2>
    
    <?php if(!empty($error_msg)): ?>
        <div class="text-danger" style="text-align: center; margin-bottom: 15px; border: 1px solid #ff4d4d; padding: 10px; border-radius: 6px; background: rgba(255, 77, 77, 0.1);">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>
    
    <?php if(!empty($success_msg)): ?>
        <div class="text-success" style="color: #00e676; background: rgba(0, 230, 118, 0.1); padding: 15px; border-radius: 6px; border: 1px solid rgba(0, 230, 118, 0.3); text-align: center;">
            <i class="fas fa-check-circle" style="font-size: 1.5rem; margin-bottom: 10px; display: block;"></i> 
            <strong style="display: block; margin-bottom: 15px;"><?php echo $success_msg; ?></strong>
            <a href="login.php" class="btn btn-primary" style="width: 100%;">Go to Login</a>
        </div>
    <?php else: ?>

    <form action="register.php" method="POST">
        <div style="display: flex; gap: 15px;">
            <div class="form-group" style="flex: 1;">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-control" placeholder="e.g. Xuan Ming" required value="<?php echo isset($_POST['first_name']) ? $_POST['first_name'] : ''; ?>">
            </div>
            <div class="form-group" style="flex: 1;">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-control" placeholder="e.g. Yeoh" required value="<?php echo isset($_POST['last_name']) ? $_POST['last_name'] : ''; ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="name@example.com" required value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
        </div>

        <div class="form-group">
            <label class="form-label">Re-Enter Password</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Must match password" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
            Create Account <i class="fas fa-arrow-right"></i>
        </button>
    </form>
    
    <?php endif; ?>

    <div style="text-align: center; margin-top: 25px; font-size: 0.9rem; color: var(--text-muted);">
        Already have an account? <a href="login.php" style="color: var(--accent-blue); font-weight: bold;">Log in here</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>