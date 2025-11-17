<?php
/**
 * ============================================
 * UPDATE BOOKING STATUS HANDLER
 * File: backend/user/owner/classes/update_booking_status.php
 * ============================================
 * Handle update status booking dan kirim notifikasi ke customer
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
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Get POST data
$booking_id = $data['booking_id'] ?? 0;
$status = $data['status'] ?? '';
$notes = $data['notes'] ?? '';

if (!$booking_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Booking ID and status required']);
    exit();
}

try {
    // Verify booking belongs to owner's property
    $sql_verify = "SELECT b.*, k.name as property_name, k.owner_id, u.full_name as customer_name
                   FROM bookings b
                   JOIN kos k ON b.kos_id = k.id
                   JOIN users u ON b.user_id = u.id
                   WHERE b.id = ? AND k.owner_id = ?";
    
    $stmt_verify = $conn->prepare($sql_verify);
    $stmt_verify->bind_param("ii", $booking_id, $owner_id);
    $stmt_verify->execute();
    $result = $stmt_verify->get_result();
    $booking = $result->fetch_assoc();
    $stmt_verify->close();
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found or unauthorized']);
        exit();
    }
    
    // Process based on status
    if ($status === 'confirmed') {
        confirmBooking($conn, $booking);
    } elseif ($status === 'rejected') {
        rejectBooking($conn, $booking, $notes);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
    }
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();

/**
 * Confirm booking and send notification
 */
function confirmBooking($conn, $booking) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update booking status
        $sql_update = "UPDATE bookings SET status = 'confirmed', updated_at = NOW() WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $booking['id']);
        
        if (!$stmt_update->execute()) {
            throw new Exception('Failed to update booking status');
        }
        $stmt_update->close();
        
        // Create notification for customer
        $notification_title = "Booking Disetujui! 🎉";
        $notification_message = "Booking Anda untuk {$booking['property_name']} telah disetujui oleh owner. Silakan lakukan pembayaran untuk mengonfirmasi reservasi Anda.";
        
        $sql_notif = "INSERT INTO notifications 
                      (user_id, type, title, message, kos_id, related_id, is_read, is_archived, created_at)
                      VALUES (?, 'property_approved', ?, ?, ?, ?, 0, 0, NOW())";
        
        $stmt_notif = $conn->prepare($sql_notif);
        $stmt_notif->bind_param(
            "issii",
            $booking['user_id'],
            $notification_title,
            $notification_message,
            $booking['kos_id'],
            $booking['id']
        );
        
        if (!$stmt_notif->execute()) {
            throw new Exception('Failed to create notification');
        }
        $stmt_notif->close();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Booking berhasil disetujui dan notifikasi telah dikirim',
            'booking_id' => $booking['id'],
            'status' => 'confirmed'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Reject booking and send notification
 */
function rejectBooking($conn, $booking, $notes) {
    $reject_reason = !empty($notes) ? $notes : 'Tidak ada alasan yang diberikan';
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update booking status
        $sql_update = "UPDATE bookings 
                       SET status = 'rejected', 
                           notes = CONCAT(IFNULL(notes, ''), '\n\nAlasan penolakan: ', ?),
                           updated_at = NOW() 
                       WHERE id = ?";
        
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $reject_reason, $booking['id']);
        
        if (!$stmt_update->execute()) {
            throw new Exception('Failed to update booking status');
        }
        $stmt_update->close();
        
        // Create notification for customer
        $notification_title = "Booking Ditolak ❌";
        $notification_message = "Maaf, booking Anda untuk {$booking['property_name']} telah ditolak oleh owner. Alasan: {$reject_reason}";
        
        $sql_notif = "INSERT INTO notifications 
                      (user_id, type, title, message, kos_id, related_id, is_read, is_archived, created_at)
                      VALUES (?, 'property_rejected', ?, ?, ?, ?, 0, 0, NOW())";
        
        $stmt_notif = $conn->prepare($sql_notif);
        $stmt_notif->bind_param(
            "issii",
            $booking['user_id'],
            $notification_title,
            $notification_message,
            $booking['kos_id'],
            $booking['id']
        );
        
        if (!$stmt_notif->execute()) {
            throw new Exception('Failed to create notification');
        }
        $stmt_notif->close();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Booking berhasil ditolak dan notifikasi telah dikirim',
            'booking_id' => $booking['id'],
            'status' => 'rejected'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}
?>