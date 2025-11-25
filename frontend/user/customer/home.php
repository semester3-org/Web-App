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
    height: 90vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding-top: 205px;
    color: white;
    /* ❌ HAPUS background dari sini — overlay yang handle */
  }

  .hero-content {
    max-width: 700px;
    padding: 0 1.5rem;
    position: relative; /* agar di atas overlay */
    z-index: 2;
  }

  .hero h1 {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 1.2rem;
    animation: fadeInUp 1s ease-out;
  }

  .hero p {
    font-size: 1.3rem;
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

  /* ——————— OVERLAY BACKGROUND ——————— */
  .hero-bg-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    opacity: 0;
    transition: opacity 1s ease-in-out;
    z-index: 0;
  }

  .hero-bg-overlay.active {
    opacity: 1;
  }

  /* Gradient gelap di atas background, di bawah konten */
  .hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3));
    z-index: 1;
    pointer-events: none;
  }

  /* ——————— THUMBNAIL GALLERY ——————— */
  .hero-thumbnails {
  display: grid;
  grid-template-columns: repeat(4, 160px); /* Lebar tiap thumbnail: 160px */
  justify-content: center;
  gap: 20px; /* Sedikit lebih rapat */
  margin-top: 85px;
}

.thumbnail-item {
  width: 160px;
  height: 120px; /* Rasio 4:3 */
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  border-radius: 12px; /* Lebih kecil agar sesuai */
  position: relative;
  box-shadow: 0 4px 12px rgba(0,0,0,0.12);
  overflow: hidden;
  cursor: default;
  pointer-events: none;
  transition: transform 0.35s ease, border 0.3s ease, box-shadow 0.3s ease;
  border: 2px solid transparent; /* Lebih tipis */
}

.thumbnail-item.active {
  border: 2px solid white;
  transform: scale(1.04); /* Zoom lebih halus */
  box-shadow: 0 6px 16px rgba(0,0,0,0.2);
  z-index: 2;
}

/* Label (jika digunakan) */
.thumbnail-label {
  position: absolute;
  bottom: 6px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(0, 0, 0, 0.6);
  color: white;
  font-size: 0.8rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 16px;
  white-space: nowrap;
}

  /* ==================== FEATURES ==================== */
  .features {
    padding: 100px 0;
  }

  .section-title {
    text-align: center;
    margin-bottom: 60px;
  }

  .section-title h2 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark);
    position: relative;
    display: inline-block;
  }

  .section-title h2::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 70px;
    height: 4px;
    background: var(--secondary);
    border-radius: 2px;
  }

  .feature-card {
    background: white;
    border-radius: 16px;
    padding: 40px 25px;
    text-align: center;
    box-shadow: var(--shadow);
    transition: var(--transition);
    height: 100%;
  }

  .feature-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.08);
  }

  .feature-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: white;
    font-size: 1.8rem;
  }

  .feature-card h3 {
    font-size: 1.5rem;
    margin-bottom: 12px;
  }

  .feature-card p {
    color: #64748b;
    font-size: 1rem;
  }

  /* ==================== FOOTER ==================== */
  footer {
    background: #1e293b;
    color: #cbd5e1;
    padding: 40px 0 20px;
    text-align: center;
  }

  footer p {
    margin: 8px 0;
    font-size: 0.95rem;
  }

  footer .social a {
    color: #cbd5e1;
    margin: 0 10px;
    font-size: 1.3rem;
    text-decoration: none;
  }

  footer .social a:hover {
    color: var(--secondary);
  }

  /* ==================== ANIMATIONS ==================== */
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

  /* ==================== RESPONSIVE ==================== */
  @media (max-width: 992px) {
  .hero-thumbnails {
    grid-template-columns: repeat(4, 130px);
    gap: 16px;
  }
  .thumbnail-item {
    width: 130px;
    height: 98px; /* ~4:3 */
    border-radius: 10px;
  }
}

