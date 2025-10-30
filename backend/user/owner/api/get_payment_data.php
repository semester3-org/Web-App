<?php
ob_start();
header('Content-Type: application/json');

error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once '../../../config/db.php';
require_once '../../../config/midtrans.php';

ob_clean();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$owner_id = $_SESSION['user_id'];
$kos_id = intval($_GET['kos_id'] ?? 0);

if ($kos_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid kos ID']);
    exit();
}

try {
    $sql = "SELECT pp.*, k.name as kos_name
            FROM property_payments pp
            JOIN kos k ON pp.kos_id = k.id
            WHERE pp.kos_id = ? AND pp.owner_id = ? AND pp.payment_status = 'pending'
            ORDER BY pp.created_at DESC LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $kos_id, $owner_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        exit();
    }

    echo json_encode([
        'success' => true,
        'payment' => [
            'kos_id' => $payment['kos_id'],
            'payment_id' => $payment['id'],
            'order_id' => $payment['order_id'],
            'kos_name' => $payment['kos_name'],
            'price_monthly' => $payment['price_monthly'],
            'tax_amount' => $payment['tax_amount'],
            'total_amount' => $payment['total_amount']
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>

