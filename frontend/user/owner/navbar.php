<!-- navbar.php -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
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

  <!-- Profile Dropdown -->
  <div class="dropdown">
    <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
      <i class="bi bi-person-circle fs-4 me-2"></i>
      <span>Admin</span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end shadow">
      <li><a class="dropdown-item" href="settings.php">⚙️ Settings</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item text-danger" href="#">🚪 Sign Out</a></li>
    </ul>
  </div>
</nav>
