<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user';

$profilePic = '/Web-App/frontend/assets/default-avatar.png';
$fullName = 'Guest';

if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT full_name, profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        $profilePic = !empty($user['profile_picture']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $user['profile_picture'])
            ? $user['profile_picture']
            : '/Web-App/frontend/assets/default-avatar.png';
        $fullName = htmlspecialchars($user['full_name']);
    }
}

// Filter
$search_location = $_GET['location'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_price = $_GET['price'] ?? '';

// Pagination
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 9;
$offset = ($current_page - 1) * $limit;

// Query utama
$query = "
    SELECT 
        k.id, k.name, k.description, k.city, k.province, k.address,
        k.kos_type, k.price_monthly, k.price_daily, k.total_rooms,
        k.available_rooms, k.created_at, u.full_name AS owner_name
    FROM kos k
    LEFT JOIN users u ON k.owner_id = u.id
    WHERE k.status = 'approved'
";

if (!empty($search_location)) {
    $query .= " AND (k.city LIKE ? OR k.province LIKE ? OR k.address LIKE ?)";
}
if (!empty($filter_type)) {
    $query .= " AND k.kos_type = ?";
}
if (!empty($filter_price)) {
    $query .= " AND k.price_monthly <= ?";
}
$query .= " ORDER BY k.created_at DESC LIMIT ? OFFSET ?";

// Count query
$count_query = "SELECT COUNT(*) as total FROM kos k WHERE k.status = 'approved'";
$count_params = [];
$count_types = "";

if (!empty($search_location)) {
    $count_query .= " AND (k.city LIKE ? OR k.province LIKE ? OR k.address LIKE ?)";
    $search_param = "%$search_location%";
    $count_params = array_merge($count_params, [$search_param, $search_param, $search_param]);
    $count_types .= "sss";
}
if (!empty($filter_type)) {
    $count_query .= " AND k.kos_type = ?";
    $count_params[] = $filter_type;
    $count_types .= "s";
}
if (!empty($filter_price)) {
    $count_query .= " AND k.price_monthly <= ?";
    $count_params[] = $filter_price;
    $count_types .= "i";
}

$count_stmt = $conn->prepare($count_query);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_properties = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_properties / $limit);
$count_stmt->close();

// Main query
$stmt = $conn->prepare($query);
$params = [];
$types = "";

