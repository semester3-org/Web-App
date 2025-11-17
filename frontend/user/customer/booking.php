<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: /Web-App/frontend/auth/login.php");
    exit;
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

$user_id = $_SESSION['user_id'];

// Get all bookings for this user
$sql = "SELECT b.*, k.name as kos_name, k.address, k.city, k.kos_type,
        u.full_name as owner_name
        FROM bookings b
        JOIN kos k ON b.kos_id = k.id
        LEFT JOIN users u ON k.owner_id = u.id
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Your Bookings - KostHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --success: #198754;
      --danger: #dc3545;
      --warning: #ffc107;
      --gray: #6c757d;
      --light: #f8f9fa;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
      min-height: 100vh;
      padding-top: 70px;
    }

    .booking-card {
      background: white;
      border-radius: 16px;
      padding: 22px;
      margin-bottom: 20px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
      border: 1px solid #e9ecef;
    }

    .booking-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    }

    .status-badge {
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      display: inline-block;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0% { opacity: 1; }
      50% { opacity: 0.8; }
      100% { opacity: 1; }
    }

    .status-pending { background: #fff3cd; color: #856404; }
    .status-confirmed { background: #d4edda; color: #155724; }
    .status-completed { background: #d1ecf1; color: #0c5460; }
    .status-rejected { background: #f8d7da; color: #721c24; }
    .status-cancelled { background: #e2e3e5; color: #383d41; }

    .kos-type-badge {
      font-size: 0.75rem;
      padding: 4px 10px;
      border-radius: 10px;
      font-weight: 600;
    }

    .type-putra { background: #e3f2fd; color: #1976d2; }
    .type-putri { background: #fce4ec; color: #c2185b; }
    .type-campur { background: #fff3e0; color: #f57c00; }

    .booking-id {
      font-family: 'Courier New', monospace;
      font-weight: bold;
      color: var(--gray);
      font-size: 0.9rem;
    }

    .empty-state {
      text-align: center;
      padding: 80px 20px;
      color: #6c757d;
    }

    .empty-state i {
      font-size: 5rem;
      color: #dee2e6;
      margin-bottom: 20px;
      animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }

    /* MODAL CUSTOM */
    .cancel-modal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0; top: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.6);
      backdrop-filter: blur(8px);
      animation: fadeIn 0.4s ease-out;
    }

    .cancel-modal.show { display: flex; }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .modal-content {
      margined: 500;
      background: white;
      margin: auto;
      border-radius: 20px;
      width: 90%;
      max-width: 420px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      animation: slideUp 0.5s ease-out;
    }

    @keyframes slideUp {
      from { transform: translateY(50px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    .modal-header {
      padding: 24px 24px 16px;
      text-align: center;
      border-bottom: none;
    }

    .modal-header h5 {
      font-weight: 700;
      color: #212529;
      margin: 0;
    }

    .modal-header .icon {
      width: 70px;
      height: 70px;
      background: #fee;
      color: var(--danger);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      font-size: 2rem;
      animation: shake 0.6s ease-in-out;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
      20%, 40%, 60%, 80% { transform: translateX(4px); }
    }

    .modal-body {
      padding: 0 24px 20px;
      text-align: center;
      color: #495057;
    }

    .modal-footer {
      padding: 16px 24px 24px;
      border-top: none;
      display: flex;
      gap: 12px;
    }

    .btn-cancel-confirm {
      background: var(--danger);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 12px;
      font-weight: 600;
      flex: 1;
      transition: all 0.3s ease;
    }

    .btn-cancel-confirm:hover {
      background: #c82333;
      transform: translateY(-2px);
    }

    .btn-cancel-close {
      background: #f8f9fa;
      color: #6c757d;
      border: 1px solid #dee2e6;
      padding: 10px 20px;
      border-radius: 12px;
      font-weight: 600;
      flex: 1;
      transition: all 0.3s ease;
    }

    .btn-cancel-close:hover {
      background: #e9ecef;
      transform: translateY(-2px);
    }

    .spinner {
      display: none;
      width: 16px;
      height: 16px;
      border: 2px solid #fff;
      border-top: 2px solid transparent;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin-left: 8px;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* NOTIFIKASI */
    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
      padding: 14px 24px;
      border-radius: 12px;
      color: white;
      font-weight: 600;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      opacity: 0;
      transform: translateX(100%);
      transition: all 0.4s ease;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .notification.show {
      opacity: 1;
      transform: translateX(0);
    }

    .notification.success { background: var(--success); }
    .notification.error { background: var(--danger); }

    .notification i { font-size: 1.2rem; }
  </style>
</head>
<body>

<?php include("navbar.php"); ?>

<div class="container my-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">
      <i class="bi bi-calendar-check text-success"></i> Your Bookings
    </h3>
    <a href="explore.php" class="btn btn-outline-success btn-sm">
      <i class="bi bi-search"></i> Cari Kos
    </a>
  </div>

  <?php if (empty($bookings)): ?>
    <div class="empty-state">
      <i class="bi bi-inbox"></i>
      <h5 class="text-muted">Belum ada booking</h5>
      <p class="text-muted">Mulai cari dan booking kos impian Anda!</p>
      <a href="explore.php" class="btn btn-success mt-3 px-4">
        <i class="bi bi-search"></i> Explore Kos
      </a>
    </div>
  <?php else: ?>
    <div class="row">
      <?php foreach ($bookings as $booking): ?>
        <div class="col-lg-6 col-xl-4">
          <div class="booking-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <span class="booking-id">#<?php echo str_pad($booking['id'], 5, '0', STR_PAD_LEFT); ?></span>
                <span class="kos-type-badge type-<?php echo $booking['kos_type']; ?> ms-2">
                  <?php echo ucfirst($booking['kos_type']); ?>
                </span>
              </div>
              <span class="status-badge status-<?php echo $booking['status']; ?>">
                <?php 
                  $status_labels = [
                    'pending' => 'Menunggu',
                    'confirmed' => 'Dikonfirmasi',
                    'completed' => 'Selesai',
                    'rejected' => 'Ditolak',
                    'cancelled' => 'Dibatalkan'
                  ];
                  echo $status_labels[$booking['status']] ?? ucfirst($booking['status']);
                ?>
              </span>
            </div>

            <h6 class="fw-bold mb-2">
              <a href="detail_kos.php?id=<?php echo $booking['kos_id']; ?>" 
                 class="text-decoration-none text-dark">
                <?php echo htmlspecialchars($booking['kos_name']); ?>
              </a>
            </h6>

            <p class="text-muted small mb-3">
              <i class="bi bi-geo-alt-fill text-success"></i>
              <?php echo htmlspecialchars($booking['city']); ?>
            </p>

            <div class="border-top pt-3">
              <div class="row g-2 mb-3">
                <div class="col-6">
                  <small class="text-muted d-block">Check-in</small>
                  <strong><?php echo date('d M Y', strtotime($booking['check_in_date'])); ?></strong>
                </div>
                <div class="col-6">
                  <small class="text-muted d-block">
                    <?php echo $booking['booking_type'] === 'daily' ? 'Check-out' : 'Durasi'; ?>
                  </small>
                  <strong>
                    <?php 
                      if ($booking['booking_type'] === 'daily') {
                        echo $booking['check_out_date'] ? date('d M Y', strtotime($booking['check_out_date'])) : '-';
                      } else {
                        echo $booking['duration_months'] . ' bulan';
                      }
                    ?>
                  </strong>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted">Total Harga:</span>
                <h5 class="text-success mb-0">
                  Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?>
                </h5>
              </div>

              <?php if ($booking['notes']): ?>
                <div class="alert alert-light py-2 px-3 mb-3">
                  <small><strong>Catatan:</strong> <?php echo htmlspecialchars($booking['notes']); ?></small>
                </div>
              <?php endif; ?>

              <div class="d-flex gap-2">
                <?php if ($booking['status'] === 'confirmed'): ?>
                  <a href="#" class="btn btn-sm btn-success flex-grow-1" onclick="alert('Fitur pembayaran sedang dikembangkan'); return false;">
                    <i class="bi bi-credit-card"></i> Bayar Sekarang
                  </a>
                <?php endif; ?>

                <a href="booking_detail.php?id=<?php echo $booking['id']; ?>" 
                  class="btn btn-sm btn-outline-success <?php echo $booking['status'] === 'confirmed' ? '' : 'flex-grow-1'; ?>">
                  <i class="bi bi-eye"></i> Detail
                </a>
                
                <?php if ($booking['status'] === 'pending'): ?>
                  <button class="btn btn-sm btn-outline-danger position-relative" 
                          onclick="openCancelModal(<?php echo $booking['id']; ?>, '<?php echo addslashes(htmlspecialchars($booking['kos_name'])); ?>')">
                    <i class="bi bi-x-circle"></i> Batal
                  </button>
                <?php endif; ?>
              </div>
            </div>

            <div class="border-top mt-3 pt-2">
              <small class="text-muted">
                <i class="bi bi-clock"></i> 
                Dibuat <?php echo date('d M Y, H:i', strtotime($booking['created_at'])); ?>
              </small>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- MODAL BATALKAN BOOKING -->
<div id="cancelModal" class="cancel-modal">
  <div class="modal-content">
    <div class="modal-header">
      <div class="icon">
        <i class="bi bi-exclamation-triangle-fill"></i>
      </div>
      <h5 id="modalTitle">Batalkan Booking?</h5>
    </div>
    <div class="modal-body">
      <p id="modalText">Apakah Anda yakin ingin membatalkan booking ini?</p>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel-close" onclick="closeCancelModal()">
        Tutup
      </button>
      <button id="confirmCancelBtn" class="btn-cancel-confirm" onclick="confirmCancel()">
        Ya, Batalkan
        <span class="spinner" id="cancelSpinner"></span>
      </button>
    </div>
  </div>
</div>

<!-- NOTIFIKASI -->
<div id="notification" class="notification">
  <i id="notifIcon"></i>
  <span id="notifText"></span>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentBookingId = null;

function openCancelModal(id, kosName) {
  currentBookingId = id;
  document.getElementById('modalTitle').textContent = 'Batalkan Booking?';
  document.getElementById('modalText').innerHTML = `
    Anda akan membatalkan booking untuk:<br>
    <strong>${kosName}</strong><br>
    <small class="text-muted">Booking ID: #${String(id).padStart(5, '0')}</small>
  `;
  document.getElementById('cancelModal').classList.add('show');
  document.getElementById('confirmCancelBtn').disabled = false;
  document.getElementById('cancelSpinner').style.display = 'none';
}

function closeCancelModal() {
  document.getElementById('cancelModal').classList.remove('show');
  currentBookingId = null;
}

async function confirmCancel() {
  if (!currentBookingId) return;

  const btn = document.getElementById('confirmCancelBtn');
  const spinner = document.getElementById('cancelSpinner');
  btn.disabled = true;
  spinner.style.display = 'inline-block';

  try {
    const res = await fetch('/Web-App/backend/user/customer/classes/cancel_booking.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ booking_id: currentBookingId })
    });
    const data = await res.json();

    showNotif(data.message, data.success ? 'success' : 'error');
    
    if (data.success) {
      setTimeout(() => location.reload(), 1500);
    } else {
      btn.disabled = false;
      spinner.style.display = 'none';
    }
  } catch (err) {
    showNotif('Terjadi kesalahan jaringan', 'error');
    btn.disabled = false;
    spinner.style.display = 'none';
  }
}

function showNotif(msg, type = 'success') {
  const notif = document.getElementById('notification');
  const icon = document.getElementById('notifIcon');
  const text = document.getElementById('notifText');

  text.textContent = msg;
  icon.className = type === 'success' ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill';
  notif.className = 'notification ' + type;
  notif.classList.add('show');

  setTimeout(() => notif.classList.remove('show'), 3000);
}

// Tutup modal saat klik luar
document.getElementById('cancelModal').addEventListener('click', function(e) {
  if (e.target === this) closeCancelModal();
});
</script>
</body>
</html>