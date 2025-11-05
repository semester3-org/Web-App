<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user';

$profilePic = '/Web-App/frontend/assets/default-avatar.png';
$fullName = 'Guest';

// Hanya ambil data user kalau benar-benar login
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

  /* Recommendation Cards */
  .recommend-card {
    border-radius: 12px;
    overflow: hidden;
    transform: translateY(20px);
    opacity: 0;
    animation: fadeInUp 0.8s ease forwards;
  }

  .recommend-card img {
    transition: transform 0.4s ease;
  }

  .recommend-card:hover img {
    transform: scale(1.08);
  }

  .recommend-card:hover {
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
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
    <div class="card recommend-card shadow-sm border-0 rounded-3 overflow-hidden">
      <!-- Gambar -->
      <img src="../assets/favorite.jpg" class="card-img-top" alt="Favorite" style="height:180px; object-fit:cover;">
      <!-- Isi teks -->
      <div class="card-body text-left text-black">
        <h6 class="m-0">Favorite</h6>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card recommend-card shadow-sm border-0 rounded-3 overflow-hidden">
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
