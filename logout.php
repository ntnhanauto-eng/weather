<?php
include 'config.php';
writeLog($conn, "Đăng xuất khỏi hệ thống");
session_destroy();
header('Location: login.php');
exit();
?>
