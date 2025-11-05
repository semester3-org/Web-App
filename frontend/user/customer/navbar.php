<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

// Cek login
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user';

// Default profile
$profilePic = '/Web-App/frontend/assets/default-avatar.png';
$fullName = 'Guest';

// Jika login, ambil data user
if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT full_name, profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        $profilePic = (!empty($user['profile_picture']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $user['profile_picture']))
            ? $user['profile_picture']
            : '/Web-App/frontend/assets/default-avatar.png';
        $fullName = htmlspecialchars($user['full_name']);
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top" style="height:65px; z-index:1050;">
  <div class="container-fluid px-4">

    <!-- Brand -->
    <a class="navbar-brand fw-bold d-flex align-items-center" href="/Web-App/frontend/user/customer/home.php">
      <img src="/Web-App/frontend/assets/logo_kos.png" alt="logo" style="height:30px;" class="me-2">
      KostHub
    </a>

    <!-- Toggle (mobile) -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Menu -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'home.php' ? 'fw-bold text-success' : ''; ?>" 
             href="/Web-App/frontend/user/customer/home.php">
            Home
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'explore.php' ? 'fw-bold text-success' : ''; ?>" 
             href="/Web-App/frontend/user/customer/explore.php">
            Explore
          </a>
        </li>

        <!-- Wishlist -->
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'wishlist.php' ? 'fw-bold text-success' : ''; ?>"
             href="<?php echo $isLoggedIn ? '/Web-App/frontend/user/customer/wishlist.php' : '#'; ?>"
             <?php if (!$isLoggedIn): ?>onclick="showLoginAlert(event)"<?php endif; ?>>
            Wishlist
          </a>
        </li>

        <!-- Booking -->
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'booking.php' ? 'fw-bold text-success' : ''; ?>"
             href="<?php echo $isLoggedIn ? '/Web-App/frontend/user/customer/booking.php' : '#'; ?>"
             <?php if (!$isLoggedIn): ?>onclick="showLoginAlert(event)"<?php endif; ?>>
            Your Booking
          </a>
        </li>
      </ul>

      <!-- Bagian kanan navbar -->
      <div class="d-flex align-items-center">
        <?php if (!$isLoggedIn): ?>
          <!-- Jika belum login -->
          <a href="/Web-App/frontend/auth/login.php" class="btn btn-success btn-sm">Login</a>
        <?php else: ?>
          <!-- Notifikasi -->
          <a href="#" class="text-dark me-3">
            <i class="bi bi-bell fs-5"></i>
          </a>

          <!-- Dropdown profil -->
          <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-dark text-decoration-none"
               id="profileDropdown"
               data-bs-toggle="dropdown"
               aria-expanded="false">
              <img src="<?= htmlspecialchars($profilePic) ?>" alt="profile"
                   class="rounded-circle border"
                   style="height:35px; width:35px; object-fit:cover;">
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="profileDropdown">
              <li class="dropdown-item-text fw-semibold text-center"><?= $fullName ?></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="/Web-App/frontend/user/customer/profile.php">
                <i class="bi bi-person-circle me-2"></i> Profile
              </a></li>
              <li><a class="dropdown-item text-danger" href="/Web-App/logout.php">
                <i class="bi bi-box-arrow-right me-2"></i> Log Out
              </a></li>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- 🔒 Modal: Login Diperlukan -->
<div class="modal fade" id="loginAlertModal" tabindex="-1" aria-labelledby="loginAlertLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="loginAlertLabel"><i class="bi bi-lock-fill me-2"></i>Login Diperlukan</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <p class="mb-4 fs-6">Anda harus login terlebih dahulu untuk mengakses halaman ini.</p>
        <div class="d-flex justify-content-center gap-3">
          <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
          <a href="/Web-App/frontend/auth/login.php" class="btn btn-success px-4">Login Sekarang</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function showLoginAlert(e) {
  e.preventDefault();
  const modal = new bootstrap.Modal(document.getElementById('loginAlertModal'));
  modal.show();
}
</script>
