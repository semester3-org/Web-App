<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    echo json_encode(["success" => true]);
    exit;
}

$user_id = intval($_GET['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid user_id"]);
    exit;
}

// kalau notif dimatiin, unread = 0 biar lonceng anteng
$u = $conn->prepare("SELECT notification_enabled FROM users WHERE id = ? LIMIT 1");
$u->bind_param("i", $user_id);
$u->execute();
$ur = $u->get_result();
if ($ur->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}
$row = $ur->fetch_assoc();
if (intval($row["notification_enabled"]) === 0) {
    echo json_encode(["success" => true, "unread_count" => 0]);
    exit;
}

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE user_id = ?
      AND is_read = 0
      AND is_archived = 0
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

echo json_encode([
    "success" => true,
    "unread_count" => intval($res["total"] ?? 0)
]);
