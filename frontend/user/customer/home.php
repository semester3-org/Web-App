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
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Home - KostHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
  body {
    font-family: 'Poppins', sans-serif;
    background-color: #f9fcfaff;
    color: #222;
    overflow-x: hidden;
  }

  /* HERO SECTION */
  .hero {
    position: relative;
    height: 95vh;
    background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), url('/Web-App/frontend/assets/hero-bg.jpg') center/cover no-repeat;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
    color: #fff;
  }
  .hero h1 {
    font-size: 3.2rem;
    font-weight: 700;
  }
  .hero p {
    font-size: 1.2rem;
    margin-top: 15px;
  }

  /* PROMO SECTION */
  .promo {
    padding: 80px 0;
  }
  .promo h2 {
    font-weight: 700;
    margin-bottom: 50px;
  }
  .promo-card {
    position: relative;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  .promo-card:hover {
    transform: scale(1.03);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
  }
  .promo-card img {
    width: 100%;
    height: 250px;
    object-fit: cover;
    filter: brightness(85%);
    transition: 0.4s ease;
  }
  .promo-card:hover img {
    filter: brightness(100%);
  }
  .promo-text {
    position: absolute;
    bottom: 15px;
    left: 20px;
    color: white;
    font-weight: 600;
    text-shadow: 0 3px 6px rgba(0,0,0,0.4);
  }

  /* GALLERY SECTION */
  .gallery {
    background: #f5f6f8;
    padding: 100px 0;
  }
  .gallery h2 {
    font-weight: 700;
    margin-bottom: 50px;
  }
  .gallery img {
    width: 100%;
    height: 250px;
    object-fit: cover;
    border-radius: 15px;
    transition: 0.3s;
  }
  .gallery img:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
  }

  /* FOOTER */
  footer {
    background: #212529;
    color: #ddd;
    padding: 40px 0;
    text-align: center;
  }

  @media (max-width: 768px) {
    .hero h1 { font-size: 2.2rem; }
    .promo-card img, .gallery img { height: 180px; }
  }
  </style>
</head>
<body>

<?php include("navbar.php"); ?>

<!-- Hero Section -->
<section class="hero">
  <div class="container">
    <h1>Temukan Kos Impianmu di KostHub</h1>
    <p>Solusi cepat, mudah, dan modern untuk mencari tempat tinggal nyaman di sekitar kampus.</p>
  </div>
</section>

<!-- Promo Section -->
<section class="promo container text-center">
  <h2 class="fw-bold">Promosi Spesial</h2>
  <div class="row g-4">
    <div class="col-md-4">
      <div class="promo-card">
        <img src="/Web-App/frontend/assets/promo1.jpg" alt="Promo Kost Modern">
        <div class="promo-text">Diskon 20% Kost Modern</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="promo-card">
        <img src="/Web-App/frontend/assets/promo2.jpg" alt="Kost Dekat Kampus">
        <div class="promo-text">Kost Dekat Kampus</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="promo-card">
        <img src="/Web-App/frontend/assets/promo3.jpg" alt="Kost Nyaman & Aman">
        <div class="promo-text">Kost Nyaman & Aman</div>
      </div>
    </div>
  </div>
</section>

<!-- Gallery Section -->
<section class="gallery">
  <div class="container">
    <h2 class="fw-bold text-center">Suasana Kost yang Nyaman</h2>
    <div class="row g-4">
      <div class="col-md-3 col-6">
        <img src="/Web-App/frontend/assets/gal1.jpg" alt="Interior Kost">
      </div>
      <div class="col-md-3 col-6">
        <img src="/Web-App/frontend/assets/gal2.jpg" alt="Fasilitas Bersih">
      </div>
      <div class="col-md-3 col-6">
        <img src="/Web-App/frontend/assets/gal3.jpg" alt="Kamar Minimalis">
      </div>
      <div class="col-md-3 col-6">
        <img src="/Web-App/frontend/assets/gal4.jpg" alt="Suasana Asri">
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer>
  <div class="container">
    <p>&copy; <?= date('Y') ?> KostHub. Semua hak dilindungi.</p>
    <small>Dibangun dengan ❤️ oleh Tim KostHub</small>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
