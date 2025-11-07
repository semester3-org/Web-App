<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user';

$kos_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($kos_id == 0) {
    header("Location: explore.php");
    exit();
}

// Fetch property data dengan JOIN
$sql = "SELECT k.*, u.full_name as owner_name, u.phone as owner_phone,
        (SELECT COUNT(*) FROM saved_kos WHERE kos_id = k.id) as total_saved,
        (SELECT AVG(rating) FROM reviews WHERE kos_id = k.id) as avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE kos_id = k.id) as total_reviews
        FROM kos k 
        LEFT JOIN users u ON k.owner_id = u.id
        WHERE k.id = ? AND k.status = 'approved'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $kos_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: explore.php");
    exit();
}

$property = $result->fetch_assoc();

// Check if favorited
$is_favorited = false;
if ($isLoggedIn) {
    $fav_check = $conn->query("SELECT id FROM saved_kos WHERE user_id = {$_SESSION['user_id']} AND kos_id = {$kos_id} LIMIT 1");
    $is_favorited = ($fav_check && $fav_check->num_rows > 0);
}

// Fetch images
$sql_images = "SELECT image_url FROM kos_images WHERE kos_id = ? ORDER BY id";
$stmt_images = $conn->prepare($sql_images);
$stmt_images->bind_param("i", $kos_id);
$stmt_images->execute();
$images = $stmt_images->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch facilities dengan nama
$sql_facilities = "SELECT f.name, f.icon, f.category 
                   FROM kos_facilities kf 
                   JOIN facilities f ON kf.facility_id = f.id 
                   WHERE kf.kos_id = ?
                   ORDER BY f.category";
$stmt_facilities = $conn->prepare($sql_facilities);
$stmt_facilities->bind_param("i", $kos_id);
$stmt_facilities->execute();
$facilities = $stmt_facilities->get_result()->fetch_all(MYSQLI_ASSOC);

// Group facilities by category
$grouped_facilities = [];
foreach ($facilities as $facility) {
    $grouped_facilities[$facility['category']][] = $facility;
}

// Parse rules
$rules = [];
if (!empty($property['rules'])) {
    $rules = json_decode($property['rules'], true);
}

// Fetch reviews dengan user info
$sql_reviews = "SELECT r.*, u.full_name, u.profile_picture 
                FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.kos_id = ? 
                ORDER BY r.created_at DESC";
