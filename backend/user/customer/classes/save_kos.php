<?php
session_start();
header('Content-Type: application/json');

// Gunakan koneksi utama dari config
require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login dulu']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$input = json_decode(file_get_contents("php://input"), true);
$kos_id = intval($input['kos_id'] ?? 0);

if ($kos_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID invalid']);
    exit;
}

// Cek apakah sudah difavoritkan
$stmt = $conn->prepare("SELECT id FROM saved_kos WHERE user_id = ? AND kos_id = ?");
$stmt->bind_param("ii", $user_id, $kos_id);
$stmt->execute();
$exists = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($exists) {
    $delete = $conn->prepare("DELETE FROM saved_kos WHERE user_id = ? AND kos_id = ?");
    $delete->bind_param("ii", $user_id, $kos_id);
    $delete->execute();
    $delete->close();

    echo json_encode(['success' => true, 'favorited' => false, 'message' => 'Dihapus dari wishlist']);
} else {
    $insert = $conn->prepare("INSERT INTO saved_kos (user_id, kos_id) VALUES (?, ?)");
    $insert->bind_param("ii", $user_id, $kos_id);
    
    $insert->execute();
    $conn->commit(); 
    $insert->close();

    echo json_encode(['success' => true, 'favorited' => true, 'message' => 'Ditambah ke wishlist']);
}

$conn->close();
