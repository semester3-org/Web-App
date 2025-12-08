<?php
header("Content-Type: application/json");
include "../../config/db.php";

require_once __DIR__ . "/../../../vendor/autoload.php";

use Midtrans\Config;
use Midtrans\Snap;

try {
    // ========== KONFIG MIDTRANS ==========
    Config::$serverKey    = 'YOUR_SERVER_KEY'; // ganti pakai server key asli
    Config::$isProduction = false;             // true kalau udah live
    Config::$isSanitized  = true;
    Config::$is3ds        = true;

    // ========== INPUT ==========
    $user_id    = isset($_POST["user_id"]) ? intval($_POST["user_id"]) : 0;
    $booking_id = isset($_POST["booking_id"]) ? intval($_POST["booking_id"]) : 0;

    if ($user_id <= 0 || $booking_id <= 0) {
        throw new Exception("user_id dan booking_id wajib diisi");
    }

    // ========== AMBIL DATA BOOKING ==========
    $stmt = $conn->prepare("
        SELECT b.id, b.total_price, b.payment_status, b.status,
               b.order_id, u.name AS user_name, u.email, u.phone,
               k.name AS kos_name
        FROM bookings b
        JOIN users u ON u.id = b.user_id
        JOIN kos k   ON k.id = b.kos_id
        WHERE b.id = ? AND b.user_id = ?
    ");
    if (!$stmt) {
        throw new Exception("Query prepare gagal: " . $conn->error);
    }

    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if (!$booking) {
        throw new Exception("Booking tidak ditemukan");
    }

    if ($booking["payment_status"] === "paid") {
        throw new Exception("Booking sudah dibayar");
    }

    // ========== BIKIN ORDER ID ==========
    $order_id = $booking["order_id"];
    if (!$order_id) {
        $order_id = "BOOK-" . $booking_id . "-" . time();
    }

    // ========== PARAM KE MIDTRANS ==========
    $params = [
        "transaction_details" => [
            "order_id"     => $order_id,
            "gross_amount" => intval($booking["total_price"])
        ],
        "item_details" => [
            [
                "id"       => "BOOKING-" . $booking_id,
                "price"    => intval($booking["total_price"]),
                "quantity" => 1,
                "name"     => $booking["kos_name"]
            ]
        ],
        "customer_details" => [
            "first_name" => $booking["user_name"],
            "email"      => $booking["email"],
            "phone"      => $booking["phone"]
        ]
    ];

    // ========== CALL MIDTRANS ==========
    $snapToken   = Snap::getSnapToken($params);
    $redirectUrl = "https://app.midtrans.com/snap/v2/vtweb/" . $snapToken;

    // ========== UPDATE DB ==========
    $upd = $conn->prepare("
        UPDATE bookings
        SET order_id = ?, payment_token = ?, payment_status = 'pending'
        WHERE id = ?
    ");
    if (!$upd) {
        throw new Exception("Update prepare gagal: " . $conn->error);
    }

    $upd->bind_param("ssi", $order_id, $snapToken, $booking_id);
    $upd->execute();

    echo json_encode([
        "success"         => true,
        "message"         => "Transaksi berhasil dibuat",
        "transaction_url" => $redirectUrl,
        "order_id"        => $order_id
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
