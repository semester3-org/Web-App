<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . "/backend/config/db.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/backend/config/midtrans.php");

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
    // Get booking data
    $query = "SELECT b.id, b.kos_id, b.order_id, b.payment_status, b.status, b.total_price
              FROM bookings b WHERE b.id = ? AND b.user_id = ?";
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
    
    // Cek status dari Midtrans
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
        throw new Exception('Gagal mengecek status dari Midtrans');
    }
    
    $midtrans = json_decode($response, true);
    $transaction_status = $midtrans['transaction_status'] ?? '';
    $fraud_status = $midtrans['fraud_status'] ?? 'accept';
    $payment_type = $midtrans['payment_type'] ?? '';
    $gross_amount = $midtrans['gross_amount'] ?? $booking['total_price'];
    
    // Determine payment status
    $payment_status = 'pending';
    if ($transaction_status == 'capture' && $fraud_status == 'accept') {
        $payment_status = 'paid';
    } elseif ($transaction_status == 'settlement') {
        $payment_status = 'paid';
    } elseif (in_array($transaction_status, ['deny', 'cancel', 'expire'])) {
        $payment_status = 'failed';
    }

    // Start transaction
    $conn->begin_transaction();

    // Update booking jika status berubah
    if ($payment_status === 'paid' && $old_payment_status !== 'paid') {
        // Update booking ke paid
        $update_booking = $conn->prepare("UPDATE bookings 
                                          SET payment_status = 'paid', 
                                              paid_at = NOW() 
                                          WHERE id = ?");
        $update_booking->bind_param("i", $booking_id);
        if (!$update_booking->execute()) {
            throw new Exception("Gagal update booking: " . $conn->error);
        }

        // Kurangi available rooms
        $update_rooms = $conn->prepare("UPDATE kos 
                                        SET available_rooms = available_rooms - 1 
                                        WHERE id = ? AND available_rooms > 0");
        $update_rooms->bind_param("i", $kos_id);
        if (!$update_rooms->execute()) {
            throw new Exception("Gagal update kamar: " . $conn->error);
        }
        
        if ($update_rooms->affected_rows === 0) {
            throw new Exception("Kamar sudah tidak tersedia");
        }

        // KIRIM NOTIFIKASI KE OWNER
        $kos_query = $conn->prepare("SELECT owner_id, name FROM kos WHERE id = ?");
        $kos_query->bind_param("i", $kos_id);
        $kos_query->execute();
        $kos_data = $kos_query->get_result()->fetch_assoc();
        
        if ($kos_data) {
            require_once($_SERVER['DOCUMENT_ROOT'] . "/backend/user/owner/classes/Notification.php");
            $notif = new Notification($conn);

            // Cek agar tidak double notifikasi
            $check_notif = $conn->prepare("SELECT id FROM notifications 
                                           WHERE type='new_booking' 
                                           AND related_id = ? 
                                           AND title LIKE '%Berhasil%'");
            $check_notif->bind_param("i", $booking_id);
            $check_notif->execute();
            
            if ($check_notif->get_result()->num_rows == 0) {
                $notif->createNewBookingNotification(
                    $kos_id,
                    $kos_data['owner_id'],
                    $booking_id,
                    "Pembayaran Berhasil!",
                    "Pembayaran Rp " . number_format($gross_amount, 0, ',', '.') . " untuk {$kos_data['name']} telah berhasil diterima."
                );
            }
            $check_notif->close();
        }
        $kos_query->close();
        
    } elseif ($payment_status !== $old_payment_status) {
        // Update status lainnya (failed, pending, dll)
        $update_booking = $conn->prepare("UPDATE bookings 
                                          SET payment_status = ? 
                                          WHERE id = ?");
        $update_booking->bind_param("si", $payment_status, $booking_id);
        $update_booking->execute();
    }

    // ✅ INSERT/UPDATE PAYMENT LOG (FIX BAGIAN INI)
    // Cek dulu apakah sudah ada log
    $check_log = $conn->prepare("SELECT id FROM payment_logs WHERE order_id = ? LIMIT 1");
    $check_log->bind_param("s", $order_id);
    $check_log->execute();
    $log_exists = $check_log->get_result()->num_rows > 0;
    $check_log->close();

    $notification_json = json_encode($midtrans);

    if ($log_exists) {
        // UPDATE existing log
        $log_stmt = $conn->prepare("UPDATE payment_logs 
                                    SET transaction_status = ?,
                                        payment_type = ?,
                                        gross_amount = ?,
                                        payment_status = ?,
                                        notification_data = ?
                                    WHERE order_id = ?");
        $log_stmt->bind_param("ssdsss", 
            $transaction_status,
            $payment_type,
            $gross_amount,
            $payment_status,
            $notification_json,
            $order_id
        );
    } else {
        // INSERT new log
        $log_stmt = $conn->prepare("INSERT INTO payment_logs 
                                    (booking_id, order_id, transaction_status, payment_type, 
                                     gross_amount, payment_status, notification_data, created_at)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $log_stmt->bind_param("isssdss", 
            $booking_id,
            $order_id,
            $transaction_status,
            $payment_type,
            $gross_amount,
            $payment_status,
            $notification_json
        );
    }

    if (!$log_stmt->execute()) {
        // Log error tapi jangan throw exception (biar proses tetap lanjut)
        error_log("Failed to save payment log: " . $log_stmt->error);
    }
    $log_stmt->close();

    // Commit semua perubahan
    $conn->commit();

    // ✅ RETURN DENGAN transaction_status (INI PENTING!)
    echo json_encode([
        'success' => true,
        'payment_status' => $payment_status,
        'transaction_status' => $transaction_status, // ← TAMBAHKAN INI
        'message' => 'Status pembayaran berhasil diperbarui'
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
    // Close semua statement
    if (isset($stmt)) $stmt->close();
    if (isset($update_booking)) $update_booking->close();
    if (isset($update_rooms)) $update_rooms->close();
    if (isset($conn)) $conn->close();
}
?>