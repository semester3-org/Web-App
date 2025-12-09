<?php
header("Content-Type: application/json");
include "../../config/db.php";

try {
    $booking_id = isset($_GET["booking_id"]) ? intval($_GET["booking_id"]) : 0;

    if ($booking_id <= 0) {
        throw new Exception("booking_id wajib diisi");
    }

    $base_url = "http://10.207.134.61/Web-App/";

    $sql = "
        SELECT 
            b.*,
            k.name AS kos_name,
            k.city AS location_name,
            k.address,
            k.kos_type,
            k.price_monthly,
            k.price_daily,
            k.latitude,
            k.longitude,
            (
                SELECT GROUP_CONCAT(f.name SEPARATOR ', ')
                FROM kos_facilities kf
                JOIN facilities f ON f.id = kf.facility_id
                WHERE kf.kos_id = k.id
            ) AS facilities,
            (
                SELECT AVG(r.rating)
                FROM reviews r
                WHERE r.kos_id = k.id
            ) AS avg_rating
        FROM bookings b
        JOIN kos k ON k.id = b.kos_id
        WHERE b.id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        throw new Exception("Booking tidak ditemukan");
    }

    // ambil semua gambar kos
    $imgStmt = $conn->prepare("SELECT image_url FROM kos_images WHERE kos_id = ?");
    $imgStmt->bind_param("i", $row["kos_id"]);
    $imgStmt->execute();
    $imgRes = $imgStmt->get_result();

    $images = [];
    while ($img = $imgRes->fetch_assoc()) {
        $images[] = $base_url . $img["image_url"];
    }

    $rating = $row["avg_rating"] ? round(floatval($row["avg_rating"]), 1) : 0;

    $data = [
        "id"             => intval($row["id"]),
        "kos_id"         => intval($row["kos_id"]),
        "user_id"        => intval($row["user_id"]),
        "check_in_date"  => $row["check_in_date"],
        "check_out_date" => $row["check_out_date"],
        "booking_type"   => $row["booking_type"],
        "duration_months"=> $row["duration_months"] ? intval($row["duration_months"]) : null,
        "total_price"    => intval($row["total_price"]),
        "status"         => $row["status"],
        "payment_status" => $row["payment_status"],
        "created_at"     => $row["created_at"],
        "paid_at"        => $row["paid_at"],

        "kos_name"       => $row["kos_name"],
        "location_name"  => $row["location_name"],
        "address"        => $row["address"],
        "kos_type"       => $row["kos_type"],
        "price_monthly"  => intval($row["price_monthly"]),
        "price_daily"    => $row["price_daily"] !== null ? intval($row["price_daily"]) : null,
        "latitude"       => $row["latitude"] !== null ? floatval($row["latitude"]) : null,
        "longitude"      => $row["longitude"] !== null ? floatval($row["longitude"]) : null,
        "facilities"     => $row["facilities"] ?? "",
        "rating"         => $rating,
        "images"         => $images
    ];

    echo json_encode([
        "status" => "success",
        "data"   => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}
