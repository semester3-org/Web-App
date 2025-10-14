<?php

/**
 * File: frontend/admin/pages/approved.php
 * Admin Property Approval Page
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../../../backend/config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

// Get filter status from URL
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'pending';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Query untuk get properties
$where_clauses = [];
$count_params = [];
$count_types = '';

if ($filter_status !== 'all') {
    $where_clauses[] = "k.status = ?";
    $count_params[] = $filter_status;
    $count_types .= 's';
}

if (!empty($search)) {
    $where_clauses[] = "(k.name LIKE ? OR k.city LIKE ? OR k.address LIKE ?)";
    $search_param = "%$search%";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_types .= 'sss';
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM kos k $where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
$count_stmt->close();

// Get properties with owner info - Build params for main query
$main_params = [];
$main_types = '';

if ($filter_status !== 'all') {
    $main_params[] = $filter_status;
    $main_types .= 's';
}

if (!empty($search)) {
    $main_params[] = $search_param;
    $main_params[] = $search_param;
    $main_params[] = $search_param;
    $main_types .= 'sss';
}

$sql = "SELECT 
            k.*,
            u.full_name as owner_name,
            u.email as owner_email,
            u.phone as owner_phone,
            (SELECT image_url FROM kos_images WHERE kos_id = k.id LIMIT 1) as main_image,
            (SELECT COUNT(*) FROM kos_images WHERE kos_id = k.id) as image_count
        FROM kos k
        LEFT JOIN users u ON k.owner_id = u.id
        $where_sql
        ORDER BY k.created_at DESC
        LIMIT ? OFFSET ?";

$main_params[] = $limit;
$main_params[] = $offset;
$main_types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($main_params)) {
    $stmt->bind_param($main_types, ...$main_params);
}
$stmt->execute();
$result = $stmt->get_result();
// DEBUG: Tambahkan ini
echo "<!-- DEBUG: Total rows = " . $result->num_rows . " -->";
echo "<!-- DEBUG: SQL = " . $sql . " -->";
echo "<!-- DEBUG: Filter status = " . $filter_status . " -->";
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Approval - KostHub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Style Admin -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/approved.css">
</head>>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="page-header">
                <h2 class="mb-2"><i class="bi bi-check-circle"></i> Property Approval</h2>
                <p class="mb-0">Kelola persetujuan property kos yang diajukan oleh owner</p>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['success'];
                                                            unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $_SESSION['error'];
                                                                    unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="row mb-4">
                <?php
                $stats_sql = "SELECT 
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                    FROM kos";
                $stats_result = $conn->query($stats_sql);
                $stats = $stats_result->fetch_assoc();
                ?>
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted mb-1">Menunggu Approval</div>
                                <div class="number"><?php echo $stats['pending'] ?? 0; ?></div>
                            </div>
                            <div class="text-warning" style="font-size: 2.5rem;">
                                <i class="bi bi-clock-history"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card" style="border-left-color: var(--primary-green);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted mb-1">Disetujui</div>
                                <div class="number"><?php echo $stats['approved'] ?? 0; ?></div>
                            </div>
                            <div style="font-size: 2.5rem; color: var(--primary-green);">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card" style="border-left-color: #ef4444;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted mb-1">Ditolak</div>
                                <div class="number"><?php echo $stats['rejected'] ?? 0; ?></div>
                            </div>
                            <div class="text-danger" style="font-size: 2.5rem;">
                                <i class="bi bi-x-circle-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter_status === 'all' ? 'active' : ''; ?>" href="?status=all">
                            <i class="bi bi-grid"></i> Semua
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter_status === 'pending' ? 'active' : ''; ?>" href="?status=pending">
                            <i class="bi bi-clock"></i> Pending
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter_status === 'approved' ? 'active' : ''; ?>" href="?status=approved">
                            <i class="bi bi-check-circle"></i> Approved
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter_status === 'rejected' ? 'active' : ''; ?>" href="?status=rejected">
                            <i class="bi bi-x-circle"></i> Rejected
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" class="row g-2">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama property, kota, atau alamat..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-approve w-100">
                            <i class="bi bi-search"></i> Cari
                        </button>
                    </div>
                </form>
            </div>

            <!-- Properties List -->
            <?php if ($result->num_rows > 0): ?>
                <?php while ($property = $result->fetch_assoc()): ?>
                    <div class="property-card">
                        <div class="row g-0">
                            <div class="col-md-3">
                                <img src="../../../<?php echo $property['main_image'] ?? 'assets/no-image.png'; ?>"
                                    class="property-image"
                                    alt="<?php echo htmlspecialchars($property['name']); ?>">
                            </div>
                            <div class="col-md-9">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($property['name']); ?></h5>
                                            <p class="text-muted mb-2">
                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($property['city'] . ', ' . $property['province']); ?>
                                            </p>
                                        </div>
                                        <span class="status-badge status-<?php echo $property['status']; ?>">
                                            <?php echo strtoupper($property['status']); ?>
                                        </span>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <span class="info-label">Owner:</span>
                                                <span class="info-value"><?php echo htmlspecialchars($property['owner_name']); ?></span>
                                            </div>
                                            <div class="mb-2">
                                                <span class="info-label">Email:</span>
                                                <span class="info-value"><?php echo htmlspecialchars($property['owner_email']); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <span class="info-label">Jenis:</span>
                                                <span class="info-value"><?php echo strtoupper($property['kos_type']); ?></span>
                                            </div>
                                            <div class="mb-2">
                                                <span class="info-label">Total Kamar:</span>
                                                <span class="info-value"><?php echo $property['total_rooms']; ?> kamar</span>
                                            </div>
                                            <div class="mb-2">
                                                <span class="info-label">Harga:</span>
                                                <span class="info-value">Rp <?php echo number_format($property['price_monthly'], 0, ',', '.'); ?>/bulan</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button class="btn btn-view"
                                            onclick="viewDetails(<?php echo $property['id']; ?>)">
                                            <i class="bi bi-eye"></i> Lihat Detail
                                        </button>

                                        <?php if ($property['status'] === 'pending'): ?>
                                            <button class="btn btn-approve"
                                                onclick="approveProperty(<?php echo $property['id']; ?>, '<?php echo htmlspecialchars($property['name']); ?>')">
                                                <i class="bi bi-check-lg"></i> Setujui
                                            </button>
                                            <button class="btn btn-reject"
                                                onclick="showRejectModal(<?php echo $property['id']; ?>, '<?php echo htmlspecialchars($property['name']); ?>')">
                                                <i class="bi bi-x-lg"></i> Tolak
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?status=<?php echo $filter_status; ?>&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?status=<?php echo $filter_status; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?status=<?php echo $filter_status; ?>&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #9ca3af;"></i>
                    <h5 class="mt-3 text-muted">Tidak ada property ditemukan</h5>
                    <p class="text-muted">Property dengan status "<?php echo $filter_status; ?>" tidak tersedia</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-x-circle"></i> Tolak Property</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="rejectForm" method="POST" action="../../../backend/admin/classes/approved_process.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="property_id" id="reject_property_id">

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            Anda akan menolak property: <strong id="reject_property_name"></strong>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" class="form-control" rows="4" placeholder="Tuliskan alasan penolakan..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-reject">
                            <i class="bi bi-x-lg"></i> Tolak Property
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-info-circle"></i> Detail Property</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function approveProperty(id, name) {
            if (confirm(`Setujui property "${name}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../../backend/admin/classes/approved_process.php';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'approve';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'property_id';
                idInput.value = id;

                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function showRejectModal(id, name) {
            document.getElementById('reject_property_id').value = id;
            document.getElementById('reject_property_name').textContent = name;
            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        }

        function viewDetails(id) {
            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            modal.show();

            fetch(`../../../backend/admin/api/get_property_detail.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('detailContent').innerHTML = data.html;
                    } else {
                        document.getElementById('detailContent').innerHTML =
                            '<div class="alert alert-danger">Gagal memuat detail</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('detailContent').innerHTML =
                        '<div class="alert alert-danger">Error: ' + error + '</div>';
                });
        }
    </script>
</body>

</html>

<?php
if (!empty($stmt)) {
    try {
        $stmt->close();
    } catch (Error $e) {
        // Statement sudah ditutup, abaikan error
    }
}

if (!empty($conn)) {
    $conn->close();
}
?>