<?php
// config.php - Database Connection Setup

$host = "localhost";
$db_user = "root";       
$db_pass = "";           
$db_name = "pcshop";    

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("(Database Connection Failed): " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
$smtp_password = 'vyay fzmg glan quun';