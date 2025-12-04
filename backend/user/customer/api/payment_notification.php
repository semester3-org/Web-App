<?php
/**
 * ============================================
 * MIDTRANS PAYMENT NOTIFICATION HANDLER
 * File: backend/user/customer/api/payment_notification.php
 * Purpose: Handle payment notification from Midtrans
 * ============================================
 */

require_once($_SERVER['DOCUMENT_ROOT'] . "/backend/config/db.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/backend/config/midtrans.php");

// Log function for debugging
function logNotification($message, $data = null) {
    $log_file = $_SERVER['DOCUMENT_ROOT'] . '/backend/logs/payment_notifications.log';
    $log_dir = dirname($log_file);
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message";
    
    if ($data) {
        $log_message .= "\n" . json_encode($data, JSON_PRETTY_PRINT);
    }
    
    $log_message .= "\n" . str_repeat('-', 80) . "\n";
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Get notification data from Midtrans
$json_result = file_get_contents('php://input');
$notification = json_decode($json_result, true);

logNotification('Received notification from Midtrans', $notification);

if (!$notification) {
    logNotification('Invalid notification data');
    http_response_code(400);
    exit;
}

try {
    // Extract notification data
    $order_id = $notification['order_id'] ?? '';
    $transaction_status = $notification['transaction_status'] ?? '';
    $fraud_status = $notification['fraud_status'] ?? 'accept';
    $payment_type = $notification['payment_type'] ?? '';
    $transaction_time = $notification['transaction_time'] ?? date('Y-m-d H:i:s');
    
    // Verify signature key for security
    $signature_key = $notification['signature_key'] ?? '';
    $expected_signature = hash('sha512', 
        $order_id . 
        $notification['status_code'] . 
        $notification['gross_amount'] . 
        MIDTRANS_SERVER_KEY
    );
    
    if ($signature_key !== $expected_signature) {
        logNotification('Invalid signature key', [
            'received' => $signature_key,
            'expected' => $expected_signature
        ]);
        http_response_code(403);
        exit;
    }
    
    // Get booking by order_id
    $query = "SELECT id, payment_status, status FROM bookings WHERE order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        logNotification('Booking not found for order_id: ' . $order_id);
        http_response_code(404);
        exit;
    }
    
    $booking = $result->fetch_assoc();
    $booking_id = $booking['id'];
    
    // Determine payment status based on transaction status
    $payment_status = 'pending';
    
    if ($transaction_status == 'capture') {
        if ($fraud_status == 'accept') {
            $payment_status = 'paid';
        }
    } else if ($transaction_status == 'settlement') {
        $payment_status = 'paid';
    } else if ($transaction_status == 'pending') {
        $payment_status = 'pending';
    } else if (in_array($transaction_status, ['deny', 'cancel', 'expire'])) {
        $payment_status = 'failed';
    } else if ($transaction_status == 'refund') {
        $payment_status = 'failed'; // or create 'refund' status
    }
    
    logNotification("Processing payment for booking #$booking_id", [
        'order_id' => $order_id,
        'transaction_status' => $transaction_status,
        'payment_status' => $payment_status
    ]);
    
    // Start transaction
    $conn->begin_transaction();
    
    // Update booking payment status
    if ($payment_status === 'paid') {
        $update_query = "UPDATE bookings 
                        SET payment_status = ?,
                            paid_at = NOW()
                        WHERE id = ?";
    } else {
        $update_query = "UPDATE bookings 
                        SET payment_status = ?
                        WHERE id = ?";
    }
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $payment_status, $booking_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update booking: ' . $conn->error);
    }
    
    logNotification("Successfully updated booking #$booking_id payment status to: $payment_status");
    
    // Insert to payment history/log table (optional)
    $log_query = "INSERT INTO payment_logs 
                 (booking_id, order_id, transaction_status, payment_type, 
                  gross_amount, payment_status, notification_data, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $log_stmt = $conn->prepare($log_query);
    if ($log_stmt) {
        $gross_amount = $notification['gross_amount'] ?? 0;
        $notification_json = json_encode($notification);
        $log_stmt->bind_param("isssdss", 
            $booking_id, 
            $order_id, 
            $transaction_status, 
            $payment_type,
            $gross_amount,
            $payment_status,
            $notification_json
        );
        $log_stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Notification processed']);
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    logNotification('Exception occurred', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    // Close statements and connection
    if (isset($stmt)) $stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    if (isset($log_stmt)) $log_stmt->close();
    if (isset($conn)) $conn->close();
}
?>