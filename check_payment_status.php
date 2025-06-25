<?php
session_start();
require_once 'config.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$sql = "SELECT b.id, (SELECT status FROM payments p WHERE p.booking_id = b.id ORDER BY p.payment_date DESC LIMIT 1) as payment_status
        FROM bookings b WHERE b.user_id = ? AND b.status IN ('pending', 'confirmed', 'paid')";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['updated' => false]);
    exit;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$updated = false;

while ($row = $result->fetch_assoc()) {
    if ($row['payment_status'] == 'paid') {
        $updated = true;
        break;
    }
}

echo json_encode(['updated' => $updated]);
$stmt->close();
$conn->close();
?>