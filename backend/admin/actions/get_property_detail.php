<?php
// backend/admin/actions/get_property_detail.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once '../classes/TransactionManager.php';

// Check if user is admin or superadmin
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID properti tidak ditemukan']);
    exit();
}

$transactionManager = new TransactionManager();
$property = $transactionManager->getPropertyDetail($_GET['id']);

if ($property) {
    echo json_encode([
        'success' => true,
        'property' => $property
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Properti tidak ditemukan']);
}