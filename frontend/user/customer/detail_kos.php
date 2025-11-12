<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user';
$isOwner    = isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'owner';

$kos_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$owner_id = $isOwner ? $_SESSION['user_id'] : 0;

if ($kos_id == 0) {
    header("Location: explore.php");
    exit();
}

// Query utama – owner bisa lihat semua miliknya, user hanya yang approved
$sql = "SELECT k.*, u.full_name as owner_name, u.phone as owner_phone,
        (SELECT COUNT(*) FROM saved_kos WHERE kos_id = k.id) as total_saved,
        (SELECT AVG(rating) FROM reviews WHERE kos_id = k.id) as avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE kos_id = k.id) as total_reviews
        FROM kos k 
        LEFT JOIN users u ON k.owner_id = u.id
        WHERE k.id = ? " . ($isOwner ? "AND k.owner_id = ?" : "AND k.status = 'approved'");

$stmt = $conn->prepare($sql);
if ($isOwner) {
    $stmt->bind_param("ii", $kos_id, $owner_id);
} else {
    $stmt->bind_param("i", $kos_id);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: " . ($isOwner ? "your_property.php" : "explore.php"));
    exit();
}
$property = $result->fetch_assoc();

// Check if favorited (hanya untuk user)
$is_favorited = false;
if ($isLoggedIn) {
    $fav_check = $conn->query("SELECT id FROM saved_kos WHERE user_id = {$_SESSION['user_id']} AND kos_id = {$kos_id} LIMIT 1");
    $is_favorited = ($fav_check && $fav_check->num_rows > 0);
}

// Images
$sql_images = "SELECT image_url FROM kos_images WHERE kos_id = ? ORDER BY id";
$stmt_images = $conn->prepare($sql_images);
$stmt_images->bind_param("i", $kos_id);
$stmt_images->execute();
$images = $stmt_images->get_result()->fetch_all(MYSQLI_ASSOC);

// Facilities
$sql_facilities = "SELECT f.name, f.icon, f.category 
                   FROM kos_facilities kf 
                   JOIN facilities f ON kf.facility_id = f.id 
                   WHERE kf.kos_id = ? ORDER BY f.category";
$stmt_facilities = $conn->prepare($sql_facilities);
$stmt_facilities->bind_param("i", $kos_id);
$stmt_facilities->execute();
$facilities = $stmt_facilities->get_result()->fetch_all(MYSQLI_ASSOC);
$grouped_facilities = [];
foreach ($facilities as $f) {
    $grouped_facilities[$f['category']][] = $f;
}

// Rules
$rules = !empty($property['rules']) ? json_decode($property['rules'], true) : [];

// Reviews
$sql_reviews = "SELECT r.*, u.full_name, u.profile_picture 
                FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.kos_id = ? 
                ORDER BY r.created_at DESC";
$stmt_reviews = $conn->prepare($sql_reviews);
$stmt_reviews->bind_param("i", $kos_id);
$stmt_reviews->execute();
$reviews = $stmt_reviews->get_result()->fetch_all(MYSQLI_ASSOC);

// Saved by users (untuk owner & user)
$sql_saved = "SELECT u.full_name, u.profile_picture, sk.created_at 
              FROM saved_kos sk 
              JOIN users u ON sk.user_id = u.id 
              WHERE sk.kos_id = ? 
              ORDER BY sk.created_at DESC 
              LIMIT 10";
