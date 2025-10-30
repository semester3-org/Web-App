<?php

/**
 * ============================================
 * CHECK PAYMENT STATUS
 * File: backend/user/owner/api/check_payment_status.php
 * ============================================
 * Manual check payment status dari Midtrans
 */

error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

require_once '../../../config/db.php';
require_once '../../../config/midtrans.php';
require_once '../../../libraries/midtrans-php/Midtrans.php';

// Check login
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$owner_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? '';

if (empty($order_id)) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit();
}

try {
    // Get payment from database
    $sql = "SELECT pp.*, k.id as kos_id
            FROM property_payments pp
            JOIN kos k ON pp.kos_id = k.id
            WHERE pp.order_id = ? AND pp.owner_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $order_id, $owner_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        exit();
    }

    // Configure Midtrans
    \Midtrans\Config::$serverKey = MIDTRANS_SERVER_KEY;
    \Midtrans\Config::$isProduction = MIDTRANS_IS_PRODUCTION;

    // Get status from Midtrans
    /** @var object $status */
    $status = \Midtrans\Transaction::status($order_id);

    // Safely extract values with null coalescing
    $transaction_status = $status->transaction_status ?? 'unknown';
    $fraud_status = $status->fraud_status ?? null;
    $payment_type = $status->payment_type ?? null;
    $transaction_id = $status->transaction_id ?? null;

    // Validate transaction status
    if ($transaction_status === 'unknown') {
        throw new Exception('Invalid transaction status from Midtrans');
    }

    // Determine status
    $new_payment_status = 'pending';
    $kos_payment_status = 'pending';
    $kos_status = null;

    if ($transaction_status == 'capture') {
        if ($fraud_status == 'accept') {
            $new_payment_status = 'capture';
            $kos_payment_status = 'paid';
            $kos_status = 'pending';
        }
    } else if ($transaction_status == 'settlement') {
        $new_payment_status = 'settlement';
        $kos_payment_status = 'paid';
        $kos_status = 'pending';
    } else if ($transaction_status == 'pending') {
        $new_payment_status = 'pending';
        $kos_payment_status = 'pending';
    } else if ($transaction_status == 'deny' || $transaction_status == 'cancel' || $transaction_status == 'expire') {
        $new_payment_status = $transaction_status;
        $kos_payment_status = 'failed';
    }

    // ... rest of the code remains the same

    // Update database jika ada perubahan
    if ($new_payment_status !== $payment['payment_status']) {
        $conn->begin_transaction();

        try {
            // Update payment
            $update_payment = "UPDATE property_payments 
                              SET transaction_id = ?,
                                  payment_type = ?,
                                  payment_status = ?,
                                  paid_at = IF(? IN ('settlement', 'capture'), NOW(), paid_at)
                              WHERE id = ?";

            $stmt_update = $conn->prepare($update_payment);
            $stmt_update->bind_param('ssssi', $transaction_id, $payment_type, $new_payment_status, $new_payment_status, $payment['id']);
            $stmt_update->execute();

            // Update kos
            $update_kos = "UPDATE kos 
                          SET payment_status = ?,
                              status = IF(? = 'paid', 'pending', status)
                          WHERE id = ?";
            $stmt_kos = $conn->prepare($update_kos);
            $stmt_kos->bind_param('ssi', $kos_payment_status, $kos_payment_status, $payment['kos_id']);
            $stmt_kos->execute();

            $conn->commit();

            // Clear pending payment session
            if (isset($_SESSION['pending_payment']) && $_SESSION['pending_payment']['order_id'] === $order_id) {
                unset($_SESSION['pending_payment']);
            }
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    echo json_encode([
        'success' => true,
        'transaction_status' => $transaction_status,
        'payment_status' => $new_payment_status,
        'kos_payment_status' => $kos_payment_status,
        'updated' => ($new_payment_status !== $payment['payment_status'])
    ]);
} catch (Exception $e) {
    error_log("Check payment status error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
