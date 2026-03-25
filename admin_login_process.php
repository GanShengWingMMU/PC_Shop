<?php
session_start();
header('Content-Type: application/json');

$inputJSON = file_get_contents('php://input');
$requestData = json_decode($inputJSON, true);

if (!$requestData) {
    echo json_encode(["status" => "error", "message" => "System error: No data received."]);
    exit();
}

$username = $requestData['username'];
$password = $requestData['password'];

// 为了方便你现在测试，我先把密码写死。
// 只要你输入 admin 和 password，就能登录成功！
// 以后你可以把你连接数据库 (Database) 的代码换到这里。
if ($username === 'admin' && $password === 'password') {
    $_SESSION['role'] = 'admin';
    $_SESSION['username'] = $username;
    
    // 告诉前端：密码对了！(前端收到 success 就会执行 window.location.href 跳转)
    echo json_encode(["status" => "success", "message" => "Login successful"]);
} else {
    // 告诉前端：密码错了！(前端会在屏幕上显示红色的错误提示)
    echo json_encode(["status" => "error", "message" => "Invalid username or password!"]);
}
?>
