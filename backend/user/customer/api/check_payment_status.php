<?php
/**
 * ============================================
 * CHECK PAYMENT STATUS (UPDATED)
 * File: backend/user/customer/api/check_payment_status.php
 * ============================================
 */

session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/midtrans.php");

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
    // Get booking data with kos_id
    $query = "SELECT b.id, b.kos_id, b.order_id, b.payment_status, b.status 
              FROM bookings b
              WHERE b.id = ? AND b.user_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    $booking = $result->fetch_assoc();
    $order_id = $booking['order_id'];
    $kos_id = $booking['kos_id'];
    $old_payment_status = $booking['payment_status'];
    
    if (empty($order_id)) {
        echo json_encode(['success' => false, 'message' => 'Order ID not found']);
        exit;
    }
    
    // Check status from Midtrans API
    $midtrans_url = (MIDTRANS_IS_PRODUCTION ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com') . '/v2/' . $order_id . '/status';
    $auth = base64_encode(MIDTRANS_SERVER_KEY . ':');
    
    $ch = curl_init($midtrans_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Basic ' . $auth
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        throw new Exception('Failed to check payment status from Midtrans');
    }
    
    $midtrans_response = json_decode($response, true);
    $transaction_status = $midtrans_response['transaction_status'] ?? '';
    $fraud_status = $midtrans_response['fraud_status'] ?? 'accept';
    
    // Determine payment status
    $payment_status = 'pending';
    
    if ($transaction_status == 'capture') {
        $payment_status = ($fraud_status == 'accept') ? 'paid' : 'pending';
    } else if ($transaction_status == 'settlement') {
        $payment_status = 'paid';
    } else if ($transaction_status == 'pending') {
        $payment_status = 'pending';
    } else if (in_array($transaction_status, ['deny', 'cancel', 'expire'])) {
        $payment_status = 'failed';
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Update booking payment status
    if ($payment_status === 'paid') {
        $update_query = "UPDATE bookings 
                        SET payment_status = ?, paid_at = NOW() 
                        WHERE id = ?";
        
        // PENTING: Kurangi available_rooms HANYA jika status berubah dari unpaid/pending ke paid
        if ($old_payment_status !== 'paid') {
            $update_rooms = $conn->prepare("UPDATE kos SET available_rooms = available_rooms - 1 WHERE id = ? AND available_rooms > 0");
            $update_rooms->bind_param("i", $kos_id);
            
            if (!$update_rooms->execute()) {
                throw new Exception("Gagal mengurangi kamar tersedia");
            }
            
            if ($update_rooms->affected_rows === 0) {
                throw new Exception("Kamar sudah tidak tersedia");
            }
        }
    } else {
        $update_query = "UPDATE bookings 
                        SET payment_status = ? 
                        WHERE id = ?";
    }
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $payment_status, $booking_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update booking');
    }
    
    // Log to payment_logs
    $log_query = "INSERT INTO payment_logs 
                 (booking_id, order_id, transaction_status, payment_type, 
                  gross_amount, payment_status, notification_data, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE 
                 transaction_status = VALUES(transaction_status),
                 payment_status = VALUES(payment_status),
                 notification_data = VALUES(notification_data)";
    
    $log_stmt = $conn->prepare($log_query);
    if ($log_stmt) {
        $payment_type = $midtrans_response['payment_type'] ?? '';
        $gross_amount = $midtrans_response['gross_amount'] ?? 0;
        $notification_json = json_encode($midtrans_response);
        
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
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'payment_status' => $payment_status,
        'transaction_status' => $transaction_status,
        'message' => 'Payment status updated successfully'
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    if (isset($update_rooms)) $update_rooms->close();
    if (isset($log_stmt)) $log_stmt->close();
    if (isset($conn)) $conn->close();
}
?>