$stmt_saved = $conn->prepare($sql_saved);
$stmt_saved->bind_param("i", $kos_id);
$stmt_saved->execute();
$saved_by = $stmt_saved->get_result()->fetch_all(MYSQLI_ASSOC);
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
        /* ---- Sama persis dengan owner ---- */
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
        .main-image, .thumbnail-image { width: 100%; object-fit: cover; cursor: pointer; }
        .main-image { height: 400px; }
        .thumbnail-image { height: 120px; }
        .overlay-more { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; cursor: pointer; border-radius: 8px; }
        .review-avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
        .review-item { padding: 15px; border-bottom: 1px solid #e9ecef; }
        .review-item:last-child { border-bottom: none; }
        .review-rating { color: #ffc107; margin: 5px 0; }
        .btn-favorite.favorited { background: #fff5f5; border-color: #dc3545; color: #dc3545; }
        .btn-favorite.favorited i { color: #dc3545; }
        .kos-type { font-size: 0.9rem; font-weight: bold; border-radius: 10px; padding: 5px 12px; display: inline-block; }
        .kos-type.putra { background: #e3f2fd; color: #1976d2; }
        .kos-type.putri { background: #fce4ec; color: #c2185b; }
        .kos-type.campur { background: #fff3e0; color: #f57c00; }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .saved-user-item img { width: 36px; height: 36px; border-radius: 50%; }
        .saved-users { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .lightbox { display: none; position: fixed; z-index: 9999; padding-top: 100px; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); }
        .lightbox-content { margin: auto; display: block; max-width: 90%; max-height: 80%; }
        .close { position: absolute; top: 15px; right: 35px; color: #f1f1f1; font-size: 40px; font-weight: bold; cursor: pointer; }
        .lightbox-controls { position: absolute; top: 50%; width: 100%; display: flex; justify-content: space-between; transform: translateY(-50%); }
        .prev, .next { cursor: pointer; padding: 16px; color: white; font-weight: bold; font-size: 30px; user-select: none; }
        .caption { color: #ccc; text-align: center; padding: 10px; }
        .notification { position: fixed; top: 20px; right: 20px; background: #198754; color: white; padding: 12px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); opacity: 0; transform: translateY(-20px); transition: all 0.4s ease; z-index: 9999; }
        .notification.show { opacity: 1; transform: translateY(0); }
        .notification.error { background: #dc3545; }
        /* RATING BINTANG MODERN */
.star-rating-modern {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 8px;
    font-size: 2.2rem;
    position: relative;
}

.star-rating-modern input {
    display: none;
}

.star-rating-modern label {
    cursor: pointer;
    color: #e0e0e0;
    transition: all 0.25s ease;
    transform: scale(1);
}

.star-rating-modern label:hover,
.star-rating-modern label:hover ~ label,
.star-rating-modern input:checked ~ label {
    color: #ffc107;
    transform: scale(1.15);
    filter: drop-shadow(0 0 8px rgba(255, 193, 7, 0.6));
}

.star-rating-modern label:active {
    transform: scale(0.95);
}

/* Efek glow saat terpilih */
.star-rating-modern input:checked ~ label {
    animation: starPulse 0.6s ease-out;
}

@keyframes starPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1.15); }
}

/* Responsif mobile */
@media (max-width: 576px) {
    .star-rating-modern {
        font-size: 1.9rem;
        gap: 6px;
    }
}

/* ZOOM IN EFFECT ON HOVER */
.image-wrapper {
    overflow: hidden;
    border-radius: 12px; /* sama dengan .rounded */
    display: block;
}

.zoomable {
    transition: transform 0.4s ease;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-wrapper:hover .zoomable {
    transform: scale(1.1);
}

/* Overlay +Foto tetap di atas saat hover */
.overlay-more {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
    cursor: pointer;
    transition: background 0.3s ease;
    border-radius: 12px;
}

.image-wrapper:hover .overlay-more {
    background: rgba(0,0,0,0.65);
}

/* UKURAN GAMBAR IDEAL & RESPONSIF */
.main-image {
    height: 420px !important;     /* tinggi tetap, lebar otomatis */
    object-fit: cover;
    object-position: center;
}

.thumbnail-image {
    height: 130px !important;     /* tinggi tetap */
    object-fit: cover;
    object-position: center;
}

/* Pastikan wrapper tidak memotong gambar */
.image-wrapper {
    overflow: hidden;
    border-radius: 12px;
    display: block;
    width: 100%;
}

/* Zoom-in tetap halus */
.zoomable {
    transition: transform 0.4s ease;
    width: 100%;
    height: 100%;
}

.image-wrapper:hover .zoomable {
    transform: scale(1.1);
}

/* Mobile: sedikit lebih kecil agar tidak penuh layar */
@media (max-width: 768px) {
    .main-image {
        height: 320px !important;
    }
    .thumbnail-image {
        height: 100px !important;
    }
}

@media (max-width: 576px) {
    .main-image {
        height: 280px !important;
    }
    .thumbnail-image {
        height: 90px !important;
    }
}
    </style>
</head>
<body>

<div class="container my-4">
    <!-- Header -->
    <div class="header-bar py-3 d-flex align-items-center justify-content-center position-relative">
        <button type="button" class="btn btn-outline-success position-absolute start-0 ms-3 d-flex align-items-center"
            onclick="window.location.href='<?php echo $isOwner ? 'your_property.php' : 'explore.php'; ?>'">
            <i class="bi bi-arrow-left"></i>
        </button>
        <div class="d-flex align-items-center text-center">
            <img src="../../assets/logo_kos.png" alt="logo" style="height:40px;" class="me-2">
            <h2 class="fw-bold m-0">KostHub</h2>
        </div>
    </div>

  <!-- Image Gallery -->
<div class="row g-3 mb-4">
    <?php if (!empty($images)): ?>
        <div class="col-md-8">
            <?php
                $mainImage = '/Web-App/' . $images[0]['image_url'];
                $defaultImg = '/Web-App/frontend/assets/default-kos.jpg';
                $mainPath = $_SERVER['DOCUMENT_ROOT'] . $mainImage;
                if (!file_exists($mainPath) || empty($images[0]['image_url'])) {
                    $mainImage = $defaultImg;
                }
            ?>
            <div class="image-wrapper">
                <img src="<?php echo htmlspecialchars($mainImage); ?>"
                     class="img-fluid rounded main-image shadow-sm zoomable"
                     alt="Foto utama kos"
                     onclick="openLightbox(0)">
            </div>
        </div>

        <div class="col-md-4">
            <div class="row g-2">
                <?php for ($i = 1; $i < min(4, count($images)); $i++): ?>
                    <?php
                        $imgUrl = '/Web-App/' . $images[$i]['image_url'];
                        $path = $_SERVER['DOCUMENT_ROOT'] . $imgUrl;
                        if (!file_exists($path) || empty($images[$i]['image_url'])) {
                            $imgUrl = $defaultImg;
                        }
                    ?>
                    <div class="col-6">
                        <div class="image-wrapper">
                            <img src="<?php echo htmlspecialchars($imgUrl); ?>"
                                 class="img-fluid rounded thumbnail-image shadow-sm zoomable"
                                 alt="Foto kos"
                                 onclick="openLightbox(<?php echo $i; ?>)">
                        </div>
                    </div>
                <?php endfor; ?>

                <?php if (count($images) > 4): ?>
                    <div class="col-6 position-relative">
                        <div class="image-wrapper">
                            <img src="<?php echo htmlspecialchars($mainImage); ?>"
                                 class="img-fluid rounded thumbnail-image shadow-sm zoomable"
                                 alt="Foto kos">
                            <div class="overlay-more d-flex align-items-center justify-content-center"
                                 onclick="openLightbox(4)">
                                <span>+<?php echo count($images) - 4; ?> Foto</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="col-12">
            <div class="main-image d-flex align-items-center justify-content-center bg-secondary text-white rounded">
                <div class="text-center">
                    <i class="bi bi-image" style="font-size: 4rem;"></i>
                    <p class="mt-2">Tidak ada gambar</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>


    <!-- Lightbox -->
    <div id="lightbox" class="lightbox">
        <span class="close" onclick="closeLightbox()">&times;</span>
        <img id="lightbox-img" class="lightbox-content" alt="Preview">
        <div class="lightbox-controls">
            <span class="prev" onclick="changeImage(-1)">&#10094;</span>
            <span class="next" onclick="changeImage(1)">&#10095;</span>
        </div>
        <div id="lightbox-caption" class="caption"></div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Title & Stats -->
            <div class="detail-card mb-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <span class="kos-type <?php echo $property['kos_type']; ?> mb-2"><?php echo ucfirst($property['kos_type']); ?></span>
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
                        <div><strong><?php echo $property['available_rooms']; ?>/<?php echo $property['total_rooms']; ?></strong><small>Kamar</small></div>
                    </div>
                    <div class="stat-item">
                        <i class="bi bi-star-fill"></i>
                        <div><strong><?php echo $property['avg_rating'] ? number_format($property['avg_rating'], 1) : 'N/A'; ?></strong><small><?php echo $property['total_reviews']; ?> Review</small></div>
                    </div>
                    <div class="stat-item">
                        <i class="bi bi-heart-fill"></i>
                        <div><strong><?php echo $property['total_saved']; ?></strong><small>Disimpan</small></div>
                    </div>
                    <?php if ($isOwner): ?>
                        <div class="stat-item">
                            <i class="bi bi-gender-ambiguous"></i>
                            <div><strong><?php echo ucfirst($property['kos_type']); ?></strong><small>Tipe Kos</small></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Deskripsi -->
            <div class="detail-card mb-4">
                <h5 class="section-title"><i class="bi bi-info-circle-fill"></i> Deskripsi Property</h5>
                <p style="color:#495057;line-height:1.8;"><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
            </div>

            <!-- Fasilitas -->
            <?php if (!empty($grouped_facilities)): ?>
                <div class="detail-card mb-4">
                    <h5 class="section-title"><i class="bi bi-star-fill"></i> Fasilitas</h5>
                    <?php foreach ($grouped_facilities as $cat => $items): ?>
                        <div class="mb-3">
                            <h6 class="facility-category"><?php echo ucfirst($cat); ?></h6>
                            <div class="facility-grid">
                                <?php foreach ($items as $f): ?>
                                    <div class="facility-item">
                                        <i class="fa <?php echo htmlspecialchars($f['icon']); ?>"></i>
                                        <?php echo htmlspecialchars($f['name']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Aturan -->
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
                        foreach ($rules as $r):
                            $label = $rule_labels[$r] ?? $r;
                        ?>
                            <div class="rule-item"><i class="bi bi-check2-circle"></i> <?php echo htmlspecialchars($label); ?></div>
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
                <h5 class="section-title"><i class="bi bi-chat-left-quote-fill"></i> Ulasan & Penilaian</h5>

                <?php
                $avg_rating = $property['avg_rating'] ? number_format($property['avg_rating'], 1) : 0;
                $rating_counts = [1=>0,2=>0,3=>0,4=>0,5=>0];
                foreach ($reviews as $r) $rating_counts[$r['rating']]++;
                $total_reviews = array_sum($rating_counts);
                ?>

                <div class="row align-items-center mb-4">
                    <div class="col-md-4 text-center">
                        <h1 class="display-4 fw-bold text-warning mb-0"><?php echo $avg_rating; ?></h1>
                        <div class="text-warning fs-5 mb-2">
                            <?php for ($i=1;$i<=5;$i++): ?>
                                <i class="bi <?php echo $i<=round($avg_rating)?'bi-star-fill':'bi-star'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="text-muted"><?php echo $total_reviews; ?> ulasan</p>
                    </div>
                    <div class="col-md-8">
                        <?php for ($i=5;$i>=1;$i--):
                            $pct = $total_reviews ? ($rating_counts[$i]/$total_reviews*100) : 0;
                        ?>
                            <div class="d-flex align-items-center mb-1">
                                <div class="me-2" style="width:40px;"><?php echo $i; ?> <i class="bi bi-star-fill text-warning"></i></div>
                                <div class="progress flex-grow-1" style="height:10px;">
                                    <div class="progress-bar bg-warning" style="width:<?php echo $pct; ?>%"></div>
                                </div>
                                <div class="ms-2 text-muted" style="width:40px;"><?php echo $rating_counts[$i]; ?></div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Filter -->
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button class="btn btn-outline-secondary btn-sm active" onclick="filterReviews(0)">Semua</button>
                    <?php for ($i=5;$i>=1;$i--): ?>
                        <button class="btn btn-outline-secondary btn-sm" onclick="filterReviews(<?php echo $i; ?>)"><?php echo $i; ?> <i class="bi bi-star-fill text-warning"></i></button>
                    <?php endfor; ?>
                </div>

                <!-- Daftar Review -->
                <div id="reviewList">
                    <?php if ($reviews): ?>
                        <?php foreach ($reviews as $r): ?>
                            <div class="review-item border-bottom py-3" data-rating="<?php echo $r['rating']; ?>">
                                <div class="d-flex gap-3">
                                    <?php
                                            $profilePic = $r['profile_picture'] ?? '';
                                            $defaultAvatar = '/Web-App/frontend/assets/default-avatar.png';

                                            // bersihkan slash ganda
                                            $profilePic = preg_replace('#/+#', '/', $profilePic);

                                            // tambahkan /Web-App kalau belum ada
                                            if (!str_starts_with($profilePic, '/Web-App/')) {
                                                $profilePic = '/Web-App/' . ltrim($profilePic, '/');
                                            }

                                            // cek apakah file beneran ada
                                            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $profilePic)) {
                                                $profilePic = $defaultAvatar;
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($profilePic); ?>"
                                                class="review-avatar"
                                                onerror="this.onerror=null; this.src='<?php echo $defaultAvatar; ?>';">

                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?php echo htmlspecialchars($r['full_name']); ?></strong>
                                                <div class="text-warning small mb-1">
                                                    <?php for ($i=1;$i<=5;$i++): ?>
                                                        <i class="bi <?php echo $i<=$r['rating']?'bi-star-fill':'bi-star'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            <?php if ($isLoggedIn && $_SESSION['user_id'] == $r['user_id']): ?>
                                                <button class="btn btn-sm btn-outline-danger btn-delete-review" data-review-id="<?php echo $r['id']; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php elseif ($isOwner): ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteReview(<?php echo $r['id']; ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($r['comment'])); ?></p>
                                        <small class="text-muted"><?php echo date('d M Y', strtotime($r['created_at'])); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">Belum ada review</p>
                    <?php endif; ?>
                </div>

               <!-- Form Review (hanya user) -->
<?php if ($isLoggedIn && !$isOwner): ?>
    <hr>
    <h6 class="fw-bold mb-3">Beri Penilaian Anda</h6>
    <form id="reviewForm" method="POST">
        <input type="hidden" name="kos_id" value="<?php echo $kos_id; ?>">

        <!-- RATING BINTANG MODERN -->
        <div class="mb-4">
            <label class="form-label fw-semibold text-dark">Rating</label>
            <div class="star-rating-modern">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" name="rating" value="<?php echo $i; ?>" id="rate<?php echo $i; ?>" required>
                    <label for="rate<?php echo $i; ?>" title="<?php echo $i; ?> bintang">
                        <i class="fa-solid fa-star"></i>
                    </label>
                <?php endfor; ?>
            </div>
            <small class="text-muted d-block mt-2" id="rating-text">Pilih rating terlebih dahulu</small>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold text-dark">Komentar</label>
            <textarea name="comment" class="form-control" rows="4" placeholder="Ceritakan pengalaman Anda di kos ini..." required></textarea>
        </div>

        <button type="submit" class="btn btn-success px-4">
            <i class="bi bi-send"></i> Kirim Review
        </button>
    </form>
<?php endif; ?>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-4">
            <div class="sticky-top" style="top:20px;">
                <?php if ($isOwner): ?>
                    <div class="detail-card mb-3">
                        <h6 class="mb-3">Status Property</h6>
                        <div class="status-badge status-<?php echo $property['status']; ?> mb-3">
                            <?php echo ucfirst($property['status']); ?>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="edit_property.php?id=<?php echo $kos_id; ?>" class="btn btn-success">
                                <i class="bi bi-pencil-fill"></i> Edit Property
                            </a>
                            <button class="btn btn-outline-success" onclick="shareProperty()"><i class="bi bi-share-fill"></i> Share</button>
                        </div>
                        <hr>
                        <?php if ($saved_by): ?>
                            <h6 class="mb-3">Disimpan Oleh</h6>
                            <div class="saved-users">
                                <?php foreach (array_slice($saved_by,0,5) as $u):
                                    $pp = $u['profile_picture'] ? '/Web-App/'.$u['profile_picture'] : '/Web-App/frontend/assets/default-avatar.png';
                                ?>
                                    <div class="saved-user-item">
                                        <img src="<?php echo $pp; ?>" alt="user" onerror="this.src='/Web-App/frontend/assets/default-avatar.png'">
                                        <small><?php echo htmlspecialchars($u['full_name']); ?></small>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($saved_by)>5): ?>
                                    <small class="text-muted">+<?php echo count($saved_by)-5; ?> lainnya</small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="detail-card mb-3">
                        <div class="contact-owner bg-light p-3 rounded mb-3">
                            <h6><i class="bi bi-person-circle"></i> Pemilik Kos</h6>
                            <p class="fw-bold mb-1"><?php echo htmlspecialchars($property['owner_name']); ?></p>
                            <?php if (!empty($property['owner_phone'])): ?>
                                <p class="mb-0 text-muted"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($property['owner_phone']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="d-grid gap-2">
                            <button class="btn btn-success" onclick="contactOwner()">
                                <i class="bi bi-whatsapp"></i> Hubungi Pemilik
                            </button>
                            <button class="btn btn-outline-success btn-favorite <?php echo $is_favorited?'favorited':''; ?>" id="fav-btn-main"
                                    onclick="toggleFavorite(<?php echo $kos_id; ?>, this)">
                                <i class="bi <?php echo $is_favorited?'bi-heart-fill':'bi-heart'; ?>"></i>
                                <?php echo $is_favorited?'Tersimpan':'Simpan'; ?>
                            </button>
                            <button class="btn btn-outline-secondary" onclick="shareProperty()">
                                <i class="bi bi-share-fill"></i> Share
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="detail-card">
                    <h6 class="mb-3">Informasi Harga</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Harga Bulanan:</span>
                        <strong class="text-success">Rp <?php echo number_format($property['price_monthly'],0,',','.'); ?></strong>
                    </div>
                    <?php if ($property['price_daily']): ?>
                        <div class="d-flex justify-content-between">
                            <span>Harga Harian:</span>
                            <strong class="text-success">Rp <?php echo number_format($property['price_daily'],0,',','.'); ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="notification" class="notification"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Map
    const detailMap = L.map('detailMap').setView([<?php echo $property['latitude']; ?>, <?php echo $property['longitude']; ?>], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(detailMap);
    L.marker([<?php echo $property['latitude']; ?>, <?php echo $property['longitude']; ?>]).addTo(detailMap);

    // 🖼️ Lightbox
    const defaultImg = '/Web-App/frontend/assets/default-kos.jpg';
    const images = <?php echo json_encode(array_map(function($img) {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/Web-App/' . $img['image_url'];
        return (file_exists($path) && !empty($img['image_url'])) ? $img['image_url'] : 'frontend/assets/default-kos.jpg';
    }, $images)); ?>;

    let currentIndex = 0;

    function openLightbox(i) {
        if (!images.length) {
            document.getElementById('lightbox-img').src = defaultImg;
            document.getElementById('lightbox-caption').innerHTML = 'Tidak ada gambar';
        } else {
            currentIndex = i;
            document.getElementById('lightbox').style.display = 'block';
            updateLightbox();
        }
    }

    function closeLightbox() {
        document.getElementById('lightbox').style.display = 'none';
    }

    function changeImage(step) {
        if (!images.length) return;
        currentIndex = (currentIndex + step + images.length) % images.length;
        updateLightbox();
    }

    function updateLightbox() {
        const imgSrc = images[currentIndex] ? '/Web-App/' + images[currentIndex] : defaultImg;
        document.getElementById('lightbox-img').src = imgSrc;
        document.getElementById('lightbox-caption').innerHTML = `Foto ${currentIndex + 1} dari ${images.length}`;
    }

    document.addEventListener('keydown', e => {
        if (document.getElementById('lightbox').style.display === 'block') {
            if (e.key === 'ArrowRight') changeImage(1);
            if (e.key === 'ArrowLeft') changeImage(-1);
            if (e.key === 'Escape') closeLightbox();
        }
    });


    // Favorite
    function toggleFavorite(kosId, btn){
        <?php if($isLoggedIn && !$isOwner): ?>
            const icon = btn.querySelector('i');
            const wasFav = icon.classList.contains('bi-heart-fill');
            icon.className='bi bi-arrow-repeat fa-spin';
            btn.disabled=true;
            fetch('/Web-App/backend/user/customer/classes/save_kos.php',{
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body:JSON.stringify({kos_id:kosId})
            })
            .then(r=>r.json())
            .then(d=>{
                if(d.success){
                    if(d.favorited){
                        icon.className='bi bi-heart-fill';
                        btn.classList.add('favorited');
                        btn.innerHTML='<i class="bi bi-heart-fill"></i> Tersimpan';
                    }else{
                        icon.className='bi bi-heart';
                        btn.classList.remove('favorited');
                        btn.innerHTML='<i class="bi bi-heart"></i> Simpan';
                    }
                    showNotif(d.message);
                }else showNotif(d.message||'Gagal','error');
                btn.disabled=false;
            });
        <?php else: ?>
            alert('Login sebagai user untuk menyimpan');
        <?php endif; ?>
    }

    // Contact Owner
    function contactOwner(){
        <?php if(!empty($property['owner_phone'])): ?>
            const phone = '<?php echo preg_replace('/\D/','', $property['owner_phone']); ?>';
            const msg = encodeURIComponent('Halo, saya tertarik dengan kos <?php echo addslashes($property['name']); ?>');
            window.open(`https://wa.me/${phone}?text=${msg}`,'_blank');
        <?php else: ?>
            alert('Nomor pemilik tidak tersedia');
        <?php endif; ?>
    }

    // Share
    function shareProperty(){
        const url = location.href;
        if(navigator.share){
            navigator.share({title:'<?php echo addslashes($property['name']); ?>',text:'Lihat kos ini di KostHub!',url});
        }else{
            navigator.clipboard.writeText(url);
            showNotif('Link disalin!');
        }
    }

    // Filter Review
    function filterReviews(star){
        document.querySelectorAll('.btn-outline-secondary').forEach(b=>b.classList.remove('active'));
        event.target.classList.add('active');
        document.querySelectorAll('.review-item').forEach(r=>{
            r.style.display = (star===0 || parseInt(r.dataset.rating)===star) ? 'block' : 'none';
        });
    }

    // Notifikasi
    function showNotif(msg,type='success'){
        const n = document.getElementById('notification');
        n.textContent=msg;
        n.className='notification'+(type==='error'?' error':'');
        n.classList.add('show');
        setTimeout(()=>n.classList.remove('show'),3000);
    }

    // Submit Review
    document.getElementById('reviewForm')?.addEventListener('submit',async e=>{
        e.preventDefault();
        const f = new FormData(e.target);
        const data = Object.fromEntries(f);
        try{
            const res = await fetch('/Web-App/backend/user/customer/classes/add_review.php', {
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body:JSON.stringify(data)
            });
            const json = await res.json();
            showNotif(json.message, json.success?'success':'error');
            if(json.success) setTimeout(()=>location.reload(),1200);
        }catch(err){ showNotif('Error server','error'); }
    });

    // Delete own review (user)
    document.querySelectorAll('.btn-delete-review').forEach(b=>{
        b.addEventListener('click',function(){
            const id = this.dataset.reviewId;
            if(!confirm('Hapus review ini?')) return;
            fetch('/Web-App/backend/user/customer/classes/delete_my_review.php', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'review_id='+id
            })
            .then(r=>r.json())
            .then(d=>{
                showNotif(d.message, d.success?'success':'error');
                if(d.success) this.closest('.review-item').remove();
            });
        });
    });
</script>
</body>
</html>