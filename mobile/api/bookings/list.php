<?php
header("Content-Type: application/json");
include "../../config/db.php";

try {
    $user_id = isset($_GET["user_id"]) ? intval($_GET["user_id"]) : 0;

    if ($user_id <= 0) {
        throw new Exception("user_id wajib diisi");
    }

    $base_url = "http://10.134.206.61/Web-App/";

    $sql = "
        SELECT 
            b.id,
            b.kos_id,
            b.check_in_date,
            b.check_out_date,
            b.booking_type,
            b.duration_months,
            b.total_price,
            b.status,
            b.payment_status,
            b.created_at,
            
            k.name AS kos_name,
            k.city AS location_name,
            k.address,
            k.kos_type,

            (
              SELECT image_url 
              FROM kos_images 
              WHERE kos_id = b.kos_id
              ORDER BY id ASC
              LIMIT 1
            ) AS image_url
        FROM bookings b
        JOIN kos k ON k.id = b.kos_id
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $image = $row["image_url"] ? $base_url . $row["image_url"] : null;

        $data[] = [
            "id"             => intval($row["id"]),
            "kos_id"         => intval($row["kos_id"]),
            "kos_name"       => $row["kos_name"],
            "location_name"  => $row["location_name"],
            "address"        => $row["address"],
            "kos_type"       => $row["kos_type"],

            "image"          => $image,
            "check_in_date"  => $row["check_in_date"],
            "check_out_date" => $row["check_out_date"],
            "booking_type"   => $row["booking_type"],
            "duration_months"=> $row["duration_months"] ? intval($row["duration_months"]) : null,
            "total_price"    => intval($row["total_price"]),
            "status"         => $row["status"],
            "payment_status" => $row["payment_status"],
            "created_at"     => $row["created_at"],
        ];
    }

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
