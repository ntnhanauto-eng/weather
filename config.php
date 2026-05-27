<?php
session_name('HOTEL_PRO_SESS');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$conn = mysqli_connect("localhost", "nacwxjyg_khachsan", "9NDvHSK9dgT3JvqzSRTE", "nacwxjyg_khachsan");
if (!$conn) { die("Kết nối thất bại: " . mysqli_connect_error()); }
mysqli_set_charset($conn, "utf8mb4");

// Giá phòng mặc định: 50.000đ / Giờ
define('ROOM_PRICE_PER_HOUR', 50000);

// Hàm ghi lịch sử hoạt động của nhân viên
function writeLog($conn, $action) {
    $uid = $_SESSION['user_id'] ?? 0;
    $uname = $_SESSION['username'] ?? 'Hệ thống';
    $action = mysqli_real_escape_string($conn, $action);
    mysqli_query($conn, "INSERT INTO system_logs (user_id, username, action) VALUES ($uid, '$uname', '$action')");
}

// Tự động dọn dẹp Log cũ hơn 30 ngày để nhẹ database
mysqli_query($conn, "DELETE FROM system_logs WHERE log_time < NOW() - INTERVAL 30 DAY");

function checkAuth() {
    if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
}
function checkAdmin() {
    if ($_SESSION['role'] !== 'admin') { die("Quyền truy cập bị từ chối!"); }
}
?>
