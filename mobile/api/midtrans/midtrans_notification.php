<?php
header("Content-Type: application/json");
include "../../config/db.php";
require_once "../../vendor/autoload.php";

\Midtrans\Config::$serverKey = 'YOUR_SERVER_KEY';
\Midtrans\Config::$isProduction = false;

$notif = new \Midtrans\Notification();

$order_id = $notif->order_id;
$transaction = $notif->transaction_status;
$fraud = $notif->fraud_status;

// map midtrans -> payment_status di DB
$payment_status = "unpaid";

if ($transaction == "capture") {
    if ($fraud == "accept") {
        $payment_status = "paid";
    } else {
        $payment_status = "failed";
    }
} else if ($transaction == "settlement") {
    $payment_status = "paid";
} else if ($transaction == "pending") {
    $payment_status = "pending";
} else if ($transaction == "deny" || $transaction == "cancel") {
    $payment_status = "failed";
} else if ($transaction == "expire") {
    $payment_status = "expired";
}

$stmt = $conn->prepare("
    UPDATE bookings
    SET payment_status = ?, updated_at = NOW()
    WHERE order_id = ?
");
$stmt->bind_param("ss", $payment_status, $order_id);
$stmt->execute();

// boleh juga: kalau paid → status booking jadi 'confirmed'
if ($payment_status === "paid") {
    $conn->query("UPDATE bookings SET status = 'confirmed' WHERE order_id = '". $conn->real_escape_string($order_id) ."'");
}

echo json_encode(["status" => "ok"]);
