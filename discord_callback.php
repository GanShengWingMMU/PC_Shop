<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

$client_id = '1481496029013479464';
$client_secret = 'cium3rXnf2MWAoQH8_nFVhqv-MJQw1E8'; 
$redirect_uri = 'http://localhost/projects/discord_callback.php'; 

if (isset($_GET['code'])) {
    

    if (!isset($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
        die("Security Alert: Invalid OAuth state parameter. Possible CSRF attack intercepted.");
    }

    $code = $_GET['code'];

    $token_url = 'https://discord.com/api/oauth2/token';
    $post_data = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirect_uri
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

    $response = curl_exec($ch);
    
    if(curl_errno($ch)){
        die('cURL Error: ' . curl_error($ch));
    }
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (isset($token_data['access_token'])) {
        $access_token = $token_data['access_token'];
        
        $profile_url = 'https://discord.com/api/users/@me';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $profile_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        
        $profile_response = curl_exec($ch);
        curl_close($ch);

        $profile_data = json_decode($profile_response, true);
        
        if (isset($profile_data['email'])) {
            $email = $profile_data['email'];
            $first_name = $profile_data['username'];
            $last_name = 'Discord';
            $full_username = $first_name . '_' . rand(1000, 9999); 

           
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

            $_SESSION['role'] = 'Customer';
            header("Location: index.php");
            exit();
        } else {
             die("Login Failed: Unable to retrieve Discord Email data.");
        }
    } else {
        echo "<h3 style='color: red;'>Discord rejected the request. Error details:</h3>";
        echo "<pre style='background: #eee; padding: 20px; border-radius: 5px;'>";
        print_r($token_data);
        echo "</pre>";
        die();
    }
} else {
    header("Location: login.php");
    exit();
}
?>