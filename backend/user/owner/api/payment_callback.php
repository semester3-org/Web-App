<?php
/**
 * ============================================
 * PAYMENT NOTIFICATION HANDLER
 * File: backend/user/owner/api/payment_callback.php
 * ============================================
 * Handle payment notifications from Midtrans
 */

header('Content-Type: application/json');

require_once '../../../config/db.php';
require_once '../../../config/midtrans.php';
require_once '../../../libraries/midtrans-php/Midtrans.php';

// Configure Midtrans
\Midtrans\Config::$serverKey = MIDTRANS_SERVER_KEY;
\Midtrans\Config::$isProduction = MIDTRANS_IS_PRODUCTION;

// Get notification from Midtrans
try {
    $json_result = file_get_contents('php://input');
    $result = json_decode($json_result);
    
    // Log notification
    error_log("Midtrans Notification: " . $json_result);
    
    if (!$result) {
        throw new Exception('Invalid notification data');
    }
    
    // Get notification object
    $notification = new \Midtrans\Notification();
    
    $order_id = $notification->order_id;
    $transaction_status = $notification->transaction_status;
    $transaction_id = $notification->transaction_id;
    $payment_type = $notification->payment_type;
    $fraud_status = isset($notification->fraud_status) ? $notification->fraud_status : null;
    
    // Get payment record
    $sql = "SELECT * FROM property_payments WHERE order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $order_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    if (!$payment) {
        throw new Exception('Payment not found');
    }
    
    $payment_id = $payment['id'];
    $kos_id = $payment['kos_id'];
    
    // Determine payment status
    $new_status = 'pending';
    $kos_payment_status = 'pending';
    $kos_status = null; // Akan diisi jika payment berhasil
    
    if ($transaction_status == 'capture') {
        if ($fraud_status == 'accept') {
            $new_status = 'capture';
            $kos_payment_status = 'paid';
            $kos_status = 'pending'; // Set status kos jadi 'pending' untuk masuk review admin
        }
    } else if ($transaction_status == 'settlement') {
        $new_status = 'settlement';
        $kos_payment_status = 'paid';
        $kos_status = 'pending'; // Set status kos jadi 'pending' untuk masuk review admin
    } else if ($transaction_status == 'pending') {
        $new_status = 'pending';
        $kos_payment_status = 'pending';
    } else if ($transaction_status == 'deny') {
        $new_status = 'deny';
        $kos_payment_status = 'failed';
    } else if ($transaction_status == 'expire') {
        $new_status = 'expire';
        $kos_payment_status = 'expired';
    } else if ($transaction_status == 'cancel') {
        $new_status = 'cancel';
        $kos_payment_status = 'failed';
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update property_payments
        $update_payment = "UPDATE property_payments 
                          SET transaction_id = ?,
                              payment_type = ?,
                              payment_method = ?,
                              payment_status = ?,
                              midtrans_response = ?,
                              paid_at = IF(? IN ('settlement', 'capture'), NOW(), paid_at)
                          WHERE id = ?";
        
        $stmt_update = $conn->prepare($update_payment);
        $stmt_update->bind_param(
            'ssssssi',
            $transaction_id,
            $payment_type,
            $payment_type,
            $new_status,
            $json_result,
            $new_status,
            $payment_id
        );
        $stmt_update->execute();
        
        // Update kos payment_status DAN status
        // Status kos diisi 'pending' hanya jika payment berhasil (paid)
        $update_kos = "UPDATE kos 
                      SET payment_status = ?, 
                          status = IF(? = 'paid', 'pending', status)
                      WHERE id = ?";
        $stmt_kos = $conn->prepare($update_kos);
        $stmt_kos->bind_param('ssi', $kos_payment_status, $kos_payment_status, $kos_id);
        $stmt_kos->execute();
        
        // Log payment event
        $log_sql = "INSERT INTO payment_logs (payment_id, order_id, event_type, response_data) 
                   VALUES (?, ?, ?, ?)";
        $stmt_log = $conn->prepare($log_sql);
        $event_type = 'notification_' . $transaction_status;
        $stmt_log->bind_param('isss', $payment_id, $order_id, $event_type, $json_result);
        $stmt_log->execute();
        
        $conn->commit();
        
        // Clear pending payment session if exists
        session_start();
        if (isset($_SESSION['pending_payment']) && $_SESSION['pending_payment']['order_id'] === $order_id) {
            unset($_SESSION['pending_payment']);
        }
        
        error_log("Payment updated: Order $order_id, Status: $new_status");
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment notification processed',
            'order_id' => $order_id,
            'status' => $new_status
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Payment notification error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>