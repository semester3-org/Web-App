<?php
header("Content-Type: application/json");
include "../../config/db.php";

try {
    $booking_id = isset($_POST["booking_id"]) ? intval($_POST["booking_id"]) : 0;
    $user_id    = isset($_POST["user_id"]) ? intval($_POST["user_id"]) : 0;

    if ($booking_id <= 0 || $user_id <= 0) {
        throw new Exception("booking_id dan user_id wajib diisi");
    }

    // cek dulu status sekarang (+ ambil kos_id, owner_id, nama kos buat notif)
    $stmt = $conn->prepare("
        SELECT b.status, b.payment_status, b.kos_id, k.owner_id, k.name AS kos_name
        FROM bookings b
        JOIN kos k ON k.id = b.kos_id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if (!$res) {
        throw new Exception("Booking tidak ditemukan");
    }

    if ($res["status"] !== "pending" || $res["payment_status"] !== "unpaid") {
        throw new Exception("Booking tidak bisa dibatalkan");
    }

    $kos_id   = intval($res["kos_id"]);
    $owner_id = intval($res["owner_id"]);
    $kos_name = $res["kos_name"];

    $update = $conn->prepare("
        UPDATE bookings
        SET status = 'cancelled'
        WHERE id = ? AND user_id = ?
    ");
    $update->bind_param("ii", $booking_id, $user_id);
    $update->execute();

    // ===== NOTIF: kasih tau owner booking dibatalkan =====
    if ($owner_id > 0 && $owner_id !== $user_id) {
        $title = "Booking Dibatalkan";
        $msg   = "Booking untuk \"$kos_name\" telah dibatalkan oleh pengguna.\nBooking ID: $booking_id";
        $type  = "new_booking"; // still valid enum

        $notif = $conn->prepare("
            INSERT INTO notifications (user_id, kos_id, type, title, message, related_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if ($notif) {
            $notif->bind_param("iisssi", $owner_id, $kos_id, $type, $title, $msg, $booking_id);
            $notif->execute();
        }
    }

    echo json_encode([
        "status"  => "success",
        "message" => "Booking berhasil dibatalkan"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}
