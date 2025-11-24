<?php
header("Content-Type: application/json");

include "../../config/db.php";  // file koneksi DB

try {
    // Ambil semua kos
    $query = "
        SELECT 
            k.id,
            k.name,
            k.description,
            k.address,
            k.city AS location_name,
            k.price_monthly,
            k.latitude,
            k.longitude,

            -- Ambil fasilitas (string)
            (
                SELECT GROUP_CONCAT(f.name SEPARATOR ', ')
                FROM kos_facilities kf
                JOIN facilities f ON f.id = kf.facility_id
                WHERE kf.kos_id = k.id
            ) AS facilities,

            -- Ambil rating rata-rata
            (
                SELECT AVG(r.rating)
                FROM reviews r
                WHERE r.kos_id = k.id
            ) AS avg_rating
        FROM kos k
        ORDER BY k.id DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    $kosData = [];
    $base_url = "http://10.169.123.171/Web-App/"; // Ganti dengan base URL yang sesuai

    while ($row = $result->fetch_assoc()) {
        // Ambil semua gambar
        $imgQuery = $conn->prepare("
            SELECT image_url
            FROM kos_images
            WHERE kos_id = ?
        ");
        $imgQuery->bind_param("i", $row["id"]);
        $imgQuery->execute();
        $imgResult = $imgQuery->get_result();

        $images = [];
        while ($img = $imgResult->fetch_assoc()) {
            // Tambahkan base URL sebelum image_url
            $images[] = $base_url . $img["image_url"];
        }

        // handle rating null
        $rating = $row["avg_rating"] ? round(floatval($row["avg_rating"]), 1) : 0;

        $kosData[] = [
            "id" => intval($row["id"]),
            "name" => $row["name"],
            "description" => $row["description"],
            "location_name" => $row["location_name"],
            "address" => $row["address"],
            "latitude" => floatval($row["latitude"]),
            "longitude" => floatval($row["longitude"]),
            "price_monthly" => intval($row["price_monthly"]),
            "facilities" => $row["facilities"] ?? "",
            "rating" => $rating,
            "images" => $images,
        ];
    }

    echo json_encode([
        "status" => "success",
        "data" => $kosData
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
