<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../../config/db.php';
require_once '../../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse("error", "Invalid request method");
}

$user_id = intval($_POST['user_id'] ?? 0);
$enabled = intval($_POST['enabled'] ?? -1); // harus 0 / 1

if ($user_id <= 0) {
    jsonResponse("error", "Invalid user_id");
}
if (!in_array($enabled, [0, 1], true)) {
    jsonResponse("error", "Invalid enabled value (0/1)");
}

$q = $conn->prepare("UPDATE users SET notification_enabled = ? WHERE id = ?");
$q->bind_param("ii", $enabled, $user_id);

if ($q->execute()) {
    jsonResponse("success", "Notification preference updated", [
        "user_id" => $user_id,
        "notification_enabled" => $enabled
    ]);
} else {
    jsonResponse("error", "Failed to update notification preference", [
        "db_error" => $q->error
    ]);
}
