<?php
session_start();
require_once '../../../backend/config/db.php';
// Debug - cek session user_id
error_log("SESSION user_id: " . var_export($_SESSION['user_id'], true));
error_log("SESSION user_id type: " . gettype($_SESSION['user_id']));
error_log("SESSION user_id int: " . (int)$_SESSION['user_id']);

// Check if user is logged in and is admin/superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header('Location: ../../login.php');
    exit();
}

// Handle actions (approve, reject, mark as disbursed)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $booking_id = (int)$_POST['booking_id'];

        // Validate booking_id
        if ($booking_id <= 0) {
            $_SESSION['error_message'] = "Invalid booking ID";
            header('Location: bookings.php?page=' . ($_POST['current_page'] ?? 1));
            exit();
        }

        switch ($_POST['action']) {
            case 'disburse':
                try {
                    // Start transaction
                    $conn->begin_transaction();

                    // Get user_id and ensure it's an integer
                    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

                    // Validate user_id
                    if ($user_id === null || $user_id <= 0) {
                        throw new Exception("Invalid user session");
                    }

                    // Validate user_id range for INT type
                    if ($user_id > 2147483647) {
                        throw new Exception("User ID exceeds INT maximum value");
                    }

                    // Check if booking exists and is paid
                    $check_stmt = $conn->prepare("
                        SELECT id, payment_status, disbursement_status 
                        FROM bookings 
                        WHERE id = ? AND payment_status = 'paid'
                    ");
                    $check_stmt->bind_param("i", $booking_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();

                    if ($check_result->num_rows === 0) {
                        throw new Exception("Booking tidak ditemukan atau belum dibayar");
                    }

                    $booking_data = $check_result->fetch_assoc();
                    if ($booking_data['disbursement_status'] === 'disbursed') {
                        throw new Exception("Dana sudah pernah disalurkan sebelumnya");
                    }

                    $check_stmt->close();

                    // Mark as disbursed to owner
                    $stmt = $conn->prepare("
                        UPDATE bookings 
                        SET disbursement_status = 'disbursed', 
                            disbursed_at = NOW(),
                            disbursed_by = ?
                        WHERE id = ? AND payment_status = 'paid'
                    ");

                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }

                    // Bind parameters: i = integer
                    $stmt->bind_param("ii", $user_id, $booking_id);

                    if (!$stmt->execute()) {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }

                    if ($stmt->affected_rows === 0) {
                        throw new Exception("Tidak ada data yang diupdate");
                    }

                    $stmt->close();
                    $conn->commit();

                    $_SESSION['success_message'] = "Dana berhasil ditandai sebagai telah disalurkan ke owner!";
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error_message'] = "Error: " . $e->getMessage();
                    error_log("Disburse Error - User ID: " . ($_SESSION['user_id'] ?? 'null') . " - " . $e->getMessage());
                }
                break;

            case 'confirm':
                try {
                    $conn->begin_transaction();

                    $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
                    $stmt->bind_param("i", $booking_id);
                    $stmt->execute();

                    if ($stmt->affected_rows > 0) {
                        $_SESSION['success_message'] = "Booking berhasil dikonfirmasi!";
                    } else {
                        $_SESSION['error_message'] = "Booking tidak ditemukan";
                    }

                    $stmt->close();
                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error_message'] = "Error: " . $e->getMessage();
                }
                break;

            case 'reject':
                try {
                    $conn->begin_transaction();

                    $stmt = $conn->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
                    $stmt->bind_param("i", $booking_id);
                    $stmt->execute();

                    if ($stmt->affected_rows > 0) {
                        $_SESSION['success_message'] = "Booking berhasil ditolak!";
                    } else {
                        $_SESSION['error_message'] = "Booking tidak ditemukan";
                    }

                    $stmt->close();
                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error_message'] = "Error: " . $e->getMessage();
                }
                break;
        }

        header('Location: bookings.php?page=' . ($_POST['current_page'] ?? 1));
        exit();
    }
}

// Pagination
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $records_per_page;

