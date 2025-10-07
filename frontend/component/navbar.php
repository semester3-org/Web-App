<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$isLoggedIn = isset($_SESSION['username']);
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- navbar.php -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top" style="height:65px;">
  <div class="container-fluid px-4">

    <!-- Brand -->
    <a class="navbar-brand fw-bold d-flex align-items-center"
       href="<?php echo $isLoggedIn ? '/Web-App/frontend/user/customer/home.php' : '/Web-App/index.php'; ?>">
      <img src="/Web-App/frontend/assets/logo_kos.png" alt="logo" style="height:30px;" class="me-2">
      KostHub
    </a>

    <!-- Toggle (mobile) -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Nav links -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        <!-- Home -->
        <li class="nav-item">
          <a class="nav-link <?php echo in_array($currentPage, ['index.php','home.php']) ? 'fw-bold text-success' : ''; ?>"
             href="<?php echo $isLoggedIn ? '/Web-App/frontend/user/customer/home.php' : '/Web-App/index.php'; ?>">
            Home
          </a>
        </li>

        <!-- Explore -->
        <li class="nav-item">
          <a class="nav-link <?php echo $currentPage=='explore.php' ? 'fw-bold text-success' : ''; ?>"
             href="<?php echo $isLoggedIn ? '/Web-App/frontend/user/customer/explore.php' : '/Web-App/explore.php'; ?>">
            Explore
          </a>
        </li>

        <!-- Wishlist -->
        <li class="nav-item">
          <a class="nav-link <?php echo $currentPage=='wishlist.php' ? 'fw-bold text-success' : ''; ?>"
             href="<?php echo $isLoggedIn ? '/Web-App/frontend/user/customer/wishlist.php' : '/Web-App/wishlist.php'; ?>">
            Wishlist
          </a>
        </li>

        <!-- Booking -->
        <li class="nav-item">
          <a class="nav-link <?php echo $currentPage=='booking.php' ? 'fw-bold text-success' : ''; ?>"
             href="<?php echo $isLoggedIn ? '/Web-App/frontend/user/customer/booking.php' : '/Web-App/booking.php'; ?>">
            Your Booking
          </a>
        </li>

      </ul>
    </div>

    <!-- Right section -->
    <div class="d-flex align-items-center">
      <?php if ($isLoggedIn): ?>
        <!-- Search -->
        <form class="d-flex me-3">
          <input class="form-control form-control-sm" type="search" placeholder="Search">
          <button class="btn btn-outline-success btn-sm ms-2" type="submit">
            <i class="bi bi-search"></i>
          </button>
        </form>

        <!-- Notification -->
        <a href="#" class="text-dark me-3">
          <i class="bi bi-bell fs-5"></i>
        </a>

        <!-- Profile Dropdown -->
        <div class="dropdown">
          <a href="#" class="d-flex align-items-center text-dark text-decoration-none"
             id="profileDropdown"
             data-bs-toggle="dropdown"
             aria-expanded="false">
            <img src="/Web-App/frontend/assets/profile.jpg"
                 alt="profile"
                 class="rounded-circle"
                 style="height:35px; width:35px; object-fit:cover;">
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="profileDropdown">
            <li>
              <a class="dropdown-item" href="/Web-App/frontend/user/customer/settings.php">
                <i class="bi bi-person-circle me-2"></i> Profile
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item text-danger" href="/Web-App/frontend/auth/logout.php">
                <i class="bi bi-box-arrow-right me-2"></i> Log Out
              </a>
            </li>
          </ul>
        </div>

      <?php else: ?>
        <!-- Login Button -->
        <a href="/Web-App/frontend/auth/login.php" class="btn btn-outline-success btn-sm">
          🔑 Login
        </a>
      <?php endif; ?>
    </div>

  </div>
</nav>
