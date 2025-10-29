<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Dashboard Owner - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
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
    header('Location: ../../../auth/login.php');
    exit();
  }

  $owner_id = $_SESSION['user_id'];

  // Check pending payment
  $has_pending_payment = isset($_SESSION['pending_payment']);
  $payment_data = $has_pending_payment ? $_SESSION['pending_payment'] : null;
  ?>

  <?php include "../includes/navbar.php"; ?>

  <div class="container mt-5">
    <h3 class="fw-bold mb-4 text-center">Dashboard Owner</h3>

    <div class="d-flex justify-content-center">
      <div class="card shadow-sm border-0 p-4 text-center" style="width: 300px; border-radius: 15px;">
        <h5 class="fw-bold mb-3">Add Your Property</h5>
        <a href="add_property.php" class="btn btn-success px-4 py-3 w-100 fw-semibold" style="border-radius: 10px;">
          <i class="bi bi-plus-circle me-2"></i> Add Property
        </a>
      </div>
    </div>

    <!-- Property List (Optional - jika ingin tampilkan list property) -->
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
      <div class="container mt-5">
        <h5 class="fw-bold mb-3">List Property Payment</h5>
        <div class="row">
          <?php while ($property = $properties->fetch_assoc()): ?>
            <div class="col-md-4 mb-3">
              <div class="card">
                <div class="card-body">
                  <h5><?php echo htmlspecialchars($property['name']); ?></h5>
                  <p class="text-muted"><?php echo htmlspecialchars($property['city']); ?></p>

                  <p class="mb-2">
                    <strong>Status Kos:</strong>
                    <?php if (is_null($property['status'])): ?>
                      <span class="badge bg-secondary">Belum Dibayar</span>
                    <?php else: ?>
                      <span class="badge bg-<?php echo $property['status'] === 'approved' ? 'success' : ($property['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                        <?php echo strtoupper($property['status']); ?>
                      </span>
                    <?php endif; ?>
                  </p>

                  <p class="mb-2">
                    <strong>Status Bayar:</strong>
                    <span class="badge bg-<?php echo $property['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                      <?php echo strtoupper($property['payment_status']); ?>
                    </span>
                  </p>

                  <?php if ($property['payment_status'] === 'unpaid'): ?>
                    <button class="btn btn-sm btn-success mt-2" onclick="showPaymentModal(<?php echo $property['id']; ?>)">
                      <i class="bi bi-credit-card"></i> Bayar Sekarang
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Payment Modal -->
  <div class="modal fade payment-modal" id="paymentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
          <h5 class="modal-title">
            <i class="bi bi-credit-card"></i> Pembayaran Property
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="payment-warning">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Perhatian:</strong> Property Anda akan ditinjau admin setelah pembayaran selesai.
          </div>

          <h6><strong>Detail Property:</strong></h6>
          <p id="property-name" class="mb-3"></p>

          <div class="payment-summary">
            <table>
              <tr>
                <td>Harga Perbulan:</td>
                <td class="text-end" id="price-monthly">Rp 0</td>
              </tr>
              <tr>
                <td>Biaya Listing (Pajak <?php echo TAX_PERCENTAGE; ?>%):</td>
                <td class="text-end" id="tax-amount">Rp 0</td>
              </tr>
              <tr class="total-row">
                <td>Total Pembayaran:</td>
                <td class="text-end" id="total-amount">Rp 0</td>
              </tr>
            </table>

            <div class="alert alert-info mb-0">
              <small>
                <i class="bi bi-info-circle"></i>
                Pembayaran berlaku untuk 1 bulan pertama property listing
              </small>
            </div>
          </div>

          <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-pay-later" onclick="payLater()">
              <i class="bi bi-clock"></i> Bayar Nanti
            </button>
            <button type="button" class="btn btn-pay-now" id="payNowBtn" onclick="payNow()">
              <i class="bi bi-credit-card"></i> Bayar Sekarang
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
        .then(response => response.text()) // Ubah ke .text() dulu untuk debug
        .then(text => {
          console.log('Raw response:', text); // Lihat response aslinya

          try {
            const data = JSON.parse(text);
            if (data.success) {
              showPaymentModalWithData(data.payment);
            } else {
              Swal.fire('Error', data.message, 'error');
            }
          } catch (error) {
            console.error('JSON Parse Error:', error);
            console.error('Response was:', text);
            Swal.fire('Error', 'Terjadi kesalahan saat memuat data pembayaran', 'error');
          }
        })
        .catch(error => {
          console.error('Fetch Error:', error);
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
            snap.pay(result.snap_token, {
              onSuccess: function(result) {
                handlePaymentSuccess(result);
              },
              onPending: function(result) {
                handlePaymentPending(result);
              },
              onError: function(result) {
                handlePaymentError(result);
              },
              onClose: function() {
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

    function handlePaymentSuccess(result) {
      fetch('../../../../backend/user/owner/api/payment_callback.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            order_id: result.order_id,
            transaction_status: 'settlement',
            payment_type: result.payment_type,
            transaction_id: result.transaction_id
          })
        })
        .then(() => {
          Swal.fire({
            icon: 'success',
            title: 'Pembayaran Berhasil!',
            text: 'Property Anda akan segera ditinjau admin.',
            confirmButtonColor: '#10b981'
          }).then(() => {
            location.reload();
          });
        });
    }

    function handlePaymentPending(result) {
      console.log('Payment pending for order:', result.order_id);

      // Deteksi environment (sandbox/production)
      const isSandbox = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

      const autoSimulateTime = 5; // detik
      let countdown = autoSimulateTime;
      let countdownTimer = null;

      Swal.fire({
        icon: 'info',
        title: 'Pembayaran Diproses',
        html: `
      <p class="mb-2">Silakan selesaikan pembayaran Anda.</p>
      <p class="text-sm text-gray-600 mb-3">Status akan diperbarui otomatis...</p>
      
      ${isSandbox ? `
        <div class="mt-4 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
          <div class="flex items-center justify-center mb-2">
            <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span class="text-sm font-semibold text-yellow-800">Sandbox Mode</span>
          </div>
          <p class="text-xs text-yellow-700 mb-3">
            Auto-simulating payment in <span id="countdown" class="font-bold text-lg">${countdown}</span>s
          </p>
          <button id="simulateBtn" onclick="simulatePaymentNow('${result.order_id}')" 
                  class="w-full bg-green-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors shadow-sm">
            🧪 Simulate Payment Now
          </button>
          <button onclick="cancelSimulation()" 
                  class="w-full mt-2 bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-xs hover:bg-gray-200 transition-colors">
            Cancel Auto-Simulate
          </button>
        </div>
      ` : ''}
      
      <div class="mt-4">
        <div class="animate-pulse flex justify-center">
          <div class="h-2 w-2 bg-green-500 rounded-full mx-1 animate-bounce"></div>
          <div class="h-2 w-2 bg-green-500 rounded-full mx-1 animate-bounce" style="animation-delay: 0.2s"></div>
          <div class="h-2 w-2 bg-green-500 rounded-full mx-1 animate-bounce" style="animation-delay: 0.4s"></div>
        </div>
      </div>
    `,
        showConfirmButton: false,
        showCloseButton: true,
        allowOutsideClick: false,
        didOpen: () => {
          // Start checking payment status
          checkPaymentStatus(result.order_id);

          // Auto-simulate countdown (hanya di sandbox)
          if (isSandbox) {
            const countdownEl = document.getElementById('countdown');

            countdownTimer = setInterval(() => {
              countdown--;
              if (countdownEl) {
                countdownEl.textContent = countdown;

                // Animasi countdown
                if (countdown <= 3) {
                  countdownEl.classList.add('text-red-600');
                }
              }

              if (countdown <= 0) {
                clearInterval(countdownTimer);
                simulatePaymentNow(result.order_id);
              }
            }, 1000);
          }
        },
        willClose: () => {
          if (countdownTimer) {
            clearInterval(countdownTimer);
          }
        }
      });
    }

    // Fungsi untuk simulate payment segera
    function simulatePaymentNow(orderId) {
      console.log('Simulating payment for:', orderId);

      // Disable button
      const btn = document.getElementById('simulateBtn');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '⏳ Processing...';
        btn.classList.add('opacity-50', 'cursor-not-allowed');
      }

      fetch('../../../../backend/user/owner/api/manual_update_payment.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `order_id=${orderId}&status=settlement`
        })
        .then(response => response.json())
        .then(data => {
          console.log('Payment simulation result:', data);

          if (data.success) {
            // Update button
            if (btn) {
              btn.innerHTML = '✅ Payment Simulated!';
              btn.classList.remove('bg-green-600', 'hover:bg-green-700');
              btn.classList.add('bg-green-500');
            }
          } else {
            console.error('Simulation failed:', data.error);

            if (btn) {
              btn.innerHTML = '❌ Simulation Failed';
              btn.classList.remove('bg-green-600');
              btn.classList.add('bg-red-600');
            }
          }
        })
        .catch(error => {
          console.error('Error simulating payment:', error);

          if (btn) {
            btn.innerHTML = '❌ Error Occurred';
            btn.classList.remove('bg-green-600');
            btn.classList.add('bg-red-600');
          }
        });
    }

    // Fungsi untuk cancel auto-simulation
    function cancelSimulation() {
      Swal.update({
        html: Swal.getHtmlContainer().innerHTML.replace(/Auto-simulating.*?<\/p>/, '<p class="text-xs text-gray-600">Auto-simulation cancelled. Waiting for manual payment...</p>')
      });

      // Hapus countdown
      const countdownEl = document.getElementById('countdown');
      if (countdownEl && countdownEl.parentElement) {
        countdownEl.parentElement.remove();
      }
    }

    function checkPaymentStatus(orderId) {
      const interval = setInterval(() => {
        fetch(`../../../../backend/user/owner/api/check_payment_status.php?order_id=${orderId}`)
          .then(response => response.json())
          .then(data => {
            console.log('Payment status check:', data);

            // Success statuses
            if (data.payment_status === 'settlement' || data.payment_status === 'capture') {
              clearInterval(interval);

              Swal.fire({
                icon: 'success',
                title: 'Pembayaran Berhasil!',
                html: `
              <p>Transaksi Anda telah dikonfirmasi.</p>
              <p class="text-sm text-gray-600 mt-2">Order ID: ${data.order_id}</p>
            `,
                confirmButtonColor: '#10b981',
                confirmButtonText: 'OK'
              }).then(() => {
                location.reload();
              });
            }
            // Failed statuses
            else if (data.payment_status === 'deny' || data.payment_status === 'expire' || data.payment_status === 'cancel') {
              clearInterval(interval);

              Swal.fire({
                icon: 'error',
                title: 'Pembayaran Gagal',
                text: `Status: ${data.payment_status}`,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'OK'
              }).then(() => {
                location.reload();
              });
            }
          })
          .catch(error => {
            console.error('Error checking payment:', error);
          });
      }, 3000); // Check setiap 3 detik

      // Stop polling setelah 10 menit
      setTimeout(() => {
        clearInterval(interval);

        Swal.fire({
          icon: 'warning',
          title: 'Waktu Habis',
          text: 'Silakan refresh halaman untuk memeriksa status pembayaran.',
          confirmButtonColor: '#f59e0b'
        });
      }, 600000);
    }




    function handlePaymentError(result) {
      Swal.fire({
        icon: 'error',
        title: 'Pembayaran Gagal',
        text: 'Silakan coba lagi.',
        confirmButtonColor: '#10b981'
      });
    }
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
  <script>
    console.log('showPaymentModal:', typeof showPaymentModal);
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