<?php
require_once '../config/db.php';

// Get filter parameters
$type = isset($_GET['type']) ? $_GET['type'] : '';
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 10000000;
$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';

// Build query
$query = "SELECT k.*, u.name as owner_name 
          FROM kos k 
          JOIN users u ON k.owner_id = u.id 
          WHERE (k.name LIKE ? OR k.address LIKE ?) 
          AND k.price BETWEEN ? AND ?";

$params = [$search, $search, $min_price, $max_price];

if (!empty($type) && in_array($type, ['male', 'female', 'mixed'])) {
    $query .= " AND k.type = ?";
    $params[] = $type;
}

$query .= " ORDER BY k.created_at DESC";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$kos_list = $stmt->fetchAll();

echo json_encode(['data' => $kos_list]);
?>