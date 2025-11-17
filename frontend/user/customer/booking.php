<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
  header("Location: /Web-App/frontend/auth/login.php");
  exit;
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/midtrans.php"); // TAMBAHKAN INI

$user_id = $_SESSION['user_id'];

// Get all bookings for this user - TAMBAHKAN payment_status, order_id, paid_at
$sql = "SELECT b.*, 
        k.name as kos_name, 
        k.address, 
        k.city, 
        k.kos_type,
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

$stmt->close();

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your Bookings - KosHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <style>
    .booking-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      transition: transform 0.2s;
    }

    .booking-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .booking-id {
      font-size: 0.85rem;
      color: #666;
      font-weight: 500;
    }

    .kos-type-badge {
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .type-putra { background-color: #e3f2fd; color: #1976d2; }
    .type-putri { background-color: #fce4ec; color: #c2185b; }
    .type-campur { background-color: #f3e5f5; color: #7b1fa2; }

    .status-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .status-pending { background-color: #fff3cd; color: #856404; }
    .status-confirmed { background-color: #d1ecf1; color: #0c5460; }
    .status-completed { background-color: #d4edda; color: #155724; }
    .status-rejected { background-color: #f8d7da; color: #721c24; }
    .status-cancelled { background-color: #e2e3e5; color: #383d41; }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
    }

    .empty-state i {
      font-size: 4rem;
      color: #dee2e6;
    }

    /* Cancel Modal Styles */
    .cancel-modal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      animation: fadeIn 0.3s;
    }

    .cancel-modal.show {
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: white;
      border-radius: 12px;
      padding: 0;
      max-width: 400px;
      width: 90%;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
      animation: slideUp 0.3s;
    }

    .modal-header {
      padding: 24px;
      border-bottom: 1px solid #dee2e6;
      text-align: center;
    }

    .modal-header .icon {
      font-size: 3rem;
      color: #ffc107;
      margin-bottom: 16px;
    }

    .modal-body {
      padding: 24px;
      text-align: center;
    }

    .modal-footer {
      padding: 16px 24px;
      border-top: 1px solid #dee2e6;
      display: flex;
      gap: 12px;
    }

    .btn-cancel-close,
    .btn-cancel-confirm {
      flex: 1;
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn-cancel-close {
      background-color: #6c757d;
      color: white;
    }

    .btn-cancel-close:hover {
      background-color: #5a6268;
    }

    .btn-cancel-confirm {
      background-color: #dc3545;
      color: white;
      position: relative;
    }

    .btn-cancel-confirm:hover:not(:disabled) {
      background-color: #c82333;
    }

    .btn-cancel-confirm:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .spinner {
      display: none;
      width: 16px;
      height: 16px;
      border: 2px solid white;
      border-top: 2px solid transparent;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin-left: 8px;
    }

    /* Notification Styles */
    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 16px 24px;
      border-radius: 8px;
      color: white;
      font-weight: 500;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      z-index: 10000;
      display: flex;
      align-items: center;
      gap: 12px;
      transform: translateX(400px);
      transition: transform 0.3s ease;
    }

    .notification.show {
      transform: translateX(0);
    }

    .notification.success {
      background-color: #28a745;
    }

    .notification.error {
      background-color: #dc3545;
    }

    .notification i {
      font-size: 1.5rem;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes slideUp {
      from {
        transform: translateY(50px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body>

  <?php 
  // Include midtrans config untuk mendapatkan MIDTRANS_CLIENT_KEY
  require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/midtrans.php");
  include("navbar.php"); 
  ?>

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
                <div class="d-flex flex-column align-items-end gap-1">
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
                  <?php if (isset($booking['payment_status']) && $booking['status'] === 'confirmed'): ?>
                    <span class="badge bg-<?php
                      echo $booking['payment_status'] === 'paid' ? 'success' : 
                           ($booking['payment_status'] === 'pending' ? 'warning' : 'secondary');
                    ?>">
                      <?php
                      $payment_labels = [
                        'unpaid' => 'Belum Bayar',
                        'pending' => 'Menunggu Pembayaran',
                        'paid' => 'Lunas',
                        'failed' => 'Gagal',
                        'expired' => 'Kadaluarsa'
                      ];
                      echo $payment_labels[$booking['payment_status']] ?? ucfirst($booking['payment_status']);
                      ?>
                    </span>
                  <?php endif; ?>
                </div>
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
                  <?php if (
                    $booking['status'] === 'confirmed' &&
                    (!isset($booking['payment_status']) ||
                      $booking['payment_status'] === 'unpaid' ||
                      $booking['payment_status'] === 'failed')
                  ): ?>
                    <button class="btn btn-sm btn-success flex-grow-1"
                      onclick="processPayment(<?php echo $booking['id']; ?>)"
                      id="pay-btn-<?php echo $booking['id']; ?>">
                      <i class="bi bi-credit-card"></i> Bayar Sekarang
                    </button>
                  <?php elseif ($booking['status'] === 'confirmed' && $booking['payment_status'] === 'pending'): ?>
                    <button class="btn btn-sm btn-warning flex-grow-1"
                      onclick="checkPaymentStatus(<?php echo $booking['id']; ?>)">
                      <i class="bi bi-clock-history"></i> Cek Status Pembayaran
                    </button>
                  <?php elseif ($booking['status'] === 'confirmed' && $booking['payment_status'] === 'paid'): ?>
                    <button class="btn btn-sm btn-success flex-grow-1" disabled>
                      <i class="bi bi-check-circle"></i> Sudah Dibayar
                    </button>
                  <?php endif; ?>

                  <a href="booking_detail.php?id=<?php echo $booking['id']; ?>"
                    class="btn btn-sm btn-outline-success <?php echo ($booking['status'] === 'confirmed' && (!isset($booking['payment_status']) || $booking['payment_status'] !== 'paid')) ? '' : 'flex-grow-1'; ?>">
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

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- Include Midtrans Snap.js - HARUS SEBELUM SCRIPT LAINNYA -->
  <script 
    src="https://app.sandbox.midtrans.com/snap/snap.js" 
    data-client-key="<?php echo MIDTRANS_CLIENT_KEY; ?>"
    onload="console.log('Midtrans Snap.js loaded successfully')"
    onerror="console.error('Failed to load Midtrans Snap.js')">
  </script>
  
  <script>
    let currentBookingId = null;

    /**
     * Process payment for booking
     */
    function processPayment(bookingId) {
      const btnId = 'pay-btn-' + bookingId;
      const btn = document.getElementById(btnId);
      const originalHtml = btn.innerHTML;

      // Check if Snap is loaded
      if (typeof snap === 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Midtrans Snap belum dimuat. Silakan refresh halaman.',
          confirmButtonColor: '#dc3545'
        });
        return;
      }

      // Disable button and show loading
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';

      // Create FormData
      const formData = new FormData();
      formData.append('booking_id', bookingId);

      const apiUrl = '/Web-App/backend/user/customer/api/create_payment.php';
      console.log('Fetching to:', apiUrl);

      // Request payment token from backend
      fetch(apiUrl, {
          method: 'POST',
          body: formData
        })
        .then(response => {
          console.log('Response status:', response.status);
          return response.text();
        })
        .then(text => {
          console.log('Raw response:', text);
          const data = JSON.parse(text);
          
          if (data.success) {
            console.log('Snap token received:', data.snap_token);
            
            // Open Midtrans Snap payment popup
            snap.pay(data.snap_token, {
              onSuccess: function(result) {
                console.log('Payment success:', result);
                Swal.fire({
                  icon: 'success',
                  title: 'Pembayaran Berhasil!',
                  text: 'Terima kasih, pembayaran Anda telah berhasil diproses.',
                  confirmButtonColor: '#28a745'
                }).then(() => {
                  location.reload();
                });
              },
              onPending: function(result) {
                console.log('Payment pending:', result);
                Swal.fire({
                  icon: 'info',
                  title: 'Pembayaran Pending',
                  text: 'Pembayaran Anda sedang diproses. Silakan selesaikan pembayaran.',
                  confirmButtonColor: '#17a2b8'
                }).then(() => {
                  location.reload();
                });
              },
              onError: function(result) {
                console.error('Payment error:', result);
                Swal.fire({
                  icon: 'error',
                  title: 'Pembayaran Gagal',
                  text: 'Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.',
                  confirmButtonColor: '#dc3545'
                });
                btn.disabled = false;
                btn.innerHTML = originalHtml;
              },
              onClose: function() {
                console.log('Payment popup closed');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
              }
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Gagal',
              text: data.message || 'Gagal membuat token pembayaran',
              confirmButtonColor: '#dc3545'
            });
            btn.disabled = false;
            btn.innerHTML = originalHtml;
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Terjadi kesalahan sistem. Silakan coba lagi.',
            confirmButtonColor: '#dc3545'
          });
          btn.disabled = false;
          btn.innerHTML = originalHtml;
        });
    }

    /**
     * Check payment status
     */
    function checkPaymentStatus(bookingId) {
      Swal.fire({
        title: 'Mengecek Status Pembayaran...',
        text: 'Mohon tunggu sebentar',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      const formData = new FormData();
      formData.append('booking_id', bookingId);

      fetch('/Web-App/backend/user/customer/api/check_payment_status.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            if (data.payment_status === 'paid') {
              Swal.fire({
                icon: 'success',
                title: 'Pembayaran Berhasil!',
                text: 'Pembayaran Anda telah dikonfirmasi.',
                confirmButtonColor: '#28a745'
              }).then(() => {
                location.reload();
              });
            } else if (data.payment_status === 'failed') {
              Swal.fire({
                icon: 'error',
                title: 'Pembayaran Gagal',
                html: 'Pembayaran Anda gagal atau dibatalkan.<br>Status: ' + data.transaction_status,
                showCancelButton: true,
                confirmButtonText: 'Coba Bayar Lagi',
                cancelButtonText: 'Tutup',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d'
              }).then((result) => {
                if (result.isConfirmed) {
                  // Reset dan bayar lagi
                  resetPaymentAndRetry(bookingId);
                } else {
                  location.reload();
                }
              });
            } else {
              // Status masih pending
              Swal.fire({
                icon: 'info',
                title: 'Pembayaran Masih Pending',
                html: 'Pembayaran Anda belum selesai.<br><br>Apa yang ingin Anda lakukan?',
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-credit-card"></i> Lanjutkan Pembayaran',
                denyButtonText: '<i class="bi bi-x-circle"></i> Batalkan',
                cancelButtonText: 'Tutup',
                confirmButtonColor: '#28a745',
                denyButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d'
              }).then((result) => {
                if (result.isConfirmed) {
                  // Lanjutkan pembayaran
                  continuePayment(bookingId);
                } else if (result.isDenied) {
                  // Batalkan pembayaran
                  cancelPendingPayment(bookingId);
                }
              });
            }
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Gagal mengecek status pembayaran',
              confirmButtonColor: '#dc3545'
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Terjadi kesalahan sistem',
            confirmButtonColor: '#dc3545'
          });
        });
    }

    /**
     * Continue pending payment
     */
    function continuePayment(bookingId) {
      Swal.fire({
        title: 'Memuat...',
        text: 'Mohon tunggu',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      const formData = new FormData();
      formData.append('booking_id', bookingId);

      fetch('/Web-App/backend/user/customer/api/get_payment_token.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          Swal.close();
          
          if (data.success && data.snap_token) {
            // Buka Snap dengan token yang sudah ada
            snap.pay(data.snap_token, {
              onSuccess: function(result) {
                console.log('Payment success:', result);
                Swal.fire({
                  icon: 'success',
                  title: 'Pembayaran Berhasil!',
                  text: 'Terima kasih, pembayaran Anda telah berhasil diproses.',
                  confirmButtonColor: '#28a745'
                }).then(() => {
                  location.reload();
                });
              },
              onPending: function(result) {
                console.log('Payment pending:', result);
                Swal.fire({
                  icon: 'info',
                  title: 'Pembayaran Pending',
                  text: 'Pembayaran Anda sedang diproses.',
                  confirmButtonColor: '#17a2b8'
                }).then(() => {
                  location.reload();
                });
              },
              onError: function(result) {
                console.error('Payment error:', result);
                Swal.fire({
                  icon: 'error',
                  title: 'Pembayaran Gagal',
                  text: 'Terjadi kesalahan saat memproses pembayaran.',
                  confirmButtonColor: '#dc3545'
                });
              },
              onClose: function() {
                console.log('Payment popup closed');
              }
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Token pembayaran tidak tersedia. Silakan coba lagi.',
              confirmButtonColor: '#dc3545'
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Terjadi kesalahan sistem',
            confirmButtonColor: '#dc3545'
          });
        });
    }

    /**
     * Reset payment and retry
     */
    function resetPaymentAndRetry(bookingId) {
      // Cancel dulu, lalu bayar lagi
      const formData = new FormData();
      formData.append('booking_id', bookingId);

      fetch('/Web-App/backend/user/customer/api/cancel_payment.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            location.reload();
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Gagal',
              text: data.message,
              confirmButtonColor: '#dc3545'
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          location.reload();
        });
    }

    /**
     * Cancel pending payment
     */
    function cancelPendingPayment(bookingId) {
      Swal.fire({
        title: 'Batalkan Pembayaran?',
        text: 'Anda akan membatalkan proses pembayaran ini.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Tidak'
      }).then((result) => {
        if (result.isConfirmed) {
          const formData = new FormData();
          formData.append('booking_id', bookingId);

          fetch('/Web-App/backend/user/customer/api/cancel_payment.php', {
              method: 'POST',
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire({
                  icon: 'success',
                  title: 'Berhasil!',
                  text: 'Pembayaran telah dibatalkan',
                  confirmButtonColor: '#28a745'
                }).then(() => {
                  location.reload();
                });
              } else {
                Swal.fire({
                  icon: 'error',
                  title: 'Gagal',
                  text: data.message || 'Gagal membatalkan pembayaran',
                  confirmButtonColor: '#dc3545'
                });
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Terjadi kesalahan sistem',
                confirmButtonColor: '#dc3545'
              });
            });
        }
      });
    }

    /**
     * Open cancel booking modal
     */
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

    /**
     * Close cancel modal
     */
    function closeCancelModal() {
      document.getElementById('cancelModal').classList.remove('show');
      currentBookingId = null;
    }

    /**
     * Confirm cancel booking
     */
    async function confirmCancel() {
      if (!currentBookingId) return;

      const btn = document.getElementById('confirmCancelBtn');
      const spinner = document.getElementById('cancelSpinner');
      btn.disabled = true;
      spinner.style.display = 'inline-block';

      try {
        const res = await fetch('/Web-App/backend/user/customer/classes/cancel_booking.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            booking_id: currentBookingId
          })
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

    /**
     * Show notification
     */
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

    // Close modal when clicking outside
    document.getElementById('cancelModal').addEventListener('click', function(e) {
      if (e.target === this) closeCancelModal();
    });
  </script>
</body>
</html>