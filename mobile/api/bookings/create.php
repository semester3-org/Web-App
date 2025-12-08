<?php
header("Content-Type: application/json");
include "../../config/db.php";

try {
    $user_id       = isset($_POST["user_id"]) ? intval($_POST["user_id"]) : 0;
    $kos_id        = isset($_POST["kos_id"]) ? intval($_POST["kos_id"]) : 0;
    $booking_type  = $_POST["booking_type"] ?? "monthly"; // monthly / daily
    $check_in_date = $_POST["check_in_date"] ?? null;
    $check_out_date = $_POST["check_out_date"] ?? null;
    $duration_months = isset($_POST["duration_months"]) ? intval($_POST["duration_months"]) : null;

    if ($user_id <= 0 || $kos_id <= 0) {
        throw new Exception("user_id dan kos_id wajib diisi");
    }

    // ambil harga kos (+ owner_id + nama kos untuk notif)
    $stmt = $conn->prepare("
        SELECT owner_id, name, price_monthly, price_daily
        FROM kos
        WHERE id = ? AND status = 'approved'
    ");
    $stmt->bind_param("i", $kos_id);
    $stmt->execute();
    $kos = $stmt->get_result()->fetch_assoc();

    if (!$kos) {
        throw new Exception("Kos tidak ditemukan atau belum disetujui");
    }

    $owner_id     = intval($kos["owner_id"]);
    $kos_name     = $kos["name"];
    $price_monthly = intval($kos["price_monthly"]);
    $price_daily   = $kos["price_daily"] !== null ? intval($kos["price_daily"]) : null;

    if (!$check_in_date) {
        throw new Exception("check_in_date wajib diisi");
    }

    $total_price = 0;

    if ($booking_type === "monthly") {
        if (!$duration_months) {
            throw new Exception("duration_months wajib untuk booking bulanan");
        }

        $total_price = $duration_months * $price_monthly;

        // auto hitung check_out_date kalau belum dikirim
        if (!$check_out_date) {
            $check_out_date = date(
                "Y-m-d",
                strtotime("+{$duration_months} month", strtotime($check_in_date))
            );
        }
    } else {
        // DAILY
        if (!$check_out_date) {
            throw new Exception("check_out_date wajib untuk booking harian");
        }

        $days = (strtotime($check_out_date) - strtotime($check_in_date)) / (60 * 60 * 24);
        if ($days <= 0) {
            throw new Exception("Rentang tanggal tidak valid");
        }

        if ($price_daily !== null) {
            $total_price = $days * $price_daily;
        } else {
            $total_price = $days * round($price_monthly / 30);
        }
    }

    $status         = "pending";
    $payment_status = "unpaid";

    $insert = $conn->prepare("
        INSERT INTO bookings
        (kos_id, user_id, check_in_date, check_out_date, booking_type, duration_months, total_price, status, payment_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $insert->bind_param(
        "iisssiiss",
        $kos_id,
        $user_id,
        $check_in_date,
        $check_out_date,
        $booking_type,
        $duration_months,
        $total_price,
        $status,
        $payment_status
    );
    $insert->execute();

    $booking_id = $insert->insert_id;

    // ===== NOTIF: kirim ke owner (biar owner tau ada booking masuk) =====
    // pakai type 'new_booking' (sesuai enum sekarang)
    if ($owner_id > 0 && $owner_id !== $user_id) {
        $title = "Booking Baru";
        $msg   = "Ada booking baru untuk \"$kos_name\".\nCheck-in: $check_in_date\nCheck-out: $check_out_date";
        $type  = "new_booking";

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
        "message" => "Booking berhasil dibuat, menunggu persetujuan pemilik",
        "data"    => [
            "id"             => $booking_id,
            "kos_id"         => $kos_id,
            "user_id"        => $user_id,
            "check_in_date"  => $check_in_date,
            "check_out_date" => $check_out_date,
            "booking_type"   => $booking_type,
            "duration_months"=> $duration_months,
            "total_price"    => $total_price,
            "status"         => $status,
            "payment_status" => $payment_status
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}
