<?php
header("Content-Type: application/json");
include "../../config/db.php";

function base_url(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? "localhost";
    // asumsi project lu ada di /Web-App/
    return $scheme . "://" . $host . "/Web-App/";
}

try {
    // ✅ terima kos_id ATAU id (biar mobile/web aman)
    $kos_id = filter_input(INPUT_GET, 'kos_id', FILTER_VALIDATE_INT);
    if (!$kos_id) $kos_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$kos_id) {
        echo json_encode([
            "status" => "error",
            "message" => "kos_id (or id) parameter is required"
        ]);
        exit;
    }

    // ✅ detail kos (no ORDER BY, pake LIMIT 1)
    $query = "
        SELECT
            k.id,
            k.name,
            k.description,
            k.address,
            k.city AS location_name,
            k.province,
            k.postal_code,
            k.kos_type,
            k.total_rooms,
            k.available_rooms,
            k.price_monthly,
            k.latitude,
            k.longitude,
            (SELECT AVG(r.rating) FROM reviews r WHERE r.kos_id = k.id) AS avg_rating,
            (SELECT COUNT(*) FROM reviews r WHERE r.kos_id = k.id) AS rating_count
        FROM kos k
        WHERE k.id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $kos_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Kos not found"
        ]);
        exit;
    }

    $kosData = $result->fetch_assoc();

    // ✅ facilities: keluarin array {name, icon} + string facilities_text
    $facStmt = $conn->prepare("
        SELECT f.name, f.icon
        FROM kos_facilities kf
        JOIN facilities f ON f.id = kf.facility_id
        WHERE kf.kos_id = ?
        ORDER BY f.name ASC
    ");
    $facStmt->bind_param("i", $kos_id);
    $facStmt->execute();
    $facRes = $facStmt->get_result();

    $facilities = [];
    $facTextArr = [];
    while ($f = $facRes->fetch_assoc()) {
        $facilities[] = [
            "name" => $f["name"],
            "icon" => $f["icon"]
        ];
        $facTextArr[] = $f["name"];
    }
    $facilitiesText = implode(", ", $facTextArr);

    // ✅ images: bikin full URL dari host yang sama dengan API
    $imgStmt = $conn->prepare("
        SELECT image_url
        FROM kos_images
        WHERE kos_id = ?
        ORDER BY id ASC
    ");
    $imgStmt->bind_param("i", $kos_id);
    $imgStmt->execute();
    $imgRes = $imgStmt->get_result();

    $images = [];
    $base = base_url();
    while ($img = $imgRes->fetch_assoc()) {
        $path = ltrim($img["image_url"], "/");
        $images[] = $base . $path;
    }

    $rating = ($kosData["avg_rating"] !== null) ? round((float)$kosData["avg_rating"], 1) : 0.0;
    $ratingCount = (int)($kosData["rating_count"] ?? 0);

    echo json_encode([
        "status" => "success",
        "data" => [
            "id" => (int)$kosData["id"],
            "name" => $kosData["name"],
            "description" => $kosData["description"],
            "location_name" => $kosData["location_name"],
            "address" => $kosData["address"],
            "province" => $kosData["province"],
            "postal_code" => $kosData["postal_code"],
            "kos_type" => $kosData["kos_type"],
            "total_rooms" => (int)$kosData["total_rooms"],
            "available_rooms" => (int)$kosData["available_rooms"],
            "latitude" => $kosData["latitude"] !== null ? (float)$kosData["latitude"] : null,
            "longitude" => $kosData["longitude"] !== null ? (float)$kosData["longitude"] : null,
            "price_monthly" => (int)$kosData["price_monthly"],

            // backward compatible:
            "facilities" => $facilitiesText,

            // versi baru yg enak buat mobile:
            "facilities_list" => $facilities,

            "rating" => $rating,
            "rating_count" => $ratingCount,
            "images" => $images
        ]
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
