<?php
require_once('../../../config/db.php');
require_once('../../../config/midtrans.php');

// Set autocommit true untuk handler ini
$conn->autocommit(TRUE);

try {
    // Get notification dari Midtrans
    $notif = new \Midtrans\Notification();
    
    $transaction_status = $notif->transaction_status;
    $order_id = $notif->order_id;
    $fraud_status = $notif->fraud_status ?? 'accept';
    $payment_type = $notif->payment_type;
    
    error_log("Midtrans Notification - Order: $order_id, Status: $transaction_status");
    
    // Tentukan status pembayaran
    $payment_status = 'pending';
    
    if ($transaction_status == 'capture') {
        if ($payment_type == 'credit_card') {
            if ($fraud_status == 'accept') {
                $payment_status = 'paid';
            }
        }
    } else if ($transaction_status == 'settlement') {
        $payment_status = 'paid';
    } else if ($transaction_status == 'pending') {
        $payment_status = 'pending';
    } else if ($transaction_status == 'deny') {
        $payment_status = 'denied';
    } else if ($transaction_status == 'expire') {
        $payment_status = 'expired';
    } else if ($transaction_status == 'cancel') {
        $payment_status = 'cancelled';
    }
    
    // Update status di database
    $stmt = $conn->prepare("UPDATE property_payments SET payment_status = ?, updated_at = NOW() WHERE order_id = ?");
    $stmt->bind_param("ss", $payment_status, $order_id);
    $stmt->execute();
    
    error_log("Payment status updated to: $payment_status");
    
    echo "OK";
    
} catch (Exception $e) {
    error_log("Notification handler error: " . $e->getMessage());
    http_response_code(500);
    echo "Error";
}