<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'user';
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

$profilePic = '/Web-App/frontend/assets/default-avatar.png';
$fullName = 'Guest';

// Ambil semua fasilitas untuk filter
$all_facilities = $conn->query("SELECT id, name, icon FROM facilities ORDER BY name")->fetch_all(MYSQLI_ASSOC);

if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT full_name, profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {
        $profilePic = !empty($user['profile_picture']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $user['profile_picture'])
            ? $user['profile_picture']
            : $profilePic;
        $fullName = htmlspecialchars($user['full_name']);
    }
}

// === FILTER & SORT ===
$search_location = trim($_GET['location'] ?? '');
$filter_type     = $_GET['type'] ?? '';
$filter_price    = $_GET['price'] ?? '';
$sort            = $_GET['sort'] ?? 'newest';
$selected_facility_ids = array_filter(array_map('intval', $_GET['facilities'] ?? []));

// Sorting logic
$order_by = match($sort) {
    'price_low'  => "ORDER BY k.price_monthly ASC",
    'price_high' => "ORDER BY k.price_monthly DESC",
    default      => "ORDER BY k.created_at DESC"
};

// Pagination
$current_page = max(1, intval($_GET['page'] ?? 1));
$limit = 30;
$offset = ($current_page - 1) * $limit;

// Fasilitas filter
$has_facility_filter = !empty($selected_facility_ids);
$fac_count = $has_facility_filter ? count($selected_facility_ids) : 0;
$placeholders = $has_facility_filter ? str_repeat('?,', $fac_count - 1) . '?' : '';

// === COUNT TOTAL ===
if ($has_facility_filter) {
    $count_query = "SELECT COUNT(*) AS total FROM (
                        SELECT kf.kos_id FROM kos_facilities kf
                        WHERE kf.facility_id IN ($placeholders)
                        GROUP BY kf.kos_id HAVING COUNT(*) = ?
                    ) AS matched
                    INNER JOIN kos k ON k.id = matched.kos_id
                    WHERE k.status = 'approved'";
    $params = array_merge($selected_facility_ids, [$fac_count]);
    $types  = str_repeat('i', $fac_count) . 'i';
} else {
    $count_query = "SELECT COUNT(*) AS total FROM kos k WHERE k.status = 'approved'";
    $params = [];
    $types  = "";
}

// Tambah filter lain ke count
if ($search_location !== '') {
    $count_query .= " AND (k.city LIKE ? OR k.province LIKE ? OR k.address LIKE ?)";
    $like = "%$search_location%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= "sss";
}
if ($filter_type !== '') {
    $count_query .= " AND k.kos_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}
if ($filter_price !== '') {
    $count_query .= " AND k.price_monthly <= ?";
    $params[] = $filter_price;
    $types .= "i";
}

$stmt = $conn->prepare($count_query);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total_properties = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_properties / $limit);
$stmt->close();

// === MAIN QUERY ===
if ($has_facility_filter) {
    $query = "SELECT k.id, k.name, k.description, k.city, k.province, k.address,
                     k.kos_type, k.price_monthly, k.price_daily, k.total_rooms,
                     k.available_rooms, k.created_at, u.full_name AS owner_name
              FROM kos k
              LEFT JOIN users u ON k.owner_id = u.id
              INNER JOIN (
                  SELECT kf.kos_id FROM kos_facilities kf
                  WHERE kf.facility_id IN ($placeholders)
                  GROUP BY kf.kos_id HAVING COUNT(*) = ?
              ) AS matched ON k.id = matched.kos_id
              WHERE k.status = 'approved'";
    $params = array_merge($selected_facility_ids, [$fac_count]);
    $types  = str_repeat('i', $fac_count) . 'i';
} else {
    $query = "SELECT k.id, k.name, k.description, k.city, k.province, k.address,
                     k.kos_type, k.price_monthly, k.price_daily, k.total_rooms,
                     k.available_rooms, k.created_at, u.full_name AS owner_name
              FROM kos k
              LEFT JOIN users u ON k.owner_id = u.id
              WHERE k.status = 'approved'";
    $params = [];
    $types  = "";
}

