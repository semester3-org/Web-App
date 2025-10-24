<?php
// backend/admin/actions/get_admin.php
session_start();
header('Content-Type: application/json');

require_once '../classes/AdminManager.php';

// Check if user is superadmin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID admin tidak ditemukan']);
    exit();
}

$adminManager = new AdminManager();
$admin = $adminManager->getAdminById($_GET['id']);

if ($admin) {
    echo json_encode(['success' => true, 'admin' => $admin]);
} else {
    echo json_encode(['success' => false, 'message' => 'Admin tidak ditemukan']);
}