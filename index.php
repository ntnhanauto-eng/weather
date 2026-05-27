<?php
include 'config.php';
checkAuth();

// 1. Thao tác xử lý nghiệp vụ Khách sạn
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $room_id = (int)($_POST['room_id'] ?? 0);

    if ($action === 'checkin') {
        mysqli_query($conn, "UPDATE rooms SET status = 'khach', checkin_time = NOW() WHERE id = $room_id");
        $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT room_number FROM rooms WHERE id = $room_id"));
        writeLog($conn, "Cho khách nhận phòng {$r['room_number']}");
    } 
    elseif ($action === 'cleaning') {
        mysqli_query($conn, "UPDATE rooms SET status = 've_sinh', checkin_time = NULL WHERE id = $room_id");
        $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT room_number FROM rooms WHERE id = $room_id"));
        writeLog($conn, "Chuyển phòng {$r['room_number']} sang trạng thái dọn dẹp");
    } 
    elseif ($action === 'finish_cleaning') {
        mysqli_query($conn, "UPDATE rooms SET status = 'trong', checkin_time = NULL WHERE id = $room_id");
        $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT room_number FROM rooms WHERE id = $room_id"));
        writeLog($conn, "Hoàn tất dọn dẹp phòng {$r['room_number']}");
    } 
    elseif ($action === 'add_service') {
        $service_id = (int)$_POST['service_id'];
        $qty = (int)$_POST['quantity'];
        mysqli_query($conn, "INSERT INTO room_services (room_id, service_id, quantity) VALUES ($room_id, $service_id, $qty)");
        $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT room_number FROM rooms WHERE id = $room_id"));
        writeLog($conn, "Thêm dịch vụ cho phòng {$r['room_number']}");
    } 
    elseif ($action === 'checkout') {
        // Thực hiện xóa hóa đơn phòng cũ để giải phóng phòng về trạng thái trống
        mysqli_query($conn, "DELETE FROM room_services WHERE room_id = $room_id");
        mysqli_query($conn, "UPDATE rooms SET status = 've_sinh', checkin_time = NULL WHERE id = $room_id");
        $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT room_number FROM rooms WHERE id = $room_id"));
        writeLog($conn, "Thực hiện thanh toán và trả phòng {$r['room_number']}");
    }
    header("Location: index.php");
    exit();
}

