<?php
include 'config.php';
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = $_POST['password'];

    $res = mysqli_query($conn, "SELECT * FROM users WHERE username = '$user'");
    $u = mysqli_fetch_assoc($res);

    if ($u && $u['password'] === $pass) {
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['username'] = $u['username'];
        $_SESSION['fullname'] = $u['fullname'];
        $_SESSION['role'] = $u['role'];
        
        writeLog($conn, "Đăng nhập vào hệ thống");
        header('Location: index.php');
        exit();
    } else {
        $error = "Tài khoản hoặc mật khẩu không đúng!";
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Đăng nhập hệ thống</title>
<style>
    body { font-family: sans-serif; background: #2c3e50; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
    .box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); width: 300px; text-align: center; }
    input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    button { width: 100%; padding: 10px; background: #27ae60; color: white; border: none; border-radius: 4px; cursor: pointer; }
</style>
</head>
<body>
<div class="box">
    <h2>SMART HOTEL LOG IN</h2>
    <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Tên đăng nhập" required>
        <input type="password" name="password" placeholder="Mật khẩu" required>
        <button type="submit">Đăng Nhập</button>
    </form>
</div>
</body>
</html>
