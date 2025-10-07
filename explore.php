<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$isLoggedIn = isset($_SESSION['username']);
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Explore - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      padding-top: 70px;
    }
    .search-filter {
      background: #fff;
      border-radius: 12px;
      padding: 15px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
      margin-bottom: 20px;
    }
    .kost-card {
      border: none;
      border-radius: 12px;
      overflow: hidden;
      transition: 0.2s;
    }
    .kost-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 16px rgba(0,0,0,0.12);
    }
    .kost-img {
      height: 180px;
      object-fit: cover;
    }
    .kost-price {
      font-weight: bold;
      color: #28a745;
    }
    .kost-type {
      font-size: 0.8rem;
      font-weight: bold;
      border-radius: 10px;
      padding: 3px 8px;
      margin-right: 5px;
      background: #e9f7ef;
      color: #28a745;
    }
    .btn-detail {
      background: #28a745;
      border: none;
      color: white;
      font-weight: 600;
      padding: 5px 14px;
      border-radius: 8px;
      transition: 0.2s;
    }
    .btn-detail:hover {
      background: #218838;
    }
    .btn-fav {
      border: 1.5px solid #28a745;
      color: #28a745;
      border-radius: 8px;
      transition: 0.2s;
    }
    .btn-fav:hover {
      background: #28a745;
      color: white;
    }
  </style>
</head>
<body>

<?php include("frontend/component/navbar.php"); ?>

<div class="container my-4">
  <h4 class="fw-bold mb-4">Explore Kost</h4>

  <!-- Search & Filter -->
  <div class="search-filter d-flex flex-wrap gap-2 align-items-center">
    <input type="text" class="form-control flex-grow-1" placeholder="Search by location (e.g. Jakarta, Bandung)">
    <select class="form-select" style="max-width:200px;">
      <option value="">All Kost Types</option>
      <option value="putra">Male</option>
      <option value="putri">Female</option>
      <option value="campur">Mixed</option>
    </select>
    <select class="form-select" style="max-width:150px;">
      <option value="">Any Price</option>
      <option value="500000">≤ Rp 500.000</option>
      <option value="1000000">≤ Rp 1.000.000</option>
      <option value="2000000">≤ Rp 2.000.000</option>
    </select>
    <button class="btn btn-success"><i class="bi bi-search"></i></button>
  </div>

  <!-- Grid Kost -->
  <div class="row g-4">
    <?php
    // Dummy data kost
    $kosts = [
      ["img" => "kos1.jpg", "type" => "Male", "price" => "1.500.000", "name" => "Cozy Haven", "loc" => "Jakarta, Indonesia", "fasilitas" => ["wifi", "ac", "kitchen"]],
      ["img" => "kos2.jpg", "type" => "Female", "price" => "1.200.000", "name" => "Serene Suites", "loc" => "Bandung, Indonesia", "fasilitas" => ["wifi", "ac"]],
      ["img" => "kos3.jpg", "type" => "Mixed", "price" => "2.000.000", "name" => "Urban Retreat", "loc" => "Surabaya, Indonesia", "fasilitas" => ["wifi", "ac", "kitchen"]],
    ];

    foreach ($kosts as $kost): ?>
      <div class="col-md-4">
        <div class="card kost-card shadow-sm">
          <img src="frontend/assets/<?php echo $kost['img']; ?>" class="kost-img" alt="Kos">
          <div class="card-body">
            <span class="kost-type"><?php echo $kost['type']; ?></span>
            <span class="kost-price float-end">Rp <?php echo $kost['price']; ?> /mo</span>
            <h6 class="mt-2"><?php echo $kost['name']; ?></h6>
            <small class="text-muted"><?php echo $kost['loc']; ?></small>
            <div class="mt-2">
              <?php if (in_array("wifi", $kost['fasilitas'])) echo '<small><i class="bi bi-wifi"></i> Wi-Fi</small>'; ?>
              <?php if (in_array("ac", $kost['fasilitas'])) echo '<small class="ms-2"><i class="bi bi-snow"></i> AC</small>'; ?>
              <?php if (in_array("kitchen", $kost['fasilitas'])) echo '<small class="ms-2"><i class="bi bi-egg-fried"></i> Kitchen</small>'; ?>
            </div>
            <div class="d-flex justify-content-between mt-3">
              <?php if ($isLoggedIn): ?>
                <a href="/Web-App/frontend/user/customer/detail.php" class="btn btn-detail btn-sm">Detail</a>
                <button class="btn btn-fav btn-sm"><i class="bi bi-heart"></i></button>
              <?php else: ?>
                <a href="/Web-App/frontend/auth/login.php" class="btn btn-detail btn-sm" onclick="alert('Login terlebih dahulu untuk melihat detail!')">Detail</a>
                <button class="btn btn-fav btn-sm" onclick="alert('Login untuk menambahkan ke wishlist!')"><i class="bi bi-heart"></i></button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
