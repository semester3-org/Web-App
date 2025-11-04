<?php
session_start();
require_once "../../../../backend/config/db.php";

// Check authentication
if (!isset($_SESSION['user_id'])) {
  header("Location: ../../auth/login.php");
  exit();
}

if ($_SESSION['user_type'] !== 'owner') {
  header("Location: ../../auth/login.php");
  exit();
}

$owner_id = $_SESSION['user_id'];
?>

<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Your Property - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../css/your_property.css" rel="stylesheet">
</head>

<body>
  <!-- Header -->
  <?php include "../includes/navbar.php"; ?>


  <!-- Main Content -->
  <div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div class="mb-4">
        <h2 class="fw-bold mb-1">Your Property</h2>
        <p class="text-muted mb-0">Kelola semua properti kos Anda</p>
      </div>
      <div class="d-flex gap-2">
        <select id="filterStatus" class="form-select form-select-sm" style="width: auto;">
          <option value="">Semua Status</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="text-center py-5">
      <div class="spinner-border text-success" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-3 text-muted">Memuat properti Anda...</p>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="text-center py-5" style="display: none;">
      <i class="bi bi-house-x display-1 text-muted"></i>
      <h4 class="mt-3">Belum Ada Properti</h4>
      <p class="text-muted">Mulai tambahkan properti kos Anda sekarang</p>
      <a href="add_property.php" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Tambah Properti
      </a>
    </div>

    <!-- Property Grid -->
    <div id="propertyGrid" class="row g-4" style="display: none;">
      <!-- Cards will be loaded here via JavaScript -->
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-bold">Konfirmasi Hapus</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="text-center py-3">
            <i class="bi bi-exclamation-triangle text-danger" style="font-size: 4rem;"></i>
            <h5 class="mt-3">Apakah Anda yakin?</h5>
            <p class="text-muted mb-0">Properti <strong id="deletePropertyName"></strong> akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.</p>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
            <i class="bi bi-trash"></i> Ya, Hapus
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/your_property.js"></script>
</body>

</html>