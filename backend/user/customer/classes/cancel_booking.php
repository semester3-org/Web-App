<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

$input = json_decode(file_get_contents('php://input'), true);
$booking_id = intval($input['booking_id'] ?? 0);
$user_id = intval($_SESSION['user_id']);

if ($booking_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Booking ID tidak valid']);
    exit;
}

// Check if booking exists and belongs to user
$check_sql = "SELECT id, status FROM bookings WHERE id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $booking_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Booking tidak ditemukan']);
    exit;
}

$booking = $result->fetch_assoc();

if ($booking['status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'Hanya booking dengan status pending yang bisa dibatalkan']);
    exit;
}

// Update booking status to cancelled
$update_sql = "UPDATE bookings SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("i", $booking_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Booking berhasil dibatalkan']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal membatalkan booking']);
}

$update_stmt->close();
$conn->close();
?>