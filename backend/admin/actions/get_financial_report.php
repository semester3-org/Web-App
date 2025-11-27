<?php
// backend/admin/actions/get_financial_report.php
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

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

$transactionManager = new TransactionManager();
$report = $transactionManager->getFinancialReport($startDate, $endDate);

echo json_encode([
    'success' => true,
    'summary' => $report['summary'],
    'transactions' => $report['transactions'],
    'chart_data' => $report['chart_data']
]);