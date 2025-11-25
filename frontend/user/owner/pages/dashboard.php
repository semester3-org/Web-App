<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Dashboard Owner - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="../css/dashboard.css?v=<?php echo time(); ?>">

</head>

<body>

  <?php
  session_start();
  require_once '../../../../backend/config/db.php';
  require_once '../../../../backend/config/midtrans.php';

  // Check login
  if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    header('Location: ../../login.php');
    exit();
  }

  $owner_id = $_SESSION['user_id'];

  // Check pending payment
  $has_pending_payment = isset($_SESSION['pending_payment']);
  $payment_data = $has_pending_payment ? $_SESSION['pending_payment'] : null;
  ?>

  <?php include "../includes/navbar.php"; ?>
  <br><br>

  <div class="dashboard-container">
    <div class="dashboard-header">
      <h1 class="dashboard-title">My Properties</h1>
      <button class="btn-add-property" onclick="window.location.href='add_property.php'">
        <i class="bi bi-plus-lg"></i>
      </button>
    </div>

    <!-- Property Grid -->
    <?php
    $sql = "SELECT k.*, pp.payment_status as payment_detail_status, pp.total_amount
          FROM kos k
          LEFT JOIN property_payments pp ON k.payment_id = pp.id
          WHERE k.owner_id = ?
          ORDER BY k.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $owner_id);
    $stmt->execute();
    $properties = $stmt->get_result();

    if ($properties->num_rows > 0):
    ?>
      <div class="property-grid">
        <?php while ($property = $properties->fetch_assoc()): ?>
          <div class="property-card">
            <div class="property-card-header">
              <h3 class="property-name"><?php echo htmlspecialchars($property['name']); ?></h3>
              <span class="property-location">
                <i class="bi bi-geo-alt"></i>
                <?php echo htmlspecialchars($property['city']); ?>
              </span>
            </div>

            <div class="property-card-body">
              <div class="payment-info">
                <span class="payment-label">Payment Status</span>
                <span class="badge badge-<?php echo $property['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                  <?php echo $property['payment_status'] === 'paid' ? 'Paid' : 'Unpaid'; ?>
                </span>
              </div>

              <?php if ($property['payment_status'] === 'unpaid'): ?>
                <button class="btn-pay" onclick="showPaymentModal(<?php echo $property['id']; ?>)">
                  <i class="bi bi-credit-card"></i>
                  Pay Now
                </button>
              <?php else: ?>
                <div class="paid-check">
                  <i class="bi bi-check-circle-fill"></i>
                  Payment Complete
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon">
          <i class="bi bi-house"></i>
        </div>
        <h3>No Properties Yet</h3>
        <p>Start by adding your first property</p>
        <button class="btn-primary" onclick="window.location.href='add_property.php'">
          <i class="bi bi-plus-circle"></i>
          Add Property
        </button>
      </div>
    <?php endif; ?>
  </div>

  <!-- Payment Modal -->
  <div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content payment-modal">
        <div class="modal-header">
          <h5 class="modal-title">Property Payment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert-info">
            <i class="bi bi-info-circle"></i>
            <span>Your property will be reviewed by admin after payment completion</span>
          </div>

          <div class="payment-detail">
            <h6>Property Details</h6>
            <p id="property-name" class="property-detail-name"></p>
          </div>

          <div class="payment-breakdown">
            <div class="breakdown-row">
              <span class="breakdown-label">Monthly Price</span>
              <span class="breakdown-value" id="price-monthly">Rp 0</span>
            </div>
            <div class="breakdown-row">
              <span class="breakdown-label">Listing Fee (Tax <?php echo TAX_PERCENTAGE; ?>%)</span>
              <span class="breakdown-value breakdown-highlight" id="tax-amount">Rp 0</span>
            </div>
            <div class="breakdown-row breakdown-total">
              <span class="breakdown-label">Total Payment</span>
              <span class="breakdown-value" id="total-amount">Rp 0</span>
            </div>
          </div>

          <div class="payment-note">
            <i class="bi bi-check-circle"></i>
            <small>One-time tax payment per property listing</small>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-secondary" onclick="payLater()">
              Pay Later
            </button>
            <button type="button" class="btn-primary" id="payNowBtn" onclick="payNow()">
              <i class="bi bi-credit-card"></i>
              Pay Now
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Load Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Load SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Midtrans Snap JS -->
  <script src="<?php echo MIDTRANS_SNAP_URL; ?>"
    data-client-key="<?php echo MIDTRANS_CLIENT_KEY; ?>"></script>

  <!-- Payment Modal Script -->
  <script>
    // Payment data from PHP
    const paymentData = <?php echo $has_pending_payment ? json_encode($payment_data) : 'null'; ?>;

    // Show payment modal on page load if has pending payment
    window.addEventListener('DOMContentLoaded', function() {
      if (paymentData) {
        showPaymentModalWithData(paymentData);
      }
    });

    function formatRupiah(amount) {
      return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
    }

    function showPaymentModalWithData(data) {
      document.getElementById('property-name').textContent = data.kos_name;
      document.getElementById('price-monthly').textContent = formatRupiah(data.price_monthly);
      document.getElementById('tax-amount').textContent = formatRupiah(data.tax_amount);
      document.getElementById('total-amount').textContent = formatRupiah(data.total_amount);

      window.currentPaymentData = data;

      const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
      modal.show();
    }

    function showPaymentModal(kosId) {
      fetch(`../../../../backend/user/owner/api/get_payment_data.php?kos_id=${kosId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showPaymentModalWithData(data.payment);
          } else {
            Swal.fire('Error', data.message, 'error');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire('Error', 'Terjadi kesalahan saat memuat data pembayaran', 'error');
        });
    }

    function payNow() {
      if (!window.currentPaymentData) {
        Swal.fire('Error', 'Data pembayaran tidak ditemukan', 'error');
        return;
      }

      const data = window.currentPaymentData;
      const payButton = document.getElementById('payNowBtn');
      payButton.disabled = true;
      payButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

      fetch('../../../../backend/user/owner/api/create_payment.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            payment_id: data.payment_id,
            order_id: data.order_id
          })
        })
        .then(response => response.json())
        .then(result => {
          if (result.success) {
            // Store order_id for status checking
            window.currentOrderId = result.order_id;

            snap.pay(result.snap_token, {
              onSuccess: function(result) {
                // Check immediately
                checkPaymentStatusAndUpdate(window.currentOrderId, true);
              },
              onPending: function(result) {
                // Start polling for pending payments (QRIS, Bank Transfer, etc)
                Swal.fire({
                  icon: 'info',
                  title: 'Pembayaran Pending',
                  html: 'Silakan selesaikan pembayaran Anda.<br><small>System akan otomatis check status</small>',
                  confirmButtonColor: '#10b981',
                  timer: 3000,
                  timerProgressBar: true
                });

                // Start polling
                startPaymentPolling(window.currentOrderId);
              },
              onError: function(result) {
                Swal.fire('Error', 'Pembayaran gagal. Silakan coba lagi.', 'error');
                payButton.disabled = false;
                payButton.innerHTML = '<i class="bi bi-credit-card"></i> Bayar Sekarang';
              },
              onClose: function() {
                // User close popup
                // Check status once
                setTimeout(() => {
                  fetch(`../../../backend/user/owner/api/check_payment_status.php?order_id=${window.currentOrderId}`)
                    .then(response => response.json())
                    .then(data => {
                      if (data.transaction_status === 'pending') {
                        // Still pending, ask user
                        Swal.fire({
                          title: 'Pembayaran Pending',
                          text: 'Pembayaran Anda masih pending. Lanjutkan check otomatis?',
                          icon: 'question',
                          showCancelButton: true,
                          confirmButtonText: 'Ya, Check Otomatis',
                          cancelButtonText: 'Bayar Nanti',
                          confirmButtonColor: '#10b981'
                        }).then((result) => {
                          if (result.isConfirmed) {
                            // Start polling
                            startPaymentPolling(window.currentOrderId);
                          } else {
                            location.reload();
                          }
                        });
                      } else if (data.kos_payment_status === 'paid') {
                        // Already paid
                        Swal.fire({
                          icon: 'success',
                          title: 'Pembayaran Berhasil!',
                          confirmButtonColor: '#10b981'
                        }).then(() => location.reload());
                      }
                    });
                }, 1000);

                payButton.disabled = false;
                payButton.innerHTML = '<i class="bi bi-credit-card"></i> Bayar Sekarang';
              }
            });
          } else {
            Swal.fire('Error', result.message, 'error');
            payButton.disabled = false;
            payButton.innerHTML = '<i class="bi bi-credit-card"></i> Bayar Sekarang';
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire('Error', error.message, 'error');
          payButton.disabled = false;
          payButton.innerHTML = '<i class="bi bi-credit-card"></i> Bayar Sekarang';
        });
    }

    // Check payment status from Midtrans
    // Check payment status with polling
    function checkPaymentStatusAndUpdate(orderId, isManualCheck = false) {
      if (isManualCheck) {
        Swal.fire({
          title: 'Checking Payment Status...',
          html: 'Mohon tunggu sebentar',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
      }

      fetch(`../../../../backend/user/owner/api/check_payment_status.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
          console.log('Payment status:', data);

          if (data.success) {
            if (data.kos_payment_status === 'paid') {
              // PAYMENT SUCCESS
              Swal.fire({
                icon: 'success',
                title: 'Pembayaran Berhasil!',
                text: 'Property Anda akan segera ditinjau admin dan untuk lebih lengkapnya bisa ke menu your property ',
                confirmButtonColor: '#10b981'
              }).then(() => {
                location.reload();
              });

            } else if (data.transaction_status === 'pending') {
              // STILL PENDING
              if (isManualCheck) {
                Swal.fire({
                  icon: 'info',
                  title: 'Pembayaran Pending',
                  html: 'Silakan selesaikan pembayaran Anda.<br><small>Scan QR code atau selesaikan di app GoPay</small>',
                  confirmButtonColor: '#10b981'
                });
              }
              // Continue polling jika tidak manual check

            } else if (data.transaction_status === 'expire' || data.transaction_status === 'cancel') {
              // EXPIRED/CANCELLED
              Swal.fire({
                icon: 'warning',
                title: 'Pembayaran ' + (data.transaction_status === 'expire' ? 'Expired' : 'Dibatalkan'),
                text: 'Silakan coba lagi untuk membayar.',
                confirmButtonColor: '#10b981'
              }).then(() => {
                location.reload();
              });

            } else {
              // OTHER STATUS
              if (isManualCheck) {
                Swal.fire({
                  icon: 'warning',
                  title: 'Status: ' + data.transaction_status,
                  text: 'Silakan check kembali atau hubungi support.',
                  confirmButtonColor: '#10b981'
                });
              }
            }
          } else {
            if (isManualCheck) {
              Swal.fire({
                icon: 'error',
                title: 'Gagal Check Status',
                text: data.message,
                confirmButtonColor: '#10b981'
              });
            }
          }
        })
        .catch(error => {
          console.error('Error:', error);
          if (isManualCheck) {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Gagal check status pembayaran',
              confirmButtonColor: '#10b981'
            });
          }
        });
    }

    // Start polling payment status
    function startPaymentPolling(orderId) {
      let pollCount = 0;
      const maxPolls = 120; // 120 x 5 seconds = 10 minutes

      const pollInterval = setInterval(() => {
        pollCount++;

        // Stop after max polls
        if (pollCount >= maxPolls) {
          clearInterval(pollInterval);
          Swal.fire({
            icon: 'warning',
            title: 'Timeout',
            text: 'Silakan check status pembayaran secara manual dari dashboard.',
            confirmButtonColor: '#10b981'
          });
          return;
        }

        // Check status
        fetch(`../../../../backend/user/owner/api/check_payment_status.php?order_id=${orderId}`)
          .then(response => response.json())
          .then(data => {
            console.log('Poll #' + pollCount + ':', data.transaction_status);

            if (data.success && data.kos_payment_status === 'paid') {
              // PAYMENT SUCCESS - Stop polling
              clearInterval(pollInterval);

              Swal.fire({
                icon: 'success',
                title: 'Pembayaran Berhasil!',
                text: 'Property Anda akan segera ditinjau admin.',
                confirmButtonColor: '#10b981'
              }).then(() => {
                location.reload();
              });
            } else if (data.transaction_status === 'expire' || data.transaction_status === 'cancel' || data.transaction_status === 'deny') {
              // FAILED - Stop polling
              clearInterval(pollInterval);

              Swal.fire({
                icon: 'error',
                title: 'Pembayaran Gagal',
                text: 'Status: ' + data.transaction_status,
                confirmButtonColor: '#10b981'
              }).then(() => {
                location.reload();
              });
            }
            // If still pending, continue polling
          })
          .catch(error => {
            console.error('Poll error:', error);
          });

      }, 5000); // Check every 5 seconds

      // Store interval ID untuk stop manual
      window.paymentPollInterval = pollInterval;
    }

    function payLater() {
      Swal.fire({
        title: 'Bayar Nanti?',
        text: 'Property akan tersimpan dan menunggu pembayaran. Anda bisa membayar kapan saja dari dashboard.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Bayar Nanti',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          fetch('../../../../backend/user/owner/api/clear_pending_payment.php', {
              method: 'POST'
            })
            .then(() => {
              bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
              location.reload();
            });
        }
      });
    }

    // Handle payment return dari Midtrans
    window.addEventListener('DOMContentLoaded', function() {
      // Check URL parameters
      const urlParams = new URLSearchParams(window.location.search);
      const orderId = urlParams.get('order_id');
      const statusCode = urlParams.get('status_code');
      const transactionStatus = urlParams.get('transaction_status');

      // Jika ada parameter payment dari Midtrans
      if (orderId && transactionStatus) {
        console.log('Payment return detected:', orderId, transactionStatus);

        // Show loading
        Swal.fire({
          title: 'Memproses Pembayaran...',
          html: 'Mohon tunggu, sedang memverifikasi pembayaran Anda',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        // Check payment status
        setTimeout(() => {
          fetch(`../../../../backend/user/owner/api/check_payment_status.php?order_id=${orderId}`)
            .then(response => response.json())
            .then(data => {
              console.log('Status check result:', data);

              if (data.success && data.kos_payment_status === 'paid') {
                Swal.fire({
                  icon: 'success',
                  title: 'Pembayaran Berhasil!',
                  text: 'Property Anda akan segera ditinjau admin.',
                  confirmButtonColor: '#10b981'
                }).then(() => {
                  // Clean URL dan reload
                  window.history.replaceState({}, document.title, window.location.pathname);
                  location.reload();
                });
              } else if (data.transaction_status === 'pending') {
                Swal.fire({
                  icon: 'info',
                  title: 'Pembayaran Pending',
                  text: 'Pembayaran Anda sedang diproses. Mohon tunggu beberapa saat.',
                  confirmButtonColor: '#10b981'
                }).then(() => {
                  window.history.replaceState({}, document.title, window.location.pathname);
                  location.reload();
                });
              } else {
                Swal.fire({
                  icon: 'warning',
                  title: 'Status: ' + data.transaction_status,
                  text: 'Silakan check kembali dari dashboard.',
                  confirmButtonColor: '#10b981'
                }).then(() => {
                  window.history.replaceState({}, document.title, window.location.pathname);
                  location.reload();
                });
              }
            })
            .catch(error => {
              console.error('Error checking status:', error);
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Gagal memverifikasi pembayaran. Silakan check manual.',
                confirmButtonColor: '#10b981'
              }).then(() => {
                window.history.replaceState({}, document.title, window.location.pathname);
                location.reload();
              });
            });
        }, 2000); // Delay 2 detik untuk ensure Midtrans sudah update
      }

      // Check pending payment modal (existing code)
      if (paymentData) {
        showPaymentModalWithData(paymentData);
      }
    });
  </script>

  <!-- Toast Notification -->
  <script>
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 4000,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
      }
    });
  </script>

  <?php if (isset($_SESSION['success'])): ?>
    <script>
      Toast.fire({
        icon: 'success',
        title: '<?php echo addslashes($_SESSION['success']); ?>'
      });
    </script>
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    <script>
      Toast.fire({
        icon: 'error',
        title: '<?php echo addslashes($_SESSION['error']); ?>'
      });
    </script>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

</body>

</html>

<?php
$conn->close();
?>