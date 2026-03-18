<?php
// config.php - Database Connection Setup

$host = "localhost";
$db_user = "root";       
$db_pass = "";           
$db_name = "pcshop";    // 已经更新为你指定的数据库名

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("数据库连接失败 (Database Connection Failed): " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");