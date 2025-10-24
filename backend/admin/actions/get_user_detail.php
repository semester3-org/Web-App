<?php
// backend/admin/actions/get_user_detail.php
session_start();
header('Content-Type: application/json');

require_once '../classes/UserManager.php';

// Check if user is superadmin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID user tidak ditemukan']);
    exit();
}

$userManager = new UserManager();
$user = $userManager->getUserById($_GET['id']);

if ($user) {
    $properties = $userManager->getUserProperties($_GET['id']);
    echo json_encode([
        'success' => true, 
        'user' => $user,
        'properties' => $properties
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
}