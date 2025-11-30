<?php
header('Content-Type: application/json');
include "../../config/db.php";

if (!isset($_POST['id'])) {
    echo json_encode([
        "success" => false,
        "message" => "id is required"
    ]);
    exit;
}

$notif_id = intval($_POST['id']);

$sql = "UPDATE notifications SET is_read = 1 WHERE id = $notif_id";
$exec = mysqli_query($conn, $sql);

if ($exec) {
    echo json_encode([
        "success" => true,
        "message" => "Notification marked as read"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to update notification"
    ]);
}
