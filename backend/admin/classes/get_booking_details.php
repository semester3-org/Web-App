<?php
session_start();
require_once '../../config/db.php';

// Check if user is logged in and is admin/superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    echo "Unauthorized";
    exit();
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get booking details
$query = "
    SELECT 
        b.*,
        k.name as kos_name,
        k.address,
        k.city,
        k.province,
        k.kos_type,
        u.full_name as customer_name,
        u.email as customer_email,
        u.phone as customer_phone,
        o.full_name as owner_name,
        o.email as owner_email,
        o.phone as owner_phone,
        pl.payment_type,
        pl.gross_amount,
        pl.transaction_status,
        pl.created_at as payment_date
    FROM bookings b
    JOIN kos k ON b.kos_id = k.id
    JOIN users u ON b.user_id = u.id
    JOIN users o ON k.owner_id = o.id
    LEFT JOIN payment_logs pl ON b.id = pl.booking_id
    WHERE b.id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    echo "<p>Booking tidak ditemukan</p>";
    exit();
}

// System tax rate (10%)
$system_tax_rate = 0.10;
$system_fee = $booking['total_price'] * $system_tax_rate;
$owner_payment = $booking['total_price'] - $system_fee;
?>

<style>
    .detail-container {
        padding: 1rem 0;
    }

    .detail-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--border-color);
    }

    .detail-header h2 {
        color: var(--primary-green);
        margin-bottom: 0.5rem;
    }

    .detail-section {
        margin-bottom: 2rem;
    }

    .detail-section h3 {
        color: var(--text-dark);
        font-size: 1.125rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--primary-green-light);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .detail-section h3 i {
        color: var(--primary-green);
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .detail-item {
        padding: 0.75rem;
        background: var(--bg-light);
        border-radius: 8px;
    }

    .detail-label {
        font-size: 0.813rem;
        color: var(--text-gray);
        margin-bottom: 0.25rem;
        font-weight: 600;
    }

    .detail-value {
        font-size: 0.938rem;
        color: var(--text-dark);
        font-weight: 500;
    }

    .payment-breakdown {
        background: var(--primary-green-light);
        padding: 1.5rem;
        border-radius: 12px;
        border-left: 4px solid var(--primary-green);
    }

    .payment-row {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--border-color);
    }

    .payment-row:last-child {
        border-bottom: none;
        font-weight: 700;
        font-size: 1.125rem;
        color: var(--primary-green-dark);
        padding-top: 1rem;
        margin-top: 0.5rem;
        border-top: 2px solid var(--primary-green);
    }

    .payment-label {
        color: var(--text-dark);
    }

    .payment-value {
        color: var(--text-dark);
        font-weight: 600;
    }

    .status-badge-large {
        display: inline-block;
        padding: 0.5rem 1.5rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-success {
        background: #d1fae5;
        color: #059669;
    }

    .badge-warning {
        background: #fef3c7;
        color: #d97706;
    }

    .badge-danger {
        background: #fee2e2;
        color: #dc2626;
    }

    .badge-info {
        background: #dbeafe;
        color: #2563eb;
    }

    .notes-box {
        background: #fef3c7;
        padding: 1rem;
        border-radius: 8px;
        border-left: 4px solid #f59e0b;
    }

    .notes-box p {
        color: #92400e;
        margin: 0;
    }

    @media (max-width: 768px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="detail-container">
    <div class="detail-header">
        <h2><i class="fas fa-file-invoice"></i> Detail Booking #<?= $booking['id'] ?></h2>
        <p>Order ID: <?= htmlspecialchars($booking['order_id'] ?? '-') ?></p>
    </div>

    <!-- Customer Information -->
    <div class="detail-section">
        <h3><i class="fas fa-user"></i> Informasi Customer</h3>
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Nama Lengkap</div>
                <div class="detail-value"><?= htmlspecialchars($booking['customer_name']) ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Email</div>
                <div class="detail-value"><?= htmlspecialchars($booking['customer_email']) ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Nomor Telepon</div>
                <div class="detail-value"><?= htmlspecialchars($booking['customer_phone'] ?? '-') ?></div>
            </div>
        </div>
    </div>

    <!-- Kos Information -->
    <div class="detail-section">
        <h3><i class="fas fa-home"></i> Informasi Kos</h3>
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Nama Kos</div>
                <div class="detail-value"><?= htmlspecialchars($booking['kos_name']) ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Tipe Kos</div>
                <div class="detail-value"><?= ucfirst($booking['kos_type']) ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Alamat</div>
                <div class="detail-value"><?= htmlspecialchars($booking['address']) ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Kota/Provinsi</div>
                <div class="detail-value"><?= htmlspecialchars($booking['city']) ?>, <?= htmlspecialchars($booking['province']) ?></div>
            </div>
        </div>
    </div>

    <!-- Owner Information -->
    <div class="detail-section">
        <h3><i class="fas fa-user-tie"></i> Informasi Owner</h3>
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Nama Owner</div>
                <div class="detail-value"><?= htmlspecialchars($booking['owner_name']) ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Email Owner</div>
                <div class="detail-value"><?= htmlspecialchars($booking['owner_email']) ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Nomor Telepon Owner</div>
                <div class="detail-value"><?= htmlspecialchars($booking['owner_phone'] ?? '-') ?></div>
            </div>
        </div>
    </div>

    <!-- Booking Information -->
    <div class="detail-section">
        <h3><i class="fas fa-calendar-alt"></i> Detail Booking</h3>
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Tipe Booking</div>
                <div class="detail-value">
                    <span class="status-badge-large badge-<?= $booking['booking_type'] === 'monthly' ? 'info' : 'warning' ?>">
                        <?= ucfirst($booking['booking_type']) ?>
                    </span>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Durasi</div>
                <div class="detail-value">
                    <?= $booking['booking_type'] === 'monthly' ? $booking['duration_months'] . ' Bulan' : 'Harian' ?>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Check In</div>
                <div class="detail-value"><?= date('d F Y', strtotime($booking['check_in_date'])) ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Check Out</div>
                <div class="detail-value">
                    <?= $booking['check_out_date'] ? date('d F Y', strtotime($booking['check_out_date'])) : '-' ?>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Status Booking</div>
                <div class="detail-value">
                    <span class="status-badge-large badge-<?= 
                        $booking['status'] === 'confirmed' ? 'success' : 
                        ($booking['status'] === 'pending' ? 'warning' : 'danger') 
                    ?>">
                        <?= ucfirst($booking['status']) ?>
                    </span>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Tanggal Booking</div>
                <div class="detail-value"><?= date('d F Y H:i', strtotime($booking['created_at'])) ?></div>
            </div>
        </div>
    </div>

    <!-- Payment Information -->
    <div class="detail-section">
        <h3><i class="fas fa-credit-card"></i> Informasi Pembayaran</h3>
        <div class="payment-breakdown">
            <div class="payment-row">
                <span class="payment-label">Total Harga Booking</span>
                <span class="payment-value">Rp <?= number_format($booking['total_price'], 0, ',', '.') ?></span>
            </div>
            <div class="payment-row">
                <span class="payment-label">Pajak Sistem (10%)</span>
                <span class="payment-value">- Rp <?= number_format($system_fee, 0, ',', '.') ?></span>
            </div>
            <div class="payment-row">
                <span class="payment-label">Total yang Diterima Owner</span>
                <span class="payment-value">Rp <?= number_format($owner_payment, 0, ',', '.') ?></span>
            </div>
        </div>

        <div class="detail-grid" style="margin-top: 1.5rem;">
            <div class="detail-item">
                <div class="detail-label">Status Pembayaran</div>
                <div class="detail-value">
                    <span class="status-badge-large badge-<?= 
                        $booking['payment_status'] === 'paid' ? 'success' : 
                        ($booking['payment_status'] === 'pending' ? 'warning' : 'danger') 
                    ?>">
                        <?= ucfirst($booking['payment_status']) ?>
                    </span>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Metode Pembayaran</div>
                <div class="detail-value"><?= $booking['payment_type'] ? strtoupper($booking['payment_type']) : '-' ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Tanggal Pembayaran</div>
                <div class="detail-value">
                    <?= $booking['paid_at'] ? date('d F Y H:i', strtotime($booking['paid_at'])) : '-' ?>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Status Penyaluran</div>
                <div class="detail-value">
                    <?php 
                    $disbursement_status = $booking['disbursement_status'] ?? 'pending';
                    ?>
                    <span class="status-badge-large badge-<?= $disbursement_status === 'disbursed' ? 'success' : 'warning' ?>">
                        <?= $disbursement_status === 'disbursed' ? 'Sudah Disalurkan' : 'Belum Disalurkan' ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if ($booking['disbursement_status'] === 'disbursed'): ?>
            <div class="detail-grid" style="margin-top: 1rem;">
                <div class="detail-item">
                    <div class="detail-label">Tanggal Penyaluran</div>
                    <div class="detail-value">
                        <?= $booking['disbursed_at'] ? date('d F Y H:i', strtotime($booking['disbursed_at'])) : '-' ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Notes -->
    <?php if ($booking['notes']): ?>
    <div class="detail-section">
        <h3><i class="fas fa-sticky-note"></i> Catatan</h3>
        <div class="notes-box">
            <p><?= nl2br(htmlspecialchars($booking['notes'])) ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>