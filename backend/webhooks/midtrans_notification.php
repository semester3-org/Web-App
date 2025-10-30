<?php
/**
 * Midtrans Notification Webhook
 * Public endpoint untuk Midtrans callback
 */

require_once '../config/db.php';
require_once '../config/midtrans.php';
require_once '../libraries/midtrans-php/Midtrans.php';

// Log all requests
$raw_notification = file_get_contents('php://input');
error_log("Midtrans Notification: " . $raw_notification);

// Configure Midtrans
\Midtrans\Config::$serverKey = MIDTRANS_SERVER_KEY;
\Midtrans\Config::$isProduction = MIDTRANS_IS_PRODUCTION;

try {
    $notification = new \Midtrans\Notification();
    
    $order_id = $notification->order_id;
    $transaction_status = $notification->transaction_status;
    $fraud_status = isset($notification->fraud_status) ? $notification->fraud_status : null;
    
    // Get payment record
    $sql = "SELECT * FROM property_payments WHERE order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $order_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    if (!$payment) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit();
    }
    
    // Update status based on transaction_status
    $new_status = 'pending';
    $kos_payment_status = 'pending';
    
    if ($transaction_status == 'capture' && $fraud_status == 'accept') {
        $new_status = 'capture';
        $kos_payment_status = 'paid';
    } else if ($transaction_status == 'settlement') {
        $new_status = 'settlement';
        $kos_payment_status = 'paid';
    } else if ($transaction_status == 'pending') {
        $new_status = 'pending';
    } else if (in_array($transaction_status, ['deny', 'cancel', 'expire'])) {
        $new_status = $transaction_status;
        $kos_payment_status = 'failed';
    }
    
    // Update database
    $conn->begin_transaction();
    
    $update_payment = "UPDATE property_payments 
                      SET payment_status = ?, 
                          transaction_id = ?,
                          paid_at = IF(? IN ('settlement', 'capture'), NOW(), paid_at)
                      WHERE id = ?";
    $stmt_update = $conn->prepare($update_payment);
    $transaction_id = $notification->transaction_id;
    $stmt_update->bind_param('sssi', $new_status, $transaction_id, $new_status, $payment['id']);
    $stmt_update->execute();
    
    $update_kos = "UPDATE kos 
                  SET payment_status = ?,
                      status = IF(? = 'paid', 'pending', status)
                  WHERE id = ?";
    $stmt_kos = $conn->prepare($update_kos);
    $stmt_kos->bind_param('ssi', $kos_payment_status, $kos_payment_status, $payment['kos_id']);
    $stmt_kos->execute();
    
    $conn->commit();
    
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    error_log("Webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>