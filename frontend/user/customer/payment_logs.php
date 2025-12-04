<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
  header("Location: /frontend/auth/login.php");
  exit;
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/backend/config/db.php");

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

// Verify booking belongs to user
$sql = "SELECT b.*, k.name as kos_name, k.address, k.city 
        FROM bookings b
        JOIN kos k ON b.kos_id = k.id
        WHERE b.id = ? AND b.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
  header("Location: bookings.php");
  exit;
}

// Get payment logs
$sql = "SELECT * FROM payment_logs 
        WHERE booking_id = ? 
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$logs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Logs - KosHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
  body {
    background-color: #f8f9fa;
  }

  /* Header utama – hijau gradient */
  .page-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
  }

  .booking-info-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
    border: 1px solid #e9f7ef;
  }

  .log-timeline {
    position: relative;
    padding-left: 40px;
  }

  .log-timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 3px;
    background: #d4edda;
    border-radius: 3px;
  }

  .log-item {
    background: white;
    border-radius: 16px;
    padding: 22px;
    margin-bottom: 24px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    position: relative;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
  }

  .log-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(40, 167, 69, 0.18);
    border-left-color: #28a745;
  }

  .log-item::before {
    content: '';
    position: absolute;
    left: -26px;
    top: 26px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: white;
    border: 4px solid;
    z-index: 2;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
  }

  /* Warna bullet sesuai status */
  .log-item.status-pending::before   { border-color: #ffc107; }
  .log-item.status-paid::before      { border-color: #28a745; background: #d4edda; }
  .log-item.status-failed::before    { border-color: #dc3545; }
  .log-item.status-expired::before   { border-color: #6c757d; }
  .log-item.status-refund::before    { border-color: #17a2b8; }

  /* Badge status – tetap pakai warna default Bootstrap agar konsisten */
  .status-badge {
    padding: 7px 16px;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 600;
  }

  /* Transaction badge jadi hijau muda */
  .transaction-badge {
    background-color: #e8f5e9;
    color: #155724;
    font-weight: 600;
  }

  /* Tombol collapse “Lihat Detail Transaksi” */
  .btn-outline-primary {
    --bs-btn-color: #28a745;
    --bs-btn-border-color: #28a745;
    --bs-btn-hover-bg: #28a745;
    --bs-btn-hover-border-color: #28a745;
    border-radius: 50px;
    font-weight: 500;
  }

  .collapse-btn {
    color: #28a745 !important;
    font-weight: 600;
  }

  .collapse-btn:hover {
    color: #218838 !important;
    text-decoration: underline;
  }

  /* Date badge di detail transaksi */
  .date-badge {
    background-color: #d4edda;
    color: #155724;
    font-weight: 600;
  }

  /* Hover efek lebih halus */
  .log-item:hover .collapse-btn {
    color: #218838 !important;
  }

  /* Optional: garis timeline jadi hijau muda saat ada log paid */
  .log-timeline:has(.status-paid)::before {
    background: linear-gradient(to bottom, #d4edda, #c3e6cb);
  }
</style>
</head>
<body>
  <?php include("navbar.php"); ?>

  <br><br>
  <div class="page-header">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h3 class="mb-1">
            <i class="bi bi-receipt"></i> Payment Logs
          </h3>
          <p class="mb-0 opacity-75">Riwayat transaksi pembayaran booking</p>
        </div>
        <a href="booking.php?id=<?php echo $booking_id; ?>" class="btn btn-light">
          <i class="bi bi-arrow-left"></i> Kembali
        </a>
      </div>
    </div>
  </div>

  <div class="container mb-5">
    <!-- Booking Info Card -->
    <div class="booking-info-card">
      <div class="row">
        <div class="col-md-6">
          <h5 class="mb-3">
            <i class="bi bi-building text-primary"></i>
            <?php echo htmlspecialchars($booking['kos_name']); ?>
          </h5>
          <p class="text-muted mb-2">
            <i class="bi bi-geo-alt-fill text-success"></i>
            <?php echo htmlspecialchars($booking['city']); ?>
          </p>
          <p class="text-muted mb-0">
            <i class="bi bi-card-text"></i>
            Booking ID: <strong>#<?php echo str_pad($booking_id, 5, '0', STR_PAD_LEFT); ?></strong>
          </p>
        </div>
        <div class="col-md-6">
          <div class="info-row">
            <span class="info-label">Status Booking:</span>
            <span class="info-value">
              <span class="status-badge status-<?php echo $booking['status']; ?>">
                <?php echo ucfirst($booking['status']); ?>
              </span>
            </span>
          </div>
          <div class="info-row">
            <span class="info-label">Status Pembayaran:</span>
            <span class="info-value">
              <span class="status-badge status-<?php echo $booking['payment_status']; ?>">
                <?php echo ucfirst($booking['payment_status']); ?>
              </span>
            </span>
          </div>
          <div class="info-row">
            <span class="info-label">Total Harga:</span>
            <span class="info-value text-success">
              Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Logs Timeline -->
    <h5 class="mb-3">
      <i class="bi bi-clock-history"></i> Riwayat Transaksi
      <span class="badge bg-secondary"><?php echo count($logs); ?> log</span>
    </h5>

    <?php if (empty($logs)): ?>
      <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <h5 class="text-muted">Belum Ada Log Pembayaran</h5>
        <p class="text-muted">Belum ada transaksi pembayaran yang tercatat untuk booking ini.</p>
      </div>
    <?php else: ?>
      <div class="log-timeline">
        <?php foreach ($logs as $index => $log): ?>
          <div class="log-item status-<?php echo $log['payment_status']; ?>">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h6 class="mb-1">
                  <i class="bi bi-credit-card-2-front"></i>
                  Log #<?php echo $log['id']; ?>
                </h6>
                <small class="text-muted">
                  <i class="bi bi-calendar3"></i>
                  <?php echo date('d M Y, H:i:s', strtotime($log['created_at'])); ?>
                </small>
              </div>
              <span class="status-badge status-<?php echo $log['payment_status']; ?>">
                <?php 
                $status_labels = [
                  'pending' => 'Pending',
                  'paid' => 'Berhasil',
                  'failed' => 'Gagal',
                  'expired' => 'Kadaluarsa',
                  'refund' => 'Refund'
                ];
                echo $status_labels[$log['payment_status']] ?? ucfirst($log['payment_status']); 
                ?>
              </span>
            </div>

            <!-- Info Grid -->
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <div class="info-row">
                  <span class="info-label">Order ID:</span>
                  <span class="info-value">
                    <span class="transaction-badge">
                      <?php echo htmlspecialchars($log['order_id']); ?>
                    </span>
                  </span>
                </div>
                <div class="info-row">
                  <span class="info-label">Transaction Status:</span>
                  <span class="info-value">
                    <?php echo $log['transaction_status'] ? htmlspecialchars($log['transaction_status']) : '-'; ?>
                  </span>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-row">
                  <span class="info-label">Payment Type:</span>
                  <span class="info-value">
                    <?php if ($log['payment_type']): ?>
                      <span class="payment-type-badge">
                        <?php echo htmlspecialchars(strtoupper($log['payment_type'])); ?>
                      </span>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </span>
                </div>
                <div class="info-row">
                  <span class="info-label">Gross Amount:</span>
                  <span class="info-value text-success">
                    <?php echo $log['gross_amount'] ? 'Rp ' . number_format($log['gross_amount'], 0, ',', '.') : '-'; ?>
                  </span>
                </div>
              </div>
            </div>

            <!-- Detail Transaksi Midtrans -->
            <?php if (!empty($log['notification_data'])): ?>
              <?php $notif = json_decode($log['notification_data'], true); ?>

              <div class="mt-4">
                <!-- Tombol Buka Detail -->
                <button class="btn btn-sm btn-outline-primary rounded-pill px-4 d-flex align-items-center gap-2 shadow-sm"
                        data-bs-toggle="collapse"
                        data-bs-target="#notifDetail-<?php echo $log['id']; ?>">
                  <i class="bi bi-chevron-down chevron-rotate"></i>
                  <span>Lihat Detail Transaksi</span>
                </button>

                <!-- Detail Transaksi -->
                <div class="collapse mt-3" id="notifDetail-<?php echo $log['id']; ?>">
                  <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">

                      <!-- Tanggal Check-in & Check-out -->
                      <div class="row g-3 mb-4 pb-3 border-bottom">
                        <div class="col-12 col-md-6">
                          <div class="d-flex align-items-center gap-3">
                            <div class="text-muted small text-end" style="min-width:140px;">
                              <i class="bi bi-calendar-check me-1"></i>
                              <strong>Check-in:</strong>
                            </div>
                            <div class="flex-grow-1">
                              <span class="date-badge">
                                <?php echo date('d M Y', strtotime($booking['check_in_date'])); ?>
                              </span>
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-md-6">
                          <div class="d-flex align-items-center gap-3">
                            <div class="text-muted small text-end" style="min-width:140px;">
                              <i class="bi bi-calendar-x me-1"></i>
                              <strong><?php echo $booking['booking_type'] === 'daily' ? 'Check-out:' : 'Durasi:'; ?></strong>
                            </div>
                            <div class="flex-grow-1">
                              <span class="date-badge">
                                <?php 
                                if ($booking['booking_type'] === 'daily') {
                                  echo $booking['check_out_date'] ? date('d M Y', strtotime($booking['check_out_date'])) : '-';
                                } else {
                                  echo $booking['duration_months'] . ' Bulan';
                                }
                                ?>
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>

                      <?php
                      $fields = [
                        'transaction_status' => ['label' => 'Status Transaksi',     'badge' => true],
                        'transaction_time'   => ['label' => 'Waktu Transaksi'],
                        'settlement_time'    => ['label' => 'Waktu Settlement'],
                        'payment_type'       => ['label' => 'Metode Pembayaran',   'icon' => 'bi-credit-card-2-front'],
                        'order_id'           => ['label' => 'ID Transaksi'],
                        'gross_amount'       => ['label' => 'Jumlah Dibayar',      'type' => 'currency'],
                        'fraud_status'       => ['label' => 'Status Fraud',        'badge' => true],
                        'status_message'     => ['label' => 'Pesan Midtrans'],
                        'bank'               => ['label' => 'Bank'],
                        'va_number'          => ['label' => 'Nomor VA'],
                        'bill_key'           => ['label' => 'Bill Key'],
                        'biller_code'        => ['label' => 'Biller Code'],
                        'masked_card'        => ['label' => 'Kartu Kredit'],
                        'approval_code'      => ['label' => 'Kode Approval'],
                      ];
                      ?>

                      <div class="row g-3">
                        <?php foreach ($fields as $key => $cfg): ?>
                          <?php if (empty($notif[$key])) continue; ?>
                          <?php $val = $notif[$key]; ?>

                          <div class="col-12 col-md-6">
                            <div class="d-flex align-items-center gap-3">
                              <div class="text-muted small text-end" style="min-width:140px;">
                                <?php if (!empty($cfg['icon'])): ?><i class="<?php echo $cfg['icon']; ?> me-1"></i><?php endif; ?>
                                <strong><?php echo $cfg['label']; ?>:</strong>
                              </div>
                              <div class="flex-grow-1">

                                <?php if (!empty($cfg['badge'])): ?>
                                  <?php
                                    $status = strtolower($val);
                                    $bgClass = 'bg-secondary';
                                    if (in_array($status, ['settlement','capture'])) $bgClass = 'bg-success';
                                    elseif ($status === 'pending') $bgClass = 'bg-warning text-dark';
                                    elseif (in_array($status, ['deny','cancel','expire','failure'])) $bgClass = 'bg-danger';
                                    elseif ($status === 'accept') $bgClass = 'bg-info text-dark';
                                  ?>
                                  <span class="badge <?php echo $bgClass; ?> px-3 py-2 fw-semibold"><?php echo strtoupper($val); ?></span>

                                <?php elseif (isset($cfg['type']) && $cfg['type'] === 'currency'): ?>
                                  <strong class="text-success fs-5">Rp <?php echo number_format((float)$val, 0, ',', '.'); ?></strong>

                                <?php else: ?>
                                  <span class="fw-semibold"><?php echo htmlspecialchars($val); ?></span>
                                <?php endif; ?>

                              </div>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>

                      <!-- Tombol Cetak - Hanya tampil jika BUKAN pending -->
                      <?php 
                      $isPaid = isset($notif['transaction_status']) && 
                                in_array(strtolower($notif['transaction_status']), ['settlement', 'capture']);
                      ?>
                      
                      <?php if ($isPaid): ?>
                        <div class="text-center mt-4">
                          <button onclick="printReceipt(<?php echo $log['id']; ?>)"
                                  class="btn btn-success btn-md px-4 py-2 rounded-pill shadow-sm">
                            <i class="bi bi-printer-fill me-2"></i>
                            Cetak Bukti Pembayaran
                          </button>
                        </div>
                      <?php else: ?>
                        <div class="text-center mt-4">
                          <div class="alert alert-warning mb-0" role="alert">
                            <i class="bi bi-info-circle me-2"></i>
                            Bukti pembayaran hanya tersedia setelah transaksi berhasil
                          </div>
                        </div>
                      <?php endif; ?>

                    </div>
                  </div>
                </div>
              </div>

              <!-- CSS Chevron Rotate -->
              <style>
                .chevron-rotate { transition: transform 0.3s ease; }
                [data-bs-toggle].collapsed .chevron-rotate { transform: rotate(-90deg); }
              </style>

              <!-- JavaScript Print Struk -->
              <script>
                function printReceipt(logId) {
                  const data = <?php echo $log['notification_data']; ?>;
                  const checkIn = '<?php echo date('d M Y', strtotime($booking['check_in_date'])); ?>';
                  const checkOut = '<?php 
                    if ($booking['booking_type'] === 'daily') {
                      echo $booking['check_out_date'] ? date('d M Y', strtotime($booking['check_out_date'])) : '-';
                    } else {
                      echo $booking['duration_months'] . ' Bulan';
                    }
                  ?>';
                  const bookingType = '<?php echo $booking['booking_type']; ?>';
                  const kosName = '<?php echo htmlspecialchars($booking['kos_name']); ?>';

                  const win = window.open('', '_blank', 'width=500,height=700');
                  win.document.write(`
                    <!DOCTYPE html>
                    <html lang="id">
                    <head>
                      <meta charset="utf-8">
                      <title>Bukti Pembayaran - KosHub</title>
                      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
                      <style>
                        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; padding: 15px; }
                        .receipt { max-width: 400px; margin: 20px auto; background: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); text-align: center; }
                        .logo { font-size: 38px; font-weight: bold; color: #198754; margin-bottom: 8px; }
                        .subtitle { color: #666; margin-bottom: 20px; }
                        .status { font-size: 30px; font-weight: bold; color: #198754; margin: 20px 0; }
                        .kos-name { font-size: 18px; font-weight: 600; color: #333; margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 8px; }
                        table { width: 100%; margin: 20px 0; font-size: 15px; }
                        td { padding: 8px 0; }
                        .label { text-align: left; color: #555; font-weight: 600; width: 45%; }
                        .value { text-align: right; font-weight: 500; }
                        .amount { font-size: 26px; font-weight: bold; color: #198754; }
                        .date-info { background: #e8f5e9; padding: 12px; border-radius: 8px; margin: 15px 0; }
                        .date-info strong { color: #2e7d32; }
                        .footer { margin-top: 35px; color: #888; font-size: 13px; }
                        @media print { body { background: white; padding: 5px; } }
                      </style>
                    </head>
                    <body>
                      <div class="receipt">
                        <div class="logo">KosHub</div>
                        <div class="subtitle">Bukti Pembayaran Berhasil</div>

                        <div class="status">LUNAS</div>
                        
                        <div class="kos-name">${kosName}</div>

                        <div class="date-info">
                          <div style="margin-bottom: 8px;">
                            <strong>Check-in:</strong> ${checkIn}
                          </div>
                          <div>
                            <strong>${bookingType === 'daily' ? 'Check-out' : 'Durasi'}:</strong> ${checkOut}
                          </div>
                        </div>

                        <table>
                          <tr><td class="label">ID Transaksi</td><td class="value">${data.order_id || '-'}</td></tr>
                          <tr><td class="label">Waktu Bayar</td><td class="value">${data.settlement_time || data.transaction_time || '-'}</td></tr>
                          <tr><td class="label">Metode</td><td class="value">${(data.payment_type || '').toUpperCase()} ${data.bank ? '('+data.bank.toUpperCase()+')' : ''}</td></tr>
                          ${data.va_number ? `<tr><td class="label">No. VA</td><td class="value">${data.va_number}</td></tr>` : ''}
                          ${data.masked_card ? `<tr><td class="label">Kartu</td><td class="value">${data.masked_card}</td></tr>` : ''}
                          <tr><td class="label">Jumlah</td><td class="value amount">Rp ${Number(data.gross_amount).toLocaleString('id-ID')}</td></tr>
                        </table>

                        <div class="footer">
                          Terima kasih telah menggunakan KosHub<br>
                          <strong>www.koshub.com</strong><br>
                          <small>Bukti pembayaran ini sah dan dapat digunakan sebagai tanda terima</small>
                        </div>
                      </div>
                    </body>
                    </html>
                  `);
                  win.document.close();
                  win.focus();
                  setTimeout(() => win.print(), 700);
                }
              </script>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>