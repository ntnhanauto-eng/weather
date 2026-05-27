<?php
include 'config.php';
checkAuth();
checkAdmin(); // Chặn tài khoản nhân viên vào phá dữ liệu

$message = "";

// Xử lý hành động quản trị viên
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        
        $chk = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
        if(mysqli_num_rows($chk) > 0) { $message = "Tên tài khoản này đã tồn tại!"; }
        else {
            mysqli_query($conn, "INSERT INTO users (username, password, fullname, role) VALUES ('$username', '$password', '$fullname', '$role')");
            writeLog($conn, "Tạo mới tài khoản nhân viên: $username");
            $message = "Thêm thành công tài khoản!";
        }
    } 
    elseif ($action === 'delete') {
        $id = (int)$_POST['user_id'];
        $u_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM users WHERE id = $id"));
        if($u_info['username'] === 'admin') { $message = "Không thể xóa tài khoản admin gốc!"; }
        else {
            mysqli_query($conn, "DELETE FROM users WHERE id = $id");
            writeLog($conn, "Xóa tài khoản người dùng: " . $u_info['username']);
            $message = "Đã xóa tài khoản!";
        }
    } 
    elseif ($action === 'reset_pass') {
        $id = (int)$_POST['user_id'];
        $new_pass = mysqli_real_escape_string($conn, $_POST['new_password']);
        $u_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM users WHERE id = $id"));
        
        mysqli_query($conn, "UPDATE users SET password = '$new_pass' WHERE id = $id");
        writeLog($conn, "Đổi/Cấp lại mật khẩu cho tài khoản: " . $u_info['username']);
        $message = "Đã cập nhật mật khẩu mới!";
    }
}

$users = mysqli_query($conn, "SELECT * FROM users WHERE username != 'admin'");
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Quản lý tài khoản nhân viên</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    th { background: #34495e; color: white; }
    .form-box { background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin-bottom: 20px; border-radius: 4px; }
    input, select { padding: 6px; margin-right: 10px; border: 1px solid #ccc; border-radius: 4px; }
    .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; color: white; font-weight: bold; }
</style>
</head>
<body>
<a href="index.php" style="text-decoration:none; font-weight:bold;"><- Quay lại Bảng điều khiển</a>
<h2>QUẢN LÝ DANH SÁCH NHÂN VIÊN (QUYỀN TỐI CAO ADMIN)</h2>

<?php if($message != "") echo "<p style='color:blue; font-weight:bold;'>$message</p>"; ?>

<div class="form-box">
    <h3>Thêm nhân viên mới vào hệ thống</h3>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <input type="text" name="username" placeholder="Tên đăng nhập" required>
        <input type="text" name="password" placeholder="Mật khẩu thô" required>
        <input type="text" name="fullname" placeholder="Họ và tên" required>
        <select name="role">
            <option value="staff">Nhân viên Lễ tân</option>
            <option value="admin">Quản trị viên (Admin)</option>
        </select>
        <button type="submit" class="btn" style="background:#2ecc71;">Thêm mới</button>
    </form>
</div>

<h3>Danh sách nhân viên đang vận hành</h3>
<table>
    <tr><th>ID</th><th>Tên đăng nhập</th><th>Mật khẩu hiển thị công khai</th><th>Họ và tên</th><th>Chức vụ</th><th>Thao tác quản trị</th></tr>
    <?php while($row = mysqli_fetch_assoc($users)): ?>
    <tr>
        <td><?php echo $row['id']; ?></td>
        <td><b><?php echo $row['username']; ?></b></td>
        <td style="color:#c0392b; font-family:monospace; font-weight:bold;"><?php echo $row['password']; ?></td>
        <td><?php echo $row['fullname']; ?></td>
        <td><?php echo strtoupper($row['role']); ?></td>
        <td>
            <form method="POST" style="display:inline-block;">
                <input type="hidden" name="action" value="reset_pass">
                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                <input type="text" name="new_password" placeholder="Mật khẩu mới" required style="width:100px;">
                <button type="submit" class="btn" style="background:#3498db;">Đổi Pass</button>
            </form>
            
            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Bạn thực sự muốn khai trừ nhân viên này khỏi hệ thống?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                <button type="submit" class="btn" style="background:#e74c3c;">Xóa tài khoản</button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</body>
</html>
