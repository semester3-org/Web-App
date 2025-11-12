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

        .detail-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }

        .status-badge {
            padding: 8px 18px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.85; }
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-cancelled { background: #e2e3e5; color: #383d41; }

        .kos-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.4s ease;
        }

        .kos-image:hover {
            transform: scale(1.02);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px dashed #e9ecef;
            font-size: 0.95rem;
        }

        .info-row:last-child { border-bottom: none; }

        .info-label { color: var(--gray); font-weight: 500; }
        .info-value { font-weight: 600; color: #212529; }

        .btn-action {
            border-radius: 12px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }

        /* MODAL CANTIK */
        .cancel-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(10px);
            animation: fadeIn 0.4s ease-out;
            justify-content: center;
            align-items: center;
        }

        .cancel-modal.show { display: flex; }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            border-radius: 24px;
            width: 90%;
            max-width: 440px;
            overflow: hidden;
            box-shadow: 0 25px 70px rgba(0,0,0,0.3);
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(60px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            padding: 28px 28px 16px;
            text-align: center;
            border-bottom: none;
        }

        .modal-header h5 {
            font-weight: 700;
            color: #212529;
            margin: 0;
            font-size: 1.3rem;
        }

        .modal-header .icon {
            width: 80px;
            height: 80px;
            background: #fee;
            color: var(--danger);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 2.2rem;
            animation: shake 0.7s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        .modal-body {
            padding: 0 28px 22px;
            text-align: center;
            color: #495057;
            line-height: 1.6;
        }

        .modal-footer {
            padding: 18px 28px 28px;
            border-top: none;
            display: flex;
            gap: 14px;
        }

        .btn-cancel-confirm {
            background: var(--danger);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 14px;
            font-weight: 600;
            flex: 1;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-cancel-confirm:hover {
            background: #c82333;
            transform: translateY(-3px);
        }

        .btn-cancel-close {
            background: #f8f9fa;
            color: #6c757d;
            border: 1.5px solid #dee2e6;
            padding: 12px 24px;
            border-radius: 14px;
            font-weight: 600;
            flex: 1;
            transition: all 0.3s ease;
        }

        .btn-cancel-close:hover {
            background: #e9ecef;
            transform: translateY(-3px);
        }

        .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2.5px solid #fff;
            border-top: 2.5px solid transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
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
            padding: 16px 26px;
            border-radius: 14px;
            color: white;
            font-weight: 600;
            box-shadow: 0 12px 35px rgba(0,0,0,0.2);
            opacity: 0;
            transform: translateX(120%);
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
        }

        .notification.show {
            opacity: 1;
            transform: translateX(0);
        }

        .notification.success { background: var(--success); }
        .notification.error { background: var(--danger); }

        .notification i { font-size: 1.3rem; }
    </style>
</head>
<body>

<?php include("navbar.php"); ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="detail-card">
                <div class="d-flex align-items-center mb-4">
                    <button class="btn btn-outline-success me-3 btn-action" onclick="history.back()">
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
                    <h6 class="fw-bold mb-3 text-success">Informasi Kos</h6>
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
                        <span class="badge bg-primary rounded-pill px-3 py-2"><?php echo ucfirst($booking['kos_type']); ?></span>
                        <a href="detail_kos.php?id=<?php echo $booking['kos_id']; ?>" 
                           class="btn btn-sm btn-outline-success ms-2 btn-action">
                            <i class="bi bi-eye"></i> Lihat Detail
                        </a>
                    </div>
                </div>

                <!-- Booking Details -->
                <div class="mb-4">
                    <h6 class="fw-bold mb-3 text-success">Detail Booking</h6>
                    <div class="info-row">
                        <span class="info-label">Tipe Booking:</span>
                        <span class="info-value"><?php echo $booking['booking_type'] === 'monthly' ? 'Bulanan' : 'Harian'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Check-in:</span>
                        <span class="info-value"><?php echo date('d F Y', strtotime($booking['check_in_date'])); ?></span>
                    </div>
                    <?php if ($booking['booking_type'] === 'daily'): ?>
                        <div class="info-row">
                            <span class="info-label">Check-out:</span>
                            <span class="info-value"><?php echo $booking['check_out_date'] ? date('d F Y', strtotime($booking['check_out_date'])) : '-'; ?></span>
                        </div>
                    <?php else: ?>
                        <div class="info-row">
                            <span class="info-label">Durasi:</span>
                            <span class="info-value"><?php echo $booking['duration_months']; ?> Bulan</span>
                        </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <span class="info-label">Total Harga:</span>
                        <span class="info-value text-success fw-bold">Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?></span>
                    </div>
                    <?php if ($booking['notes']): ?>
                        <div class="info-row">
                            <span class="info-label">Catatan:</span>
                            <span class="info-value"><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Owner Information -->
                <div class="mb-4">
                    <h6 class="fw-bold mb-3 text-success">Pemilik Kos</h6>
                    <div class="info-row">
                        <span class="info-label">Nama:</span>
                        <span class="info-value"><?php echo htmlspecialchars($booking['owner_name']); ?></span>
                    </div>
                    <?php if ($booking['owner_phone']): ?>
                        <div class="info-row">
                            <span class="info-label">Telepon:</span>
                            <a href="tel:<?php echo htmlspecialchars($booking['owner_phone']); ?>" class="info-value text-primary">
                                <?php echo htmlspecialchars($booking['owner_phone']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if ($booking['owner_email']): ?>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <a href="mailto:<?php echo htmlspecialchars($booking['owner_email']); ?>" class="info-value text-primary">
                                <?php echo htmlspecialchars($booking['owner_email']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Timeline -->
                <div class="mb-4">
                    <h6 class="fw-bold mb-3 text-success">Timeline</h6>
                    <div class="info-row">
                        <span class="info-label">Dibuat:</span>
                        <span class="info-value"><?php echo date('d F Y, H:i', strtotime($booking['created_at'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Diperbarui:</span>
                        <span class="info-value"><?php echo date('d F Y, H:i', strtotime($booking['updated_at'])); ?></span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex gap-3 flex-wrap">
                    <?php if ($booking['status'] === 'pending'): ?>
                        <button class="btn btn-danger btn-action" onclick="openCancelModal()">
                            <i class="bi bi-x-circle"></i> Batalkan Booking
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($booking['status'] === 'confirmed'): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $booking['owner_phone']); ?>?text=Halo,%20saya%20ingin%20konfirmasi%20booking%20#<?php echo $booking['id']; ?>" 
                           class="btn btn-success btn-action" target="_blank">
                            <i class="bi bi-whatsapp"></i> Hubungi Pemilik
                        </a>
                    <?php endif; ?>
                    
                    <a href="booking.php" class="btn btn-outline-secondary btn-action ms-auto">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL BATALKAN -->
<div id="cancelModal" class="cancel-modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="icon">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <h5>Batalkan Booking?</h5>
        </div>
        <div class="modal-body">
            <p>Anda akan membatalkan booking untuk:</p>
            <strong><?php echo htmlspecialchars($booking['kos_name']); ?></strong><br>
            <small class="text-muted">ID: #<?php echo str_pad($booking['id'], 5, '0', STR_PAD_LEFT); ?></small>
            <p class="mt-3 mb-0 text-danger"><strong>Tindakan ini tidak dapat dibatalkan.</strong></p>
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
function openCancelModal() {
    document.getElementById('cancelModal').classList.add('show');
    document.getElementById('confirmCancelBtn').disabled = false;
    document.getElementById('cancelSpinner').style.display = 'none';
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.remove('show');
}

async function confirmCancel() {
    const btn = document.getElementById('confirmCancelBtn');
    const spinner = document.getElementById('cancelSpinner');
    btn.disabled = true;
    spinner.style.display = 'inline-block';

    try {
        const res = await fetch('/Web-App/backend/user/customer/classes/cancel_booking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ booking_id: <?php echo $booking['id']; ?> })
        });
        const data = await res.json();

        showNotif(data.message, data.success ? 'success' : 'error');
        
        if (data.success) {
            setTimeout(() => location.href = 'booking.php', 1800);
        } else {
            btn.disabled = false;
            spinner.style.display = 'none';
        }
    } catch (err) {
        showNotif('Koneksi gagal', 'error');
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

    setTimeout(() => notif.classList.remove('show'), 3500);
}

// Tutup modal saat klik luar
document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) closeCancelModal();
});
</script>
</body>
</html>