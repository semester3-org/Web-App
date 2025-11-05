<?php
// frontend/admin/pages/add_property_tax.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../backend/admin/classes/TransactionManager.php';

// Check if user is admin or superadmin
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header('Location: ../../../index.php');
    exit();
}

$transactionManager = new TransactionManager();
$properties = $transactionManager->getAllProperties();
$stats = $transactionManager->getPropertyTaxStats();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Tax Management - KostHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/property_tax.css">
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-file-invoice-dollar"></i> Property Tax Management</h1>
        </div>

        <!-- Info Card -->
        <div class="info-card">
            <div class="info-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="info-content">
                <h3>Informasi Pajak Properti</h3>
                <p>Semua properti yang sudah melakukan pembayaran akan masuk ke halaman ini. Pajak ini berlaku otomatis untuk semua status (Pending, Approved, Rejected).</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-home"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Total Properti Sudah Bayar</p>
                    <h3 class="stat-value"><?= number_format($stats['total_properties_paid']) ?></h3>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Total Profit Pajak</p>
                    <h3 class="stat-value">Rp <?= number_format($stats['total_tax_profit']) ?></h3>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari nama properti, alamat, atau owner...">
            </div>
            <select id="filterCity" class="filter-select">
                <option value="">Semua Kota</option>
                <?php
                $cities = array_unique(array_column($properties, 'city'));
                foreach ($cities as $city): ?>
                    <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filterStatus" class="filter-select">
                <option value="">Semua Status</option>
                <option value="approved">Approved</option>
                <option value="pending">Pending</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>

        <!-- Properties Grid -->
        <div class="properties-grid">
            <?php if (count($properties) > 0): ?>
                <?php foreach ($properties as $property): ?>
                    <div class="property-card" data-city="<?= htmlspecialchars($property['city']) ?>" data-status="<?= htmlspecialchars($property['status']) ?>">
                        <div class="property-header">
                            <h3><?= htmlspecialchars($property['name']) ?></h3>
                            <?php
                            $statusClass = '';
                            $statusText = '';
                            $statusIcon = '';
                            switch ($property['status']) {
                                case 'approved':
                                    $statusClass = 'property-status-approved';
                                    $statusText = 'Approved';
                                    $statusIcon = 'fa-check-circle';
                                    break;
                                case 'pending':
                                    $statusClass = 'property-status-pending';
                                    $statusText = 'Pending';
                                    $statusIcon = 'fa-clock';
                                    break;
                                case 'rejected':
                                    $statusClass = 'property-status-rejected';
                                    $statusText = 'Rejected';
                                    $statusIcon = 'fa-times-circle';
                                    break;
                                default:
                                    $statusClass = 'property-status-pending';
                                    $statusText = 'Unknown';
                                    $statusIcon = 'fa-question-circle';
                            }
                            ?>
                            <span class="property-status <?= $statusClass ?>">
                                <i class="fas <?= $statusIcon ?>"></i> <?= $statusText ?>
                            </span>
                        </div>

                        <div class="property-body">
                            <div class="property-info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= htmlspecialchars($property['address']) ?>, <?= htmlspecialchars($property['city']) ?></span>
                            </div>

                            <div class="property-info-item">
                                <i class="fas fa-user"></i>
                                <span><strong>Owner:</strong> <?= htmlspecialchars($property['owner_name']) ?></span>
                            </div>

                            <div class="property-info-item">
                                <i class="fas fa-envelope"></i>
                                <span><?= htmlspecialchars($property['owner_email']) ?></span>
                            </div>

                            <?php if ($property['paid_at']): ?>
                                <div class="property-info-item">
                                    <i class="fas fa-calendar-check"></i>
                                    <span><strong>Dibayar:</strong> <?= date('d M Y H:i', strtotime($property['paid_at'])) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="property-price">
                                <div class="price-label">Harga Bulanan</div>
                                <div class="price-value">Rp <?= number_format($property['price_monthly']) ?></div>
                            </div>

                            <div class="tax-info">
                                <div class="tax-row">
                                    <span class="tax-label">Harga Bulanan</span>
                                    <span class="tax-value">Rp <?= number_format($property['price_monthly']) ?></span>
                                </div>
                                <div class="tax-row">
                                    <span class="tax-label">Pajak (10%)</span>
                                    <span class="tax-value">Rp <?= number_format($property['price_monthly'] * 0.1) ?></span>
                                </div>
                                <div class="tax-row total-row">
                                    <span class="tax-label">Pajak Yang Harus di Bayarkan</span>
                                    <span class="tax-value">Rp <?= number_format($property['total_amount']) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="property-footer-view">
                            <button class="btn-view-detail" onclick="viewPropertyDetail(<?= $property['id'] ?>)">
                                <i class="fas fa-eye"></i> Lihat Detail
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-home"></i>
                    <p>Tidak ada properti yang sudah melakukan pembayaran</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Property Detail Modal -->
    <div id="propertyDetailModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2><i class="fas fa-home"></i> Detail Properti & Pembayaran</h2>
                <span class="close" onclick="closeDetailModal()">&times;</span>
            </div>
            <div class="modal-body" id="propertyDetailContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>

    <script src="../js/property_tax.js"></script>
    <!-- buat logout -->
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