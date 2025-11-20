<?php
session_start();
require_once '../../config/db.php';

// Check if user is logged in and is admin/superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    echo "Unauthorized";
    exit();
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get booking details with disbursed admin info
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
        da.full_name as disbursed_admin_name,
        (SELECT pl.payment_type FROM payment_logs pl WHERE pl.booking_id = b.id ORDER BY pl.created_at DESC LIMIT 1) as payment_type
    FROM bookings b
    JOIN kos k ON b.kos_id = k.id
    JOIN users u ON b.user_id = u.id
    JOIN users o ON k.owner_id = o.id
    LEFT JOIN users da ON b.disbursed_by = da.id
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

// Format phone for WhatsApp
$whatsapp_phone = $booking['owner_phone'] ? preg_replace('/[^0-9]/', '', $booking['owner_phone']) : '';
if (substr($whatsapp_phone, 0, 1) === '0') {
    $whatsapp_phone = '62' . substr($whatsapp_phone, 1);
}
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

    /* Contact Owner Buttons */
    .contact-owner-section {
        margin-top: 1rem;
        padding: 1rem;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
    }

    .contact-title {
        color: white;
        font-size: 0.938rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .contact-buttons {
        display: flex;
        gap: 0.75rem;
    }

    .btn-contact {
        flex: 1;
        padding: 0.75rem 1rem;
        border: 2px solid white;
        border-radius: 8px;
        background: white;
        color: var(--primary-green);
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s;
        cursor: pointer;
    }

    .btn-contact:hover {
        background: var(--primary-green-dark);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .btn-contact i {
        font-size: 1.125rem;
    }

    .disbursed-info {
        background: #d1fae5;
        padding: 1rem;
        border-radius: 8px;
        border-left: 4px solid var(--primary-green);
        margin-top: 1rem;
    }

    .disbursed-info p {
        margin: 0.25rem 0;
        color: var(--text-dark);
        font-size: 0.875rem;
    }

    .disbursed-info strong {
        color: var(--primary-green-dark);
    }

    @media (max-width: 768px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }

        .contact-buttons {
            flex-direction: column;
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

    <!-- Owner Information with Contact -->
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

        <!-- Contact Owner Section -->
        <div class="contact-owner-section">
            <div class="contact-title">
                <i class="fas fa-paper-plane"></i>
                Hubungi Pemilik untuk Penyaluran Dana
            </div>
            <div class="contact-buttons">
                <a href="mailto:<?= htmlspecialchars($booking['owner_email']) ?>?subject=Penyaluran Dana Booking #<?= $booking['id'] ?>&body=Yth. Bapak/Ibu <?= htmlspecialchars($booking['owner_name']) ?>,%0D%0A%0D%0AKami informasikan bahwa dana untuk booking #<?= $booking['id'] ?> telah siap disalurkan.%0D%0A%0D%0ADetail:%0D%0A- Nama Kos: <?= htmlspecialchars($booking['kos_name']) ?>%0D%0A- Total Pembayaran: Rp <?= number_format($booking['total_price'], 0, ',', '.') ?>%0D%0A- Pajak Sistem (10%%): Rp <?= number_format($system_fee, 0, ',', '.') ?>%0D%0A- Dana yang Anda Terima: Rp <?= number_format($owner_payment, 0, ',', '.') ?>%0D%0A%0D%0AMohon konfirmasi nomor rekening untuk transfer.%0D%0A%0D%0ATerima kasih,%0D%0AKostHub Admin" 
                   class="btn-contact" target="_blank">
                    <i class="fas fa-envelope"></i>
                    Email
                </a>
                <?php if ($whatsapp_phone): ?>
                <a href="https://wa.me/<?= $whatsapp_phone ?>?text=Yth.%20Bapak%2FIbu%20<?= urlencode($booking['owner_name']) ?>%2C%0A%0AKami%20informasikan%20bahwa%20dana%20untuk%20booking%20%23<?= $booking['id'] ?>%20telah%20siap%20disalurkan.%0A%0ADetail%3A%0A-%20Nama%20Kos%3A%20<?= urlencode($booking['kos_name']) ?>%0A-%20Total%20Pembayaran%3A%20Rp%20<?= number_format($booking['total_price'], 0, ',', '.') ?>%0A-%20Pajak%20Sistem%20(10%25)%3A%20Rp%20<?= number_format($system_fee, 0, ',', '.') ?>%0A-%20Dana%20yang%20Anda%20Terima%3A%20Rp%20<?= number_format($owner_payment, 0, ',', '.') ?>%0A%0AMohon%20konfirmasi%20nomor%20rekening%20untuk%20transfer.%0A%0ATerima%20kasih%2C%0AKostHub%20Admin" 
                   class="btn-contact" target="_blank">
                    <i class="fab fa-whatsapp"></i>
                    WhatsApp
                </a>
                <?php endif; ?>
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
            <div class="disbursed-info">
                <p><strong><i class="fas fa-check-circle"></i> Informasi Penyaluran Dana</strong></p>
                <p>Tanggal Penyaluran: <strong><?= $booking['disbursed_at'] ? date('d F Y H:i', strtotime($booking['disbursed_at'])) : '-' ?></strong></p>
                <p>Disalurkan oleh: <strong><?= $booking['disbursed_admin_name'] ?? '-' ?></strong></p>
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