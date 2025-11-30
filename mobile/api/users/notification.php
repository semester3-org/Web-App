<?php
header('Content-Type: application/json');
include "../../config/db.php";

if (!isset($_GET['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "user_id is required"
    ]);
    exit;
}

$user_id = intval($_GET['user_id']);

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
        WHERE user_id = $user_id
        ORDER BY id DESC";

$result = mysqli_query($conn, $sql);

$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $data
]);