$stmt_reviews = $conn->prepare($sql_reviews);
$stmt_reviews->bind_param("i", $kos_id);
$stmt_reviews->execute();
$reviews = $stmt_reviews->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['name']); ?> - KostHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { background-color: #f8f9fa; }
        .detail-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .property-name { font-size: 1.8rem; font-weight: bold; color: #212529; margin-bottom: 8px; }
        .property-location { color: #6c757d; font-size: 1rem; }
        .property-price { font-size: 1.8rem; font-weight: bold; color: #28a745; }
        .stats-row { display: flex; gap: 20px; flex-wrap: wrap; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .stat-item { display: flex; align-items: center; gap: 10px; }
        .stat-item i { font-size: 1.5rem; color: #28a745; }
        .stat-item strong { display: block; font-size: 1.1rem; }
        .stat-item small { color: #6c757d; font-size: 0.85rem; }
        .section-title { font-weight: bold; margin-bottom: 15px; color: #212529; }
        .section-title i { color: #28a745; margin-right: 8px; }
        .facility-category { font-weight: 600; color: #495057; margin-bottom: 10px; font-size: 0.95rem; }
        .facility-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
        .facility-item { display: flex; align-items: center; gap: 8px; padding: 8px; background: #f8f9fa; border-radius: 6px; }
        .facility-item i { color: #28a745; }
        .rules-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; }
        .rule-item { display: flex; align-items: center; gap: 8px; padding: 10px; background: #f8f9fa; border-radius: 6px; }
        .rule-item i { color: #28a745; font-size: 1.2rem; }
        .main-image { width: 100%; height: 400px; object-fit: cover; cursor: pointer; }
        .thumbnail-image { width: 100%; height: 120px; object-fit: cover; cursor: pointer; }
        .overlay-more { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; cursor: pointer; border-radius: 8px; }
        .review-avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
        .review-item { padding: 15px; border-bottom: 1px solid #e9ecef; }
        .review-item:last-child { border-bottom: none; }
        .review-rating { color: #ffc107; margin: 5px 0; }
        .review-comment { margin: 10px 0; color: #495057; }
        .btn-favorite { transition: all 0.3s ease; }
        .btn-favorite.favorited { background: #fff5f5; border-color: #dc3545; color: #dc3545; }
        .btn-favorite.favorited i { color: #dc3545; }
        .contact-owner { background: #e8f5e9; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        .kos-type { font-size: 0.9rem; font-weight: bold; border-radius: 10px; padding: 5px 12px; display: inline-block; }
        .kos-type.putra { background: #e3f2fd; color: #1976d2; }
        .kos-type.putri { background: #fce4ec; color: #c2185b; }
        .kos-type.campur { background: #fff3e0; color: #f57c00; }
        @keyframes fa-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .fa-spin { animation: fa-spin 1s infinite linear; }
        .rating {
        direction: rtl;
        unicode-bidi: bidi-override;
        }
        .rating input {
        display: none;
        }
        .rating label {
        font-size: 2rem;
        color: #ccc;
        cursor: pointer;
        transition: color 0.2s;
        }
        .rating input:checked ~ label,
        .rating label:hover,
        .rating label:hover ~ label {
        color: #ffc107;
        }
        .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #198754; /* default hijau sukses */
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        opacity: 0;
        transform: translateY(-20px);
        transition: all 0.4s ease;
        z-index: 9999;
        pointer-events: none;
        font-weight: 500;
        }
        .notification.show {
        opacity: 1;
        transform: translateY(0);
        }
        .notification.error {
        background-color: #dc3545; /* merah untuk gagal */
        }

    </style>
</head>
<body>


<div class="container my-4">
   <!-- Header -->
<div class="header-bar py-3 d-flex align-items-center justify-content-center position-relative">
    <button type="button" class="btn btn-outline-success position-absolute start-0 ms-3 d-flex align-items-center"
        onclick="window.location.href='explore.php'">
        <i class="bi bi-arrow-left"></i>
    </button>

    <div class="d-flex align-items-center text-center">
        <img src="../../assets/logo_kos.png" alt="logo" style="height:40px;" class="me-2">
        <h2 class="fw-bold m-0">KostHub</h2>
    </div>
</div>

    <!-- Image Gallery -->
    <div class="row g-2 mb-4">
        <?php if (count($images) > 0): ?>
            <div class="col-md-8">
                <img src="<?php echo htmlspecialchars('/Web-App/' . $images[0]['image_url']); ?>"
                    class="img-fluid rounded main-image"
                    alt="Main"
                    onerror="this.src='/Web-App/frontend/assets/default-kos.jpg'">
            </div>
            <div class="col-md-4">
                <div class="row g-2">
                    <?php for ($i = 1; $i < min(4, count($images)); $i++): ?>
                        <div class="col-6">
                            <img src="<?php echo htmlspecialchars('/Web-App/' . $images[$i]['image_url']); ?>"
                                class="img-fluid rounded thumbnail-image"
                                alt="Thumbnail"
                                onerror="this.src='/Web-App/frontend/assets/default-kos.jpg'">
                        </div>
                    <?php endfor; ?>
                    <?php if (count($images) > 4): ?>
                        <div class="col-6 position-relative">
                            <img src="<?php echo htmlspecialchars('/Web-App/' . $images[4]['image_url']); ?>"
                                class="img-fluid rounded thumbnail-image"
                                alt="Thumbnail">
                            <div class="overlay-more">
                                <span>+<?php echo count($images) - 4; ?> Foto</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="col-12">
                <div class="main-image d-flex align-items-center justify-content-center bg-secondary text-white">
                    <div class="text-center">
                        <i class="bi bi-image" style="font-size: 4rem;"></i>
                        <p class="mt-2">Tidak ada gambar tersedia</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Title & Stats -->
            <div class="detail-card mb-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <span class="kos-type <?php echo $property['kos_type']; ?> mb-2">
                            <?php echo ucfirst($property['kos_type']); ?>
                        </span>
                        <h2 class="property-name"><?php echo htmlspecialchars($property['name']); ?></h2>
                        <p class="property-location">
                            <i class="bi bi-geo-alt-fill text-success"></i>
                            <?php echo htmlspecialchars($property['address'] . ', ' . $property['city'] . ', ' . $property['province']); ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <div class="property-price">Rp <?php echo number_format($property['price_monthly'], 0, ',', '.'); ?></div>
                        <small class="text-muted">per bulan</small>
                    </div>
                </div>

                <div class="stats-row mt-3">
                    <div class="stat-item">
                        <i class="bi bi-door-closed-fill"></i>
                        <div>
                            <strong><?php echo $property['available_rooms']; ?>/<?php echo $property['total_rooms']; ?></strong>
                            <small>Kamar Tersedia</small>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="bi bi-star-fill"></i>
                        <div>
                            <strong><?php echo $property['avg_rating'] ? number_format($property['avg_rating'], 1) : 'N/A'; ?></strong>
                            <small><?php echo $property['total_reviews']; ?> Review</small>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="bi bi-heart-fill"></i>
                        <div>
                            <strong><?php echo $property['total_saved']; ?></strong>
                            <small>Disimpan</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="detail-card mb-4">
                <h5 class="section-title"><i class="bi bi-info-circle-fill"></i> Deskripsi Property</h5>
                <p style="color: #495057; line-height: 1.8;"><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
            </div>

            <!-- Facilities -->
            <?php if (!empty($facilities)): ?>
                <div class="detail-card mb-4">
                    <h5 class="section-title"><i class="bi bi-star-fill"></i> Fasilitas</h5>

                    <?php foreach ($grouped_facilities as $category => $items): ?>
                        <div class="mb-3">
                            <h6 class="facility-category"><?php echo ucfirst($category); ?></h6>
                            <div class="facility-grid">
                                <?php foreach ($items as $facility): ?>
                                    <div class="facility-item">
                                        <i class="fa <?php echo htmlspecialchars($facility['icon']); ?>"></i>
                                        <?php echo htmlspecialchars($facility['name']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Rules -->
            <?php if (!empty($rules)): ?>
                <div class="detail-card mb-4">
                    <h5 class="section-title"><i class="bi bi-shield-fill-check"></i> Aturan Kos</h5>
                    <div class="rules-grid">
                        <?php
                        $rule_labels = [
                            'tidak_merokok' => 'Tidak Boleh Merokok',
                            'tidak_membawa_hewan' => 'Tidak Boleh Membawa Hewan Peliharaan',
                            'tidak_berisik' => 'Tidak Boleh Berisik Setelah Jam 10 Malam',
                            'tamu_sampai_21' => 'Tamu Hanya Sampai Pukul 21.00',
                            'tamu_tidak_menginap' => 'Tamu Tidak Boleh Menginap',
                            'tamu_lawan_jenis' => 'Tamu Lawan Jenis Dilarang Masuk Kamar',
                            'jaga_kebersihan' => 'Wajib Menjaga Kebersihan Kamar',
                            'larangan_cuci_umum' => 'Dilarang Mencuci di Kamar Mandi Umum',
                            'modifikasi_fasilitas' => 'Dilarang Memodifikasi Fasilitas Kos',
                            'kunci_gerbang' => 'Wajib Mengunci Pintu Gerbang Setelah Jam 22.00',
                            'kompor_menyala' => 'Tidak Boleh Meninggalkan Kompor Menyala',
                            'lapor_kehilangan' => 'Wajib Lapor Jika Kehilangan Barang',
                            'bayar_tepat_waktu' => 'Pembayaran Wajib Sebelum Tanggal 5 Setiap Bulan',
                            'denda_keterlambatan' => 'Denda Keterlambatan Rp50.000 per Hari',
                            'deposit_hangus' => 'Deposit Hangus Jika Keluar Sebelum Kontrak Habis'
                        ];

                        foreach ($rules as $rule):
                            $label = isset($rule_labels[$rule]) ? $rule_labels[$rule] : $rule;
                        ?>
                            <div class="rule-item">
                                <i class="bi bi-check2-circle"></i>
                                <?php echo htmlspecialchars($label); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Map -->
            <div class="detail-card mb-4">
                <h5 class="section-title"><i class="bi bi-map-fill"></i> Lokasi</h5>
                <div id="detailMap" style="height: 300px; border-radius: 10px;"></div>
            </div>

            <!-- Reviews -->
<div class="detail-card mb-4">
    <h5 class="section-title mb-3">
        <i class="bi bi-chat-left-quote-fill"></i> Ulasan & Penilaian
    </h5>

    <?php
    $avg_rating = $property['avg_rating'] ? number_format($property['avg_rating'], 1) : 0;
    $rating_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    foreach ($reviews as $r) {
        $rating_counts[$r['rating']]++;
    }
    $total_reviews = array_sum($rating_counts);
    ?>

    <!-- Rating Summary -->
    <div class="row align-items-center mb-4">
        <div class="col-md-4 text-center">
            <h1 class="display-4 fw-bold text-warning mb-0"><?php echo $avg_rating; ?></h1>
            <div class="text-warning fs-5 mb-2">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi <?php echo ($i <= round($avg_rating)) ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                <?php endfor; ?>
            </div>
            <p class="text-muted"><?php echo $total_reviews; ?> total ulasan</p>
        </div>
        <div class="col-md-8">
            <?php for ($i = 5; $i >= 1; $i--): 
                $percentage = $total_reviews ? ($rating_counts[$i] / $total_reviews * 100) : 0;
            ?>
            <div class="d-flex align-items-center mb-1">
                <div class="me-2" style="width: 40px;"><?php echo $i; ?> <i class="bi bi-star-fill text-warning"></i></div>
                <div class="progress flex-grow-1" style="height: 10px;">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $percentage; ?>%"></div>
                </div>
                <div class="ms-2 text-muted" style="width: 40px;"><?php echo $rating_counts[$i]; ?></div>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Filter -->
    <div class="d-flex flex-wrap gap-2 mb-3">
        <button class="btn btn-outline-secondary btn-sm active" onclick="filterReviews(0)">Semua</button>
        <?php for ($i = 5; $i >= 1; $i--): ?>
            <button class="btn btn-outline-secondary btn-sm" onclick="filterReviews(<?php echo $i; ?>)">
                <?php echo $i; ?> <i class="bi bi-star-fill text-warning"></i>
            </button>
        <?php endfor; ?>
    </div>

    <!-- Daftar Review -->
<div id="reviewList">
  <?php if (count($reviews) > 0): ?>
    <?php foreach ($reviews as $review): ?>
      <div class="border-bottom py-3 review-item" data-rating="<?php echo $review['rating']; ?>">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($review['full_name']); ?></h6>
            <div class="text-warning small mb-1">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="bi <?php echo ($i <= $review['rating']) ? 'bi-star-fill' : 'bi-star'; ?>"></i>
              <?php endfor; ?>
            </div>
            <p class="mb-1"><?php echo htmlspecialchars($review['comment']); ?></p>
            <small class="text-muted"><?php echo date('d M Y', strtotime($review['created_at'])); ?></small>
          </div>

          <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['user_id']): ?>
            <button 
                type="button" 
                class="btn btn-sm btn-outline-danger btn-delete-review" 
                data-review-id="<?php echo htmlspecialchars($review['review_id'] ?? $review['id'], ENT_QUOTES); ?>">
                <i class="bi bi-trash"></i>
            </button>
        <?php endif; ?>



        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p class="text-muted">Belum ada review untuk kos ini.</p>
  <?php endif; ?>
</div>

<!-- Konfirmasi Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-sm">
      <div class="modal-body text-center p-4">
        <i class="bi bi-exclamation-triangle text-warning fs-1 mb-3"></i>
        <h5 class="fw-semibold">Yakin ingin menghapus review ini?</h5>
        <p class="text-muted small mb-4">Tindakan ini tidak dapat dibatalkan.</p>
        <div class="d-flex justify-content-center gap-2">
          <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
          <button type="button" id="confirmDeleteBtn" class="btn btn-danger px-4">Hapus</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Toast Notification -->
<div id="customToast" class="toast align-items-center text-bg-light border-0 position-fixed bottom-0 end-0 m-3" role="alert">
  <div class="d-flex">
    <div class="toast-body"></div>
    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
  </div>
</div>


    <!-- Form Tambah Review -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
    <hr>
    <h6 class="fw-bold mb-2">Tulis Review Anda</h6>
    <form id="reviewForm" method="POST" action="../../backend/user/customer/classes/add_review.php">
    <input type="hidden" name="kos_id" value="<?php echo $property['id']; ?>">
    <div class="mb-2">
        <div class="rating d-flex flex-row-reverse justify-content-center justify-content-md-start">
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required />
                <label for="star<?php echo $i; ?>"><i class="bi bi-star-fill"></i></label>
            <?php endfor; ?>
        </div>
    </div>
    <textarea name="comment" class="form-control mb-2" placeholder="Tulis komentar Anda..." rows="3" required></textarea>
    <button type="submit" class="btn btn-success">Kirim Review</button>
</form>

    <?php endif; ?>
</div>

        </div>

        <!-- Right Column - Sidebar -->
        <div class="col-lg-4">
            <div class="sticky-top" style="top: 20px;">
                <!-- Contact Owner -->
                <div class="detail-card mb-3">
                    <div class="contact-owner">
                        <h6 class="mb-2"><i class="bi bi-person-circle"></i> Pemilik Kos</h6>
                        <p class="mb-1 fw-bold"><?php echo htmlspecialchars($property['owner_name']); ?></p>
                        <?php if (!empty($property['owner_phone'])): ?>
                            <p class="mb-0 text-muted"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($property['owner_phone']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid gap-2">
                        <button class="btn btn-success" onclick="contactOwner()">
                            <i class="bi bi-whatsapp"></i> Hubungi Pemilik
                        </button>
                        <button class="btn btn-outline-success btn-favorite <?php echo $is_favorited ? 'favorited' : ''; ?>" 
                                id="fav-btn-main"
                                onclick="toggleFavorite(<?php echo $kos_id; ?>, this)">
                            <i class="bi <?php echo $is_favorited ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                            <?php echo $is_favorited ? 'Tersimpan' : 'Simpan'; ?>
                        </button>
                        <button class="btn btn-outline-secondary" onclick="shareProperty()">
                            <i class="bi bi-share-fill"></i> Share
                        </button>
                    </div>
                </div>

                <!-- Price Info -->
                <div class="detail-card">
                    <h6 class="mb-3">Informasi Harga</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Harga Bulanan:</span>
                        <strong class="text-success">Rp <?php echo number_format($property['price_monthly'], 0, ',', '.'); ?></strong>
                    </div>
                    <?php if ($property['price_daily']): ?>
                        <div class="d-flex justify-content-between">
                            <span>Harga Harian:</span>
                            <strong class="text-success">Rp <?php echo number_format($property['price_daily'], 0, ',', '.'); ?></strong>
                        </div>
                    <?php endif; ?>
                </div>


                <!-- Notifikasi Melayang -->
                    <div id="notification" class="notification"></div>
                
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Initialize map
const detailMap = L.map('detailMap').setView([<?php echo $property['latitude']; ?>, <?php echo $property['longitude']; ?>], 15);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(detailMap);

L.marker([<?php echo $property['latitude']; ?>, <?php echo $property['longitude']; ?>]).addTo(detailMap);

// Toggle Favorite
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
                    btn.classList.add('favorited');
                    btn.innerHTML = '<i class="bi bi-heart-fill"></i> Tersimpan';
                } else {
                    icon.className = 'bi bi-heart';
                    btn.classList.remove('favorited');
                    btn.innerHTML = '<i class="bi bi-heart"></i> Simpan';
                }
                
                // Toast notification
                const toast = document.createElement('div');
                toast.className = 'alert alert-success position-fixed top-0 end-0 m-3';
                toast.style.zIndex = '9999';
                toast.textContent = data.message;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 2000);
            } else {
                alert(data.message || 'Gagal memperbarui wishlist');
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
        const modal = new bootstrap.Modal(document.getElementById('loginAlertModal'));
        modal.show();
    <?php endif; ?>
}

// Contact Owner
function contactOwner() {
    <?php if (!empty($property['owner_phone'])): ?>
        const phone = '<?php echo preg_replace('/[^0-9]/', '', $property['owner_phone']); ?>';
        const message = encodeURIComponent('Halo, saya tertarik dengan kos <?php echo addslashes($property['name']); ?>');
        window.open(`https://wa.me/${phone}?text=${message}`, '_blank');
    <?php else: ?>
        alert('Nomor telepon pemilik tidak tersedia');
    <?php endif; ?>
}

// Share Property
function shareProperty() {
    const url = window.location.href;
    if (navigator.share) {
        navigator.share({
            title: '<?php echo addslashes($property['name']); ?>',
            text: 'Lihat kos ini di KostHub!',
            url: url
        });
    } else {
        navigator.clipboard.writeText(url);
        alert('Link telah disalin ke clipboard!');
    }
}
</script>

<script>
// ====== FILTER REVIEW BERDASARKAN BINTANG ======
function filterReviews(star) {
  const buttons = document.querySelectorAll('.btn-outline-secondary');
  buttons.forEach(btn => btn.classList.remove('active'));
  event.target.classList.add('active');

  document.querySelectorAll('.review-item').forEach(r => {
    r.style.display = (star === 0 || r.dataset.rating == star) ? 'block' : 'none';
  });
}

// ====== TOAST NOTIFICATION (pakai elemen #customToast) ======
function showNotification(message, type = 'success') {
  const toastEl = document.getElementById('customToast');
  const toastBody = toastEl.querySelector('.toast-body');
  toastBody.textContent = message;

  // Ganti warna background sesuai tipe notifikasi
  toastEl.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-light');
  toastEl.classList.add(type === 'error' ? 'text-bg-danger' : 'text-bg-success');

  // Tampilkan toast pakai Bootstrap
  const toast = new bootstrap.Toast(toastEl);
  toast.show();
}

// ====== HANDLE HAPUS REVIEW DENGAN KONFIRMASI MODAL ======
let formToDelete = null;

document.querySelectorAll('.delete-review-form').forEach(form => {
  form.addEventListener('submit', e => {
    e.preventDefault();
    formToDelete = form;
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
  });
});

document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
  if (!formToDelete) return;

  const formData = new FormData(formToDelete);

  try {
    const response = await fetch(formToDelete.action, {
      method: 'POST',
      body: formData
    });
    const result = await response.json();

    const modal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
    modal.hide();

    if (result.success) {
      // Efek fade-out sebelum elemen dihapus
      const reviewItem = formToDelete.closest('.review-item');
      reviewItem.style.transition = 'opacity 0.4s ease';
      reviewItem.style.opacity = '0';
      setTimeout(() => reviewItem.remove(), 400);

      showNotification(result.message, 'success');
    } else {
      showNotification(result.message, 'error');
    }
  } catch (err) {
    console.error(err);
    showNotification('Terjadi kesalahan pada server.', 'error');
  }

  formToDelete = null;
});

// ====== SUBMIT REVIEW BARU ======
document.getElementById('reviewForm')?.addEventListener('submit', async e => {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  const data = Object.fromEntries(formData.entries());

  try {
    const response = await fetch(form.action, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await response.json();

    if (result.success) {
      showNotification(result.message, 'success');
      form.reset();
      setTimeout(() => location.reload(), 1000);
    } else {
      showNotification(result.message, 'error');
    }
  } catch (error) {
    console.error(error);
    showNotification('Terjadi kesalahan pada server.', 'error');
  }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  let selectedReviewId = null;

  // Klik tombol trash → buka modal
  document.querySelectorAll('.btn-delete-review').forEach(btn => {
    btn.addEventListener('click', function() {
      selectedReviewId = this.getAttribute('data-review-id');
      const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
      modal.show();
    });
  });

  // Klik "Hapus" di modal → kirim AJAX
  document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (!selectedReviewId) return;

    fetch('../../../backend/user/customer/classes/delete_my_review.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'review_id=' + encodeURIComponent(selectedReviewId)
    })
    .then(res => res.json())
    .then(data => {
      const toastEl = document.getElementById('customToast');
      const toastBody = toastEl.querySelector('.toast-body');

      toastBody.textContent = data.message || 'Gagal menghapus review.';
      toastEl.classList.remove('text-bg-success', 'text-bg-danger');
      toastEl.classList.add(data.success ? 'text-bg-success' : 'text-bg-danger');

      const toast = new bootstrap.Toast(toastEl);
      toast.show();

      if (data.success) {
        document.querySelector(`[data-review-id="${selectedReviewId}"]`)
          .closest('.review-item')
          .remove();
      }
    })
    .catch(err => console.error('Error:', err));

    bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
  });
});
</script>


</body>
</html>