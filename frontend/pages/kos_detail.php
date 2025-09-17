<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit;
}

$kos_id = $_GET['id'] ?? 0;
if (!$kos_id) {
    header('Location: home.php');
    exit;
}

$page_title = "Detail Kos - Kosthub";
include '../components/header.php';

// In real app, fetch from API
$kos = [
    'id' => $kos_id,
    'name' => 'Kos Mutiara Indah',
    'address' => 'Jl. Merdeka No. 123, Jakarta Pusat',
    'price' => 1200000,
    'type' => 'male',
    'facilities' => 'wifi,ac,parkir,kamar mandi dalam',
    'description' => 'Kos yang nyaman dan strategis dengan fasilitas lengkap. Dekat dengan kampus dan pusat perbelanjaan.',
    'image_url' => 'default.jpg',
    'owner_name' => 'Budi Santoso',
    'owner_phone' => '081234567890',
    'latitude' => -6.2088,
    'longitude' => 106.8456
];

$facilities = explode(',', $kos['facilities']);
?>

<div class="kos-detail">
    <div class="kos-gallery">
        <img src="../../uploads/kos/<?= $kos['image_url'] ?>" alt="<?= $kos['name'] ?>" class="main-image">
        <div class="thumbnail-grid">
            <img src="https://placehold.co/100x100" alt="Thumbnail 1">
            <img src="https://placehold.co/100x100" alt="Thumbnail 2">
            <img src="https://placehold.co/100x100" alt="Thumbnail 3">
        </div>
    </div>

    <div class="kos-info">
        <div class="kos-header">
            <h1><?= $kos['name'] ?></h1>
            <span class="kos-type <?= $kos['type'] ?>">
                <?= ucfirst($kos['type']) ?>
            </span>
        </div>

        <div class="kos-location">
            <i class="fas fa-map-marker-alt"></i>
            <?= $kos['address'] ?>
        </div>

        <div class="kos-price">
            Rp <?= number_format($kos['price'], 0, ',', '.') ?> /bulan
        </div>

        <div class="kos-description">
            <h3>Deskripsi</h3>
            <p><?= $kos['description'] ?></p>
        </div>

        <div class="kos-facilities">
            <h3>Fasilitas</h3>
            <div class="facilities-grid">
                <?php foreach ($facilities as $facility): ?>
                    <span class="facility-badge"><?= trim($facility) ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="owner-info">
            <h3>Informasi Pemilik</h3>
            <p><strong>Nama:</strong> <?= $kos['owner_name'] ?></p>
            <p><strong>Telepon:</strong> <?= $kos['owner_phone'] ?></p>
        </div>

        <div class="action-buttons">
            <button class="btn btn-primary btn-large" onclick="showBookingModal()">
                <i class="fas fa-calendar-check"></i> Booking Sekarang
            </button>
            <button class="btn btn-outline">
                <i class="fas fa-phone"></i> Hubungi Pemilik
            </button>
            <button class="btn btn-outline">
                <i class="fas fa-share"></i> Bagikan
            </button>
        </div>
    </div>
</div>

<div class="kos-map-section">
    <h3>Lokasi</h3>
    <div id="map" style="height: 400px; border-radius: 12px;"></div>
</div>

<!-- Booking Modal -->
<div id="bookingModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeBookingModal()">&times;</span>
        <h2>Booking Kos</h2>
        
        <form id="bookingForm">
            <input type="hidden" name="kos_id" value="<?= $kos['id'] ?>">
            
            <div class="form-group">
                <label>Tanggal Check-in</label>
                <input type="date" name="booking_date" required min="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="form-group">
                <label>Durasi Sewa</label>
                <select name="duration" required>
                    <option value="1">1 Bulan</option>
                    <option value="3">3 Bulan</option>
                    <option value="6">6 Bulan</option>
                    <option value="12">12 Bulan</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Catatan (Opsional)</label>
                <textarea name="notes" placeholder="Tambahkan catatan khusus..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Konfirmasi Booking</button>
                <button type="button" class="btn btn-outline" onclick="closeBookingModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function showBookingModal() {
    document.getElementById('bookingModal').style.display = 'block';
}

function closeBookingModal() {
    document.getElementById('bookingModal').style.display = 'none';
}

document.getElementById('bookingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const response = await fetch('../../backend/kos/booking.php', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert('Booking berhasil! Silakan tunggu konfirmasi dari pemilik kos.');
        closeBookingModal();
    } else {
        alert(result.error || 'Booking gagal');
    }
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('bookingModal');
    if (event.target === modal) {
        closeBookingModal();
    }
}

// Initialize map (simplified version)
function initMap() {
    // This would be replaced with actual map implementation
    console.log('Map would be initialized here with coordinates:', 
        <?= $kos['latitude'] ?>, <?= $kos['longitude'] ?>);
}
</script>

<style>
.kos-detail {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 3rem;
}

.kos-gallery {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.main-image {
    width: 100%;
    height: 400px;
    object-fit: cover;
    border-radius: 12px;
}

.thumbnail-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
}

.thumbnail-grid img {
    width: 100%;
    height: 100px;
    object-fit: cover;
    border-radius: 6px;
    cursor: pointer;
}

.kos-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.kos-type {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    color: white;
    font-size: 0.875rem;
    font-weight: 500;
}

.kos-type.male { background: #3b82f6; }
.kos-type.female { background: #ec4899; }
.kos-type.mixed { background: #10b981; }

.kos-location {
    color: #6b7280;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.kos-price {
    font-size: 1.5rem;
    font-weight: bold;
    color: #4f46e5;
    margin-bottom: 2rem;
}

.kos-description,
.kos-facilities,
.owner-info {
    margin-bottom: 2rem;
}

.facilities-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.facility-badge {
    background: #f3f4f6;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 10% auto;
    padding: 2rem;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    position: relative;
}

.close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 1.5rem;
    cursor: pointer;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.kos-map-section {
    margin-top: 3rem;
}
</style>

<?php include '../components/footer.php'; ?>