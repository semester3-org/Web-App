<?php
// ...existing code...
session_start();
require_once __DIR__ . '/../../../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$field = $input['field'] ?? '';
$value = trim($input['value'] ?? '');

if (!in_array($field, ['username', 'email'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid field']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($field === 'username') {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
} else {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
}

$stmt->bind_param('si', $value, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$exists = ($result->fetch_assoc() !== null);

echo json_encode(['exists' => $exists]);

$stmt->close();
$conn->close();
exit;