$services_res = mysqli_query($conn, "SELECT * FROM services");
$services = [];
while ($s = mysqli_fetch_assoc($services_res)) { $services[] = $s; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sơ đồ phòng Real-time</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f6fa; }
        .nav { background: #34495e; color: white; padding: 15px; display: flex; justify-content: space-between; border-radius: 4px; }
        .nav a { color: #f1c40f; text-decoration: none; margin-left: 15px; font-weight: bold; }
        
        /* Layout thiết kế hiển thị tầng đảo ngược từ dưới lên */
        .hotel-building { display: flex; flex-direction: column; gap: 20px; margin-top: 20px; }
        .floor-row { background: white; padding: 15px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .floor-title { font-size: 18px; font-weight: bold; margin-bottom: 10px; color: #2c3e50; border-bottom: 2px solid #34495e; padding-bottom: 5px; }
        .rooms-grid { display: grid; grid-template-columns: repeat(10, 1fr); gap: 10px; }
        
        /* Bảng màu phân định trạng thái phòng */
        .room { padding: 15px 5px; text-align: center; border-radius: 4px; color: white; font-weight: bold; cursor: pointer; transition: 0.2s; min-height: 70px; display: flex; flex-direction: column; justify-content: center; }
        .room:hover { transform: scale(1.03); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
        .trong { background: #2ecc71; } /* Xanh lá: Trống */
        .khach { background: #e74c3c; } /* Đỏ: Có khách */
        .ve_sinh { background: #f1c40f; color: #2c3e50; } /* Vàng: Dọn dẹp */
        .room-time { font-size: 11px; font-weight: normal; margin-top: 5px; background: rgba(0,0,0,0.2); padding: 2px; border-radius: 2px; }
        
        /* Modal hộp thoại tương tác */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 25px; border-radius: 6px; width: 450px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .btn { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 5px; }
        .btn-close { background: #95a5a6; color: white; float: right; }
        table.bill-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.bill-table th, table.bill-table td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 13px; }
    </style>
</head>
<body>

<div class="nav">
    <div>Xin chào: <b><?php echo $_SESSION['fullname']; ?></b> (<?php echo strtoupper($_SESSION['role']); ?>)</div>
    <div>
        <a href="index.php">Sơ đồ phòng</a>
        <?php if(isAdmin()) echo '<a href="admin_users.php">Quản lý nhân viên</a>'; ?>
        <?php if(isAdmin()) echo '<a href="admin_logs.php">Xem lịch sử Log</a>'; ?>
        <a href="logout.php" style="color:#e74c3c;">Đăng xuất</a>
    </div>
</div>

<div class="hotel-building">
    <?php
    // Vòng lặp vẽ sơ đồ tầng ngược từ Tầng 4 xuống Tầng 1
    for ($f = 4; $f >= 1; $f--) {
        echo "<div class='floor-row'>";
        echo "<div class='floor-title'>TẦNG $f</div>";
        echo "<div class='rooms-grid' id='floor-$f-container'>";
        // Toàn bộ các phòng dạng Real-time sẽ được JavaScript render liên tục tại đây
        echo "</div>";
        echo "</div>";
    }
    ?>
</div>

<div class="modal" id="roomModal">
    <div class="modal-content">
        <button class="btn btn-close" onclick="closeModal()">Đóng</button>
        <h2 id="modalRoomTitle">Cấu hình phòng</h2>
        <div id="modalBody"></div>
    </div>
</div>

<script>
const servicesList = <?php echo json_encode($services); ?>;
const roomPricePerHour = <?php echo ROOM_PRICE_PER_HOUR; ?>;

function loadRoomsRealtime() {
    fetch('api_room_data.php')
        .then(res => res.json())
        .then(data => {
            // Xóa dữ liệu cũ trên giao diện
            for(let f=1; f<=4; f++) { document.getElementById(`floor-${f}-container`).innerHTML = ''; }
            
            // Đổ dữ liệu phòng real-time mới nhất
            data.forEach(room => {
                let container = document.getElementById(`floor-${room.floor}-container`);
                let timeHtml = '';
                if(room.status === 'khach' && room.checkin_time) {
                    let t = room.checkin_time.split(' ')[1].substring(0, 5); // Lấy Giờ:Phút
                    timeHtml = `<div class='room-time'>Vào lúc: ${t}</div>`;
                }
                
                let roomDiv = document.createElement('div');
                roomDiv.className = `room ${room.status}`;
                roomDiv.innerHTML = `<span>Phòng ${room.room_number}</span>${timeHtml}`;
                roomDiv.onclick = () => openRoomAction(room);
                container.appendChild(roomDiv);
            });
        });
}

function openRoomAction(room) {
    document.getElementById('modalRoomTitle').innerText = `Thao tác - Phòng ${room.room_number}`;
    let body = document.getElementById('modalBody');
    body.innerHTML = '';

    if (room.status === 'trong') {
        body.innerHTML = `
            <p>Trạng thái: <b>Phòng Trống</b></p>
            <form method="POST">
                <input type="hidden" name="room_id" value="${room.id}">
                <input type="hidden" name="action" value="checkin">
                <button type="submit" class="btn" style="background:#2ecc71; color:white; width:100%;">CHO KHÁCH NHẬN PHÒNG (CHECK IN)</button>
            </form><br>
            <form method="POST">
                <input type="hidden" name="room_id" value="${room.id}">
                <input type="hidden" name="action" value="cleaning">
                <button type="submit" class="btn" style="background:#f1c40f; width:100%;">CHUYỂN SANG BẢO TRÌ/DỌN VỆ SINH</button>
            </form>
        `;
    } else if (room.status === 've_sink' || room.status === 've_sinh') {
        body.innerHTML = `
            <p>Trạng thái: <b>Đang dọn vệ sinh</b></p>
            <form method="POST">
                <input type="hidden" name="room_id" value="${room.id}">
                <input type="hidden" name="action" value="finish_cleaning">
                <button type="submit" class="btn" style="background:#2ecc71; color:white; width:100%;">HOÀN TẤT VỆ SINH (CHUYỂN THÀNH PHÒNG TRỐNG)</button>
            </form>
        `;
    } else if (room.status === 'khach') {
        // Lấy chi tiết dịch vụ phòng bằng cơ chế gọi đồng bộ dữ liệu nhanh
        fetch(`api_get_bill.php?room_id=${room.id}`)
            .then(res => res.json())
            .then(bill => {
                let checkinDate = new Date(room.checkin_time);
                let now = new Date();
                let diffMs = now - checkinDate;
                let diffHours = Math.max(1, Math.ceil(diffMs / (1000 * 60 * 60))); // Tính block giờ tối thiểu 1 tiếng
                let roomCost = diffHours * roomPricePerHour;
                
                let serviceHtml = '';
                let serviceTotal = 0;
                bill.services.forEach(s => {
                    let sub = s.quantity * s.price;
                    serviceTotal += sub;
                    serviceHtml += `<tr><td>${s.service_name}</td><td>${s.quantity}</td><td>${s.price.toLocaleString()}đ</td><td>${sub.toLocaleString()}đ</td></tr>`;
                });

                let totalBill = roomCost + serviceTotal;

                body.innerHTML = `
                    <p>Giờ vào: <b>${room.checkin_time}</b></p>
                    <p>Thời gian ở thực tế: <b>${diffHours} giờ</b> (Tiền phòng: ${roomCost.toLocaleString()}đ)</p>
                    
                    <fieldset><legend>Thêm dịch vụ đồ uống/nước suối</legend>
                    <form method="POST">
                        <input type="hidden" name="room_id" value="${room.id}">
                        <input type="hidden" name="action" value="add_service">
                        <select name="service_id" style="padding:5px;">
                            ${servicesList.map(s => `<option value="${s.id}">${s.service_name} (${s.price}đ)</option>`).join('')}
                        </select>
                        Số lượng: <input type="number" name="quantity" value="1" min="1" style="width:50px; padding:4px;">
                        <button type="submit" class="btn" style="background:#34495e; color:white;">Thêm</button>
                    </form>
                    </fieldset>

                    <h3>HÓA ĐƠN TẠM TÍNH CHI TIẾT</h3>
                    <table class="bill-table">
                        <tr><th>Mục dùng</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr>
                        <tr><td>Tiền phòng (${diffHours} giờ)</td><td>1</td><td>${roomPricePerHour.toLocaleString()}đ</td><td>${roomCost.toLocaleString()}đ</td></tr>
                        ${serviceHtml}
                        <tr><th colspan="3">TỔNG CỘNG THANH TOÁN:</th><th style="color:red; font-size:16px;">${totalBill.toLocaleString()}đ</th></tr>
                    </table><br>

                    <form method="POST" onsubmit="return confirm('Xác nhận khách thanh toán hóa đơn tổng và trả phòng?');">
                        <input type="hidden" name="room_id" value="${room.id}">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn" style="background:#e74c3c; color:white; width:100%; font-size:16px; padding:10px;">XÁC NHẬN THANH TOÁN & TRẢ PHÒNG</button>
                    </form>
                `;
            });
    }

    document.getElementById('roomModal').style.display = 'flex';
}

function closeModal() { document.getElementById('roomModal').style.display = 'none'; }
setInterval(loadRoomsRealtime, 4000); // 4 giây đồng bộ trạng thái màu và giờ 1 lần
loadRoomsRealtime();
</script>
</body>
</html>
