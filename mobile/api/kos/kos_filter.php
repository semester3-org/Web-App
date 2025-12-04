<?php
header("Content-Type: application/json");

include "../../config/db.php"; // koneksi: $conn

try {
    $kosType       = isset($_GET['kos_type']) ? trim($_GET['kos_type']) : null;        // putra/putri/campur
    $availableOnly = isset($_GET['available_only']) ? $_GET['available_only'] : null; // 1 = hanya yg avail
    $minPrice      = isset($_GET['min_price']) ? (int) $_GET['min_price'] : null;
    $maxPrice      = isset($_GET['max_price']) ? (int) $_GET['max_price'] : null;
    $facilityIds   = isset($_GET['facility_ids']) ? $_GET['facility_ids'] : null;     // "1,2,3"

    $params = [];
    $types  = "";

    $query = "
        SELECT 
            k.id,
            k.name,
            k.description,
            k.address,
            k.city AS location_name,
            k.kos_type,
            k.price_monthly,
            k.latitude,
            k.longitude,
            k.available_rooms,
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
        FROM kos k
        WHERE k.status = 'approved'
    ";

    // jenis kos - toleran (LIKE) tapi tetap pakai lower
    if (!empty($kosType)) {
        $query   .= " AND LOWER(k.kos_type) LIKE ?";
        $types   .= "s";
        $params[] = '%' . strtolower($kosType) . '%';
    }

    // hanya yang masih ada kamar
    if (!empty($availableOnly) && $availableOnly == '1') {
        $query .= " AND k.available_rooms > 0";
    }

    // harga
    if (!empty($minPrice)) {
        $query   .= " AND k.price_monthly >= ?";
        $types   .= "i";
        $params[] = $minPrice;
    }

    if (!empty($maxPrice)) {
        $query   .= " AND k.price_monthly <= ?";
        $types   .= "i";
        $params[] = $maxPrice;
    }


   // fasilitas AND logic
    $facilityIdArray = [];
if (!empty($facilityIds)) {
    $facilityIdArray = array_filter(array_map('trim', explode(',', $facilityIds)));
    if (!empty($facilityIdArray)) {
        $placeholders = implode(',', array_fill(0, count($facilityIdArray), '?'));
        $query .= "
            AND k.id IN (
                SELECT DISTINCT kf.kos_id
                FROM kos_facilities kf
                WHERE kf.facility_id IN ($placeholders)
            )
        ";
        foreach ($facilityIdArray as $fid) {
            $types   .= "i";
            $params[] = (int) $fid;
        }
    }
}


    $query .= " ORDER BY k.id DESC";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $kosData  = [];
    $base_url = "http://10.134.206.61/Web-App/"; // samain sama kos_list

    while ($row = $result->fetch_assoc()) {
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
            $images[] = $base_url . $img["image_url"];
        }

        $rating = $row["avg_rating"] ? round(floatval($row["avg_rating"]), 1) : 0;

        $kosData[] = [
            "id"             => intval($row["id"]),
            "name"           => $row["name"],
            "description"    => $row["description"],
            "location_name"  => $row["location_name"],
            "address"        => $row["address"],
            "latitude"       => floatval($row["latitude"]),
            "longitude"      => floatval($row["longitude"]),
            "kos_type"       => $row["kos_type"],
            "price_monthly"  => intval($row["price_monthly"]),
            "available_rooms"=> intval($row["available_rooms"]),
            "facilities"     => $row["facilities"] ?? "",
            "rating"         => $rating,
            "images"         => $images,
        ];
    }

    echo json_encode([
        "status" => "success",
        "data"   => $kosData
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}
