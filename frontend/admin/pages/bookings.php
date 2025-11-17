<?php
session_start();
require_once '../../../backend/config/db.php';

// Check if user is logged in and is admin/superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header('Location: ../../login.php');
    exit();
}

// Handle actions (approve, reject, mark as disbursed)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $booking_id = (int)$_POST['booking_id'];
        
        switch ($_POST['action']) {
            case 'disburse':
                // Mark as disbursed to owner
                $stmt = $conn->prepare("
                    UPDATE bookings 
                    SET disbursement_status = 'disbursed', 
                        disbursed_at = NOW(),
                        disbursed_by = ?
                    WHERE id = ? AND payment_status = 'paid'
                ");
                $stmt->bind_param("ii", $_SESSION['user_id'], $booking_id);
                $stmt->execute();
                $_SESSION['success_message'] = "Dana berhasil ditandai sebagai telah disalurkan ke owner!";
                $stmt->close();
                break;
                
            case 'confirm':
                $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                $_SESSION['success_message'] = "Booking berhasil dikonfirmasi!";
                $stmt->close();
                break;
                
            case 'reject':
                $stmt = $conn->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                $_SESSION['success_message'] = "Booking berhasil ditolak!";
                $stmt->close();
                break;
        }
        
        $conn->commit();
        header('Location: bookings.php');
        exit();
    }
}

