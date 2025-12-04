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
    // Pastikan semua field dikirim (form-urlencoded)
    if (isset($_POST['kos_id'], $_POST['rating'], $_POST['comment'], $_POST['user_id'])) {
        $kos_id  = (int) $_POST['kos_id'];
        $rating  = (float) $_POST['rating'];
        $comment = $_POST['comment'];
        $user_id = (int) $_POST['user_id'];

        // Query simpan / update rating
        $query = "
            INSERT INTO reviews (kos_id, user_id, rating, comment)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = ?, comment = ?
        ";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            echo json_encode([
                "success" => false,
                "status"  => "prepare_error",
                "message" => $conn->error
            ]);
            exit;
        }

        // i = int, d = double/float, s = string
        $stmt->bind_param("iidssd", $kos_id, $user_id, $rating, $comment, $rating, $comment);

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true,
                "status"  => "success",
                "message" => "Rating and comment successfully submitted"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "status"  => "db_error",
                "message" => $stmt->error
            ]);
        }

        $stmt->close();
    } else {
        echo json_encode([
            "success" => false,
            "status"  => "validation_error",
            "message" => "Missing required fields"
        ]);
    }

} catch (Throwable $e) {
    echo json_encode([
        "success" => false,
        "status"  => "exception",
        "message" => $e->getMessage()
    ]);
}
