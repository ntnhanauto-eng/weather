<?php
include 'config.php';
checkAuth();

$rooms = [];
$res = mysqli_query($conn, "SELECT * FROM rooms ORDER BY floor DESC, room_number ASC");
while ($r = mysqli_fetch_assoc($res)) {
    $rooms[] = $r;
}
header('Content-Type: application/json');
echo json_encode($rooms);
?>