// Get filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_payment = isset($_GET['payment']) ? $_GET['payment'] : 'all';
$filter_disbursement = isset($_GET['disbursement']) ? $_GET['disbursement'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
$query = "
    SELECT 
        b.*,
        k.name as kos_name,
        k.city,
        k.province,
        u.full_name as customer_name,
        u.email as customer_email,
        u.phone as customer_phone,
        o.full_name as owner_name,
        o.email as owner_email,
        o.phone as owner_phone,
        pl.payment_type,
        pl.gross_amount
    FROM bookings b
    JOIN kos k ON b.kos_id = k.id
    JOIN users u ON b.user_id = u.id
    JOIN users o ON k.owner_id = o.id
    LEFT JOIN payment_logs pl ON b.id = pl.booking_id
    WHERE 1=1
";

$params = [];
$types = "";

if ($filter_status !== 'all') {
    $query .= " AND b.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_payment !== 'all') {
    $query .= " AND b.payment_status = ?";
    $params[] = $filter_payment;
    $types .= "s";
}

if ($filter_disbursement !== 'all') {
    $query .= " AND COALESCE(b.disbursement_status, 'pending') = ?";
    $params[] = $filter_disbursement;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (k.name LIKE ? OR u.full_name LIKE ? OR b.order_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$query .= " ORDER BY b.created_at DESC";

// Execute query with prepared statement
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conn->query($query);
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
}

// Calculate statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_bookings,
        SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as total_revenue,
        SUM(CASE WHEN payment_status = 'paid' AND COALESCE(disbursement_status, 'pending') = 'pending' THEN total_price ELSE 0 END) as pending_disbursement
    FROM bookings
";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// System tax rate (10%)
$system_tax_rate = 0.10;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings Management - KostHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/bookings.css">
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-wrapper">
            <!-- Header -->
            <div class="page-header">
                <div class="header-content">
                    <h1><i class="fas fa-calendar-check"></i> Bookings Management</h1>
                    <p>Kelola pembayaran dan penyaluran dana ke owner</p>
                </div>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= $_SESSION['success_message'] ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--primary-green-light);">
                        <i class="fas fa-calendar-check" style="color: var(--primary-green);"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($stats['total_bookings']) ?></h3>
                        <p>Total Bookings</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #dbeafe;">
                        <i class="fas fa-check-double" style="color: #3b82f6;"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($stats['paid_bookings']) ?></h3>
                        <p>Paid Bookings</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #fef3c7;">
                        <i class="fas fa-money-bill-wave" style="color: #f59e0b;"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Rp <?= number_format($stats['total_revenue'], 0, ',', '.') ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #fee2e2;">
                        <i class="fas fa-hourglass-half" style="color: #ef4444;"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Rp <?= number_format($stats['pending_disbursement'], 0, ',', '.') ?></h3>
                        <p>Pending Disbursement</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-section">
                <form method="GET" action="" class="filter-form">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="Cari kos, customer, order ID..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div class="filter-group">
                        <select name="status">
                            <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                            <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $filter_status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="rejected" <?= $filter_status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <select name="payment">
                            <option value="all" <?= $filter_payment === 'all' ? 'selected' : '' ?>>Semua Payment</option>
                            <option value="unpaid" <?= $filter_payment === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                            <option value="pending" <?= $filter_payment === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="paid" <?= $filter_payment === 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="failed" <?= $filter_payment === 'failed' ? 'selected' : '' ?>>Failed</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <select name="disbursement">
                            <option value="all" <?= $filter_disbursement === 'all' ? 'selected' : '' ?>>Semua Disbursement</option>
                            <option value="pending" <?= $filter_disbursement === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="disbursed" <?= $filter_disbursement === 'disbursed' ? 'selected' : '' ?>>Disbursed</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="bookings.php" class="btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </form>
            </div>

            <!-- Bookings Table -->
            <div class="table-container">
                <table class="bookings-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kos</th>
                            <th>Customer</th>
                            <th>Owner</th>
                            <th>Check In/Out</th>
                            <th>Type</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Disbursement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="11" class="text-center">Tidak ada data booking</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>#<?= $booking['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($booking['kos_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($booking['city']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($booking['customer_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($booking['customer_email']) ?></small><br>
                                        <small class="text-muted"><?= htmlspecialchars($booking['customer_phone'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($booking['owner_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($booking['owner_email']) ?></small><br>
                                        <small class="text-muted"><?= htmlspecialchars($booking['owner_phone'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <small>
                                            <?= date('d M Y', strtotime($booking['check_in_date'])) ?><br>
                                            <?= $booking['check_out_date'] ? date('d M Y', strtotime($booking['check_out_date'])) : '-' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $booking['booking_type'] === 'monthly' ? 'info' : 'warning' ?>">
                                            <?= ucfirst($booking['booking_type']) ?>
                                        </span>
                                        <?php if ($booking['booking_type'] === 'monthly'): ?>
                                            <br><small><?= $booking['duration_months'] ?> bulan</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>Rp <?= number_format($booking['total_price'], 0, ',', '.') ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= 
                                            $booking['status'] === 'confirmed' ? 'success' : 
                                            ($booking['status'] === 'pending' ? 'warning' : 'danger') 
                                        ?>">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= 
                                            $booking['payment_status'] === 'paid' ? 'success' : 
                                            ($booking['payment_status'] === 'pending' ? 'warning' : 'danger') 
                                        ?>">
                                            <?= ucfirst($booking['payment_status']) ?>
                                        </span>
                                        <?php if ($booking['paid_at']): ?>
                                            <br><small><?= date('d M Y H:i', strtotime($booking['paid_at'])) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $disbursement_status = $booking['disbursement_status'] ?? 'pending';
                                        ?>
                                        <span class="badge badge-<?= $disbursement_status === 'disbursed' ? 'success' : 'warning' ?>">
                                            <?= $disbursement_status === 'disbursed' ? 'Disbursed' : 'Pending' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-info" onclick="viewDetails(<?= $booking['id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($booking['payment_status'] === 'paid' && ($booking['disbursement_status'] ?? 'pending') === 'pending'): ?>
                                                <button class="btn-action btn-success" onclick="disbursePayment(<?= $booking['id'] ?>)">
                                                    <i class="fas fa-money-bill-transfer"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Booking Details -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div id="modalBody"></div>
        </div>
    </div>

    <script>
        function viewDetails(bookingId) {
            // Fetch booking details via AJAX
            fetch(`../../../backend/admin/classes/get_booking_details.php?id=${bookingId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modalBody').innerHTML = data;
                    document.getElementById('detailModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal memuat detail booking');
                });
        }

        function closeModal() {
            document.getElementById('detailModal').style.display = 'none';
        }

        function disbursePayment(bookingId) {
            if (confirm('Apakah Anda yakin dana sudah disalurkan ke owner melalui email/nomor telepon?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="disburse">
                    <input type="hidden" name="booking_id" value="${bookingId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('detailModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>

</html>