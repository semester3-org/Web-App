<?php
session_start();
require_once "../../../../backend/config/db.php";

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    header("Location: ../../auth/login.php");
    exit();
}

$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$owner_id = $_SESSION['user_id'];

if ($property_id == 0) {
    header("Location: your_property.php");
    exit();
}

// Fetch property data dengan JOIN
$sql = "SELECT k.*, 
        (SELECT COUNT(*) FROM saved_kos WHERE kos_id = k.id) as total_saved,
        (SELECT AVG(rating) FROM reviews WHERE kos_id = k.id) as avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE kos_id = k.id) as total_reviews
        FROM kos k 
        WHERE k.id = ? AND k.owner_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $property_id, $owner_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: your_property.php");
    exit();
}

$property = $result->fetch_assoc();

// Fetch images
$sql_images = "SELECT image_url FROM kos_images WHERE kos_id = ? ORDER BY id";
$stmt_images = $conn->prepare($sql_images);
$stmt_images->bind_param("i", $property_id);
$stmt_images->execute();
$images = $stmt_images->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch facilities dengan nama
$sql_facilities = "SELECT f.name, f.icon, f.category 
                   FROM kos_facilities kf 
                   JOIN facilities f ON kf.facility_id = f.id 
                   WHERE kf.kos_id = ?
                   ORDER BY f.category";
$stmt_facilities = $conn->prepare($sql_facilities);
$stmt_facilities->bind_param("i", $property_id);
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
$stmt_reviews->bind_param("i", $property_id);
$stmt_reviews->execute();
$reviews = $stmt_reviews->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch users yang save property
$sql_saved = "SELECT u.full_name, u.profile_picture, sk.created_at 
              FROM saved_kos sk 
              JOIN users u ON sk.user_id = u.id 
              WHERE sk.kos_id = ? 
              ORDER BY sk.created_at DESC 
              LIMIT 10";
