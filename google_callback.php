<?php
session_start();
require_once 'db_connect.php';

$client_id = '136647455136-lttdv812q1oc977eg3hqnv52o2pfak32.apps.googleusercontent.com';
$client_secret = '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'; 
$redirect_uri = 'http://localhost/projects/google_callback.php';

if (isset($_GET['code'])) {
    $code = $_GET['code'];

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
        
        $profile_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $profile_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token));
        $profile_response = curl_exec($ch);
        curl_close($ch);

        $profile_data = json_decode($profile_response, true);
        
        if (isset($profile_data['email'])) {
            $email = mysqli_real_escape_string($conn, $profile_data['email']);
            $first_name = mysqli_real_escape_string($conn, $profile_data['given_name'] ?? 'Google');
            $last_name = mysqli_real_escape_string($conn, $profile_data['family_name'] ?? 'User');

            $check_sql = "SELECT customer_id, first_name, last_name FROM customers WHERE email = '$email'";
            $result = $conn->query($check_sql);

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $_SESSION['customer_id'] = $row['customer_id'];
                $_SESSION['first_name'] = $row['first_name'];
                $_SESSION['last_name'] = $row['last_name'];
            } else {
                $random_password = md5(time() . rand(1, 1000)); 
                $insert_sql = "INSERT INTO customers (first_name, last_name, email, password, account_status) 
                               VALUES ('$first_name', '$last_name', '$email', '$random_password', 'Active')";
                
                if ($conn->query($insert_sql) === TRUE) {
                    $_SESSION['customer_id'] = $conn->insert_id; 
                    $_SESSION['first_name'] = $first_name;
                    $_SESSION['last_name'] = $last_name;
                }
            }

            $_SESSION['role'] = 'Customer';
            header("Location: index.php");
            exit();
        }
    }
} else {
    header("Location: login.php");
    exit();
}
?>