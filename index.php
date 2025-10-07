<?php
// Panggil session hanya sekali
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['username']);
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>KostHub - Temukan Kost Terbaikmu</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
      padding-top: 65px;
    }
    .hero {
      height: 85vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(to right, #28a745, #007bff);
      color: white;
      text-align: center;
    }
    .hero h1 {
      font-size: 3rem;
      font-weight: bold;
    }
    .feature-box {
      background: white;
      border-radius: 16px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      padding: 30px;
      text-align: center;
      transition: all 0.3s ease;
    }
    .feature-box:hover {
      transform: translateY(-5px);
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<?php include __DIR__ . '/frontend/component/navbar.php'; ?>

<!-- ============================== -->
<!-- LANDING PAGE (sebelum login) -->
<!-- ============================== -->
<?php if (!$isLoggedIn): ?>
  <section class="hero">
    <div class="container">
      <h1>Temukan Kost Impianmu Bersama <span class="text-warning">KostHub</span></h1>
      <p class="lead mt-3">Cari, bandingkan, dan pesan kost terbaik hanya dalam hitungan detik.</p>
      <a href="/Web-App/frontend/auth/login.php" class="btn btn-light btn-lg mt-4">Mulai Sekarang</a>
    </div>
  </section>

  <div class="container my-5">
    <div class="text-center mb-4">
      <h3 class="fw-bold">Kenapa Pilih KostHub?</h3>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="feature-box">
          <i class="bi bi-search fs-2 text-success mb-3"></i>
          <h5>Pencarian Mudah</h5>
          <p>Cari kost sesuai lokasi, fasilitas, dan harga dalam satu klik.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-box">
          <i class="bi bi-shield-check fs-2 text-success mb-3"></i>
          <h5>Terjamin Aman</h5>
          <p>Semua pemilik dan kost telah diverifikasi oleh tim kami.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-box">
          <i class="bi bi-heart fs-2 text-success mb-3"></i>
          <h5>Favoritmu Tersimpan</h5>
          <p>Simak dan bandingkan kost favorit tanpa kehilangan data.</p>
        </div>
      </div>
    </div>
  </div>

<!-- ============================== -->
<!-- HOME CUSTOMER (setelah login) -->
<!-- ============================== -->
<?php else: ?>
  <div class="container my-5 pt-3">
    <h4 class="fw-bold mt-4">Welcome Back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h4><br>

    <h5 class="fw-bold mb-3">Kos Status</h5>
    <div class="p-3 mb-4 bg-white rounded shadow-sm">
      <p class="mb-1">Room 7</p>
      <h5 class="fw-bold mb-1">Kos The Raid</h5>
      <small>Contract ends in 2 months</small>
    </div>

    <h5 class="fw-bold mb-3">Recommendation</h5>
    <div class="row g-3">
      <div class="col-md-6">
        <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
          <img src="/Web-App/frontend/assets/favorite.jpg" class="card-img-top" alt="Favorite" style="height:180px; object-fit:cover;">
          <div class="card-body text-left text-black">
            <h6 class="m-0">Favorite</h6>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
          <img src="/Web-App/frontend/assets/logo_login.svg" class="card-img-top" alt="Promo" style="height:180px; object-fit:contain; background:#f8f9fa;">
          <div class="card-body text-left text-black">
            <h6 class="m-0">Promo</h6>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