$stmt_saved = $conn->prepare($sql_saved);
$stmt_saved->bind_param("i", $property_id);
$stmt_saved->execute();
$saved_by = $stmt_saved->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Property - <?php echo htmlspecialchars($property['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="../css/detail_property.css" rel="stylesheet">
</head>

<body>
    <!-- Header -->
    <div class="header-bar py-3 d-flex align-items-center justify-content-center position-relative">
        <button type="button" class="btn btn-outline-success position-absolute start-0 ms-3 d-flex align-items-center"
            onclick="window.location.href='your_property.php'">
            <i class="bi bi-arrow-left"></i>
        </button>

        <div class="d-flex align-items-center text-center">
            <img src="../../../assets/logo_kos.png" alt="logo" style="height:40px;" class="me-2">
            <h2 class="fw-bold m-0">KostHub</h2>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-4">
        <!-- Image Gallery -->
        <div class="row g-2 mb-4">
            <?php if (count($images) > 0): ?>
                <div class="col-md-8">
                    <img src="../../../../<?php echo $images[0]['image_url']; ?>"
                        class="img-fluid rounded main-image"
                        alt="Main"
                        onerror="this.src='../../../assets/default-kos.jpg'">
                </div>
                <div class="col-md-4">
                    <div class="row g-2">
                        <?php for ($i = 1; $i < min(4, count($images)); $i++): ?>
                            <div class="col-6">
                                <img src="../../../../<?php echo $images[$i]['image_url']; ?>"
                                    class="img-fluid rounded thumbnail-image"
                                    alt="Thumbnail"
                                    onerror="this.src='../../../assets/default-kos.jpg'">
                            </div>
                        <?php endfor; ?>
                        <?php if (count($images) > 4): ?>
                            <div class="col-6 position-relative">
                                <img src="../../../../<?php echo $images[4]['image_url']; ?>"
                                    class="img-fluid rounded thumbnail-image"
                                    alt="Thumbnail">
                                <div class="overlay-more" onclick="showAllPhotos()">
                                    <span>+<?php echo count($images) - 4; ?> Foto</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Title & Stats -->
                <div class="detail-card mb-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h2 class="property-name"><?php echo htmlspecialchars($property['name']); ?></h2>
                            <p class="property-location">
                                <i class="bi bi-geo-alt-fill text-success"></i>
                                <?php echo htmlspecialchars($property['address']); ?>
                            </p>
                        </div>
                        <div class="text-end">
                            <div class="property-price">Rp <?php echo number_format($property['price_monthly'], 0, ',', '.'); ?></div>
                            <small class="text-muted">/ bulan</small>
                        </div>
                    </div>

                    <div class="stats-row mt-3">
                        <div class="stat-item">
                            <i class="bi bi-door-closed-fill"></i>
                            <div>
                                <strong><?php echo $property['available_rooms']; ?>/<?php echo $property['total_rooms']; ?></strong>
                                <small>Kamar</small>
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
                        <div class="stat-item">
                            <i class="bi bi-gender-ambiguous"></i>
                            <div>
                                <strong><?php echo ucfirst($property['kos_type']); ?></strong>
                                <small>Tipe Kos</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Property Detail -->
                <div class="detail-card mb-4">
                    <h5 class="section-title"><i class="bi bi-info-circle-fill"></i> Deskripsi Property</h5>
                    <p class="property-description"><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
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
                                            <i class="bi bi-check-circle-fill"></i>
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
                            <form method="POST" action="../../backend/user/customer/classes/delete_my_review.php" onsubmit="return confirm('Hapus review Anda?')">
                                <input type="hidden" name="review_id" value="<?php echo htmlspecialchars($review['review_id'] ?? $review['id'] ?? '', ENT_QUOTES); ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">Belum ada review untuk kos ini.</p>
        <?php endif; ?>
    </div>

    <!-- Form Tambah Review -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'customer'): ?>
    <hr>
    <h6 class="fw-bold mb-2">Tulis Review Anda</h6>
    <form method="POST" action="../../backend/user/customer/classes/add_review.php">
        <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
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
                <!-- Status Card -->
                <div class="detail-card mb-3 sticky-top" style="top: 20px;">
                    <h6 class="mb-3">Status Property</h6>
                    <div class="status-badge status-<?php echo $property['status']; ?> mb-3">
                        <?php echo ucfirst($property['status']); ?>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="edit_property.php?id=<?php echo $property_id; ?>" class="btn btn-success">
                            <i class="bi bi-pencil-fill"></i> Edit Property
                        </a>
                        <button class="btn btn-outline-success" onclick="shareProperty()">
                            <i class="bi bi-share-fill"></i> Share
                        </button>
                    </div>

                    <hr>

                    <!-- Saved By Users -->
                    <?php if (count($saved_by) > 0): ?>
                        <h6 class="mb-3">Disimpan Oleh</h6>
                        <div class="saved-users">
                            <?php foreach (array_slice($saved_by, 0, 5) as $user): ?>
                                <div class="saved-user-item">
                                    <img src="../../../../<?php echo $user['profile_picture'] ?? 'assets/default-avatar.png'; ?>"
                                        alt="User"
                                        onerror="this.src='../../../assets/default-avatar.png'">
                                    <small><?php echo htmlspecialchars($user['full_name']); ?></small>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($saved_by) > 5): ?>
                                <small class="text-muted">+<?php echo count($saved_by) - 5; ?> lainnya</small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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

        // Delete review
        function deleteReview(reviewId) {
            if (!confirm('Yakin ingin menghapus review ini?')) return;

            fetch('../../../../backend/user/owner/classes/delete_review.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        review_id: reviewId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('review-' + reviewId).remove();
                        alert('Review berhasil dihapus');
                    } else {
                        alert('Gagal menghapus review');
                    }
                });
        }

        // Share property
        function shareProperty() {
            const url = window.location.href;
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($property['name']); ?>',
                    text: 'Lihat property kos ini!',
                    url: url
                });
            } else {
                navigator.clipboard.writeText(url);
                alert('Link telah disalin!');
            }
        }

        // Show all photos
        function showAllPhotos() {
            // TODO: Implement lightbox
            alert('Fitur gallery akan segera hadir');
        }
    </script>

    <script>
function filterReviews(star) {
    const buttons = document.querySelectorAll('.btn-outline-secondary');
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    const reviews = document.querySelectorAll('.review-item');
    reviews.forEach(r => {
        if (star === 0 || r.dataset.rating == star) {
            r.style.display = 'block';
        } else {
            r.style.display = 'none';
        }
    });
}
</script>

</body>

</html>