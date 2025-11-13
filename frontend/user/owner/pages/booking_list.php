<?php
session_start();
require_once "../../../../backend/config/db.php";

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
  header("Location: ../../auth/login.php");
  exit();
}

$owner_id = $_SESSION['user_id'];

// Get statistics
$sql_stats = "SELECT 
    COUNT(CASE WHEN b.status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN b.status = 'confirmed' THEN 1 END) as confirmed_count,
    COUNT(CASE WHEN b.status = 'rejected' THEN 1 END) as rejected_count,
    COUNT(CASE WHEN b.status = 'cancelled' THEN 1 END) as cancelled_count
    FROM bookings b
    JOIN kos k ON b.kos_id = k.id
    WHERE k.owner_id = ?";

$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("i", $owner_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking Management - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../css/booking_list.css" rel="stylesheet">
</head>

<body>

  <!-- Header -->
  <?php include "../includes/navbar.php"; ?>

  <!-- Main Content -->
  <div class="container my-5">
    <!-- Header Section -->
    <div class="mb-4">
      <h2 class="fw-bold mb-1">Booking Management</h2>
      <p class="text-muted mb-0">Kelola semua booking request dari customer</p>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
      <div class="col-lg-3 col-md-6">
        <div class="stat-card stat-pending">
          <div class="stat-icon">
            <i class="bi bi-clock-history"></i>
          </div>
          <div class="stat-content">
            <h3><?php echo $stats['pending_count']; ?></h3>
            <p>Pending</p>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="stat-card stat-confirmed">
          <div class="stat-icon">
            <i class="bi bi-check-circle"></i>
          </div>
          <div class="stat-content">
            <h3><?php echo $stats['confirmed_count']; ?></h3>
            <p>Confirmed</p>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="stat-card stat-rejected">
          <div class="stat-icon">
            <i class="bi bi-x-circle"></i>
          </div>
          <div class="stat-content">
            <h3><?php echo $stats['rejected_count']; ?></h3>
            <p>Rejected</p>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="stat-card stat-cancelled">
          <div class="stat-icon">
            <i class="bi bi-ban"></i>
          </div>
          <div class="stat-content">
            <h3><?php echo $stats['cancelled_count']; ?></h3>
            <p>Cancelled</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar mb-4">
      <div class="row align-items-center">
        <div class="col-md-3">
          <select id="filterStatus" class="form-select">
            <option value="">Semua Status</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="rejected">Rejected</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div class="col-md-3">
          <select id="filterProperty" class="form-select">
            <option value="">Semua Property</option>
            <!-- Will be populated via JS -->
          </select>
        </div>
        <div class="col-md-3">
          <select id="filterType" class="form-select">
            <option value="">Semua Tipe</option>
            <option value="monthly">Monthly</option>
            <option value="daily">Daily</option>
          </select>
        </div>
        <div class="col-md-3">
          <input type="text" id="searchInput" class="form-control" placeholder="Cari customer...">
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="text-center py-5">
      <div class="spinner-border text-success" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-3 text-muted">Memuat booking...</p>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="text-center py-5" style="display: none;">
      <i class="bi bi-inbox display-1 text-muted"></i>
      <h4 class="mt-3">Belum Ada Booking</h4>
      <p class="text-muted">Booking request dari customer akan muncul di sini</p>
    </div>

    <!-- Booking Table -->
    <div id="bookingTable" style="display: none;">
      <div class="table-responsive">
        <table class="table booking-table">
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>Customer</th>
              <th>Property</th>
              <th>Check In</th>
              <th>Duration</th>
              <th>Total</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="bookingTableBody">
            <!-- Will be populated via JS -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Detail Modal -->
  <div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-bold">Booking Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="modalContent">
          <!-- Will be populated via JS -->
        </div>
      </div>
    </div>
  </div>

  <!-- Confirm Modal -->
  <div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-bold">Konfirmasi Booking</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="text-center py-3">
            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
            <h5 class="mt-3">Setujui Booking Ini?</h5>
            <p class="text-muted">Customer akan menerima notifikasi konfirmasi, dan menunggu pembayaran dari customer</p>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-success" id="confirmBookingBtn">
            <i class="bi bi-check-lg"></i> Ya, Setujui
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Reject Modal -->
  <div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-bold">Tolak Booking</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="text-center mb-3">
            <i class="bi bi-x-circle text-danger" style="font-size: 4rem;"></i>
            <h5 class="mt-3">Tolak Booking Ini?</h5>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Alasan Penolakan (Opsional)</label>
            <textarea id="rejectReason" class="form-control" rows="3" placeholder="Berikan alasan mengapa booking ditolak..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-danger" id="rejectBookingBtn">
            <i class="bi bi-x-lg"></i> Ya, Tolak
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/booking_list.js"></script>
</body>

</html>