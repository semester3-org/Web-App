<!-- navbar.php -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4 fixed-top" style="height:65px;">
  <a class="navbar-brand fw-bold d-flex align-items-center" href="home.php">
    <img src="../../assets/logo_kos.png" alt="logo" style="height:30px;" class="me-2">
    KostHub
  </a>

  <div class="collapse navbar-collapse">
    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
      <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='home.php' ? 'fw-bold text-success' : ''; ?>" href="home.php">Home</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='explore.php' ? 'fw-bold text-success' : ''; ?>" href="explore.php">Explore</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='wishlist.php' ? 'fw-bold text-success' : ''; ?>" href="wishlist.php">Wishlist</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='booking.php' ? 'fw-bold text-success' : ''; ?>" href="booking.php">Your Booking</a>
      </li>
    </ul>
  </div>

  <!-- Search -->
  <form class="d-flex me-3">
    <input class="form-control form-control-sm" type="search" placeholder="Search">
    <button class="btn btn-outline-success btn-sm ms-2" type="submit"><i class="bi bi-search"></i></button>
  </form>

  <!-- Notification & Profile -->
<div class="d-flex align-items-center">
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
      <img src="../assets/profile.jpg" alt="profile" 
           class="rounded-circle" style="height:35px; width:35px;">
    </a>
    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="profileDropdown">
      <li><a class="dropdown-item" href="settings.php">⚙️ Profile</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item text-danger" href="../../../logout.php">🚪 Log Out</a></li>
    </ul>
  </div>
</div>

</nav>
