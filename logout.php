<?php
// 1. 开启或恢复现有的 Session
session_start();

// 2. 清空所有的 Session 变量 (把保险箱里的东西全拿出来)
session_unset();

// 3. 彻底销毁这个 Session (把保险箱直接砸碎)
session_destroy();

// 4. 跳转回主页
header("Location: index.php");
exit();
?>