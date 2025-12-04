<?php
header("Content-Type: application/json");
include "../../config/db.php"; // Koneksi ke database

function base_url(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? "localhost";
    // Asumsi project ada di /Web-App/
    return $scheme . "://" . $host . "/Web-App/";
}

try {
    // Terima kos_id dari request
    $kos_id = filter_input(INPUT_GET, 'kos_id', FILTER_VALIDATE_INT);
    if (!$kos_id) {
        echo json_encode([
            "status" => "error",
            "message" => "kos_id parameter is required"
        ]);
        exit;
    }

    // Ambil rating dan komentar user berdasarkan kos_id
    $query = "
        SELECT r.rating, r.comment
        FROM reviews r
        WHERE r.kos_id = ?
        ORDER BY r.created_at DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $kos_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            "status" => "error",
            "message" => "No ratings found for this kos"
        ]);
        exit;
    }

    $ratingData = $result->fetch_assoc();

    echo json_encode([
        "status" => "success",
        "data" => [
            "rating" => (float) $ratingData["rating"],
            "comment" => $ratingData["comment"]
        ]
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