@media (max-width: 768px) {
  .hero-thumbnails {
    grid-template-columns: repeat(4, 70px);
    gap: 10px;
    margin-top: 20px;
  }
  .thumbnail-item {
    width: 70px;
    height: 52px;
    border-radius: 8px;
    border-width: 2px;
  }
  .thumbnail-item.active {
    transform: scale(1.1);
  }
  .thumbnail-label {
    font-size: 0.65rem;
    padding: 2px 6px;
    bottom: 4px;
  }
}
</style>
</head>
<body>

<!-- Navbar -->
<?php include("navbar.php"); ?>
<br>

<!-- Hero Section with Thumbnail Gallery -->
<section class="hero">
  <!-- Overlay background akan diisi oleh JavaScript -->
  <div class="hero-bg-overlay active"></div>
  <div class="hero-bg-overlay"></div>
  <div class="hero-gradient-overlay"></div>

  <div class="hero-content">
    <h1>Kost Impianmu, Hanya Satu Klik Lagi</h1>
    <p>Temukan tempat tinggal nyaman, aman, dan terjangkau di sekitar kampus — tanpa ribet, tanpa penipuan.</p>
    <a href="/Web-App/frontend/user/customer/explore.php" class="btn btn-light btn-lg">Jelajahi Kost Sekarang</a>

    <div class="hero-thumbnails">
      <div class="thumbnail-item" data-bg="/Web-App/frontend/assets/bg_home1.jpg"></div>
      <div class="thumbnail-item" data-bg="/Web-App/frontend/assets/bg_home3.jpg"></div>
      <div class="thumbnail-item" data-bg="/Web-App/frontend/assets/bg_home2.jpg"></div>
      <div class="thumbnail-item" data-bg="/Web-App/frontend/assets/bg_home4.jpg"></div>
    </div>
  </div>
</section>

<!-- Features Section -->
<section class="features">
  <div class="container">
    <div class="section-title">
      <h2>Kenapa KostHub Berbeda?</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="bi bi-shield-check"></i>
          </div>
          <h3>Aman & Terverifikasi</h3>
          <p>Semua kost telah diverifikasi oleh tim KostHub. Tidak ada penipuan, tidak ada foto palsu.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="bi bi-geo-alt-fill"></i>
          </div>
          <h3>Lokasi Strategis</h3>
          <p>Dekat kampus, transportasi umum, warung makan, dan pusat belanja — semua dalam jangkauan kaki.</p>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50 && navbar) {
      navbar.classList.add('scrolled');
    } else if (navbar) {
      navbar.classList.remove('scrolled');
    }
  });
</script>

</body>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    if (thumbnails.length === 0) return;

    // Ambil semua URL dari data-bg
    const imageUrls = Array.from(thumbnails).map(thumb => thumb.dataset.bg);

    // Set background image ke setiap thumbnail
    thumbnails.forEach(thumb => {
      thumb.style.backgroundImage = `url('${thumb.dataset.bg}')`;
    });

    // Dapatkan overlay
    const hero = document.querySelector('.hero');
    const overlays = document.querySelectorAll('.hero-bg-overlay');
    if (overlays.length < 2) return;

    let currentIndex = 0;
    let activeOverlayIndex = 0; // 0 = overlays[0] aktif

    function updateActive(index) {
      // Update background
      const nextIndex = (activeOverlayIndex + 1) % 2;
      overlays[nextIndex].style.backgroundImage = `url('${imageUrls[index]}')`;
      overlays[activeOverlayIndex].classList.remove('active');
      overlays[nextIndex].classList.add('active');
      activeOverlayIndex = nextIndex;

      // Update thumbnail active
      thumbnails.forEach((thumb, i) => {
        thumb.classList.toggle('active', i === index);
      });

      currentIndex = index;
    }

    // Mulai dari gambar pertama
    updateActive(0);

    // Ganti otomatis tiap 3 detik
    setInterval(() => {
      const next = (currentIndex + 1) % imageUrls.length;
      updateActive(next);
    }, 3000);
  });
</script>
</html>