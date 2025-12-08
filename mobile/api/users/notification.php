<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../../config/db.php';

// ambil user_id dari GET atau POST
$user_id = 0;
if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
} elseif (isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
}

if ($user_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "user_id is required"
    ]);
    exit;
}

// optional: cuma notif yang belum di-archive
$sql = "SELECT 
            id,
            user_id,
            kos_id,
            type,
            title,
            message,
            wishlist_count,
            review_count,
            related_id,
            is_read,
            created_at
        FROM notifications
        WHERE user_id = ?
          AND (is_archived = 0 OR is_archived IS NULL)
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Prepare failed",
        "error"   => $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Execute failed",
        "error"   => $stmt->error
    ]);
    exit;
}

$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    // cast biar android tenang
    $row['id'] = intval($row['id']);
    $row['user_id'] = intval($row['user_id']);
    $row['kos_id'] = isset($row['kos_id']) ? intval($row['kos_id']) : null;
    $row['wishlist_count'] = isset($row['wishlist_count']) ? intval($row['wishlist_count']) : null;
    $row['review_count'] = isset($row['review_count']) ? intval($row['review_count']) : null;
    $row['related_id'] = isset($row['related_id']) ? intval($row['related_id']) : null;
    $row['is_read'] = isset($row['is_read']) ? intval($row['is_read']) : 0;

    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "message" => "OK",
    "data"    => $data
]);
