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
            <div class="header-left">
                <h1><i class="fas fa-file-invoice-dollar"></i> Property Tax Management</h1>
                <p class="header-subtitle">Kelola data pembayaran pajak properti oleh owner</p>
            </div>

            <button class="btn-financial-report" onclick="openFinancialReportModal()">
                <i class="fas fa-chart-line"></i> Catatan Keuangan
            </button>
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
                                    <span class="tax-label">Pajak Yang Sudah di Bayar</span>
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
        <!-- Financial Report Modal -->
        <div id="financialReportModal" class="modal">
            <div class="modal-content modal-xlarge">
                <div class="modal-header">
                    <h2><i class="fas fa-chart-line"></i> Catatan Keuangan</h2>
                    <span class="close" onclick="closeFinancialReportModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <!-- Filter Tanggal -->
                    <div class="date-filter-container">
                        <div class="form-group">
                            <label>Dari Tanggal</label>
                            <input type="date" id="startDate" class="date-input">
                        </div>

                        <div class="form-group">
                            <label>Sampai Tanggal</label>
                            <input type="date" id="endDate" class="date-input">
                        </div>

                        <div class="button-group">
                            <button class="btn-filter" onclick="filterFinancialReport()">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <button class="btn-reset" onclick="resetFinancialFilter()">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </div>
                    <!-- Summary Cards -->
                    <div class="financial-summary">
                        <div class="summary-card">
                            <div class="summary-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="summary-info">
                                <h3 id="totalIncome">Rp 0</h3>
                                <p>Total Pemasukan</p>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-icon">
                                <i class="fas fa-percent"></i>
                            </div>
                            <div class="summary-info">
                                <h3 id="totalTax">Rp 0</h3>
                                <p>Total Pajak (10%)</p>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-icon">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                            <div class="summary-info">
                                <h3 id="totalToOwner">Rp 0</h3>
                                <p>Total ke Owner</p>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-icon">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <div class="summary-info">
                                <h3 id="totalTransactions">0</h3>
                                <p>Total Transaksi</p>
                            </div>
                        </div>
                    </div>

                    <!-- Chart -->
                    <div class="chart-container">
                        <canvas id="incomeChart"></canvas>
                    </div>

                    <!-- Transactions Table -->
                    <div class="financial-table-container">
                        <h3><i class="fas fa-list"></i> Detail Transaksi</h3>
                        <table class="financial-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Order ID</th>
                                    <th>Properti</th>
                                    <th>Owner</th>
                                    <th>Harga</th>
                                    <th>Pajak 10%</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="financialTableBody">
                                <!-- Data will be loaded here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Export Button -->
                    <div class="export-container">
                        <button class="btn-export" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Export ke Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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