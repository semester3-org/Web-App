<?php
/**
 * ============================================
 * UPDATE BOOKING STATUS API
 * File: backend/user/owner/classes/update_booking_status.php
 * ============================================
 */

session_start();
require_once "../../../config/db.php";

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$owner_id = $_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$booking_id = isset($input['booking_id']) ? intval($input['booking_id']) : 0;
$status = isset($input['status']) ? $input['status'] : '';
$notes = isset($input['notes']) ? $input['notes'] : null;

// Validate input
if ($booking_id == 0 || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

// Validate status
$allowed_statuses = ['confirmed', 'rejected', 'cancelled'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Verify ownership - booking must be for owner's property
    $check_sql = "SELECT b.id, b.kos_id, b.status as current_status, k.available_rooms 
                  FROM bookings b 
                  JOIN kos k ON b.kos_id = k.id 
                  WHERE b.id = ? AND k.owner_id = ?";
    
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $booking_id, $owner_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception('Booking tidak ditemukan atau Anda tidak memiliki akses');
    }
    
    $booking_data = $result->fetch_assoc();
    $kos_id = $booking_data['kos_id'];
    $current_status = $booking_data['current_status'];
    $available_rooms = $booking_data['available_rooms'];
    
    // Check if status change is allowed
    if ($current_status !== 'pending') {
        throw new Exception('Hanya booking dengan status pending yang dapat diubah');
    }
    
    // Update booking status
    $update_sql = "UPDATE bookings SET 
                   status = ?,
                   notes = ?,
                   updated_at = CURRENT_TIMESTAMP
                   WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssi", $status, $notes, $booking_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Gagal update status booking');
    }
    
    // If confirmed, decrease available_rooms
    if ($status === 'confirmed') {
        if ($available_rooms <= 0) {
            throw new Exception('Tidak ada kamar tersedia');
        }
        
        $update_rooms_sql = "UPDATE kos SET available_rooms = available_rooms - 1 WHERE id = ?";
        $update_rooms_stmt = $conn->prepare($update_rooms_sql);
        $update_rooms_stmt->bind_param("i", $kos_id);
        
        if (!$update_rooms_stmt->execute()) {
            throw new Exception('Gagal update jumlah kamar');
        }
    }
    
    // Insert notification (optional - if you have notifications table)
    // You can add notification logic here
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Status booking berhasil diupdate'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>