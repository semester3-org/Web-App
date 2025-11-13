<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: /Web-App/frontend/auth/login.php");
    exit;
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

$kos_id = isset($_GET['kos_id']) ? intval($_GET['kos_id']) : 0;
if ($kos_id == 0) {
    header("Location: explore.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data kos
$sql = "SELECT k.*, u.full_name as owner_name, u.phone as owner_phone
        FROM kos k 
        LEFT JOIN users u ON k.owner_id = u.id
        WHERE k.id = ? AND k.status = 'approved'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $kos_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: explore.php");
    exit;
}
$kos = $result->fetch_assoc();

if ($kos['available_rooms'] <= 0) {
    $error = "Maaf, kamar sudah penuh!";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking - <?php echo htmlspecialchars($kos['name']); ?> - KostHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .form-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .price-display { font-size: 1.8rem; font-weight: bold; color: #28a745; }
        .notification { position: fixed; top: 20px; right: 20px; z-index: 9999; padding: 12px 20px; border-radius: 8px; color: white; opacity: 0; transform: translateY(-20px); transition: all 0.4s; }
        .notification.show { opacity: 1; transform: translateY(0); }
        .notification.success { background: #198754; }
        .notification.error { background: #dc3545; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center mb-4">
                <button onclick="history.back()" class="btn btn-outline-success me-3">
                    <i class="bi bi-arrow-left"></i>
                </button>
                <h3 class="fw-bold mb-0">Booking Kos</h3>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <a href="explore.php" class="btn btn-outline-secondary">Kembali</a>
            <?php else: ?>

            <div class="form-card">
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h5 class="fw-bold"><?php echo htmlspecialchars($kos['name']); ?></h5>
                        <p class="text-muted">
                            <i class="bi bi-geo-alt-fill"></i>
                            <?php echo htmlspecialchars($kos['address'] . ', ' . $kos['city']); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="price-display">
                            Rp <?php echo number_format($kos['price_monthly'], 0, ',', '.'); ?>
                            <small class="text-muted d-block">/ bulan</small>
                        </div>
                        <?php if ($kos['price_daily']): ?>
                            <small class="text-muted">Harian: Rp <?php echo number_format($kos['price_daily'], 0, ',', '.'); ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <form id="bookingForm">
                    <input type="hidden" name="kos_id" value="<?php echo $kos_id; ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipe Booking</label>
                        <select name="booking_type" class="form-select" required onchange="toggleDuration()">
                            <option value="">Pilih tipe</option>
                            <option value="monthly">Bulanan</option>
                            <?php if ($kos['price_daily']): ?>
                                <option value="daily">Harian</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Check-in</label>
                        <input type="date" name="check_in_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required onchange="calculatePrice()">
                    </div>

                    <div id="dailyFields" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tanggal Check-out</label>
                            <input type="date" name="check_out_date" class="form-control" onchange="calculatePrice()">
                        </div>
                    </div>

                    <div id="monthlyFields" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Durasi (bulan)</label>
                            <select name="duration_months" class="form-select" onchange="calculatePrice()">
                                <option value="">Pilih durasi</option>
                                <?php for($i=1; $i<=12; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> bulan</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Total Harga</label>
                        <div class="price-display" id="totalPrice">Rp 0</div>
                        <input type="hidden" name="total_price" id="totalPriceInput" value="0">
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check-circle"></i> Konfirmasi & Booking
                    </button>
                </form>
            </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<div id="notification" class="notification"></div>

<script>
function toggleDuration() {
    const type = document.querySelector('[name="booking_type"]').value;
    document.getElementById('dailyFields').style.display = type === 'daily' ? 'block' : 'none';
    document.getElementById('monthlyFields').style.display = type === 'monthly' ? 'block' : 'none';
    calculatePrice();
}

function calculatePrice() {
    const type = document.querySelector('[name="booking_type"]').value;
    const monthly = <?php echo $kos['price_monthly']; ?>;
    const daily = <?php echo $kos['price_daily'] ?? 0; ?>;
    let total = 0;

    if (type === 'monthly') {
        const months = parseInt(document.querySelector('[name="duration_months"]').value) || 0;
        total = months * monthly;
    } else if (type === 'daily') {
        const checkin = document.querySelector('[name="check_in_date"]').value;
        const checkout = document.querySelector('[name="check_out_date"]').value;
        if (checkin && checkout) {
            const days = (new Date(checkout) - new Date(checkin)) / (1000 * 60 * 60 * 24);
            total = days > 0 ? days * daily : 0;
        }
    }

    document.getElementById('totalPrice').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('totalPriceInput').value = total;
}

document.getElementById('bookingForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    delete data.notes; // Hapus notes dari data yang dikirim
    // === FIX: Set check_out_date = null jika bulanan ===
    if (data.booking_type === 'monthly') {
        data.check_out_date = null;
        data.duration_months = parseInt(data.duration_months);
    } else {
        data.duration_months = null;
    }

    if (data.total_price == 0) {
        showNotif('Silakan pilih durasi dan tanggal terlebih dahulu', 'error');
        return;
    }

    try {
        const res = await fetch('/Web-App/backend/user/customer/classes/create_booking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        showNotif(json.message, json.success ? 'success' : 'error');
        if (json.success) {
            setTimeout(() => location.href = 'booking.php', 1500);
        }
    } catch (err) {
        showNotif('Error server', 'error');
        console.error(err);
    }
});

function showNotif(msg, type) {
    const n = document.getElementById('notification');
    n.textContent = msg;
    n.className = 'notification ' + type;
    n.classList.add('show');
    setTimeout(() => n.classList.remove('show'), 3000);
}
</script>
</body>
</html>