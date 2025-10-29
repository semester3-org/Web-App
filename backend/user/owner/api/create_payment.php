<?php
/**
 * ============================================
 * CREATE PAYMENT - MIDTRANS SNAP
 * File: backend/user/owner/api/create_payment.php
 * ============================================
 */

session_start();
header('Content-Type: application/json');

require_once '../../../config/db.php';
require_once '../../../config/midtrans.php';

// Include Midtrans library
require_once '../../../libraries/midtrans-php/Midtrans.php';

// Check login
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$owner_id = $_SESSION['user_id'];

// Get request data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$payment_id = intval($data['payment_id'] ?? 0);
$order_id = $data['order_id'] ?? '';

if ($payment_id <= 0 || empty($order_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment data']);
    exit();
}

try {
    // Get payment details
    $sql = "SELECT pp.*, k.name as kos_name, u.full_name, u.email, u.phone
            FROM property_payments pp
            JOIN kos k ON pp.kos_id = k.id
            JOIN users u ON pp.owner_id = u.id
            WHERE pp.id = ? AND pp.owner_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $payment_id, $owner_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    if (!$payment) {
        throw new Exception('Payment not found');
    }
    
    // Jika sudah punya snap_token dan masih pending, gunakan yang lama
    if (!empty($payment['snap_token']) && $payment['payment_status'] === 'pending') {
        echo json_encode([
            'success' => true,
            'snap_token' => $payment['snap_token'],
            'order_id' => $payment['order_id']
        ]);
        exit();
    }
    
    // Generate order_id baru jika transaksi sebelumnya gagal
    if ($payment['payment_status'] !== 'pending') {
        $order_id = generateOrderId('KOS');
        $update_order = "UPDATE property_payments SET order_id = ?, payment_status = 'pending' WHERE id = ?";
        $stmt_update = $conn->prepare($update_order);
        $stmt_update->bind_param('si', $order_id, $payment_id);
        $stmt_update->execute();
    } else {
        $order_id = $payment['order_id'];
    }
    
    // Configure Midtrans
    \Midtrans\Config::$serverKey = MIDTRANS_SERVER_KEY;
    \Midtrans\Config::$isProduction = MIDTRANS_IS_PRODUCTION;
    \Midtrans\Config::$isSanitized = MIDTRANS_IS_SANITIZED;
    \Midtrans\Config::$is3ds = MIDTRANS_IS_3DS;
    
    // Prepare transaction details
    $transaction_details = [
        'order_id' => $order_id,
        'gross_amount' => $payment['total_amount']
    ];
    
    // Item details - HANYA PAJAK
    $item_details = [
        [
            'id' => 'TAX-' . $payment['kos_id'],
            'price' => $payment['tax_amount'],
            'quantity' => 1,
            'name' => 'Biaya Listing: ' . substr($payment['kos_name'], 0, 40) . ' (Pajak ' . $payment['tax_percentage'] . '%)'
        ]
    ];
    
    // Customer details
    $customer_details = [
        'first_name' => $payment['full_name'],
        'email' => $payment['email'],
        'phone' => $payment['phone'] ?? '08123456789'
    ];
    
    // Expiry settings
    $custom_expiry = [
        'start_time' => date('Y-m-d H:i:s O'),
        'unit' => 'hour',
        'duration' => PAYMENT_EXPIRY_DURATION
    ];
    
    // Transaction parameters
    $params = [
        'transaction_details' => $transaction_details,
        'item_details' => $item_details,
        'customer_details' => $customer_details,
        'expiry' => $custom_expiry,
        'enabled_payments' => PAYMENT_ENABLED_METHODS
    ];
    
    // Get Snap Token
    $snap_token = \Midtrans\Snap::getSnapToken($params);
    
    // Save snap token to database
    $update_sql = "UPDATE property_payments SET snap_token = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('si', $snap_token, $payment_id);
    $update_stmt->execute();
    
    echo json_encode([
        'success' => true,
        'snap_token' => $snap_token,
        'order_id' => $order_id
    ]);
    
} catch (Exception $e) {
    error_log("Create payment error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>