<?php
// 1. 在输出任何画面之前，先开启 Session 和数据库连接
session_start();
require_once 'config.php';

// 如果用户已经登录过了，直接把他踢回主页
if (isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit();
}

$error_msg = "";

// 2. 处理表单提交 (核心逻辑全部放在上面)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_msg = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT customer_id, first_name, last_name, password, account_status FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc(); 
            
            // 检查账号状态
            if ($user['account_status'] !== 'Active') {
                $error_msg = "Your account is disabled. Please contact support.";
            } else {
                // 核对密码
                if (password_verify($password, $user['password'])) {
                    
                    // 【登录成功】发通行证
                    session_regenerate_id(true); 
                    $_SESSION['customer_id'] = $user['customer_id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['user_type'] = 'Customer'; 

                    // 🚨 完美跳转！因为上面没有任何 HTML 输出，这里 100% 生效
                    header("Location: index.php");
                    exit(); 
                } else {
                    $error_msg = "Invalid email or password.";
                }
            }
        } else {
            $error_msg = "Invalid email or password."; 
        }
        $stmt->close();
    }
}

// ==========================================
// 3. 后端逻辑走完后，现在才开始加载前台画面！
// ==========================================

// 因为我们在上面已经 session_start() 了，为了防止 header.php 里重复开启报错
// 我们可以在引入之前临时抑制一下错误，或者你确保 header.php 里的 session_start() 改成 @session_start();
include 'includes/header.php';
?>

<div class="auth-container" style="max-width: 400px; margin-top: 6rem;">
    <h2 class="auth-title">Welcome Back</h2>
    
    <?php if(!empty($error_msg)): ?>
        <div class="text-danger" style="text-align: center; margin-bottom: 15px; border: 1px solid #ff4d4d; padding: 10px; border-radius: 6px; background: rgba(255, 77, 77, 0.1);">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="name@example.com" required value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px;">
            Login <i class="fas fa-sign-in-alt"></i>
        </button>
    </form>
    
    <div style="text-align: center; margin-top: 25px; font-size: 0.9rem; color: var(--text-muted);">
        Don't have an account? <a href="register.php" style="color: var(--accent-blue); font-weight: bold;">Create one</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>