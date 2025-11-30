<?php
// backend/admin/classes/BookingManager.php
require_once __DIR__ . '/../../config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
class BookingManager
{

    public function __construct()
    {
        // Constructor kosong; mengandalkan global $conn yang di-setup di config/db.php
    }

    public function getBookingById($id)
    {
        global $conn;
        $query = "SELECT b.* FROM bookings b WHERE b.id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) return null;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row;
    }

    // Ambil booking berdasarkan order_id
    public function getBookingByOrderId($orderId)
    {
        global $conn;
        $query = "SELECT b.* FROM bookings b WHERE b.order_id = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        if (!$stmt) return null;
        $stmt->bind_param('s', $orderId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row;
    }

    // Buat booking baru (contoh minimal)
    public function createBooking($data)
    {
        global $conn;
        $query = "INSERT INTO bookings (order_id, user_id, kos_id, total_price, booking_type, payment_status, created_at)
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        if (!$stmt) return false;
        $stmt->bind_param(
            'siidss',
            $data['order_id'],
            $data['user_id'],
            $data['kos_id'],
            $data['total_price'],
            $data['booking_type'],
            $data['payment_status']
        );
        $ok = $stmt->execute();
        if ($ok) {
            $insertId = $stmt->insert_id;
            $stmt->close();
            return $insertId;
        }
        $stmt->close();
        return false;
    }

    // Update payment status dan paid_at
    public function updatePaymentStatus($orderId, $paymentStatus, $paidAt = null)
    {
        global $conn;
        $query = "UPDATE bookings SET payment_status = ?, paid_at = ? WHERE order_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) return false;
        $stmt->bind_param('sss', $paymentStatus, $paidAt, $orderId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // Hapus booking (contoh)
    public function deleteBooking($id)
    {
        global $conn;
        $query = "DELETE FROM bookings WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /* ---------------------------------------
       Method laporan finansial (sesuai permintaan)
       --------------------------------------- */

    // Get financial report for bookings
    public function getBookingFinancialReport($startDate = null, $endDate = null)
    {
        global $conn;

        // Set default date range if not provided
        if (!$startDate) {
            $startDate = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$endDate) {
            $endDate = date('Y-m-d');
        }

        $query = "SELECT 
                    b.id,
                    b.order_id,
                    b.total_price,
                    b.booking_type,
                    b.paid_at,
                    k.name as kos_name,
                    u.full_name as customer_name
                  FROM bookings b
                  INNER JOIN kos k ON b.kos_id = k.id
                  INNER JOIN users u ON b.user_id = u.id
                  WHERE b.payment_status = 'paid'
                    AND b.paid_at IS NOT NULL
                    AND DATE(b.paid_at) BETWEEN ? AND ?
                  ORDER BY b.paid_at DESC";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            // Jika prepare gagal, kembalikan struktur kosong
            return [
                'summary' => ['total_income' => 0, 'total_transactions' => 0],
                'transactions' => [],
                'chart_data' => ['labels' => [], 'values' => []]
            ];
        }
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = [];
        $totalIncome = 0;

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Pajak / profit admin = 10% dari total harga
                $row['tax_amount'] = (float)$row['total_price'] * 0.10;
                // Untuk laporan pemasukan, total yang masuk hanya pajaknya
                $row['total_with_tax'] = $row['tax_amount']; // alias income admin

                $transactions[] = $row;
                $totalIncome += $row['tax_amount'];
            }
        }
        $stmt->close();

        // Calculate summary
        $summary = [
            'total_income' => $totalIncome,
            'total_transactions' => count($transactions)
        ];

        // Group by month for chart
        $chartData = $this->groupBookingsByMonth($transactions, $startDate, $endDate);

        return [
            'summary' => $summary,
            'transactions' => $transactions,
            'chart_data' => $chartData
        ];
    }

    // Group bookings per bulan (menghasilkan labels & values)
    private function groupBookingsByMonth($transactions, $startDate, $endDate)
    {
        $monthlyData = [];

        // Initialize months antara startDate dan endDate agar chart konsisten,
        // terutama ketika ada bulan tanpa transaksi.
        $periodStart = new DateTime($startDate);
        $periodEnd = new DateTime($endDate);
        $periodEnd->modify('+1 day'); // agar inklusif

        $interval = new DateInterval('P1M');
        $daterange = new DatePeriod($periodStart, $interval, $periodEnd);

        foreach ($daterange as $dt) {
            $monthKey = $dt->format('M Y');
            $monthlyData[$monthKey] = 0;
        }

        foreach ($transactions as $transaction) {
            $month = date('M Y', strtotime($transaction['paid_at']));
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = 0;
            }
            $monthlyData[$month] += (float)$transaction['total_with_tax'];
        }

        return [
            'labels' => array_keys($monthlyData),
            'values' => array_values($monthlyData)
        ];
    }

    /* ---------------------------
       Utility / Reporting helpers
       --------------------------- */

    // Contoh: ambil semua booking dengan filter sederhana (paging optional)
    public function listBookings($limit = 50, $offset = 0, $filters = [])
    {
        global $conn;
        $sql = "SELECT b.*, u.full_name as customer_name, k.name as kos_name
                FROM bookings b
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN kos k ON b.kos_id = k.id
                WHERE 1=1";
        $params = [];
        $types = '';

        if (!empty($filters['payment_status'])) {
            $sql .= " AND b.payment_status = ?";
            $types .= 's';
            $params[] = $filters['payment_status'];
        }

        $sql .= " ORDER BY b.created_at DESC LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }
}
