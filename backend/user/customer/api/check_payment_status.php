<?php
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
    $query = "SELECT b.id, b.kos_id, b.order_id, b.payment_status, b.status 
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
    
    // Cek status Midtrans
    $midtrans_url = (MIDTRANS_IS_PRODUCTION ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com') . '/v2/' . $order_id . '/status';
    $auth = base64_encode(MIDTRANS_SERVER_KEY . ':');
    
    $ch = curl_init($midtrans_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Authorization: Basic ' . $auth]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        throw new Exception('Gagal cek status Midtrans');
    }
    
    $midtrans = json_decode($response, true);
    $transaction_status = $midtrans['transaction_status'] ?? '';
    $fraud_status = $midtrans['fraud_status'] ?? 'accept';
    
    $payment_status = 'pending';
    if ($transaction_status == 'capture' && $fraud_status == 'accept') $payment_status = 'paid';
    elseif ($transaction_status == 'settlement') $payment_status = 'paid';
    elseif (in_array($transaction_status, ['deny', 'cancel', 'expire'])) $payment_status = 'failed';

    $conn->begin_transaction();

    if ($payment_status === 'paid' && $old_payment_status !== 'paid') {
        // Update booking
        $conn->query("UPDATE bookings SET payment_status = 'paid', status = 'confirmed', paid_at = NOW() WHERE id = $booking_id");

        // Kurangi kamar
        $update_rooms = $conn->prepare("UPDATE kos SET available_rooms = available_rooms - 1 WHERE id = ? AND available_rooms > 0");
        $update_rooms->bind_param("i", $kos_id);
        if (!$update_rooms->execute() || $update_rooms->affected_rows === 0) {
            throw new Exception("Kamar sudah penuh");
        }

        // KIRIM NOTIFIKASI KE OWNER
        $kos_data = $conn->query("SELECT owner_id, name FROM kos WHERE id = $kos_id")->fetch_assoc();
        require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/user/owner/classes/Notification.php");
        $notif = new Notification($conn);

        // Cek biar tidak double notif
        $cek = $conn->query("SELECT 1 FROM notifications WHERE type='new_booking' AND related_id = $booking_id AND title LIKE '%Berhasil%'");
        if ($cek->num_rows == 0) {
            $notif->createNewBookingNotification(
                $kos_id,
                $kos_data['owner_id'],
                $booking_id,
                "Pembayaran Berhasil!",
                "Pembayaran Rp " . number_format($midtrans['gross_amount'], 0, ',', '.') . " untuk {$kos_data['name']} telah berhasil diterima."
            );
        }
    } else {
        $conn->query("UPDATE bookings SET payment_status = '$payment_status' WHERE id = $booking_id");
    }

    // Log
    $log = $conn->prepare("INSERT INTO payment_logs (...) VALUES (...) ON DUPLICATE KEY UPDATE ..."); // tetap seperti semula

    $conn->commit();

    echo json_encode([
        'success' => true,
        'payment_status' => $payment_status,
        'message' => 'Status pembayaran berhasil diperbarui'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>