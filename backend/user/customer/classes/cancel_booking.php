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

// === MULAI TRANSAKSI ===
$conn->begin_transaction();

try {
    // Cek booking milik user dan status pending
    $check_sql = "SELECT id, kos_id, status FROM bookings WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $booking_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows == 0) {
        throw new Exception('Booking tidak ditemukan atau bukan milik Anda');
    }

    $booking = $result->fetch_assoc();

    if ($booking['status'] !== 'pending') {
        throw new Exception('Hanya booking dengan status pending yang dapat dibatalkan');
    }

    // === UPDATE STATUS SAJA (TIDAK KEMBALIKAN KAMAR) ===
    $update_sql = "UPDATE bookings SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $booking_id);
    if (!$update_stmt->execute()) {
        throw new Exception('Gagal membatalkan booking');
    }

    // === COMMIT: Simpan perubahan ===
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Booking berhasil dibatalkan.'
    ]);

} catch (Exception $e) {
    // === ROLLBACK jika error ===
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Tutup statement
$check_stmt->close();
$update_stmt->close();
$conn->close();
?>