<?php
// Cek apakah session sudah dimulai, jika belum baru start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: /Web-App/frontend/auth/login.php");
    exit;
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

$userId = $_SESSION['user_id'];

// Ambil data wishlist dari tabel saved_kos dengan query yang lebih simple
$query = "
    SELECT 
        k.id,
        k.name,
        k.kos_type,
        k.price_monthly,
        k.description,
        k.city,
        k.province,
        k.address,
        k.available_rooms,
        k.total_rooms,
        sk.created_at,
        (SELECT image_url FROM kos_images WHERE kos_id = k.id ORDER BY id ASC LIMIT 1) AS image_url
    FROM saved_kos sk
    INNER JOIN kos k ON sk.kos_id = k.id
    WHERE sk.user_id = ?
    ORDER BY sk.created_at DESC
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $userId);

if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}

$result = $stmt->get_result();
$wishlist = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Debug (hapus setelah selesai debugging)
// echo "<!-- User ID: $userId, Total wishlist: " . count($wishlist) . " -->";
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Wishlist - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    /* CSS tetap sama seperti sebelumnya */
    body {
      background-color: #f8f9fa;
      padding-top: 80px;
    }

    .wishlist-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .wishlist-count {
      background: #28a745;
      color: white;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 600;
    }

    .wishlist-card {
      border-radius: 12px;
      overflow: hidden;
      display: flex;
      margin-bottom: 1rem;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      background: #fff;
      min-height: 200px;
      opacity: 0;
      animation: fadeInUp 0.6s ease forwards;
      transition: all 0.3s ease;
      position: relative;
    }

    .wishlist-card:nth-child(1) { animation-delay: 0.1s; }
    .wishlist-card:nth-child(2) { animation-delay: 0.2s; }
    .wishlist-card:nth-child(3) { animation-delay: 0.3s; }
    .wishlist-card:nth-child(4) { animation-delay: 0.4s; }
    .wishlist-card:nth-child(5) { animation-delay: 0.5s; }

    .wishlist-card:hover {
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      transform: translateY(-5px);
    }

    .wishlist-info {
      flex: 3;
      padding: 1.5rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .wishlist-img {
      flex: 2;
      border-left: 1px solid #dee2e6;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f8f9fa;
      overflow: hidden;
      position: relative;
      min-width: 300px;
      max-width: 400px;
      height: 250px;
    }

    .wishlist-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center;
      transition: transform 0.4s ease;
    }

    .wishlist-card:hover img {
      transform: scale(1.08);
    }

    .kos-type-badge {
      font-size: 0.75rem;
      font-weight: bold;
      border-radius: 10px;
      padding: 4px 10px;
      display: inline-block;
      margin-bottom: 8px;
    }

    .kos-type-badge.putra {
      background: #e3f2fd;
      color: #1976d2;
    }

    .kos-type-badge.putri {
      background: #fce4ec;
      color: #c2185b;
    }

    .kos-type-badge.campur {
      background: #fff3e0;
      color: #f57c00;
    }

    .wishlist-info .kos-name {
      font-size: 1.1rem;
      font-weight: bold;
      margin-bottom: 8px;
      color: #212529;
    }

    .wishlist-info .location {
      color: #6c757d;
      font-size: 0.9rem;
      margin-bottom: 10px;
    }

    .wishlist-info .location i {
      color: #28a745;
    }

    .wishlist-info .description {
      color: #6c757d;
      font-size: 0.85rem;
      margin: 10px 0;
      overflow: hidden;
      text-overflow: ellipsis;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      line-height: 1.4;
    }

    .wishlist-info .price {
      font-weight: bold;
      font-size: 1.2rem;
      color: #28a745;
      margin: 0;
    }

    .wishlist-info .price small {
      font-size: 0.75rem;
      color: #6c757d;
      font-weight: normal;
    }

    .rooms-info {
      background: #f8f9fa;
      padding: 6px 10px;
      border-radius: 6px;
      font-size: 0.85rem;
      display: inline-block;
      margin-top: 8px;
    }

    .rooms-info i {
      color: #28a745;
    }

    .action-buttons {
      display: flex;
      gap: 8px;
      margin-top: 12px;
    }

    .btn-remove {
      background: transparent;
      border: 1.5px solid #dc3545;
      color: #dc3545;
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 600;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .btn-remove:hover {
      background: #dc3545;
      color: white;
      transform: scale(1.05);
    }

    .btn-view {
      background: #28a745;
      border: none;
      color: white;
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 600;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 6px;
      text-decoration: none;
    }

    .btn-view:hover {
      background: #218838;
      color: white;
      transform: scale(1.05);
    }

    .empty-state {
      text-align: center;
      padding: 80px 20px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }

    .empty-state i {
      font-size: 5rem;
      color: #dee2e6;
      margin-bottom: 20px;
      animation: heartbeat 1.5s ease-in-out infinite;
    }

    .empty-state h5 {
      color: #495057;
      margin-bottom: 10px;
    }

    .empty-state p {
      color: #6c757d;
      margin-bottom: 24px;
    }

    @keyframes fadeInUp {
      from { 
        opacity: 0; 
        transform: translateY(30px); 
      }
      to { 
        opacity: 1; 
        transform: translateY(0); 
      }
    }

    @keyframes heartbeat {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }

    @keyframes slideOut {
      from {
        opacity: 1;
        transform: translateX(0);
      }
      to {
        opacity: 0;
        transform: translateX(100%);
      }
    }

    .removing {
      animation: slideOut 0.4s ease forwards;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .wishlist-card {
        flex-direction: column;
      }

      .wishlist-img {
        border-left: none;
        border-top: 1px solid #dee2e6;
        min-height: 200px;
      }

      .action-buttons {
        flex-direction: column;
      }

      .btn-remove, .btn-view {
        justify-content: center;
      }
    }

    /* Toast Notification */
    .toast-notification {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: white;
      padding: 16px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 9999;
      display: flex;
      align-items: center;
      gap: 12px;
      animation: slideInRight 0.3s ease;
      max-width: 350px;
    }

    .toast-notification.success {
      border-left: 4px solid #28a745;
    }

    .toast-notification.error {
      border-left: 4px solid #dc3545;
    }

    .toast-notification i {
      font-size: 1.5rem;
    }

    .toast-notification.success i {
      color: #28a745;
    }

    .toast-notification.error i {
      color: #dc3545;
    }

    @keyframes slideInRight {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    @keyframes slideOutRight {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(400px);
        opacity: 0;
      }
    }
  </style>
</head>
<body>

<?php include("navbar.php"); ?>

<div class="container my-4">
  <div class="wishlist-header">
    <h4 class="fw-bold mb-0">
      <i class="bi bi-heart-fill text-danger"></i> Wishlist Kamu
    </h4>
    <?php if (!empty($wishlist)): ?>
      <span class="wishlist-count">
        <?php echo count($wishlist); ?> Kos
      </span>
    <?php endif; ?>
  </div>

  <?php if (empty($wishlist)): ?>
    <div class="empty-state">
      <i class="bi bi-heart"></i>
      <h5>Belum ada kos di wishlist</h5>
      <p>Yuk, tambahkan kos favoritmu dari halaman Explore!</p>
      <a href="/Web-App/frontend/user/customer/explore.php" class="btn btn-success btn-lg">
        <i class="bi bi-compass"></i> Jelajahi Kos Sekarang
      </a>
    </div>
  <?php else: ?>
    <div id="wishlist-container">
      <?php foreach ($wishlist as $item): ?>
        <div class="wishlist-card" id="wishlist-<?php echo $item['id']; ?>" data-kos-id="<?php echo $item['id']; ?>">
          <div class="wishlist-info">
            <div>
              <span class="kos-type-badge <?php echo $item['kos_type']; ?>">
                <?php 
                  $type_icons = [
                    'putra' => '👨',
                    'putri' => '👩',
                    'campur' => '👥'
                  ];
                  echo ($type_icons[$item['kos_type']] ?? '') . ' ' . ucfirst($item['kos_type']); 
                ?>
              </span>
              <h5 class="kos-name"><?= htmlspecialchars($item['name']); ?></h5>
              <p class="location">
                <i class="bi bi-geo-alt-fill"></i> 
                <?= htmlspecialchars($item['city'] . ', ' . $item['province']); ?>
              </p>
              
              <?php if (!empty($item['description'])): ?>
                <p class="description">
                  <?= htmlspecialchars($item['description']); ?>
                </p>
              <?php endif; ?>
              
              <?php if ($item['total_rooms'] > 0): ?>
                <div class="rooms-info">
                  <i class="bi bi-door-open-fill"></i>
                  <strong><?php echo $item['available_rooms']; ?></strong> dari 
                  <strong><?php echo $item['total_rooms']; ?></strong> kamar tersedia
                </div>
              <?php endif; ?>
            </div>
            
            <div>
              <p class="price">
                Rp <?= number_format($item['price_monthly'], 0, ',', '.'); ?> 
                <small>/ bulan</small>
              </p>
              <div class="action-buttons">
                <a href="/Web-App/frontend/user/customer/explore.php" class="btn-view">
                  <i class="bi bi-eye-fill"></i> Lihat Detail
                </a>
                <button class="btn-remove" onclick="removeFromWishlist(<?php echo $item['id']; ?>)">
                  <i class="bi bi-trash-fill"></i> Hapus
                </button>
              </div>
            </div>
          </div>
          
          <div class="wishlist-img">
            <?php if (!empty($item['image_url'])): ?>
              <img src="<?php echo htmlspecialchars('/Web-App/' . $item['image_url']); ?>" 
                   alt="<?= htmlspecialchars($item['name']); ?>"
                   onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'text-center text-muted p-5\'><i class=\'bi bi-image fs-1\'></i><p class=\'mt-2\'>Gambar tidak tersedia</p></div>';">
            <?php else: ?>
              <div class="text-center text-muted p-5">
                <i class="bi bi-image fs-1"></i>
                <p class="mt-2">Tidak ada gambar</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ========================================
// FUNGSI NOTIFIKASI
// ========================================
function showNotification(message, type = 'success') {
  const oldNotif = document.querySelector('.toast-notification');
  if (oldNotif) {
    oldNotif.remove();
  }
  
  const icons = {
    success: 'bi-check-circle-fill',
    error: 'bi-exclamation-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
    info: 'bi-info-circle-fill'
  };
  
  const titles = {
    success: 'Berhasil!',
    error: 'Error!',
    warning: 'Peringatan!',
    info: 'Info'
  };
  
  const iconClass = icons[type] || icons.success;
  
  const notif = document.createElement('div');
  notif.className = `toast-notification ${type}`;
  notif.innerHTML = `
    <i class="bi ${iconClass}"></i>
    <div>
      <strong>${titles[type]}</strong>
      <p style="margin: 0; font-size: 0.9rem; color: #6c757d;">${message}</p>
    </div>
  `;
  
  document.body.appendChild(notif);
  
  setTimeout(() => {
    notif.style.animation = 'slideOutRight 0.3s ease';
    setTimeout(() => notif.remove(), 300);
  }, 3000);
}

// ========================================
// FUNGSI REMOVE FROM WISHLIST
// ========================================
function removeFromWishlist(kosId) {
  if (!confirm('Apakah Anda yakin ingin menghapus kos ini dari wishlist?')) {
    return;
  }
  
  const card = document.getElementById('wishlist-' + kosId);
  const removeBtn = card.querySelector('.btn-remove');
  const originalHTML = removeBtn.innerHTML;
  removeBtn.disabled = true;
  removeBtn.innerHTML = '<i class="bi bi-arrow-repeat fa-spin"></i> Menghapus...';
  
  fetch('/Web-App/backend/user/customer/classes/save_kos.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ kos_id: kosId })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      card.classList.add('removing');
      
      setTimeout(() => {
        card.remove();
        updateWishlistCount();
        
        const remainingCards = document.querySelectorAll('.wishlist-card');
        if (remainingCards.length === 0) {
          showEmptyState();
        }
        
        showNotification(data.message, 'success');
      }, 400);
    } else {
      removeBtn.disabled = false;
      removeBtn.innerHTML = originalHTML;
      showNotification(data.message || 'Gagal menghapus kos dari wishlist', 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    removeBtn.disabled = false;
    removeBtn.innerHTML = originalHTML;
    showNotification('Terjadi kesalahan saat menghapus kos', 'error');
  });
}

function updateWishlistCount() {
  const remainingCards = document.querySelectorAll('.wishlist-card');
  const countBadge = document.querySelector('.wishlist-count');
  if (countBadge) {
    countBadge.textContent = remainingCards.length + ' Kos';
  }
}

function showEmptyState() {
  const container = document.getElementById('wishlist-container');
  if (container) {
    container.innerHTML = `
      <div class="empty-state">
        <i class="bi bi-heart"></i>
        <h5>Wishlist kamu kosong</h5>
        <p>Sepertinya kamu sudah menghapus semua kos dari wishlist</p>
        <a href="/Web-App/frontend/user/customer/explore.php" class="btn btn-success btn-lg">
          <i class="bi bi-compass"></i> Jelajahi Kos Sekarang
        </a>
      </div>
    `;
  }
  
  const countBadge = document.querySelector('.wishlist-count');
  if (countBadge) {
    countBadge.remove();
  }
}

// Smooth scroll
window.addEventListener('load', () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
});
</script>

</body>
</html>