// Filter umum
if ($search_location !== '') {
    $query .= " AND (k.city LIKE ? OR k.province LIKE ? OR k.address LIKE ?)";
    $like = "%$search_location%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= "sss";
}
if ($filter_type !== '') {
    $query .= " AND k.kos_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}
if ($filter_price !== '') {
    $query .= " AND k.price_monthly <= ?";
    $params[] = $filter_price;
    $types .= "i";
}

$query .= " $order_by LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$properties = [];
while ($row = $result->fetch_assoc()) {
    // Cek favorit
    $row['is_favorited'] = $isLoggedIn && $conn->query("SELECT 1 FROM saved_kos WHERE user_id = $userId AND kos_id = {$row['id']}")->num_rows > 0;

    // Gambar pertama
    $img = $conn->prepare("SELECT image_url FROM kos_images WHERE kos_id = ? ORDER BY id ASC LIMIT 1");
    $img->bind_param("i", $row['id']);
    $img->execute();
    $row['image'] = $img->get_result()->fetch_assoc()['image_url'] ?? null;
    $img->close();

    // Fasilitas di kartu (max 3)
    $fac = $conn->prepare("SELECT f.name, f.icon FROM kos_facilities kf JOIN facilities f ON kf.facility_id = f.id WHERE kf.kos_id = ? LIMIT 3");
    $fac->bind_param("i", $row['id']);
    $fac->execute();
    $row['facilities'] = $fac->get_result()->fetch_all(MYSQLI_ASSOC);
    $fac->close();

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
body { 
  background-color: #eef0f4; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }    
    .container-fluid { padding: 0 15px; }
    .hero-section {
      background: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.65)), url('https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?ixlib=rb-4.0.3&auto=format&fit=crop&q=80') center/cover;
      color: white;
      padding: 60px 20px;
      text-align: center;
      margin-bottom: 40px;
      border-radius: 0 0 20px 20px;
    }
    .hero-section h1 { font-size: 2.5rem; font-weight: 700; margin-bottom: 15px; }
    .hero-section p { font-size: 1.1rem; opacity: 0.9; max-width: 600px; margin: 0 auto; }

    /* Search Bar */
    .search-container {
      max-width: 600px;
      margin: 20px auto;
      display: flex;
      gap: 10px;
      align-items: stretch;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      border-radius: 50px;
      overflow: hidden;
    }
    .search-input {
      flex-grow: 1;
      border: none;
      padding: 12px 20px;
      font-size: 1rem;
      outline: none;
    }
    .search-btn {
      background: #28a745;
      color: white;
      border: none;
      padding: 12px 25px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }
    .search-btn:hover { background: #218838; }

    /* Sidebar Filter */
    .filter-sidebar {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      height: fit-content;
      position: sticky;
      top: 20px;
    }
    .filter-title {
      font-weight: 600;
      font-size: 1.1rem;
      margin-bottom: 15px;
      border-bottom: 2px solid #e9ecef;
      padding-bottom: 10px;
    }
    .filter-category {
      margin-bottom: 20px;
    }
    .filter-category h6 {
      font-weight: 600;
      margin-bottom: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
    }
    .filter-category h6 i {
      transition: transform 0.2s;
    }
    .filter-category.collapsed h6 i {
      transform: rotate(-90deg);
    }
    .filter-options .form-check {
    margin-left: 8px;
    position: relative;
}
    .filter-options {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease-out;
    }
    .filter-category:not(.collapsed) .filter-options {
      max-height: 500px;
    }
    .form-check-label {
      margin-left: 8px;
      font-size: 0.9rem;
    }
    .range-slider {
      width: 100%;
      margin: 10px 0;
    }
    .apply-filter-btn {
      width: 100%;
      background: #28a745;
      color: white;
      border: none;
      padding: 12px;
      font-weight: 600;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.2s;
      margin-top: 15px;
    }
    .apply-filter-btn:hover { background: #218838; }
    .clear-all-btn {
      width: 100%;
      background: #f8f9fa;
      color: #6c757d;
      border: none;
      padding: 10px;
      font-weight: 600;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.2s;
      margin-top: 10px;
    }
    .clear-all-btn:hover { background: #e9ecef; }

    /* Main Content */
    .main-content {
      padding: 0 15px;
    }
    .results-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .results-count {
      font-size: 0.9rem;
      color: #6c757d;
    }
    .sort-dropdown {
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 0.9rem;
    }

    /* Property Cards */
    .property-card {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      transition: transform 0.2s, box-shadow 0.2s;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    .property-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    .property-img-container {
      height: 200px;
      overflow: hidden;
      position: relative;
    }
    .property-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s;
    }
    .property-card:hover .property-img {
      transform: scale(1.05);
    }
    .property-badge {
      position: absolute;
      top: 10px;
      right: 10px;
      background: rgba(255,255,255,0.9);
      color: #28a745;
      padding: 4px 8px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .custom-select {
  background: white;
  border: 1px solid #ced4da;
  cursor: pointer;
  user-select: none;
  transition: all 0.2s ease;
  font-size: 0.95rem;
  color: #495057;
}

.custom-select:hover {
  border-color: #86b7fe;
}

.custom-select-options {
  max-height: 200px;
  overflow-y: auto;
  border-radius: 12px !important;
}

.custom-select-options .option-item {
  cursor: pointer;
  transition: background 0.15s;
}

.custom-select-options .option-item:hover {
  background: #f8f9fa;
}

/* Highlight opsi yang aktif (opsional) */
.custom-select-options .option-item.active {
  background: #e9f7ef;
  color: #28a745;
  font-weight: 500;
}
    .property-badge.putra { color: #1976d2; }
    .property-badge.putri { color: #c2185b; }
    .property-badge.campur { color: #f57c00; }

    .card-body {
      padding: 15px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }
    .property-title {
      font-weight: 600;
      font-size: 1.1rem;
      margin-bottom: 5px;
      line-height: 1.3;
    }
    .property-location {
      color: #6c757d;
      font-size: 0.85rem;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .property-price {
      font-weight: 700;
      color: #28a745;
      font-size: 1.2rem;
      margin: 10px 0;
    }
    .property-facilities {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin: 10px 0;
      font-size: 0.8rem;
    }
    .facility-item {
      background: #f8f9fa;
      padding: 4px 8px;
      border-radius: 6px;
      display: flex;
      align-items: center;
      gap: 4px;
    }
    .facility-item i {
      font-size: 0.8rem;
    }
    .property-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: auto;
      padding-top: 10px;
      border-top: 1px solid #e9ecef;
    }
    .btn-detail {
      background: #28a745;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 0.85rem;
      font-weight: 600;
      text-decoration: none;
      transition: background 0.2s;
    }
    .btn-detail:hover { background: #218838; }

    .btn-fav {
      border: 1.5px solid #dee2e6;
      color: #6c757d;
      border-radius: 6px;
      padding: 6px 10px;
      background: transparent;
      transition: all 0.3s ease;
    }
    .btn-fav:hover:not(:disabled) {
      background: #f8f9fa;
      border-color: #28a745;
      color: #28a745;
    }
    .btn-fav:disabled { opacity: 0.6; cursor: not-allowed; }
    .btn-fav.favorited {
      border-color: #dc3545;
      background: #fff5f5;
      color: #dc3545;
    }
    .btn-fav i { font-size: 1rem; }

    /* Pagination */
    .pagination {
      display: flex;
      justify-content: center;
      gap: 5px;
      margin-top: 30px;
    }
    .page-link {
      padding: 8px 12px;
      border: 1px solid #dee2e6;
      border-radius: 5px;
      color: #28a745;
      text-decoration: none;
      transition: 0.2s;
    }
    /* PAGINATION – Ukuran sedang, rapi, dan modern */
.pagination .page-link {
    width: 42px;           /* pas, tidak terlalu besar */
    height: 42px;          /* kotak proporsional */
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;       /* angka jelas tapi tidak jumbo */
    font-weight: 600;
    border-radius: 10px !important;   /* agak bulat, elegan */
    margin: 0 4px;
    transition: all 0.25s ease;
    color: #495057;
    border: 1.5px solid #dee2e6;
}

.pagination .page-link:hover:not(.disabled) {
    background-color: #28a745;
    color: white;
    border-color: #28a745;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40,167,69,0.25);
}

.pagination .page-link.active {
    background-color: #28a745 !important;
    color: white !important;
    border-color: #28a745 !important;
    font-weight: 700;
}

.pagination .page-link i {
    font-size: 1.1rem;
}

.pagination .page-link.disabled {
    color: #adb5bd;
    background-color: #f8f9fa;
    border-color: #e9ecef;
}
    .page-link.disabled {
    color: #adb5bd !important;
    pointer-events: none;
    background: #f8f9fa;
    border-color: #dee2e6;
    opacity: 0.6;
    }
    .page-link:hover { background: #28a745; color: white; }
    .page-link.active { background: #28a745; color: white; border-color: #28a745; }

    @keyframes fa-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .fa-spin { animation: fa-spin 1s infinite linear; }
  </style>
</head>
<body>

<?php include("navbar.php"); ?>

<br><br>

<div class="container-fluid">
  <!-- Hero Section -->
  <div class="hero-section">
    <h1>Find your Next Home</h1>
    <p>Search your dream boarding house now in an easy and fast way</p>
      <!-- Search Bar -->
  <form action="explore.php" method="GET" class="search-container">
  <input 
    type="text" 
    name="location" 
    class="search-input" 
    placeholder="Masukkan kota, kecamatan atau alamat..." 
    value="<?php echo htmlspecialchars($search_location); ?>" 
    autocomplete="off">
  <button type="submit" class="search-btn">
    <i class="bi bi-search"></i>
  </button>
</form>
  </div>

  <div class="row">
    <!-- Sidebar Filter -->
    <div class="col-lg-3">
      <div class="filter-sidebar">
        <h5 class="filter-title">Filters</h5>
        
        <!-- Tipe Kost -->
        <div class="filter-category collapsed">
          <h6 data-toggle="collapse" data-target="#typeFilter">
            Tipe Kost <i class="bi bi-chevron-down"></i>
          </h6>
          <div id="typeFilter" class="filter-options">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="type" id="typeAll" value="" <?php echo empty($filter_type) ? 'checked' : ''; ?>>
              <label class="form-check-label" for="typeAll">Semua Tipe</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="type" id="typePutra" value="putra" <?php echo $filter_type === 'putra' ? 'checked' : ''; ?>>
              <label class="form-check-label" for="typePutra">Putra</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="type" id="typePutri" value="putri" <?php echo $filter_type === 'putri' ? 'checked' : ''; ?>>
              <label class="form-check-label" for="typePutri">Putri</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="type" id="typeCampur" value="campur" <?php echo $filter_type === 'campur' ? 'checked' : ''; ?>>
              <label class="form-check-label" for="typeCampur">Campur</label>
            </div>
          </div>
        </div>

        <!-- Harga -->
        <div class="filter-category collapsed">
          <h6 data-toggle="collapse" data-target="#priceFilter">
            Harga <i class="bi bi-chevron-down"></i>
          </h6>
          <div id="priceFilter" class="filter-options">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="price" id="priceAll" value="" <?php echo empty($filter_price) ? 'checked' : ''; ?>>
              <label class="form-check-label" for="priceAll">Semua Harga</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="price" id="price500" value="500000" <?php echo $filter_price === '500000' ? 'checked' : ''; ?>>
              <label class="form-check-label" for="price500">≤ Rp 500.000</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="price" id="price1M" value="1000000" <?php echo $filter_price === '1000000' ? 'checked' : ''; ?>>
              <label class="form-check-label" for="price1M">≤ Rp 1.000.000</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="price" id="price2M" value="2000000" <?php echo $filter_price === '2000000' ? 'checked' : ''; ?>>
              <label class="form-check-label" for="price2M">≤ Rp 2.000.000</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="price" id="price5M" value="5000000" <?php echo $filter_price === '5000000' ? 'checked' : ''; ?>>
              <label class="form-check-label" for="price5M">≤ Rp 5.000.000</label>
            </div>
          </div>
        </div>

            <!-- Fasilitas -->
            <div class="filter-category">
            <h6 data-toggle="collapse" data-target="#facilitiesFilter">
                Fasilitas <i class="bi bi-chevron-down"></i>
            </h6>
            <div id="facilitiesFilter" class="filter-options">
                <?php foreach ($all_facilities as $f): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" 
                        name="facilities[]" 
                        value="<?= (int)$f['id'] ?>"
                        id="fac-<?= (int)$f['id'] ?>"
                        <?= in_array($f['id'], $selected_facility_ids) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="fac-<?= (int)$f['id'] ?>">
                        <i class="bi <?= htmlspecialchars($f['icon']) ?>"></i>
                    <?= htmlspecialchars($f['name']) ?>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
            </div>

        <!-- Apply & Clear Buttons -->
        <button type="button" class="apply-filter-btn" onclick="applyFilters()">Apply Filter</button>
        <button type="button" class="clear-all-btn" onclick="clearAllFilters()">Clear All</button>
      </div>
    </div>

    <!-- Main Content -->
    <div class="col-lg-9">
      <div class="main-content">
        <!-- Results Header -->
        <div class="results-header">
          <div class="results-count">Menampilkan <?php echo min($limit, $total_properties); ?> dari <?php echo $total_properties; ?> kos</div>
        <div class="custom-select-wrapper" style="width: 240px; position: relative;">
  <div class="custom-select rounded-pill px-4 py-2 shadow-sm d-flex justify-content-between align-items-center"
       onclick="this.nextElementSibling.classList.toggle('d-none')">
<span class="selected-text">
    <?= $sort === 'price_low' ? 'Harga Terendah' : ($sort === 'price_high' ? 'Harga Tertinggi' : 'Terbaru') ?>
</span>
  <i class="bi bi-chevron-down text-muted"></i>
  </div>
  <ul class="custom-select-options shadow rounded-3 d-none position-absolute w-100 bg-white mt-1 z-1 list-unstyled">
  <li class="px-3 py-2 option-item <?= $sort === 'newest' ? 'active' : '' ?>" data-value="newest">Terbaru</li>
  <li class="px-3 py-2 option-item <?= $sort === 'price_low' ? 'active' : '' ?>" data-value="price_low">Harga Terendah</li>
  <li class="px-3 py-2 option-item <?= $sort === 'price_high' ? 'active' : '' ?>" data-value="price_high">Harga Tertinggi</li>
</ul>
  <!-- Hidden select tetap ada untuk aksesibilitas (opsional tapi baik) -->
  <select class="d-none" id="sort-select" onchange="applySort(this.value)">
    <option value="newest">Terbaru</option>
    <option value="price_low">Harga Terendah</option>
    <option value="price_high">Harga Tertinggi</option>
  </select>
</div>
        </div>

        <!-- Properties Grid -->
        <div class="row g-4">
          <?php if (empty($properties)): ?>
            <div class="col-12">
              <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
                <h5>Tidak ada kos ditemukan</h5>
                <p>Coba ubah filter pencarian Anda</p>
                <a href="explore.php" class="btn btn-success mt-3">Reset Filter</a>
              </div>
            </div>
          <?php else: ?>
            <?php foreach ($properties as $property): ?>
              <div class="col-md-4">
                <div class="property-card">
                  <div class="property-img-container">
                    <?php if (!empty($property['image'])): ?>
                      <img src="<?php echo htmlspecialchars('/Web-App/' . $property['image']); ?>" 
                        class="property-img" 
                        alt="<?php echo htmlspecialchars($property['name']); ?>"
                        onerror="handleImageError(this)">
                    <?php else: ?>
                      <div style="height:200px;display:flex;align-items:center;justify-content:center;background:#f0f0f0;color:#6c757d;">
                        <i class="bi bi-building fs-2"></i>
                      </div>
                    <?php endif; ?>
                    <span class="property-badge <?php echo $property['kos_type']; ?>">
                      <?php echo ucfirst($property['kos_type']); ?>
                    </span>
                  </div>
                  <div class="card-body">
                    <h6 class="property-title"><?php echo htmlspecialchars($property['name']); ?></h6>
                    <div class="property-location">
                      <i class="bi bi-geo-alt-fill"></i>
                      <?php echo htmlspecialchars($property['city'] . ', ' . $property['province']); ?>
                    </div>
                    <div class="property-price">Rp <?php echo number_format($property['price_monthly'], 0, ',', '.'); ?></div>
                    
                    <?php if (!empty($property['facilities'])): ?>
                      <div class="property-facilities">
                        <?php foreach ($property['facilities'] as $facility): ?>
                          <span class="facility-item">
                            <i class="fa <?php echo htmlspecialchars($facility['icon']); ?>"></i>
                            <?php echo htmlspecialchars($facility['name']); ?>
                          </span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                    
                    <div class="property-footer">
                      <a href="detail_kos.php?id=<?php echo $property['id']; ?>" class="btn-detail">Lihat Detail</a>
                      <button class="btn-fav <?php echo $property['is_favorited'] ? 'favorited' : ''; ?>" 
                              onclick="toggleFavorite(<?php echo $property['id']; ?>, this)">
                        <i class="bi <?php echo $property['is_favorited'] ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <?php
        // Fungsi bantu: bangun query string dengan semua filter
        function buildPaginationUrl($page, $search_location, $filter_type, $filter_price, $selected_facility_ids) {
            $params = [];
            if ($search_location !== '') $params[] = 'location=' . urlencode($search_location);
            if ($filter_type !== '') $params[] = 'type=' . urlencode($filter_type);
            if ($filter_price !== '') $params[] = 'price=' . urlencode($filter_price);
            foreach ($selected_facility_ids as $fid) {
                $params[] = 'facilities[]=' . urlencode($fid);
            }
            $params[] = 'page=' . (int)$page;
            return 'explore.php?' . implode('&', $params);
        }
        ?>

        <!-- Pagination -->
<!-- Pagination - SELALU MUNCUL (bahkan kalau ≤30 item) -->
<div class="pagination">

  <?php
  $total_pages  = ceil($total_properties / $limit);
  $current_page = max(1, min($current_page, $total_pages ?: 1));
  ?>

  <!-- Previous -->
  <?php if ($current_page > 1): ?>
    <?php
    $params = [];
    if ($search_location) $params[] = 'location=' . urlencode($search_location);
    if ($filter_type)      $params[] = 'type=' . urlencode($filter_type);
    if ($filter_price)     $params[] = 'price=' . urlencode($filter_price);
    foreach ($selected_facility_ids as $fid) {
        $params[] = 'facilities[]=' . urlencode($fid);
    }
    if (isset($_GET['sort'])) $params[] = 'sort=' . urlencode($_GET['sort']);
    $params[] = 'page=' . ($current_page - 1);
    ?>
    <a href="explore.php?<?= implode('&', $params) ?>" class="page-link">
      <i class="bi bi-chevron-left"></i>
    </a>
  <?php else: ?>
    <span class="page-link disabled"><i class="bi bi-chevron-left"></i></span>
  <?php endif; ?>

  <!-- Nomor Halaman -->
  <?php
  $start_page = max(1, $current_page - 2);
  $end_page   = min($total_pages, $current_page + 2);

  // Halaman 1 + ...
  if ($start_page > 1) {
    $params = [];
    if ($search_location) $params[] = 'location=' . urlencode($search_location);
    if ($filter_type)      $params[] = 'type=' . urlencode($filter_type);
    if ($filter_price)     $params[] = 'price=' . urlencode($filter_price);
    foreach ($selected_facility_ids as $fid) $params[] = 'facilities[]=' . urlencode($fid);
    if (isset($_GET['sort'])) $params[] = 'sort=' . urlencode($_GET['sort']);
    $params[] = 'page=1';
    echo '<a href="explore.php?' . implode('&', $params) . '" class="page-link">1</a>';
    if ($start_page > 2) echo '<span class="page-link disabled">...</span>';
  }

  // Halaman tengah
  for ($i = $start_page; $i <= $end_page; $i++) {
    $params = [];
    if ($search_location) $params[] = 'location=' . urlencode($search_location);
    if ($filter_type)      $params[] = 'type=' . urlencode($filter_type);
    if ($filter_price)     $params[] = 'price=' . urlencode($filter_price);
    foreach ($selected_facility_ids as $fid) $params[] = 'facilities[]=' . urlencode($fid);
    if (isset($_GET['sort'])) $params[] = 'sort=' . urlencode($_GET['sort']);
    $params[] = 'page=' . $i;

    $active = ($i == $current_page) ? 'active' : '';
    echo '<a href="explore.php?' . implode('&', $params) . '" class="page-link ' . $active . '">' . $i . '</a>';
  }

  // ... + Halaman terakhir
  if ($end_page < $total_pages) {
    if ($end_page < $total_pages - 1) echo '<span class="page-link disabled">...</span>';
    $params = [];
    if ($search_location) $params[] = 'location=' . urlencode($search_location);
    if ($filter_type)      $params[] = 'type=' . urlencode($filter_type);
    if ($filter_price)     $params[] = 'price=' . urlencode($filter_price);
    foreach ($selected_facility_ids as $fid) $params[] = 'facilities[]=' . urlencode($fid);
    if (isset($_GET['sort'])) $params[] = 'sort=' . urlencode($_GET['sort']);
    $params[] = 'page=' . $total_pages;
    echo '<a href="explore.php?' . implode('&', $params) . '" class="page-link">' . $total_pages . '</a>';
  }
  ?>

  <!-- Next -->
  <?php if ($current_page < $total_pages): ?>
    <?php
    $params = [];
    if ($search_location) $params[] = 'location=' . urlencode($search_location);
    if ($filter_type)      $params[] = 'type=' . urlencode($filter_type);
    if ($filter_price)     $params[] = 'price=' . urlencode($filter_price);
    foreach ($selected_facility_ids as $fid) {
        $params[] = 'facilities[]=' . urlencode($fid);
    }
    if (isset($_GET['sort'])) $params[] = 'sort=' . urlencode($_GET['sort']);
    $params[] = 'page=' . ($current_page + 1);
    ?>
    <a href="explore.php?<?= implode('&', $params) ?>" class="page-link">
      <i class="bi bi-chevron-right"></i>
    </a>
  <?php else: ?>
    <span class="page-link disabled"><i class="bi bi-chevron-right"></i></span>
  <?php endif; ?>

</div>
      </div>
    </div>
  </div>
</div>

<!-- Toast Login Modern -->
<div id="loginToast" class="position-fixed top-0 end-0 p-4" style="z-index: 9999; display: none;">
  <div class="toast align-items-center text-bg-light border-0 shadow-lg" role="alert" style="min-width: 340px;">
    <div class="d-flex">
      <div class="toast-body text-center py-4">
        <i class="bi bi-shield-lock-fill text-success mb-3" style="font-size: 3rem;"></i>
        <h5 class="mb-3">Login Diperlukan</h5>
        <p class="mb-4 text-muted">Silakan login untuk menambahkan kost ke wishlist</p>
        <div class="d-flex gap-2 justify-content-center">
          <button type="button" class="btn btn-outline-secondary rounded-pill px-4" onclick="closeLoginToast()">Batal</button>
          <a href="/Web-App/frontend/auth/login.php" class="btn btn-success rounded-pill px-5">Login Sekarang</a>
        </div>
      </div>
      <button type="button" class="btn-close btn-close me-2 m-auto" onclick="closeLoginToast()"></button>
    </div>
  </div>
</div>

<style>
  #loginToast .toast {
    border-radius: 20px;
    overflow: hidden;
    animation: slideInRight 0.4s ease;
  }
  @keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
  }
</style>

<script>
function closeLoginToast() {
  document.getElementById('loginToast').style.display = 'none';
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Toggle Filter Sections
document.querySelectorAll('.filter-category h6').forEach(function(header) {
  header.addEventListener('click', function() {
    const category = this.closest('.filter-category');
    category.classList.toggle('collapsed');
  });
});

// Apply Filters
function applyFilters() {
    const urlParams = new URLSearchParams();
    
    const location = document.querySelector('.search-input').value.trim();
    if (location) urlParams.append('location', location);
    
    const type = document.querySelector('input[name="type"]:checked')?.value;
    if (type) urlParams.append('type', type);
    
    const price = document.querySelector('input[name="price"]:checked')?.value;
    if (price) urlParams.append('price', price);
    
    // Ambil semua fasilitas yang dipilih
    const facilityCheckboxes = document.querySelectorAll('input[name="facilities[]"]:checked');
    facilityCheckboxes.forEach(cb => {
        urlParams.append('facilities[]', cb.value);
    });
    
    window.location.href = 'explore.php?' + urlParams.toString();
}

// Clear All Filters
function clearAllFilters() {
    document.querySelector('.search-input').value = '';
    document.querySelector('#typeAll').checked = true;
    document.querySelector('#priceAll').checked = true;
    // Hapus semua centang fasilitas
    document.querySelectorAll('input[name="facilities[]"]').forEach(cb => cb.checked = false);
    applyFilters();
}

// Sort Properties (Placeholder)
function applySort(sortValue) {
  const urlParams = new URLSearchParams(window.location.search);
  urlParams.set('sort', sortValue);
  urlParams.delete('page'); // reset ke halaman 1
  window.location.href = 'explore.php?' + urlParams.toString();
}

// ========================================
// TOGGLE FAVORITE + CEK LOGIN DULU
// ========================================
function toggleFavorite(kosId, btn) {
    <?php if ($isLoggedIn): ?>
        // SUDAH LOGIN → langsung proses favorit
        const icon = btn.querySelector('i');
        const wasFavorited = icon.classList.contains('bi-heart-fill');

        // Animasi loading
        icon.className = 'bi bi-arrow-repeat fa-spin';
        btn.disabled = true;

        fetch('/Web-App/backend/user/customer/classes/save_kos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ kos_id: kosId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (data.favorited) {
                    icon.className = 'bi bi-heart-fill';
                    btn.classList.add('favorited');
                } else {
                    icon.className = 'bi bi-heart';
                    btn.classList.remove('favorited');
                }

                // Toast notifikasi
                const toast = document.createElement('div');
                toast.className = 'position-fixed top-0 end-0 p-3';
                toast.style.zIndex = '9999';
                toast.innerHTML = `
                    <div class="alert alert-success alert-dismissible fade show mb-0">
                        <i class="bi bi-check-circle"></i> ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            } else {
                alert(data.message || 'Gagal memperbarui wishlist');
                icon.className = wasFavorited ? 'bi bi-heart-fill' : 'bi bi-heart';
            }
        })
        .catch(() => {
            alert('Koneksi gagal!');
            icon.className = wasFavorited ? 'bi bi-heart-fill' : 'bi bi-heart';
        })
        .finally(() => btn.disabled = false);

    <?php else: ?>
        // BELUM LOGIN → munculin modal login
        document.getElementById('loginToast').style.display = 'block';
        setTimeout(() => {
        document.querySelector('#loginToast .toast').classList.add('show');
        }, 100);
    <?php endif; ?>
}
</script>

<script>
document.querySelectorAll('.option-item').forEach(item => {
  item.addEventListener('click', function() {
    const value = this.getAttribute('data-value');
    const text = this.textContent;
    
    // Update tampilan
    this.closest('.custom-select-wrapper')
        .querySelector('.selected-text').textContent = text;
    
    // Sembunyikan menu
    this.closest('.custom-select-options').classList.add('d-none');
    
    // Trigger perubahan (seperti onchange)
    applySort(value);
    
    // (Opsional) Update hidden select
    document.getElementById('sort-select').value = value;
  });
});

// Tutup dropdown jika klik di luar
document.addEventListener('click', function(e) {
  if (!e.target.closest('.custom-select-wrapper')) {
    document.querySelectorAll('.custom-select-options').forEach(menu => {
      menu.classList.add('d-none');
    });
  }
});
</script>

<script>
function handleImageError(imgElement) {
    const container = imgElement.parentElement;
    container.innerHTML = `
        <div style="height:200px; display:flex; align-items:center; justify-content:center; background:#f0f0f0; color:#6c757d;">
            <i class="bi bi-image fs-2"></i>
        </div>
    `;
}
</script>

</body>
</html>