// Get filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_payment = isset($_GET['payment']) ? $_GET['payment'] : 'all';
$filter_disbursement = isset($_GET['disbursement']) ? $_GET['disbursement'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause for count and data query
$where_conditions = "WHERE 1=1";
$params = [];
$types = "";

if ($filter_status !== 'all') {
    $where_conditions .= " AND b.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_payment !== 'all') {
    $where_conditions .= " AND b.payment_status = ?";
    $params[] = $filter_payment;
    $types .= "s";
}

if ($filter_disbursement !== 'all') {
    $where_conditions .= " AND COALESCE(b.disbursement_status, 'pending') = ?";
    $params[] = $filter_disbursement;
    $types .= "s";
}

if (!empty($search)) {
    $where_conditions .= " AND (k.name LIKE ? OR u.full_name LIKE ? OR b.order_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Count total records
$count_query = "
    SELECT COUNT(DISTINCT b.id) as total
    FROM bookings b
    JOIN kos k ON b.kos_id = k.id
    JOIN users u ON b.user_id = u.id
    JOIN users o ON k.owner_id = o.id
    $where_conditions
";

try {
    if (!empty($params)) {
        $count_stmt = $conn->prepare($count_query);
        if (!$count_stmt) {
            throw new Exception("Count prepare failed: " . $conn->error);
        }
        $count_stmt->bind_param($types, ...$params);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_records = $count_result->fetch_assoc()['total'];
        $count_stmt->close();
    } else {
        $count_result = $conn->query($count_query);
        if (!$count_result) {
            throw new Exception("Count query failed: " . $conn->error);
        }
        $total_records = $count_result->fetch_assoc()['total'];
    }
} catch (Exception $e) {
    error_log("Count query error: " . $e->getMessage());
    $total_records = 0;
}

$total_pages = $total_records > 0 ? ceil($total_records / $records_per_page) : 1;

// Build main query
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
        admin.full_name as disbursed_by_name,
        (SELECT pl.payment_type 
         FROM payment_logs pl 
         WHERE pl.booking_id = b.id 
         ORDER BY pl.created_at DESC 
         LIMIT 1) as payment_type
    FROM bookings b
    JOIN kos k ON b.kos_id = k.id
    JOIN users u ON b.user_id = u.id
    JOIN users o ON k.owner_id = o.id
    LEFT JOIN users admin ON b.disbursed_by = admin.id
    $where_conditions
    ORDER BY b.created_at DESC
    LIMIT ? OFFSET ?
";

// Add pagination params
$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

// Execute query
try {
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Main query prepare failed: " . $conn->error);
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $bookings = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $result = $conn->query($query);
        if (!$result) {
            throw new Exception("Main query failed: " . $conn->error);
        }
        $bookings = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Main query error: " . $e->getMessage());
    $bookings = [];
}

// Calculate statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_bookings,
        SUM(CASE WHEN payment_status = 'paid' THEN total_price * 0.10 ELSE 0 END) as total_revenue,
        SUM(CASE WHEN payment_status = 'paid' AND COALESCE(disbursement_status, 'pending') = 'pending' THEN total_price * 0.90 ELSE 0 END) as pending_disbursement
    FROM bookings
";

try {
    $stats_result = $conn->query($stats_query);
    if (!$stats_result) {
        throw new Exception("Stats query failed: " . $conn->error);
    }
    $stats = $stats_result->fetch_assoc();
} catch (Exception $e) {
    error_log("Stats query error: " . $e->getMessage());
    $stats = [
        'total_bookings' => 0,
        'paid_bookings' => 0,
        'total_revenue' => 0,
        'pending_disbursement' => 0,
        'total_disbursed' => 0
    ];
}

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

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $_SESSION['error_message'] ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
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
                        <p>Total Profit</p>
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
                            <th>KOS</th>
                            <th>CUSTOMER</th>
                            <th>OWNER</th>
                            <th>CHECK IN/OUT</th>
                            <th>TYPE</th>
                            <th>TOTAL PRICE</th>
                            <th>STATUS</th>
                            <th>PAYMENT</th>
                            <th>DISBURSEMENT</th>
                            <th>ACTIONS</th>
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
                                                                    $booking['status'] === 'confirmed' ? 'success' : ($booking['status'] === 'pending' ? 'warning' : 'danger')
                                                                    ?>">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?=
                                                                    $booking['payment_status'] === 'paid' ? 'success' : ($booking['payment_status'] === 'pending' ? 'warning' : 'danger')
                                                                    ?>">
                                            <?= ucfirst($booking['payment_status']) ?>
                                        </span>
                                        <?php if ($booking['paid_at']): ?>
                                            <br><small><?= date('d M Y', strtotime($booking['paid_at'])) ?></small>
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
                                                <button class="btn-action btn-success" onclick="showDisburseModal(<?= $booking['id'] ?>, '<?= htmlspecialchars($booking['kos_name']) ?>', <?= (int)$booking['total_price'] ?>, <?= (int)$current_page ?>)">
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

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="?page=<?= $current_page - 1 ?><?= $filter_status !== 'all' ? '&status=' . $filter_status : '' ?><?= $filter_payment !== 'all' ? '&payment=' . $filter_payment : '' ?><?= $filter_disbursement !== 'all' ? '&disbursement=' . $filter_disbursement : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="page-btn">
                                <i class="fas fa-chevron-left"></i> Prev
                            </a>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);

                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?page=<?= $i ?><?= $filter_status !== 'all' ? '&status=' . $filter_status : '' ?><?= $filter_payment !== 'all' ? '&payment=' . $filter_payment : '' ?><?= $filter_disbursement !== 'all' ? '&disbursement=' . $filter_disbursement : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                class="page-btn <?= $i === $current_page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="?page=<?= $current_page + 1 ?><?= $filter_status !== 'all' ? '&status=' . $filter_status : '' ?><?= $filter_payment !== 'all' ? '&payment=' . $filter_payment : '' ?><?= $filter_disbursement !== 'all' ? '&disbursement=' . $filter_disbursement : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="page-btn">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal for Booking Details -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('detailModal')">&times;</span>
            <div id="modalBody"></div>
        </div>
    </div>

    <!-- Disburse Confirmation Modal -->
    <div id="disburseModal" class="modal">
        <div class="modal-content modal-confirm">
            <span class="close" onclick="closeModal('disburseModal')">&times;</span>
            <div class="modal-header">
                <i class="fas fa-money-bill-transfer modal-icon"></i>
                <h2>Konfirmasi Penyaluran Dana</h2>
            </div>
            <div class="modal-body">
                <p id="disburseText"></p>
                <div class="disburse-info">
                    <div class="info-row">
                        <span class="info-label">Nama Kos:</span>
                        <span class="info-value" id="disburseName"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Pembayaran:</span>
                        <span class="info-value" id="disburseTotal"></span>
                    </div>
                    <div class="info-row highlight">
                        <span class="info-label">Dana untuk Owner:</span>
                        <span class="info-value" id="disburseAmount"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('disburseModal')">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button class="btn-confirm" onclick="confirmDisburse()">
                    <i class="fas fa-check"></i> Ya, Sudah Disalurkan
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentDisburseId = null;
        let currentPage = 1;

        function viewDetails(bookingId) {
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

        function showDisburseModal(bookingId, kosName, totalPrice, page) {
            currentDisburseId = bookingId;
            currentPage = page;

            const systemFee = totalPrice * 0.10;
            const ownerAmount = totalPrice - systemFee;

            document.getElementById('disburseName').textContent = kosName;
            document.getElementById('disburseTotal').textContent = 'Rp ' + totalPrice.toLocaleString('id-ID');
            document.getElementById('disburseAmount').textContent = 'Rp ' + ownerAmount.toLocaleString('id-ID');
            document.getElementById('disburseText').textContent = 'Apakah Anda yakin dana sudah disalurkan ke owner melalui email atau nomor telepon?';

            document.getElementById('disburseModal').style.display = 'block';
        }

        function confirmDisburse() {
            if (currentDisburseId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="disburse">
                    <input type="hidden" name="booking_id" value="${currentDisburseId}">
                    <input type="hidden" name="current_page" value="${currentPage}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
    <!-- Script dropdown user -->
    <script>
        function toggleDropdown() {
            const menu = document.getElementById("dropdownMenu");
            menu.style.display = menu.style.display === "block" ? "none" : "block";
        }
        window.addEventListener("click", function(e) {
            if (!e.target.closest(".user-menu")) {
                document.getElementById("dropdownMenu").style.display = "none";
            }
        });
    </script>
</body>

</html>