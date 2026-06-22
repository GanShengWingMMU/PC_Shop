<?php
session_start();
require_once 'config.php';


$client_id = '136647455136-lttdv812q1oc977eg3hqnv52o2pfak32.apps.googleusercontent.com';
$client_secret = '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'; 
$redirect_uri = 'http://localhost/projects/google_callback.php';

if (isset($_GET['code'])) {
    
   
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

        
        $profile_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $access_token;
        $profile_response = file_get_contents($profile_url);
        $profile_data = json_decode($profile_response, true);
        
        if (isset($profile_data['email'])) {
            $email = $profile_data['email'];
            $first_name = $profile_data['given_name'] ?? 'Google';
            $last_name = $profile_data['family_name'] ?? 'User';
            $full_username = $first_name . ' ' . $last_name;

            
            $check_stmt = $conn->prepare("SELECT customer_id, username FROM customers WHERE email = ?");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            if ($result->num_rows > 0) {
         
                $row = $result->fetch_assoc();
                
                
                session_regenerate_id(true);
                
                $_SESSION['customer_id'] = $row['customer_id'];
                $_SESSION['username'] = $row['username'];
            } else {
                
                $random_secure_pass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); 
                
                
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

            $_SESSION['role'] = 'customer';
            header("Location: index.php");
            exit();
        }
    }
}


header("Location: login.php?error=oauth_failed");
exit();
?>