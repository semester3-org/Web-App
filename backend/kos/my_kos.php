<?php
require_once '../utils/auth_session.php';
require_once '../config/db.php';

check_login();
check_role('owner');

$owner_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM kos WHERE owner_id = ? ORDER BY created_at DESC");
$stmt->execute([$owner_id]);
$my_kos = $stmt->fetchAll();

echo json_encode(['data' => $my_kos]);
?>