<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "<p class='text-danger'>Data tidak valid.</p>";
    exit;
}

// Ambil data kos & owner
$query = "SELECT k.*, u.full_name AS owner_name 
          FROM kos k
          LEFT JOIN users u ON k.owner_id = u.id
          WHERE k.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();
$stmt->close();

if (!$property) {
    echo "<p class='text-danger'>Data kos tidak ditemukan.</p>";
    exit;
}

// Ambil gambar kos
$img_query = "SELECT image_url FROM kos_images WHERE kos_id = ? ORDER BY id ASC";
$img_stmt = $conn->prepare($img_query);
$img_stmt->bind_param("i", $id);
$img_stmt->execute();
$img_result = $img_stmt->get_result();
$images = [];
while ($img = $img_result->fetch_assoc()) {
    $images[] = $img['image_url'];
}
$img_stmt->close();

// Ambil fasilitas
$fac_query = "SELECT f.name, f.icon 
              FROM kos_facilities kf
              JOIN facilities f ON kf.facility_id = f.id
              WHERE kf.kos_id = ?";
$fac_stmt = $conn->prepare($fac_query);
$fac_stmt->bind_param("i", $id);
$fac_stmt->execute();
$fac_result = $fac_stmt->get_result();
$facilities = [];
while ($fac = $fac_result->fetch_assoc()) {
    $facilities[] = $fac;
}
$fac_stmt->close();
?>

<div class="property-card">
  <!-- Gambar -->
  <div class="property-images position-relative mb-3">
    <?php if (!empty($images)): ?>
      <div class="image-slider position-relative">
        <?php foreach ($images as $i => $img): ?>
          <img src="<?php echo htmlspecialchars('/Web-App/' . $img); ?>" 
               class="img-fluid mb-2 rounded slider-image <?php echo $i === 0 ? 'active' : ''; ?>"
               style="width:100%;object-fit:cover;max-height:400px;display:<?php echo $i === 0 ? 'block' : 'none'; ?>;"
               alt="<?php echo htmlspecialchars($property['name']); ?>"
               onerror="this.onerror=null; this.src='/Web-App/frontend/assets/default-property.jpg'; this.alt='Gambar tidak tersedia';">
        <?php endforeach; ?>
        <?php if (count($images) > 1): ?>
          <button class="slider-btn prev-btn btn btn-light btn-sm position-absolute top-50 start-0 translate-middle-y ms-2" style="z-index:10;">
            <i class="bi bi-chevron-left"></i>
          </button>
          <button class="slider-btn next-btn btn btn-light btn-sm position-absolute top-50 end-0 translate-middle-y me-2" style="z-index:10;">
            <i class="bi bi-chevron-right"></i>
          </button>
          <div class="image-counter position-absolute bottom-0 end-0 bg-dark text-white px-2 py-1 rounded m-2" style="font-size:0.85rem;">
            <span class="current-image">1</span> / <?php echo count($images); ?> Foto
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="no-image text-center py-5 bg-light rounded">
        <i class="bi bi-image fs-1 text-muted"></i>
        <p class="text-muted mt-2">Tidak ada gambar</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Info Dasar -->
  <h3 class="fw-bold mb-2"><?php echo htmlspecialchars($property['name']); ?></h3>
  
  <div class="owner-info mb-3 p-2 bg-light rounded">
    <p class="mb-0">
      <i class="bi bi-person-circle"></i> 
      <strong>Pemilik:</strong> <?php echo htmlspecialchars($property['owner_name'] ?? 'Tidak diketahui'); ?>
    </p>
  </div>

  <div class="location-info mb-3">
    <h6 class="fw-bold mb-2"><i class="bi bi-geo-alt-fill text-danger"></i> Lokasi Lengkap</h6>
    <div class="ps-3">
      <p class="mb-1">
        <i class="bi bi-signpost-2"></i> 
        <strong>Alamat:</strong> <?php echo htmlspecialchars($property['address']); ?>
      </p>
      <p class="mb-1">
        <i class="bi bi-building"></i> 
        <strong>Kota/Kabupaten:</strong> <?php echo htmlspecialchars($property['city']); ?>
      </p>
      <p class="mb-1">
        <i class="bi bi-map"></i> 
        <strong>Provinsi:</strong> <?php echo htmlspecialchars($property['province']); ?>
      </p>
      <?php if (!empty($property['postal_code'])): ?>
      <p class="mb-1">
        <i class="bi bi-mailbox"></i> 
        <strong>Kode Pos:</strong> <?php echo htmlspecialchars($property['postal_code']); ?>
      </p>
      <?php endif; ?>
    </div>
  </div>

  <div class="mb-3">
    <span class="badge bg-success fs-6 px-3 py-2">
      <i class="bi bi-gender-ambiguous"></i> 
      Kos <?php echo ucfirst($property['kos_type']); ?>
    </span>
  </div>

  <!-- Info Kamar & Harga -->
  <p class="mb-2">
    <i class="bi bi-door-open"></i> 
    Kamar Tersedia: <strong><?php echo $property['available_rooms']; ?></strong> dari <strong><?php echo $property['total_rooms']; ?></strong>
  </p>
  <p class="kost-price mb-3">
    <i class="bi bi-cash-coin"></i> 
    Rp <?php echo number_format($property['price_monthly'], 0, ',', '.'); ?> <small class="text-muted">/ bulan</small>
  </p>

  <hr>

  <!-- Fasilitas -->
  <?php if (!empty($facilities)): ?>
    <div class="mb-3">
      <strong><i class="bi bi-check-circle"></i> Fasilitas:</strong><br>
      <div class="mt-2">
        <?php foreach ($facilities as $f): ?>
          <span class="badge bg-light text-dark border me-1 mb-1">
            <i class="<?php echo htmlspecialchars($f['icon'] ?? 'bi bi-check'); ?>"></i>
            <?php echo htmlspecialchars($f['name']); ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="mb-3">
      <p class="text-muted"><i class="bi bi-info-circle"></i> Belum ada fasilitas terdaftar</p>
    </div>
  <?php endif; ?>

  <!-- Deskripsi -->
  <?php if (!empty($property['description'])): ?>
    <div class="mb-3">
      <strong><i class="bi bi-align-left"></i> Deskripsi:</strong>
      <p class="mt-2"><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
    </div>
  <?php else: ?>
    <div class="mb-3">
      <p class="text-muted"><i class="bi bi-info-circle"></i> Belum ada deskripsi</p>
    </div>
  <?php endif; ?>

  <!-- Peraturan Kos -->
  <?php if (!empty($property['rules'])): ?>
    <div class="mb-3">
      <strong><i class="bi bi-exclamation-triangle text-warning"></i> Peraturan Kos:</strong>
      <div class="rules-box mt-2 p-3 bg-light border-start border-warning border-4 rounded">
        <?php 
        // Split rules by newline atau bullet points
        $rules_array = preg_split('/\r\n|\r|\n/', trim($property['rules']));
        $rules_array = array_filter($rules_array, 'strlen'); // Remove empty lines
        
        if (!empty($rules_array)): ?>
          <ul class="mb-0 ps-3">
            <?php foreach ($rules_array as $rule): ?>
              <li class="mb-1"><?php echo htmlspecialchars(trim($rule)); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="mb-0"><?php echo nl2br(htmlspecialchars($property['rules'])); ?></p>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="mb-3">
      <strong><i class="bi bi-exclamation-triangle text-warning"></i> Peraturan Kos:</strong>
      <div class="rules-box mt-2 p-3 bg-light border-start border-secondary border-4 rounded">
        <p class="mb-0 text-muted"><i class="bi bi-info-circle"></i> Belum ada peraturan yang ditetapkan</p>
      </div>
    </div>
  <?php endif; ?>

  
