<?php
/**
 * ============================================
 * CREATE BOOKING PAYMENT
 * File: backend/user/customer/api/create_payment.php
 * Purpose: Generate Midtrans payment token for confirmed booking
 * ============================================
 */

session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/midtrans.php");

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please login first.'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get booking ID
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;

if ($booking_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid booking ID'
    ]);
    exit;
}

try {
    // Get booking details with kos and user information
    $query = "SELECT 
                b.id,
                b.kos_id,
                b.user_id,
                b.check_in_date,
                b.check_out_date,
                b.booking_type,
                b.duration_months,
                b.total_price,
                b.status,
                b.payment_status,
                b.order_id,
                k.name as kos_name,
                k.address as kos_address,
                k.city,
                u.full_name,
                u.email,
                u.phone
              FROM bookings b
              JOIN kos k ON b.kos_id = k.id
              JOIN users u ON b.user_id = u.id
              WHERE b.id = ? AND b.user_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Booking not found or unauthorized'
        ]);
        exit;
    }
    
    $booking = $result->fetch_assoc();
    
    // Validate booking status
    if ($booking['status'] !== 'confirmed') {
        echo json_encode([
            'success' => false,
            'message' => 'Booking belum dikonfirmasi oleh admin'
        ]);
        exit;
    }
    
    if ($booking['payment_status'] === 'paid') {
        echo json_encode([
            'success' => false,
            'message' => 'Booking sudah dibayar'
        ]);
        exit;
    }
    
    // Generate new order ID if not exists
    $order_id = $booking['order_id'];
    if (empty($order_id)) {
        $order_id = generateOrderId('BOOKING');
    }
    
    // Prepare transaction details for Midtrans
    $transaction_details = [
        'order_id' => $order_id,
        'gross_amount' => (int) $booking['total_price']
    ];
    
    // Item details
    $duration_text = $booking['booking_type'] === 'monthly' 
        ? $booking['duration_months'] . ' Bulan' 
        : 'Harian';
    
    $item_details = [
        [
            'id' => 'BOOKING-' . $booking['id'],
            'price' => (int) $booking['total_price'],
            'quantity' => 1,
            'name' => 'Booking ' . $booking['kos_name'] . ' (' . $duration_text . ')'
        ]
    ];
    
    // Customer details
    $phone = $booking['phone'];
    if (empty($phone) || !preg_match('/^[0-9+\-\s()]+$/', $phone)) {
        $phone = '08123456789'; // Default phone if invalid
    }
    
    $customer_details = [
        'first_name' => $booking['full_name'],
        'email' => $booking['email'],
        'phone' => $phone,
        'billing_address' => [
            'address' => $booking['kos_address'],
            'city' => $booking['city'],
            'postal_code' => '00000',
            'country_code' => 'IDN'
        ]
    ];
    
    // Midtrans transaction parameters
    $transaction = [
        'transaction_details' => $transaction_details,
        'item_details' => $item_details,
        'customer_details' => $customer_details,
        'enabled_payments' => PAYMENT_ENABLED_METHODS,
        'expiry' => [
            'duration' => PAYMENT_EXPIRY_DURATION,
            'unit' => 'hours'
        ]
    ];
    
    // Create Snap token via Midtrans API
    $midtrans_url = MIDTRANS_API_URL; // URL sudah lengkap dari config
    $auth = base64_encode(MIDTRANS_SERVER_KEY . ':');
    
    $ch = curl_init($midtrans_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . $auth
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transaction));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Log untuk debugging
    error_log("Midtrans Response Code: " . $http_code);
    error_log("Midtrans Response Body: " . $response);
    error_log("Curl Error: " . $curl_error);
    
    $midtrans_response = json_decode($response, true);
    
    if ($http_code !== 201 || !isset($midtrans_response['token'])) {
        $error_msg = 'HTTP Code: ' . $http_code;
        if ($curl_error) {
            $error_msg .= ', cURL Error: ' . $curl_error;
        }
        if (isset($midtrans_response['error_messages'])) {
            $error_msg .= ', Midtrans Error: ' . implode(', ', $midtrans_response['error_messages']);
        } else {
            $error_msg .= ', Response: ' . $response;
        }
        throw new Exception('Failed to create payment token: ' . $error_msg);
    }
    
    $snap_token = $midtrans_response['token'];
    
    // Update booking with payment token and order_id
    $update_query = "UPDATE bookings 
                     SET payment_token = ?, 
                         order_id = ?,
                         payment_status = 'pending'
                     WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssi", $snap_token, $order_id, $booking_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update booking: ' . $conn->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'snap_token' => $snap_token,
        'order_id' => $order_id,
        'client_key' => MIDTRANS_CLIENT_KEY,
        'message' => 'Payment token generated successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    // Close statements and connection
    if (isset($stmt)) $stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    if (isset($conn)) $conn->close();
}
?>