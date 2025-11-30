<?php
session_start();
header('Content-Type: application/json');

// FIX: Load DB config dengan path absolut yang benar
require_once __DIR__ . "/../../config/db.php";

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Anda harus login terlebih dahulu";
    header("Location: " . $redirectPath);
    exit();
}

$start_month = $_GET['start'] ?? '';
$end_month = $_GET['end'] ?? '';

if (empty($start_month) || empty($end_month)) {
    echo json_encode(['success' => false, 'message' => 'Periode tidak valid']);
    exit;
}

try {
    $start_date = $start_month . '-01';
    $end_date = date('Y-m-t', strtotime($end_month . '-01'));

    $query = "
        SELECT 
            DATE_FORMAT(paid_at, '%Y-%m') AS month_key,
            DATE_FORMAT(MIN(paid_at), '%M %Y') AS month_name,
            COUNT(*) AS transaction_count,
            SUM(total_price) AS total_booking_price,
            SUM(total_price * 0.10) AS tax_revenue
        FROM bookings
        WHERE payment_status = 'paid'
            AND paid_at IS NOT NULL
            AND DATE(paid_at) >= ?
            AND DATE(paid_at) <= ?
        GROUP BY month_key
        ORDER BY month_key ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    $total_transactions = 0;
    $total_tax = 0;

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $total_transactions += $row['transaction_count'];
        $total_tax += $row['tax_revenue'];
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'summary' => [
            'total_transactions' => $total_transactions,
            'total_tax' => $total_tax
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
