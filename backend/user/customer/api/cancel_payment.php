<?php
/**
 * ============================================
 * CANCEL PENDING PAYMENT
 * File: backend/user/customer/api/cancel_payment.php
 * ============================================
 */

session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;

if ($booking_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit;
}

try {
    $conn->begin_transaction();
    
    // Check if booking exists and belongs to user
    $check_query = "SELECT id, payment_status, status 
                    FROM bookings 
                    WHERE id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    $booking = $result->fetch_assoc();
    
    if ($booking['payment_status'] === 'paid') {
        echo json_encode(['success' => false, 'message' => 'Cannot cancel paid booking']);
        exit;
    }
    
    // Reset payment status to unpaid
    $update_query = "UPDATE bookings 
                     SET payment_status = 'unpaid',
                         payment_token = NULL,
                         order_id = NULL
                     WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $booking_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to cancel payment');
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment cancelled successfully'
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    if (isset($conn)) $conn->close();
}
?>