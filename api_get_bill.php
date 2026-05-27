<?php
include 'config.php';
checkAuth();

$room_id = (int)($_GET['room_id'] ?? 0);
$data = ['services' => []];

$query = "SELECT rs.quantity, s.service_name, s.price 
          FROM room_services rs 
          JOIN services s ON rs.service_id = s.id 
          WHERE rs.room_id = $room_id";
$res = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($res)) {
    $data['services'][] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>
