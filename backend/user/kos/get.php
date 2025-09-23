<?php
require_once '../config/db.php';

$kos_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($kos_id <= 0) {
    echo json_encode(['error' => 'ID kos tidak valid']);
    exit;
}

// Get kos details
$stmt = $pdo->prepare("SELECT k.*, u.name as owner_name, u.phone as owner_phone 
                      FROM kos k 
                      JOIN users u ON k.owner_id = u.id 
                      WHERE k.id = ?");
$stmt->execute([$kos_id]);
$kos = $stmt->fetch();

if (!$kos) {
    echo json_encode(['error' => 'Kos tidak ditemukan']);
    exit;
}

// Get facilities as array
if (!empty($kos['facilities'])) {
    $kos['facilities'] = array_map('trim', explode(',', $kos['facilities']));
} else {
    $kos['facilities'] = [];
}

echo json_encode(['data' => $kos]);
?>