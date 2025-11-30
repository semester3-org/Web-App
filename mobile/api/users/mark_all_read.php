<?php
header('Content-Type: application/json');
include "../../config/db.php";

if (!isset($_POST['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "user_id is required"
    ]);
    exit;
}

$user_id = intval($_POST['user_id']);

$sql = "UPDATE notifications SET is_read = 1 WHERE user_id = $user_id";
$run = mysqli_query($conn, $sql);

if ($run) {
    echo json_encode([
        "success" => true,
        "message" => "All notifications marked as read"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to update"
    ]);
}
