<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Use absolute path - adjust according to your structure
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/Web-App';

// Try to include db.php - check multiple possible locations
$db_paths = [
    $base_path . '/backend/config/db.php',
    $base_path . '/config/db.php',
    __DIR__ . '/../../../backend/config/db.php',
    __DIR__ . '/../../../config/db.php'
];

$db_loaded = false;
foreach ($db_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $db_loaded = true;
        break;
    }
}

if (!$db_loaded) {
    die("Error: Database config file not found. Checked paths:<br>" . implode('<br>', $db_paths));
}

// Try to include approved_process.php
$process_paths = [
    $base_path . '/backend/admin/classes/approved_process.php',
    __DIR__ . '/../../../backend/admin/classes/approved_process.php'
];

$process_loaded = false;
foreach ($process_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $process_loaded = true;
        break;
    }
}

if (!$process_loaded) {
    die("Error: approved_process.php file not found. Checked paths:<br>" . implode('<br>', $process_paths));
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    die("Error: Anda harus login sebagai admin untuk mengakses halaman ini.");
}

// Check database connection
if (!isset($conn) || $conn->connect_error) {
    die("Error: Database connection failed - " . ($conn->connect_error ?? 'Connection object not found'));
}

// Don't create ApprovalProcess here if it causes issues
// We'll create it only when needed
$approvalProcess = null;

