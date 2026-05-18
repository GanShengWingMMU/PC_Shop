<?php
session_start();
require_once 'config.php';

// Google OAuth 凭据 (建议也放入 keys.php，这里为保持完整性保留)
$client_id = '136647455136-lttdv812q1oc977eg3hqnv52o2pfak32.apps.googleusercontent.com';
$client_secret = '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'; 
$redirect_uri = 'http://localhost/projects/google_callback.php';

if (isset($_GET['code'])) {
    
    // 🌟 核心防线：CSRF State Token 校验 (防止恶意的钓鱼绑定)
    if (!isset($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
        die("Security Alert: Invalid OAuth state parameter. Possible CSRF attack intercepted.");
    }
    
    $code = $_GET['code'];

    // 1. 获取 Access Token
    $token_url = 'https://oauth2.googleapis.com/token';
    $post_data = [
        'code' => $code,
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (isset($token_data['access_token'])) {
        $access_token = $token_data['access_token'];

        // 2. 获取用户资料
        $profile_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $access_token;
        $profile_response = file_get_contents($profile_url);
        $profile_data = json_decode($profile_response, true);
        
        if (isset($profile_data['email'])) {
            $email = $profile_data['email'];
            $first_name = $profile_data['given_name'] ?? 'Google';
            $last_name = $profile_data['family_name'] ?? 'User';
            $full_username = $first_name . ' ' . $last_name;

            // 🌟 A+ 安全修复：使用 Prepared Statement 检查邮箱是否已注册
            $check_stmt = $conn->prepare("SELECT customer_id, username FROM customers WHERE email = ?");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            if ($result->num_rows > 0) {
                // 已存在用户，直接登录
                $row = $result->fetch_assoc();
                
                // 🛡️ A+ 级修复：防止 Session Fixation 攻击
                session_regenerate_id(true);
                
                $_SESSION['customer_id'] = $row['customer_id'];
                $_SESSION['username'] = $row['username'];
            } else {
                // 🌟 A+ 安全修复：生成随机强密码作为初始占位符，废弃 MD5
                $random_secure_pass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); 
                
                // 🌟 A+ 安全修复：使用 Prepared Statement 插入新用户
                $insert_stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, username, email, password, account_status) VALUES (?, ?, ?, ?, ?, 'Active')");
                $insert_stmt->bind_param("sssss", $first_name, $last_name, $full_username, $email, $random_secure_pass);
                
                if ($insert_stmt->execute()) {
                    session_regenerate_id(true);
                    $_SESSION['customer_id'] = $insert_stmt->insert_id; 
                    $_SESSION['username'] = $full_username;
                }
                $insert_stmt->close();
            }
            $check_stmt->close();

            // 登录成功，跳转
            $_SESSION['role'] = 'customer';
            header("Location: index.php");
            exit();
        }
    }
}

// 如果授权失败，返回登录页
header("Location: login.php?error=oauth_failed");
exit();
?>