<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../../config/db.php';
require_once '../../utils/response.php';
require_once '../utils/notify.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$booking_id = intval($_POST['booking_id'] ?? 0);
$status     = intval($_POST['status'] ?? -1); // 1=approved, 2=rejected

if ($booking_id <= 0 || !in_array($status, [1,2], true)) {
    jsonResponse("error", "booking_id/status invalid");
}

$q = $conn->prepare("SELECT id, user_id, kos_id FROM bookings WHERE id = ? LIMIT 1");
$q->bind_param("i", $booking_id);
$q->execute();
$r = $q->get_result();
$bk = $r->fetch_assoc();

if (!$bk) jsonResponse("error", "Booking not found");

$user_id = intval($bk["user_id"]);
$kos_id  = intval($bk["kos_id"]);

$u = $conn->prepare("UPDATE bookings SET approval_status = ? WHERE id = ?");
$u->bind_param("ii", $status, $booking_id);

if (!$u->execute()) jsonResponse("error", "Failed update booking", ["db_error" => $u->error]);

if ($status === 1) {
    pushNotification(
        $conn, $user_id, "property_approved",
        "Booking Disetujui",
        "Booking kamu sudah disetujui. Silakan lanjut pembayaran.",
        $kos_id, $booking_id
    );
} else {
    pushNotification(
        $conn, $user_id, "property_rejected",
        "Booking Ditolak",
        "Maaf, booking kamu ditolak. Coba pilih kos lain ya.",
        $kos_id, $booking_id
    );
}

jsonResponse("success", "OK");
