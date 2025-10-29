<?php
require_once('../../../config/db.php');
header('Content-Type: application/json');

// Set autocommit true untuk read operation
$conn->autocommit(TRUE);

if (!isset($_GET['order_id'])) {
    echo json_encode(['error' => 'Missing order_id']);
    exit;
}

$order_id = $_GET['order_id'];

try {
    $stmt = $conn->prepare("SELECT payment_status FROM property_payments WHERE order_id = ?");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'payment_status' => $row['payment_status'],
            'order_id' => $order_id
        ]);
    } else {
        echo json_encode([
            'error' => 'Order not found',
            'order_id' => $order_id
        ]);
    }
    
} catch (Exception $e) {
    error_log("Check payment error: " . $e->getMessage());
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}