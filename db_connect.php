<?php
$servername = "127.0.0.1"; // Use IP to avoid DNS issues
$username = "root";
$password = "";
$dbname = "pc_shop_db"; 
$port = 3306; // Check XAMPP MySQL port (usually 3306 or 3307)

// Createconnection
$conn = mysqli_connect($servername, $username, $password, $dbname, $port);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>