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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      padding-top: 70px;
    }
    .booking-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      transition: transform 0.2s;
    }
    .booking-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    .status-badge {
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      display: inline-block;
    }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-confirmed { background: #d4edda; color: #155724; }
    .status-completed { background: #d1ecf1; color: #0c5460; }
    .status-rejected { background: #f8d7da; color: #721c24; }
    .status-cancelled { background: #e2e3e5; color: #383d41; }
    .kos-type-badge {
      font-size: 0.8rem;
      padding: 4px 10px;
      border-radius: 10px;
      font-weight: 600;
    }
    .type-putra { background: #e3f2fd; color: #1976d2; }
    .type-putri { background: #fce4ec; color: #c2185b; }
    .type-campur { background: #fff3e0; color: #f57c00; }
    .booking-id {
      font-family: monospace;
      font-weight: bold;
      color: #495057;
    }
    .empty-state {
      text-align: center;
      padding: 60px 20px;
    }
    .empty-state i {
      font-size: 4rem;
      color: #dee2e6;
      margin-bottom: 20px;
    }
    .notification { 
      position: fixed; 
      top: 20px; 
      right: 20px; 
      background: #198754; 
      color: white; 
      padding: 12px 20px; 
      border-radius: 8px; 
      box-shadow: 0 4px 12px rgba(0,0,0,0.2); 
      opacity: 0; 
      transform: translateY(-20px); 
      transition: all 0.4s ease; 
      z-index: 9999; 
    }
    .notification.show { opacity: 1; transform: translateY(0); }
    .notification.error { background: #dc3545; }
  </style>
</head>
<body>

<?php include("navbar.php"); ?>

<div class="container my-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">
      <i class="bi bi-calendar-check text-success"></i> Your Bookings
    </h3>
    <a href="explore.php" class="btn btn-outline-success">
      <i class="bi bi-search"></i> Cari Kos
    </a>
  </div>

  <?php if (empty($bookings)): ?>
    <div class="empty-state">
      <i class="bi bi-inbox"></i>
      <h5 class="text-muted">Belum ada booking</h5>
      <p class="text-muted">Mulai cari dan booking kos impian Anda!</p>
      <a href="explore.php" class="btn btn-success mt-3">
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
              <a href="property_detail.php?id=<?php echo $booking['kos_id']; ?>" 
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
                <a href="booking_detail.php?id=<?php echo $booking['id']; ?>" 
                   class="btn btn-sm btn-outline-success flex-grow-1">
                  <i class="bi bi-eye"></i> Detail
                </a>
                
                <?php if ($booking['status'] === 'pending'): ?>
                  <button class="btn btn-sm btn-outline-danger" 
                          onclick="cancelBooking(<?php echo $booking['id']; ?>)">
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

<div id="notification" class="notification"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function cancelBooking(bookingId) {
  if (!confirm('Apakah Anda yakin ingin membatalkan booking ini?')) {
    return;
  }

  fetch('/Web-App/backend/user/customer/classes/cancel_booking.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ booking_id: bookingId })
  })
  .then(response => response.json())
  .then(data => {
    showNotif(data.message, data.success ? 'success' : 'error');
    if (data.success) {
      setTimeout(() => location.reload(), 1500);
    }
  })
  .catch(error => {
    showNotif('Terjadi kesalahan', 'error');
    console.error(error);
  });
}

function showNotif(msg, type = 'success') {
  const n = document.getElementById('notification');
  n.textContent = msg;
  n.className = 'notification' + (type === 'error' ? ' error' : '');
  n.classList.add('show');
  setTimeout(() => n.classList.remove('show'), 3000);
}
</script>
</body>
</html>