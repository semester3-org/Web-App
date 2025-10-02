<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Wishlist - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      padding-top: 80px; /* biar ga ketutup navbar */
    }

    .wishlist-card {
      border-radius: 12px;
      overflow: hidden;
      display: flex;
      margin-bottom: 1rem;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      background: #fff;
      min-height: 200px;

      /* animasi muncul */
      transform: translateY(20px);
      opacity: 0;
      animation: fadeInUp 0.8s ease forwards;
    }

    .wishlist-card:hover {
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      transform: translateY(-5px);
      transition: 0.3s ease;
    }

    .wishlist-info {
      flex: 3;
      padding: 1.2rem;
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
    }

    .wishlist-img img {
      max-width: 100%;
      max-height: 200px;
      object-fit: contain;
      display: block;
      transition: transform 0.4s ease;
    }

    .wishlist-card:hover img {
      transform: scale(1.08);
    }

    .wishlist-info small {
      font-weight: 600;
      color: #6c757d;
    }

    .wishlist-info p {
      margin: 0;
    }

    .wishlist-info .price {
      font-weight: bold;
      font-size: 1.05rem;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(40px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>
<body>

<?php include("navbar.php"); ?>

<div class="container">
  <h4 class="fw-bold mb-4">Wishlist, Username!</h4>

  <!-- Card Wishlist 1 -->
  <div class="wishlist-card">
    <div class="wishlist-info">
      <small>Putra</small>
      <p class="fw-bold">Kos The Raid</p>
      <div class="d-flex justify-content-between align-items-center">
        <p class="price m-0">Rp 400.000 <small>/ bulan</small></p>
        <i class="bi bi-cart fs-3 text-success"></i>
      </div>
    </div>
    <div class="wishlist-img">
      <img src="../../assets/logo_login.svg" alt="Kos">
    </div>
  </div>

  <!-- Card Wishlist 2 -->
  <div class="wishlist-card">
    <div class="wishlist-info">
      <small>Putri</small>
      <p class="fw-bold">Kos Mawar</p>
      <div class="d-flex justify-content-between align-items-center">
        <p class="price m-0">Rp 450.000 <small>/ bulan</small></p>
        <i class="bi bi-cart fs-3 text-success"></i>
      </div>
    </div>
    <div class="wishlist-img">
      <img src="../assets/kos2.jpg" alt="Kos">
    </div>
  </div>

  <!-- Card Wishlist 3 -->
  <div class="wishlist-card">
    <div class="wishlist-info">
      <small>Campur</small>
      <p class="fw-bold">Kos Sakura</p>
      <div class="d-flex justify-content-between align-items-center">
        <p class="price m-0">Rp 500.000 <small>/ bulan</small></p>
        <i class="bi bi-cart fs-3 text-success"></i>
      </div>
    </div>
    <div class="wishlist-img">
      <img src="../assets/kos3.jpg" alt="Kos">
    </div>
  </div>

  <!-- Card Wishlist 4 -->
  <div class="wishlist-card">
    <div class="wishlist-info">
      <small>Putra</small>
      <p class="fw-bold">Kos Melati</p>
      <div class="d-flex justify-content-between align-items-center">
        <p class="price m-0">Rp 550.000 <small>/ bulan</small></p>
        <i class="bi bi-cart fs-3 text-success"></i>
      </div>
    </div>
    <div class="wishlist-img">
      <img src="../assets/kos4.jpg" alt="Kos">
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
