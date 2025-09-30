<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Home - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: #f5f6f8;
    }
    /* Kos Status */
    .kos-status {
      border-radius: 16px;
      overflow: hidden;
      display: flex;
      justify-content: space-between;
      align-items: stretch;
      background: #f8f9fa;
    }
    .kos-status .left {
      background: #f8f9fa;
      color: black;
      padding: 20px;
      flex: 1;
    }
    .kos-status .right {
      background: #ffe5e5;
      width: 150px;
    }

    /* Selection box */
    .selection-box {
      background: white;
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      text-align: center;
      padding: 20px 10px;
      min-width: 100px;
      cursor: pointer;
      transition: 0.2s;
    }
    .selection-box.active {
      border-color: #28a745;
      background: #28a745;
      color: white;
      font-weight: bold;
    }

    /* Location tags */
    .location-tag {
      background: #f1f3f5;
      border-radius: 20px;
      padding: 8px 16px;
      margin: 5px;
      display: inline-block;
      cursor: pointer;
      transition: 0.2s;
    }
    .location-tag.active {
      background: #28a745;
      color: white;
    }

    /* Recommendation */
    .recommend-box {
      border-radius: 12px;
      padding: 20px;
      min-height: 150px;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      color: white;
      font-weight: bold;
      font-size: 1.1rem;
    }
    .recommend-fav {
      background: linear-gradient(to top, #dc3545 20%, #28a745 80%);
    }
    .recommend-promo {
      background: #6c757d;
    }
  </style>
</head>
<body>

<?php
session_start();
?>

<!-- navbar.php untuk Owner -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4 fixed-top" style="height:65px;">
  <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
    <img src="../../assets/logo_kos.png" alt="logo" style="height:30px;" class="me-2">
    KostHub
  </a>

  <div class="collapse navbar-collapse">
    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
      <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='dashboard.php' ? 'text-success fw-semibold' : ''; ?>" href="dashboard.php">Add Kos</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='your_property.php' ? 'text-success fw-semibold' : ''; ?>" href="your_property.php">Your Property</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='booking_list.php' ? 'text-success fw-semibold' : ''; ?>" href="booking_list.php">Booking List</a>
      </li>
    </ul>
  </div>

  <!-- Search -->
  <form class="d-flex me-3">
    <input class="form-control form-control-sm" type="search" placeholder="Search">
    <button class="btn btn-outline-success btn-sm ms-2" type="submit"><i class="bi bi-search"></i></button>
  </form>

  <!-- Login / Logout -->
  <div>
    <?php if(isset($_SESSION['username'])): ?>
      <a href="logout.php" class="btn btn-outline-danger btn-sm">🚪 Logout</a>
    <?php else: ?>
      <a href="frontend/auth/login.php" class="btn btn-outline-success btn-sm">🔑 Login</a>
    <?php endif; ?>
  </div>
</nav>



<div class="container my-4">
  <h4 class="fw-bold">Welcome Back, Username!</h4><br><br>

  <!-- Kos Status -->
     <h5 class="fw-bold mb-3">Kos Status</h5>
  <div class="kos-status my-4 shadow-sm">
    <div class="left">
      <p class="mb-1">Room 7</p>
      <h5 class="fw-bold mb-1">Kos The Raid</h5>
      <small>Contract ends in 2 months</small>
    </div>
    <div class="right"></div>
  </div>

  <!-- Selection -->
  <h5 class="fw-bold mb-3">Selection</h5>
  <div class="d-flex gap-3 mb-4">
    <div class="selection-box active">Putra</div>
    <div class="selection-box">Putri</div>
    <div class="selection-box">Outdoor bathroom</div>
    <div class="selection-box">AC</div>
    <div class="selection-box">Not AC</div>
  </div>

  <!-- Location -->
<h5 class="fw-bold mb-3">Location</h5>
<div class="mb-4 d-flex align-items-centerflex-wrap">
  <span class="location-tag d-flex align-items-center">
    <i class="bi bi-funnel me-1"></i>
  </span>
  <span class="location-tag active">Tegal Gede</span>
  <span class="location-tag">Sumber Sari</span>
  <span class="location-tag">Jl. Kalimantan</span>
  <span class="location-tag">Jl. Jawa</span>
  <span class="location-tag">Sumatra</span>
</div>


<!-- Recommendation -->
<h5 class="fw-bold mb-3">Recommendation</h5>
<div class="row g-3">
  <div class="col-md-6">
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
      <!-- Gambar -->
      <img src="../assets/favorite.jpg" class="card-img-top" alt="Favorite" style="height:180px; object-fit:cover;">
      <!-- Isi teks -->
      <div class="card-body text-left text-black">
        <h6 class="m-0">Favorite</h6>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
      <!-- Gambar -->
      <img src="../../assets/logo_login.svg" class="card-img-top" alt="Promo" style="height:180px; object-fit:contain; background:#f8f9fa;">
      <!-- Isi teks -->
      <div class="card-body text-left text-black">
        <h6 class="m-0">Promo</h6>
      </div>
    </div>
  </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
