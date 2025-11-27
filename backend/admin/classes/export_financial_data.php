<?php
session_start();


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
    die('Periode tidak valid');
}

try {
    $start_date = $start_month . '-01';
    $end_date = date('Y-m-t', strtotime($end_month . '-01'));

    $query = "
        SELECT
            DATE_FORMAT(MIN(paid_at), '%M %Y') AS `Bulan`,
            COUNT(*) AS `Total Transaksi`,
            CONCAT('Rp ', FORMAT(SUM(total_price), 0, 'id_ID')) AS `Total Harga Booking`,
            CONCAT('Rp ', FORMAT(SUM(total_price * 0.10), 0, 'id_ID')) AS `Pemasukan Pajak (10%)`
        FROM bookings
        WHERE payment_status = 'paid'
            AND paid_at IS NOT NULL
            AND DATE(paid_at) >= ?
            AND DATE(paid_at) <= ?
        GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
        ORDER BY DATE_FORMAT(paid_at, '%Y-%m') ASC;
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=catatan_keuangan_' . $start_month . '_to_' . $end_month . '.csv');

    $output = fopen('php://output', 'w');

    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Column headers
    $columns = ['Bulan', 'Total Transaksi', 'Total Harga Booking', 'Pemasukan Pajak (10%)'];
    fputcsv($output, $columns);

    // Data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
