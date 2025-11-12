<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: /Web-App/frontend/auth/login.php");
    exit;
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

if ($booking_id == 0) {
    header("Location: booking.php");
    exit();
}

// Get booking details
$sql = "SELECT b.*, k.name as kos_name, k.address, k.city, k.province, 
        k.price_monthly, k.price_daily, k.kos_type, k.owner_id,
        u.full_name as owner_name, u.phone as owner_phone, u.email as owner_email
        FROM bookings b
        JOIN kos k ON b.kos_id = k.id
        LEFT JOIN users u ON k.owner_id = u.id
        WHERE b.id = ? AND b.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: booking.php");
    exit();
}

$booking = $result->fetch_assoc();

// Get kos image
$img_sql = "SELECT image_url FROM kos_images WHERE kos_id = ? ORDER BY id LIMIT 1";
$img_stmt = $conn->prepare($img_sql);
$img_stmt->bind_param("i", $booking['kos_id']);
$img_stmt->execute();
$img_result = $img_stmt->get_result();
$kos_image = $img_result->num_rows > 0 ? $img_result->fetch_assoc()['image_url'] : null;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Booking #<?php echo str_pad($booking['id'], 5, '0', STR_PAD_LEFT); ?> - KostHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 70px; }
        .detail-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .status-badge { padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-cancelled { background: #e2e3e5; color: #383d41; }
        .kos-image { width: 100%; height: 200px; object-fit: cover; border-radius: 10px; }
        .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e9ecef; }
        .info-row:last-child { border-bottom: none; }
    </style>
</head>
<body>

<?php include("navbar.php"); ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="detail-card">
                <div class="d-flex align-items-center mb-4">
                    <button class="btn btn-outline-success me-3" onclick="history.back()">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <div>
                        <h4 class="mb-1 fw-bold">Detail Booking</h4>
                        <span class="text-muted">ID: #<?php echo str_pad($booking['id'], 5, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <span class="status-badge status-<?php echo $booking['status']; ?> ms-auto">
                        <?php 
                          $status_labels = [
                            'pending' => 'Menunggu Konfirmasi',
                            'confirmed' => 'Dikonfirmasi',
                            'completed' => 'Selesai',
                            'rejected' => 'Ditolak',
                            'cancelled' => 'Dibatalkan'
                          ];
                          echo $status_labels[$booking['status']] ?? ucfirst($booking['status']);
                        ?>
                    </span>
                </div>

                <!-- Kos Information -->
                <div class="mb-4">
                    <h6 class="fw-bold mb-3">Informasi Kos</h6>
                    <?php if ($kos_image): 
                        $imgPath = '/Web-App/' . $kos_image;
                        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $imgPath)) {
                            $imgPath = '/Web-App/frontend/assets/default-kos.jpg';
                        }
                    ?>
                        <img src="<?php echo htmlspecialchars($imgPath); ?>" 
                             class="kos-image mb-3" 
                             alt="Foto Kos"
                             onerror="this.src='/Web-App/frontend/assets/default-kos.jpg'">
                    <?php endif; ?>
                    
                    <h5 class="fw-bold"><?php echo htmlspecialchars($booking['kos_name']); ?></h5>
                    <p class="text-muted mb-2">
                        <i class="bi bi-geo-alt-fill text-success"></i>
                        <?php echo htmlspecialchars($booking['address'] . ', ' . $booking['city'] . ', ' . $booking['province']); ?>
                    </p>
                    <div>
                        <span class="badge bg-primary"><?php echo ucfirst($booking['kos_type']); ?></span>
                        <a href="detail_kos.php?id=<?php echo $booking['kos_id']; ?>" 
                           class="btn btn-sm btn-outline-success ms-2">
                            <i class="bi bi-eye"></i> Lihat Detail Kos
                        </a>
                    </div>
                </div>

                <!-- Booking Details -->
                <div class="mb-4">
                    <h6 class="fw-bold mb-3">Detail Booking</h6>
                    <div class="info-row">
                        <span class="text-muted">Tipe Booking:</span>
                        <strong><?php echo $booking['booking_type'] === 'monthly' ? 'Bulanan' : 'Harian'; ?></strong>
                    </div>
                    <div class="info-row">
                        <span class="text-muted">Tanggal Check-in:</span>
                        <strong><?php echo date('d F Y', strtotime($booking['check_in_date'])); ?></strong>
                    </div>
                    <?php if ($booking['booking_type'] === 'daily'): ?>
                        <div class="info-row">
                            <span class="text-muted">Tanggal Check-out:</span>
                            <strong><?php echo $booking['check_out_date'] ? date('d F Y', strtotime($booking['check_out_date'])) : '-'; ?></strong>
                        </div>
                    <?php else: ?>
                        <div class="info-row">
                            <span class="text-muted">Durasi:</span>
                            <strong><?php echo $booking['duration_months']; ?> Bulan</strong>
                        </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <span class="text-muted">Total Harga:</span>
                        <strong class="text-success">Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?></strong>
                    </div>
                    <?php if ($booking['notes']): ?>
                        <div class="info-row">
                            <span class="text-muted">Catatan:</span>
                            <span><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Owner Information -->
                <div class="mb-4">
                    <h6 class="fw-bold mb-3">Informasi Pemilik</h6>
                    <div class="info-row">
                        <span class="text-muted">Nama:</span>
                        <strong><?php echo htmlspecialchars($booking['owner_name']); ?></strong>
                    </div>
                    <?php if ($booking['owner_phone']): ?>
                        <div class="info-row">
                            <span class="text-muted">Telepon:</span>
                            <a href="tel:<?php echo htmlspecialchars($booking['owner_phone']); ?>">
                                <?php echo htmlspecialchars($booking['owner_phone']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if ($booking['owner_email']): ?>
                        <div class="info-row">
                            <span class="text-muted">Email:</span>
                            <a href="mailto:<?php echo htmlspecialchars($booking['owner_email']); ?>">
                                <?php echo htmlspecialchars($booking['owner_email']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Timeline -->
                <div class="mb-4">
                    <h6 class="fw-bold mb-3">Timeline</h6>
                    <div class="info-row">
                        <span class="text-muted">Dibuat:</span>
                        <span><?php echo date('d F Y, H:i', strtotime($booking['created_at'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="text-muted">Terakhir Diupdate:</span>
                        <span><?php echo date('d F Y, H:i', strtotime($booking['updated_at'])); ?></span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex gap-2">
                    <?php if ($booking['status'] === 'pending'): ?>
                        <button class="btn btn-danger" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                            <i class="bi bi-x-circle"></i> Batalkan Booking
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($booking['status'] === 'confirmed'): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $booking['owner_phone']); ?>?text=Halo,%20saya%20ingin%20konfirmasi%20booking%20#<?php echo $booking['id']; ?>" 
                           class="btn btn-success" target="_blank">
                            <i class="bi bi-whatsapp"></i> Hubungi Pemilik
                        </a>
                    <?php endif; ?>
                    
                    <a href="booking.php" class="btn btn-outline-secondary ms-auto">
                        <i class="bi bi-arrow-left"></i> Kembali ke Daftar Booking
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

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
    alert(data.message);
    if (data.success) {
      window.location.href = 'booking.php';
    }
  })
  .catch(error => {
    alert('Terjadi kesalahan');
    console.error(error);
  });
}
</script>
</body>
</html>