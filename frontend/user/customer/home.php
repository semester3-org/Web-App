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
  <title>KostHub — Tempat Tinggal Impian Mahasiswa</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --primary: #2563eb;
      --secondary: #10b981;
      --light: #f8f9fa;
      --dark: #1e293b;
      --shadow: 0 10px 40px rgba(0,0,0,0.05);
      --transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--light);
      color: var(--dark);
      overflow-x: hidden;
      line-height: 1.7;
    }

    /* ==================== HERO ==================== */
    .hero {
      position: relative;
      height: 100vh;
      background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%), 
                  url('/Web-App/frontend/assets/hero-bg.jpg') center/cover no-repeat;
      display: flex;
      align-items: center;
      overflow: hidden;
      padding-top: 65px; /* Tinggi navbar */
    }
    .hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at 70% 30%, rgba(37, 99, 235, 0.1) 0%, transparent 60%);
      z-index: 1;
    }
    .hero-content {
      position: relative;
      z-index: 2;
      max-width: 650px;
      padding: 0 2rem;
    }
    .hero h1 {
      font-size: 4rem;
      font-weight: 700;
      line-height: 1.1;
      margin-bottom: 1.5rem;
      color: var(--dark);
      animation: fadeInUp 1s ease-out;
    }
    .hero p {
      font-size: 1.3rem;
      color: #475569;
      margin-bottom: 2rem;
      font-weight: 400;
      animation: fadeInUp 1s ease-out 0.2s both;
    }
    .hero .btn {
      animation: fadeInUp 1s ease-out 0.4s both;
      padding: 0.75rem 2rem;
      font-size: 1.1rem;
      font-weight: 600;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* ==================== FEATURES ==================== */
    .features {
      padding: 120px 0;
      position: relative;
    }
    .section-title {
      text-align: center;
      margin-bottom: 80px;
    }
    .section-title h2 {
      font-size: 2.8rem;
      font-weight: 700;
      color: var(--dark);
      position: relative;
      display: inline-block;
    }
    .section-title h2::after {
      content: '';
      position: absolute;
      bottom: -12px;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 4px;
      background: var(--secondary);
      border-radius: 2px;
    }
    .feature-card {
      background: white;
      border-radius: 20px;
      padding: 50px 30px;
      text-align: center;
      box-shadow: var(--shadow);
      transition: var(--transition);
      height: 100%;
      position: relative;
      overflow: hidden;
    }
    .feature-card::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: linear-gradient(45deg, transparent, rgba(37, 99, 235, 0.05), transparent);
      transform: rotate(45deg);
      transition: var(--transition);
      z-index: -1;
    }
    .feature-card:hover::before {
      top: -10%;
      left: -10%;
    }
    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 50px rgba(0,0,0,0.1);
    }
    .feature-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 25px;
      color: white;
      font-size: 2rem;
      box-shadow: 0 10px 30px rgba(37, 99, 235, 0.2);
    }
    .feature-card h3 {
      font-size: 1.8rem;
      font-weight: 600;
      margin-bottom: 15px;
      color: var(--dark);
    }
    .feature-card p {
      color: #64748b;
      font-size: 1.1rem;
    }

    /* ==================== CTA ==================== */
    .cta {
      background: linear-gradient(135deg, #2563eb, #1e40af);
      color: white;
      padding: 100px 0;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .cta::before {
      content: '';
      position: absolute;
      top: -100px;
      right: -100px;
      width: 300px;
      height: 300px;
      background: rgba(255, 255, 255, 0.08);
      border-radius: 50%;
    }
    .cta h2 {
      font-size: 3rem;
      font-weight: 700;
      margin-bottom: 20px;
      position: relative;
      z-index: 2;
    }
    .cta p {
      font-size: 1.4rem;
      color: rgba(255,255,255,0.9);
      max-width: 700px;
      margin: 0 auto 40px;
      position: relative;
      z-index: 2;
    }
    .cta .btn {
      background: white;
      color: var(--primary);
      font-weight: 700;
      padding: 14px 40px;
      border-radius: 50px;
      font-size: 1.1rem;
      border: none;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      transition: var(--transition);
    }
    .cta .btn:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 40px rgba(0,0,0,0.3);
    }

    /* ==================== FOOTER ==================== */
    footer {
      background: #1e293b;
      color: #cbd5e1;
      padding: 50px 0 20px;
      text-align: center;
    }
    footer p {
      margin: 10px 0;
    }
    footer .social {
      margin-top: 20px;
    }
    footer .social a {
      color: #cbd5e1;
      margin: 0 10px;
      font-size: 1.5rem;
      transition: var(--transition);
    }
    footer .social a:hover {
      color: var(--secondary);
      transform: translateY(-3px);
    }

    /* Responsive */
    @media (max-width: 992px) {
      .hero h1 { font-size: 3rem; }
      .hero p { font-size: 1.2rem; }
      .section-title h2 { font-size: 2.4rem; }
      .feature-card { padding: 40px 25px; }
    }
    @media (max-width: 768px) {
      .hero h1 { font-size: 2.5rem; }
      .hero p { font-size: 1.1rem; }
      .hero-content { padding: 0 1rem; }
      .features, .cta { padding: 80px 0; }
      .cta h2 { font-size: 2.2rem; }
      .cta p { font-size: 1.2rem; }
    }
  </style>
</head>
<body>

<!-- Navbar -->
<?php include("navbar.php"); ?>

<!-- Hero Section -->
<section class="hero">
  <div class="container">
    <div class="hero-content">
      <h1>Kost Impianmu, Hanya Satu Klik Lagi</h1>
      <p>Temukan tempat tinggal nyaman, aman, dan terjangkau di sekitar kampus — tanpa ribet, tanpa penipuan, tanpa stres.</p>
      <a href="/Web-App/frontend/user/customer/explore.php" class="btn btn-lg btn-success">Jelajahi Kost Sekarang</a>
    </div>
  </div>
</section>

<!-- Features Section -->
<section class="features">
  <div class="container">
    <div class="section-title">
      <h2>Kenapa KostHub Berbeda?</h2>
    </div>
    <div class="row g-5">
      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="bi bi-shield-check"></i>
          </div>
          <h3>Aman & Terverifikasi</h3>
          <p>Semua kost telah diverifikasi oleh tim KostHub. Tidak ada penipuan, tidak ada foto palsu — hanya yang asli.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="bi bi-geo-alt-fill"></i>
          </div>
          <h3>Lokasi Strategis</h3>
          <p>Dekat kampus, transportasi umum, warung makan, dan pusat belanja — semua ada dalam jangkauan kaki.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="bi bi-heart-fill"></i>
          </div>
          <h3>Dibuat untuk Mahasiswa</h3>
          <p>Dari kamar minimalis hingga kamar ber-AC, kami paham kebutuhanmu — bukan hanya tempat tidur, tapi rumah kedua.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA Section -->
<section class="cta">
  <div class="container">
    <h2>Siap Pindah ke Kost Impianmu?</h2>
    <p>Lebih dari 12.000 mahasiswa sudah menemukan rumah mereka di KostHub. Kamu siap jadi yang berikutnya?</p>
    <a href="/Web-App/frontend/user/customer/explore.php" class="btn">Jelajahi Sekarang</a>
  </div>
</section>

<!-- Footer -->
<footer>
  <div class="container">
    <p>&copy; <?= date('Y') ?> KostHub. Semua hak dilindungi.</p>
    <p>Dibangun dengan ❤️ untuk mahasiswa Indonesia</p>
    <div class="social">
      <a href="#"><i class="bi bi-instagram"></i></a>
      <a href="#"><i class="bi bi-twitter"></i></a>
      <a href="#"><i class="bi bi-facebook"></i></a>
      <a href="#"><i class="bi bi-tiktok"></i></a>
    </div>
  </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Navbar scroll effect
  window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  });

  // Login alert
  function showLoginAlert(e) {
    e.preventDefault();
    const modal = new bootstrap.Modal(document.getElementById('loginAlertModal'));
    modal.show();
  }
</script>

</body>
</html>

