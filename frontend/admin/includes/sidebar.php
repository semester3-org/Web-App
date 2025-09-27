<?php if (session_status() == PHP_SESSION_NONE) {
  session_start();
} ?>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-content">
    <div class="logo" id="logoContainer">
      <img src="../../assets/logo_kos.png" alt="Logo" class="logo-icon">
      <span class="logo-text">KostHub</span>
      <!-- Toggle Button di dalam logo container -->
      <div class="toggle-btn" onclick="event.stopPropagation(); toggleSidebar();">
        <i class="fas fa-bars"></i>
      </div>
    </div>
    
    <nav>
      <ul>
        <li>
          <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" data-tooltip="Dashboard">
            <i class="fas fa-home"></i>
            <span class="nav-text">Dashboard</span>
          </a>
        </li>
        <li>
          <a href="#" data-tooltip="Add Kos">
            <i class="fas fa-plus-circle"></i>
            <span class="nav-text">Add Kos</span>
          </a>
        </li>
        <li>
          <a href="#" data-tooltip="Your Property">
            <i class="fas fa-building"></i>
            <span class="nav-text">Your Property</span>
          </a>
        </li>
        <li>
          <a href="booking.php" class="<?= basename($_SERVER['PHP_SELF']) === 'booking.php' ? 'active' : '' ?>" data-tooltip="Booking List">
            <i class="fas fa-list"></i>
            <span class="nav-text">Booking List</span>
          </a>
        </li>
        <li>
          <a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>" data-tooltip="Profile">
            <i class="fas fa-user"></i>
            <span class="nav-text">Profile</span>
          </a>
        </li>
      </ul>
    </nav>
  </div>
  
  <!-- User Menu -->
  <div class="user-menu">
    <div class="user-btn" onclick="toggleDropdown()">
      <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Profile">
      <span class="username-text"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    <div class="dropdown" id="dropdownMenu">
      <a href="../auth/logout.php">
        <i class="fas fa-sign-out-alt"></i>
        <span class="dropdown-text">Logout</span>
      </a>
    </div>
  </div>
</aside>

<script src="../js/sidebar.js"></script>