</div>

<style>
.kost-price {
  font-weight: bold;
  color: #28a745;
  font-size: 1.25rem;
}
.slider-btn {
  opacity: 0.8;
  transition: opacity 0.2s;
}
.slider-btn:hover {
  opacity: 1;
}
.property-card {
  animation: fadeIn 0.3s ease-in;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.owner-info {
  background: linear-gradient(135deg, #e8f5e9 0%, #f1f8f4 100%);
  border-left: 4px solid #28a745;
}
.location-info {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 8px;
  border-left: 4px solid #dc3545;
}
.location-info p {
  font-size: 0.95rem;
  color: #495057;
}
.location-info i {
  width: 20px;
  text-align: center;
  color: #6c757d;
}
</style>

<script>
// Slider kontrol yang lebih baik
document.querySelectorAll('.image-slider').forEach(slider => {
  const images = slider.querySelectorAll('.slider-image');
  const counter = slider.querySelector('.current-image');
  let current = 0;

  const updateSlider = () => {
    images.forEach((img, idx) => {
      img.style.display = idx === current ? 'block' : 'none';
      if (idx === current) {
        img.classList.add('active');
      } else {
        img.classList.remove('active');
      }
    });
    if (counter) {
      counter.textContent = current + 1;
    }
  };

  const prev = slider.querySelector('.prev-btn');
  const next = slider.querySelector('.next-btn');

  if (prev && next && images.length > 1) {
    prev.addEventListener('click', (e) => {
      e.preventDefault();
      current = (current - 1 + images.length) % images.length;
      updateSlider();
    });

    next.addEventListener('click', (e) => {
      e.preventDefault();
      current = (current + 1) % images.length;
      updateSlider();
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowLeft') {
        current = (current - 1 + images.length) % images.length;
        updateSlider();
      } else if (e.key === 'ArrowRight') {
        current = (current + 1) % images.length;
        updateSlider();
      }
    });
  }
});
</script>