try {
    if (class_exists('ApprovalProcess')) {
        $approvalProcess = new ApprovalProcess($conn);

        // Ambil status tab aktif
        $filter_status = $_GET['status'] ?? 'pending';

        // Ambil halaman saat ini
        $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 5; // jumlah data per halaman

        // 🟩 Ambil filter tambahan dari form
        $filters = [
            'city' => $_GET['city'] ?? '',
            'kos_type' => $_GET['kos_type'] ?? '',
            'price_min' => $_GET['price_min'] ?? '',
            'price_max' => $_GET['price_max'] ?? ''
        ];

        // Ambil total & data properti dengan filter
        $total_properties = $approvalProcess->getTotalPropertiesByStatus($filter_status, $filters);
        $total_pages = ceil($total_properties / $limit);

        $properties = $approvalProcess->getPropertiesByStatus($filter_status, $current_page, $limit, $filters);

        // Ambil daftar kota
        $cities = $approvalProcess->getDistinctCities();

        // Statistik
        $stats = $approvalProcess->getApprovalStats();
    } else {
        $properties = [];
        $stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0];
        $total_properties = 0;
        $total_pages = 0;
        $current_page = 1;
        $cities = [];
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Property - Admin Dashboard</title>

    <!-- Bootstrap Icons & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Style Admin -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/approved.css">
    <link rel="stylesheet" href="../css/pagination.css">

</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <!-- Header -->
        <div class="approval-header">
            <div class="header-content">
                <h1><i class="fas fa-check-circle"></i> Approval Property</h1>
                <p>Kelola persetujuan properti kos yang diajukan oleh pemilik</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card stat-pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['pending']; ?></h3>
                    <p>Menunggu Approval</p>
                </div>
            </div>

            <div class="stat-card stat-approved">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['approved']; ?></h3>
                    <p>Telah Disetujui</p>
                </div>
            </div>

            <div class="stat-card stat-rejected">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['rejected']; ?></h3>
                    <p>Ditolak</p>
                </div>
            </div>

            <div class="stat-card stat-total">
                <div class="stat-icon">
                    <i class="fas fa-home"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total']; ?></h3>
                    <p>Total Property</p>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?status=pending" class="tab-btn <?php echo $filter_status === 'pending' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Pending (<?php echo $stats['pending']; ?>)
            </a>
            <a href="?status=approved" class="tab-btn <?php echo $filter_status === 'approved' ? 'active' : ''; ?>">
                <i class="fas fa-check-circle"></i> Approved (<?php echo $stats['approved']; ?>)
            </a>
            <a href="?status=rejected" class="tab-btn <?php echo $filter_status === 'rejected' ? 'active' : ''; ?>">
                <i class="fas fa-times-circle"></i> Rejected (<?php echo $stats['rejected']; ?>)
            </a>
            <a href="?status=all" class="tab-btn <?php echo $filter_status === 'all' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> Semua Property (<?php echo $stats['total']; ?>)
            </a>
        </div>

        <?php if ($filter_status === 'all'): ?>
    <!-- Advanced Filter -->
    <div class="advanced-filter">
        <form method="GET" action="">
            <input type="hidden" name="status" value="all">

            <div class="filter-row">
                <!-- City Filter -->
                <div class="filter-group">
                    <label for="city">Kota:</label>
                    <select name="city" id="city">
                        <option value="">Semua Kota</option>
                        <?php
                        $cities = $approvalProcess->getDistinctCities();
                        foreach ($cities as $c) {
                            $selected = (isset($_GET['city']) && $_GET['city'] === $c) ? 'selected' : '';
                            echo "<option value='$c' $selected>$c</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Kos Type -->
                <div class="filter-group">
                    <label for="kos_type">Tipe Kos:</label>
                    <select name="kos_type" id="kos_type">
                        <option value="">Semua Tipe</option>
                        <option value="putra" <?= (isset($_GET['kos_type']) && $_GET['kos_type'] === 'putra') ? 'selected' : '' ?>>Putra</option>
                        <option value="putri" <?= (isset($_GET['kos_type']) && $_GET['kos_type'] === 'putri') ? 'selected' : '' ?>>Putri</option>
                        <option value="campur" <?= (isset($_GET['kos_type']) && $_GET['kos_type'] === 'campur') ? 'selected' : '' ?>>Campur</option>
                    </select>
                </div>

                <!-- Price Range -->
                <div class="filter-group">
                    <label>Harga (Rp):</label>
                    <span>
                    <input type="number" name="price_min" placeholder="Min" value="<?= htmlspecialchars($_GET['price_min'] ?? '') ?>">
                    -
                    <input type="number" name="price_max" placeholder="Max" value="<?= htmlspecialchars($_GET['price_max'] ?? '') ?>">
                </span>
                </div>

                <!-- Submit -->
                <div class="filter-group">
                    <button type="submit" class="btn-apply-filter">
                        <i class="fas fa-filter"></i> Terapkan
                    </button>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>


        <!-- Properties List -->
        <div class="properties-container">
            <?php if (empty($properties)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Tidak ada property</h3>
                    <p>Belum ada property dengan status <?php echo $filter_status; ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($properties as $property): ?>
                    <div class="property-card" data-property-id="<?php echo $property['id']; ?>">
                        <div class="property-images">
                            <?php if (!empty($property['images'])): ?>
                                <div class="image-slider">
                                    <?php foreach ($property['images'] as $index => $image): ?>
                                        <img src="../../../uploads/kos/<?php echo htmlspecialchars(basename($image)); ?>"
                                            alt="<?php echo htmlspecialchars($property['name']); ?>"
                                            class="<?php echo $index === 0 ? 'active' : ''; ?>">
                                    <?php endforeach; ?>
                                    <?php if (count($property['images']) > 1): ?>
                                        <button class="slider-btn prev-btn"><i class="fas fa-chevron-left"></i></button>
                                        <button class="slider-btn next-btn"><i class="fas fa-chevron-right"></i></button>
                                        <div class="image-counter"><?php echo count($property['images']); ?> Foto</div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-image"></i>
                                    <p>Tidak ada foto</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="property-info">
                            <div class="property-header">
                                <h3><?php echo htmlspecialchars($property['name']); ?></h3>
                                <span class="status-badge status-<?php echo $property['status']; ?>">
                                    <?php echo ucfirst($property['status']); ?>
                                </span>
                            </div>

                            <div class="property-details">
                                <div class="detail-item">
                                    <i class="fas fa-user"></i>
                                    <span>Pemilik: <?php echo htmlspecialchars($property['owner_name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($property['city'] . ', ' . $property['province']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-venus-mars"></i>
                                    <span>Tipe: <?php echo ucfirst($property['kos_type']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-door-open"></i>
                                    <span><?php echo $property['available_rooms']; ?> / <?php echo $property['total_rooms']; ?> Kamar Tersedia</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>Rp <?php echo number_format($property['price_monthly'], 0, ',', '.'); ?> / bulan</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>Diajukan: <?php echo date('d M Y, H:i', strtotime($property['created_at'])); ?></span>
                                </div>
                            </div>

                            <?php if (!empty($property['facilities'])): ?>
                                <div class="facilities">
                                    <strong><i class="fas fa-check"></i> Fasilitas:</strong>
                                    <div class="facility-tags">
                                        <?php foreach ($property['facilities'] as $facility): ?>
                                            <span class="facility-tag">
                                                <i class="fas <?php echo htmlspecialchars($facility['icon'] ?? 'fa-check'); ?>"></i>
                                                <?php echo htmlspecialchars($facility['name']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($property['description']): ?>
                                <div class="description">
                                    <strong><i class="fas fa-align-left"></i> Deskripsi:</strong>
                                    <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($property['status'] === 'rejected' && !empty($property['rejection_reason'])): ?>
                                <div class="rejection-info">
                                    <strong><i class="fas fa-exclamation-triangle"></i> Alasan Penolakan:</strong>
                                    <p><?php echo nl2br(htmlspecialchars($property['rejection_reason'])); ?></p>
                                    <small>Ditolak oleh: <?php echo htmlspecialchars($property['rejected_by']); ?> pada <?php echo date('d M Y, H:i', strtotime($property['rejected_at'])); ?></small>
                                </div>
                            <?php endif; ?>

                            <?php if ($property['status'] === 'approved'): ?>
                                <div class="approval-info">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Disetujui oleh: <?php echo htmlspecialchars($property['verified_by_name']); ?> pada <?php echo date('d M Y, H:i', strtotime($property['verified_at'])); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="property-actions">
                                <button class="btn-detail" onclick="viewDetail(<?php echo $property['id']; ?>)">
                                    <i class="fas fa-eye"></i> Detail Lengkap
                                </button>

                                <?php if ($property['status'] === 'pending'): ?>
                                    <button class="btn-approve" onclick="approveProperty(<?php echo $property['id']; ?>)">
                                        <i class="fas fa-check"></i> Setujui
                                    </button>
                                    <button class="btn-reject" onclick="showRejectModal(<?php echo $property['id']; ?>, '<?php echo htmlspecialchars($property['name'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-times"></i> Tolak
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Reject -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-times-circle"></i> Tolak Property</h2>
                <button class="close-modal" onclick="closeRejectModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Anda akan menolak property: <strong id="propertyName"></strong></p>
                <form id="rejectForm">
                    <input type="hidden" id="rejectPropertyId" name="property_id">
                    <div class="form-group">
                        <label for="rejectReason">Alasan Penolakan *</label>
                        <textarea id="rejectReason" name="reason" rows="5" required placeholder="Jelaskan alasan penolakan property ini..."></textarea>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeRejectModal()">Batal</button>
                        <button type="submit" class="btn-confirm-reject">
                            <i class="fas fa-times"></i> Tolak Property
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Detail -->
    <div id="detailModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2><i class="fas fa-info-circle"></i> Detail Property</h2>
                <button class="close-modal" onclick="closeDetailModal()">&times;</button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Memuat data...
                </div>
            </div>
        </div>
    </div>

     <!-- Pagination -->
                <?php
// Pastikan nilai current_page valid (integer)
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($current_page < 1) $current_page = 1;

if ($total_pages > 1):
    // Simpan semua filter agar tetap ada di pagination
    $queryParams = [
        'status' => $filter_status,
        'city' => $filters['city'] ?? '',
        'kos_type' => $filters['kos_type'] ?? '',
        'price_min' => $filters['price_min'] ?? '',
        'price_max' => $filters['price_max'] ?? ''
    ];

    // Helper buat bikin URL pagination
    function makePageUrl($page, $params) {
        $params['page'] = $page;
        return '?' . http_build_query($params);
    }
    ?>
    <div class="pagination-container">
        <div class="pagination">
            <!-- Tombol Previous -->
            <?php if ($current_page > 1): ?>
                <a href="<?= makePageUrl($current_page - 1, $queryParams) ?>" class="page-link">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>

            <!-- Nomor Halaman -->
            <?php
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);

            if ($start_page > 1): ?>
                <a href="<?= makePageUrl(1, $queryParams) ?>" class="page-link">1</a>
                <?php if ($start_page > 2): ?>
                    <span class="page-dots">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <a href="<?= makePageUrl($i, $queryParams) ?>"
                   class="page-link <?= $i === $current_page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <span class="page-dots">...</span>
                <?php endif; ?>
                <a href="<?= makePageUrl($total_pages, $queryParams) ?>" class="page-link">
                    <?= $total_pages ?>
                </a>
            <?php endif; ?>

            <!-- Tombol Next -->
            <?php if ($current_page < $total_pages): ?>
                <a href="<?= makePageUrl($current_page + 1, $queryParams) ?>" class="page-link">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>


            </div>
    </div>

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
    <script src="../js/approved.js"></script>
</body>

</html>