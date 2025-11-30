<?php
// backend/admin/actions/export_financial_report.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../classes/TransactionManager.php';

// Check if user is admin or superadmin
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    die('Akses ditolak');
}

$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$transactionManager = new TransactionManager();
$report = $transactionManager->getFinancialReport($startDate, $endDate);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Laporan_Keuangan_' . date('Y-m-d_His') . '.xls"');
header('Cache-Control: max-age=0');

// Start HTML for Excel
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #10b981;
            color: white;
            font-weight: bold;
        }
        .header {
            margin-bottom: 20px;
        }
        .summary {
            margin-bottom: 20px;
            font-weight: bold;
        }
        .total-row {
            background-color: #d1fae5;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN KEUANGAN PROPERTY TAX</h2>
        <p>Periode: <?= date('d M Y', strtotime($startDate)) ?> - <?= date('d M Y', strtotime($endDate)) ?></p>
        <p>Dicetak pada: <?= date('d M Y H:i:s') ?></p>
    </div>

    <div class="summary">
        <h3>RINGKASAN</h3>
        <table style="width: 50%; margin-bottom: 20px;">
            <tr>
                <td><strong>Total Pemasukan</strong></td>
                <td style="text-align: right;"><strong>Rp <?= number_format($report['summary']['total_income'], 0, ',', '.') ?></strong></td>
            </tr>
            <tr>
                <td><strong>Total Transaksi</strong></td>
                <td style="text-align: right;"><strong><?= $report['summary']['total_transactions'] ?></strong></td>
            </tr>
        </table>
    </div>

    <h3>DETAIL TRANSAKSI</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Order ID</th>
                <th>Properti</th>
                <th>Owner</th>
                <th>Harga Bulanan</th>
                <th>Pajak 10%</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($report['transactions']) > 0): ?>
                <?php foreach ($report['transactions'] as $index => $transaction): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= date('d M Y H:i', strtotime($transaction['paid_at'])) ?></td>
                        <td><?= htmlspecialchars($transaction['order_id']) ?></td>
                        <td><?= htmlspecialchars($transaction['property_name']) ?></td>
                        <td><?= htmlspecialchars($transaction['owner_name']) ?></td>
                        <td style="text-align: right;">Rp <?= number_format($transaction['price_monthly'], 0, ',', '.') ?></td>
                        <td style="text-align: right;">Rp <?= number_format($transaction['tax_amount'], 0, ',', '.') ?></td>
                        <td style="text-align: right;">Rp <?= number_format($transaction['total_amount'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="5" style="text-align: right;"><strong>TOTAL</strong></td>
                    <td style="text-align: right;"><strong>Rp <?= number_format(array_sum(array_column($report['transactions'], 'price_monthly')), 0, ',', '.') ?></strong></td>
                    <td style="text-align: right;"><strong>Rp <?= number_format($report['summary']['total_tax'], 0, ',', '.') ?></strong></td>
                    <td style="text-align: right;"><strong>Rp <?= number_format($report['summary']['total_income'], 0, ',', '.') ?></strong></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center;">Tidak ada data transaksi pada periode ini</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 40px;">
        <p><strong>Catatan:</strong></p>
        <ul>
            <li>Pajak dihitung 10% dari harga bulanan properti</li>
            <li>Total = Harga Bulanan + Pajak 10%</li>
            <li>Laporan ini mencakup semua transaksi dengan status pembayaran "Settlement"</li>
        </ul>
    </div>

    <div style="margin-top: 40px;">
        <table style="width: 100%; border: none;">
            <tr style="border: none;">
                <td style="width: 50%; border: none; text-align: center;">
                    <p>Mengetahui,</p>
                    <br><br><br>
                    <p>_____________________</p>
                    <p>Admin</p>
                </td>
                <td style="width: 50%; border: none; text-align: center;">
                    <p>Menyetujui,</p>
                    <br><br><br>
                    <p>_____________________</p>
                    <p>Supervisor</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
<?php
exit();