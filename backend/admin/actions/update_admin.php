<?php
// backend/admin/actions/update_admin.php
session_start();
header('Content-Type: application/json');

require_once '../classes/AdminManager.php';

// Check if user is superadmin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit();
}

// Validate input
if (empty($_POST['id']) || empty($_POST['username']) || empty($_POST['email']) || 
    empty($_POST['full_name']) || empty($_POST['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
    exit();
}

// Validate email format
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
    exit();
}

// Validate user type
if (!in_array($_POST['user_type'], ['admin', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Tipe admin tidak valid']);
    exit();
}

// Validate password if provided
if (!empty($_POST['password']) && strlen($_POST['password']) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
    exit();
}

$adminManager = new AdminManager();

$data = [
    'username' => trim($_POST['username']),
    'email' => trim($_POST['email']),
    'full_name' => trim($_POST['full_name']),
    'phone' => trim($_POST['phone'] ?? ''),
    'user_type' => $_POST['user_type'],
    'password' => $_POST['password'] ?? ''
];

$result = $adminManager->updateAdmin($_POST['id'], $data);
echo json_encode($result);