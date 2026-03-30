<?php
session_start();       // 1. Resume existing session
$_SESSION = array();   // 2. Clear session array
session_destroy();     // 3. Destroy session file

// 4. Redirect to Homepage
header("Location: admin_login.php"); 
exit();
?>