if (!empty($search_location)) {
    $search_param = "%$search_location%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}
if (!empty($filter_type)) {
    $params[] = $filter_type;
    $types .= "s";
}
if (!empty($filter_price)) {
    $params[] = $filter_price;
    $types .= "i";
}
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$properties = [];

while ($row = $result->fetch_assoc()) {
    // Cek favorit
    $row['is_favorited'] = false;
    if ($isLoggedIn) {
        $fav_check = $conn->query("SELECT id FROM saved_kos WHERE user_id = $userId AND kos_id = {$row['id']} LIMIT 1");
        $row['is_favorited'] = ($fav_check && $fav_check->num_rows > 0);
    }

    // Ambil gambar
    $img_query = "SELECT image_url FROM kos_images WHERE kos_id = ? ORDER BY id ASC LIMIT 1";
    $img_stmt = $conn->prepare($img_query);
    $img_stmt->bind_param("i", $row['id']);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();
    $image = $img_result->fetch_assoc();
    $row['image'] = $image ? $image['image_url'] : null;
    $img_stmt->close();

    // Ambil fasilitas
    $fac_query = "SELECT f.name, f.icon FROM kos_facilities kf 
                  JOIN facilities f ON kf.facility_id = f.id 
                  WHERE kf.kos_id = ? LIMIT 3";
    $fac_stmt = $conn->prepare($fac_query);
    $fac_stmt->bind_param("i", $row['id']);
    $fac_stmt->execute();
    $fac_result = $fac_stmt->get_result();
    $row['facilities'] = [];
    while ($fac = $fac_result->fetch_assoc()) {
        $row['facilities'][] = $fac;
    }
    $fac_stmt->close();

    $properties[] = $row;
}
$stmt->close();
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Explore - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { background-color: #f8f9fa; }
    .search-filter { background: #fff; border-radius: 12px; padding: 15px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); margin-bottom: 20px; }
    .kost-card { border: none; border-radius: 12px; overflow: hidden; transition: 0.2s; height: 100%; display: flex; flex-direction: column; }
    .kost-card:hover { transform: translateY(-5px); box-shadow: 0 6px 16px rgba(0,0,0,0.12); }
    .kost-img { height: 200px; object-fit: cover; width: 100%; }
    .no-image { height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; flex-direction: column; color: white; }
    .no-image i { font-size: 3rem; margin-bottom: 10px; opacity: 0.7; }
    .kost-price { font-weight: bold; color: #28a745; font-size: 1.1rem; }
    .kost-type { font-size: 0.8rem; font-weight: bold; border-radius: 10px; padding: 3px 8px; margin-right: 5px; background: #e9f7ef; color: #28a745; display: inline-block; }
    .kost-type.putra { background: #e3f2fd; color: #1976d2; }
    .kost-type.putri { background: #fce4ec; color: #c2185b; }
    .kost-type.campur { background: #fff3e0; color: #f57c00; }
    .btn-detail { background: #28a745; border: none; color: white; font-weight: 600; padding: 8px 16px; border-radius: 8px; transition: 0.2s; text-decoration: none; display: inline-block; }
    .btn-detail:hover { background: #218838; color: white; }
    .btn-fav { border: 1.5px solid #dee2e6; color: #6c757d; border-radius: 8px; transition: all 0.3s ease; padding: 8px 12px; background: transparent; }
    .btn-fav:hover:not(:disabled) { background: #f8f9fa; border-color: #28a745; color: #28a745; transform: scale(1.05); }
    .btn-fav:disabled { opacity: 0.6; cursor: not-allowed; }
    .btn-fav.favorited { border-color: #dc3545; background: #fff5f5; }
    .btn-fav i { font-size: 1.1rem; transition: all 0.3s ease; }
    .facilities-list { margin: 10px 0; font-size: 0.85rem; color: #6c757d; }
    .card-body { flex-grow: 1; display: flex; flex-direction: column; }
    .property-description { color: #6c757d; font-size: 0.9rem; margin: 10px 0; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; flex-grow: 1; }
    .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 30px; flex-wrap: wrap; }
    .page-link { padding: 8px 12px; border: 1px solid #dee2e6; border-radius: 5px; color: #28a745; text-decoration: none; transition: 0.2s; }
    .page-link:hover { background: #28a745; color: white; }
    .page-link.active { background: #28a745; color: white; border-color: #28a745; }
    .empty-state { text-align: center; padding: 60px 20px; color: #6c757d; }
    .empty-state i { font-size: 4rem; margin-bottom: 20px; color: #dee2e6; }
    .rooms-badge { background: #f8f9fa; padding: 4px 8px; border-radius: 6px; font-size: 0.85rem; display: inline-block; margin-top: 5px; }
    .rooms-badge i { color: #28a745; }
    @keyframes fa-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .fa-spin { animation: fa-spin 1s infinite linear; }
  </style>
</head>
<body>

<?php include("navbar.php"); ?>

<div class="container my-4">
  <h4 class="fw-bold mb-4"><i class="bi bi-compass"></i> Explore Kost</h4>

  <div class="search-filter">
    <form method="GET" action="" class="d-flex flex-wrap gap-2 align-items-center">
      <input type="text" name="location" class="form-control flex-grow-1" 
             placeholder="Cari berdasarkan kota, provinsi, atau alamat..." 
             value="<?php echo htmlspecialchars($search_location); ?>">
      
      <select name="type" class="form-select" style="max-width:200px;">
        <option value="">Semua Tipe Kos</option>
        <option value="putra" <?php echo $filter_type === 'putra' ? 'selected' : ''; ?>>Putra</option>
        <option value="putri" <?php echo $filter_type === 'putri' ? 'selected' : ''; ?>>Putri</option>
        <option value="campur" <?php echo $filter_type === 'campur' ? 'selected' : ''; ?>>Campur</option>
      </select>
      
      <select name="price" class="form-select" style="max-width:180px;">
        <option value="">Semua Harga</option>
        <option value="500000" <?php echo $filter_price === '500000' ? 'selected' : ''; ?>>≤ Rp 500.000</option>
        <option value="1000000" <?php echo $filter_price === '1000000' ? 'selected' : ''; ?>>≤ Rp 1.000.000</option>
        <option value="2000000" <?php echo $filter_price === '2000000' ? 'selected' : ''; ?>>≤ Rp 2.000.000</option>
        <option value="5000000" <?php echo $filter_price === '5000000' ? 'selected' : ''; ?>>≤ Rp 5.000.000</option>
      </select>
      
      <button type="submit" class="btn btn-success"><i class="bi bi-search"></i> Cari</button>
      
      <?php if (!empty($search_location) || !empty($filter_type) || !empty($filter_price)): ?>
        <a href="explore.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <?php if (!empty($search_location) || !empty($filter_type) || !empty($filter_price)): ?>
        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Ditemukan <?php echo $total_properties; ?> kos</span>
      <?php else: ?>
        <span class="text-muted">Menampilkan <?php echo min($limit, $total_properties); ?> dari <?php echo $total_properties; ?> kos</span>
      <?php endif; ?>
    </div>
    <div class="text-muted small">Halaman <?php echo $current_page; ?> dari <?php echo max(1, $total_pages); ?></div>
  </div>

  <div class="row g-4">
    <?php if (empty($properties)): ?>
      <div class="col-12">
        <div class="empty-state">
          <i class="bi bi-inbox"></i>
          <h3>Tidak ada kos ditemukan</h3>
          <p>Coba ubah filter pencarian Anda</p>
          <a href="explore.php" class="btn btn-success mt-3"><i class="bi bi-arrow-clockwise"></i> Reset Filter</a>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($properties as $property): ?>
        <div class="col-md-4">
          <div class="card kost-card shadow-sm">
            <?php if (!empty($property['image'])): ?>
              <img src="<?php echo htmlspecialchars('/Web-App/' . $property['image']); ?>" 
                   class="kost-img" alt="<?php echo htmlspecialchars($property['name']); ?>"
                   onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'no-image\'><i class=\'bi bi-image\'></i><p>Gambar tidak tersedia</p></div>';">
            <?php else: ?>
              <div class="no-image"><i class="bi bi-building"></i><p>No Image Available</p></div>
            <?php endif; ?>
            
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="kost-type <?php echo $property['kos_type']; ?>">
                  <?php echo ucfirst($property['kos_type']); ?>
                </span>
                <div class="text-end">
                  <div class="kost-price">Rp <?php echo number_format($property['price_monthly'], 0, ',', '.'); ?></div>
                  <small class="text-muted">per bulan</small>
                </div>
              </div>
              
              <h6 class="mt-2 mb-1 fw-bold"><?php echo htmlspecialchars($property['name']); ?></h6>
              <small class="text-muted">
                <i class="bi bi-geo-alt-fill"></i> 
                <?php echo htmlspecialchars($property['city'] . ', ' . $property['province']); ?>
              </small>
              
              <?php if (!empty($property['description'])): ?>
                <p class="property-description"><?php echo htmlspecialchars($property['description']); ?></p>
              <?php endif; ?>
              
              <?php if (!empty($property['facilities'])): ?>
                <div class="facilities-list">
                  <?php foreach ($property['facilities'] as $facility): ?>
                    <small class="me-2">
                      <i class="fa <?php echo htmlspecialchars($facility['icon']); ?>"></i>
                      <?php echo htmlspecialchars($facility['name']); ?>
                    </small>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              
              <div class="rooms-badge">
                <i class="bi bi-door-open"></i> 
                <strong><?php echo $property['available_rooms']; ?></strong> dari 
                <strong><?php echo $property['total_rooms']; ?></strong> kamar tersedia
              </div>
              
              <div class="d-flex justify-content-between mt-auto pt-3">
                <button class="btn btn-detail btn-sm" onclick="viewDetail(<?php echo $property['id']; ?>)">
                  <i class="bi bi-eye"></i> Detail
                </button>
                <button class="btn btn-fav btn-sm <?php echo $property['is_favorited'] ? 'favorited' : ''; ?>" 
                        id="fav-btn-<?php echo $property['id']; ?>"
                        onclick="toggleFavorite(<?php echo $property['id']; ?>, this)">
                  <i class="bi <?php echo $property['is_favorited'] ? 'bi-heart-fill' : 'bi-heart'; ?>" 
                     style="<?php echo $property['is_favorited'] ? 'color: #dc3545;' : ''; ?>"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php if ($current_page > 1): ?>
        <a href="?page=<?php echo $current_page - 1; ?>&location=<?php echo urlencode($search_location); ?>&type=<?php echo urlencode($filter_type); ?>&price=<?php echo urlencode($filter_price); ?>" class="page-link">
          <i class="bi bi-chevron-left"></i>
        </a>
      <?php endif; ?>

      <?php
      $start_page = max(1, $current_page - 2);
      $end_page = min($total_pages, $current_page + 2);
      
      if ($start_page > 1) {
          echo '<a href="?page=1&location=' . urlencode($search_location) . '&type=' . urlencode($filter_type) . '&price=' . urlencode($filter_price) . '" class="page-link">1</a>';
          if ($start_page > 2) echo '<span class="page-link disabled">...</span>';
      }
      
      for ($i = $start_page; $i <= $end_page; $i++):
      ?>
        <a href="?page=<?php echo $i; ?>&location=<?php echo urlencode($search_location); ?>&type=<?php echo urlencode($filter_type); ?>&price=<?php echo urlencode($filter_price); ?>" 
           class="page-link <?php echo $i === $current_page ? 'active' : ''; ?>">
          <?php echo $i; ?>
        </a>
      <?php 
      endfor;
      
      if ($end_page < $total_pages) {
          if ($end_page < $total_pages - 1) echo '<span class="page-link disabled">...</span>';
          echo '<a href="?page=' . $total_pages . '&location=' . urlencode($search_location) . '&type=' . urlencode($filter_type) . '&price=' . urlencode($filter_price) . '" class="page-link">' . $total_pages . '</a>';
      }
      ?>

      <?php if ($current_page < $total_pages): ?>
        <a href="?page=<?php echo $current_page + 1; ?>&location=<?php echo urlencode($search_location); ?>&type=<?php echo urlencode($filter_type); ?>&price=<?php echo urlencode($filter_price); ?>" class="page-link">
          <i class="bi bi-chevron-right"></i>
        </a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Modal Detail -->
<div id="detailModal" class="modal fade" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Kos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detailContent">
        <div class="text-center py-5">
          <div class="spinner-border text-success" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-3">Memuat data...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// ========================================
// TOGGLE FAVORITE
// ========================================
function toggleFavorite(kosId, btn) {
  <?php if ($isLoggedIn): ?>
    const icon = btn.querySelector('i');
    const isFavorited = icon.classList.contains('bi-heart-fill');

    icon.className = 'bi bi-arrow-repeat fa-spin';
    btn.disabled = true;

    fetch('/Web-App/backend/user/customer/classes/save_kos.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ kos_id: kosId })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        if (data.favorited) {
          icon.className = 'bi bi-heart-fill';
          icon.style.color = '#dc3545';
          btn.classList.add('favorited');
        } else {
          icon.className = 'bi bi-heart';
          icon.style.color = '';
          btn.classList.remove('favorited');
        }
        
        // Notifikasi simple
        const toast = document.createElement('div');
        toast.className = 'alert alert-success position-fixed top-0 end-0 m-3';
        toast.style.zIndex = '9999';
        toast.textContent = data.message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
      } else {
        alert(data.message || 'Gagal memperbarui wishlist');
        icon.className = isFavorited ? 'bi bi-heart-fill' : 'bi bi-heart';
      }
      btn.disabled = false;
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan koneksi');
      btn.disabled = false;
      icon.className = isFavorited ? 'bi bi-heart-fill' : 'bi bi-heart';
    });
  <?php else: ?>
    if (confirm('Anda harus login untuk menambahkan ke wishlist. Login sekarang?')) {
      window.location.href = '/Web-App/frontend/auth/login.php';
    }
  <?php endif; ?>
}

// ========================================
// VIEW DETAIL
// ========================================
function viewDetail(kosId) {
  const modal = new bootstrap.Modal(document.getElementById('detailModal'));
  const content = document.getElementById('detailContent');
  
  modal.show();
  
  // Loading state
  content.innerHTML = `
    <div class="text-center py-5">
      <div class="spinner-border text-success" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-3">Memuat data kos...</p>
    </div>
  `;
  
  // Fetch detail kos
  fetch(`/Web-App/backend/user/customer/classes/get_kos_detail.php?id=${kosId}`)
    .then(res => res.text())
    .then(html => {
      content.innerHTML = html;
      initImageSlider();
    })
    .catch(err => {
      console.error(err);
      content.innerHTML = `
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle"></i>
          Gagal memuat detail kos. Silakan coba lagi.
        </div>
      `;
    });
}

// ========================================
// IMAGE SLIDER
// ========================================
function initImageSlider() {
  const sliders = document.querySelectorAll('.image-slider');
  
  sliders.forEach(slider => {
    const images = slider.querySelectorAll('.slider-image');
    const prevBtn = slider.querySelector('.prev-btn');
    const nextBtn = slider.querySelector('.next-btn');
    const counter = slider.querySelector('.current-image');
    let current = 0;
    
    if (images.length === 0) return;
    
    function updateSlider() {
      images.forEach((img, i) => {
        img.style.display = i === current ? 'block' : 'none';
      });
      if (counter) counter.textContent = current + 1;
    }
    
    if (prevBtn && nextBtn) {
      prevBtn.onclick = (e) => {
        e.preventDefault();
        current = current === 0 ? images.length - 1 : current - 1;
        updateSlider();
      };
      
      nextBtn.onclick = (e) => {
        e.preventDefault();
        current = current === images.length - 1 ? 0 : current + 1;
        updateSlider();
      };
    }
    
    updateSlider();
  });
}
</script>


</body>
</html>