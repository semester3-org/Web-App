<?php
/**
 * ============================================
 * GET EXISTING PAYMENT TOKEN
 * File: backend/user/customer/api/get_payment_token.php
 * Purpose: Retrieve existing payment token for pending payment
 * ============================================
 */

session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . "/backend/config/db.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/backend/config/midtrans.php");

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
    // Get booking with payment token
    $query = "SELECT id, payment_token, order_id, payment_status, status 
              FROM bookings 
              WHERE id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    $booking = $result->fetch_assoc();
    
    if ($booking['status'] !== 'confirmed') {
        echo json_encode(['success' => false, 'message' => 'Booking not confirmed']);
        exit;
    }
    
    if ($booking['payment_status'] === 'paid') {
        echo json_encode(['success' => false, 'message' => 'Booking already paid']);
        exit;
    }
    
    if (empty($booking['payment_token'])) {
        echo json_encode(['success' => false, 'message' => 'Payment token not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'snap_token' => $booking['payment_token'],
        'order_id' => $booking['order_id'],
        'client_key' => MIDTRANS_CLIENT_KEY,
        'message' => 'Payment token retrieved successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>