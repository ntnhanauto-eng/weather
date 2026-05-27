<?php
include 'config.php';
checkAuth();
checkAdmin();

$logs = mysqli_query($conn, "SELECT * FROM system_logs ORDER BY log_time DESC");
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Hệ thống Audit Logs</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    th { background: #2c3e50; color: white; }
    tr:nth-child(even) { background: #f2f2f2; }
</style>
</head>
<body>
<a href="index.php" style="text-decoration:none; font-weight:bold;"><- Quay lại Bảng điều khiển</a>
<h2>HỆ THỐNG KIỂM TOÁN HÀNH VI NHÂN VIÊN (LƯU TRỮ VỆ SỐNG 30 NGÀY)</h2>
<p><i>Hệ thống tự động thực thi tiến trình xóa bỏ các bản ghi lịch sử vượt quá thời hạn 30 ngày.</i></p>

<table>
    <tr><th>ID nhật ký</th><th>Thời gian hệ thống</th><th>Tài khoản thực hiện</th><th>Nội dung thao tác nghiệp vụ trên phần mềm</th></tr>
    <?php while($row = mysqli_fetch_assoc($logs)): ?>
    <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo $row['log_time']; ?></td>
        <td><b><?php echo $row['username']; ?></b></td>
        <td><?php echo $row['action']; ?></td>
    </tr>
    <?php endwhile; ?>
</table>
</body>
</html>
