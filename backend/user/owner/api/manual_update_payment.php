<?php
require_once('../../../config/db.php');

header('Content-Type: application/json');

$conn->autocommit(TRUE);

if (!isset($_POST['order_id'])) {
    echo json_encode(['error' => 'Missing order_id']);
    exit;
}

$order_id = $_POST['order_id'];
$status = $_POST['status'] ?? 'settlement';

try {
    // Generate transaction ID untuk simulasi
    $transaction_id = 'SANDBOX-' . time() . '-' . rand(1000, 9999);
    
    $stmt = $conn->prepare("
        UPDATE property_payments 
        SET payment_status = ?,
            transaction_id = ?,
            paid_at = NOW(),
            updated_at = NOW()
        WHERE order_id = ?
    ");
    
    $stmt->bind_param("sss", $status, $transaction_id, $order_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        error_log("Payment simulated - Order: $order_id, Status: $status");
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment status updated to ' . $status,
            'order_id' => $order_id,
            'status' => $status,
            'transaction_id' => $transaction_id
        ]);
    } else {
        throw new Exception('Failed to update payment or order not found');
    }
    
} catch (Exception $e) {
    error_log("Manual update error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}