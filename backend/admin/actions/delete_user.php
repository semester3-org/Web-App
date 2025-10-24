<?php
// backend/admin/actions/delete_user.php
session_start();
header('Content-Type: application/json');

require_once '../classes/UserManager.php';

// Check if user is superadmin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID user tidak ditemukan']);
    exit();
}

$userManager = new UserManager();
$result = $userManager->deleteUser($data['id']);
echo json_encode($result);