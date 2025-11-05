<?php
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

require_once "../../../backend/config/db.php"; // sesuaikan path config db kamu


// Avatar default kalau user belum upload
$defaultAvatar = "https://cdn-icons-png.flaticon.com/512/3135/3135715.png";
$profilePicture = $defaultAvatar;

// Ambil foto profil user dari database
if (isset($_SESSION['user_id'])) {
  $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
  $stmt->bind_param("i", $_SESSION['user_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    if (!empty($row['profile_picture'])) {
      $profilePicture = $row['profile_picture'];
    }
  }
  $stmt->close();
}
?>




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
        <?php
        // Cek apakah halaman saat ini adalah salah satu submenu Transaction
        $current_page = basename($_SERVER['PHP_SELF']);
        $transaction_pages = ['bookings.php', 'add_property_tax.php'];
        $is_transaction_active = in_array($current_page, $transaction_pages);
        ?>
      
        <li class="has-submenu <?= $is_transaction_active ? 'active open' : '' ?>">
          <a href="#" class="menu-toggle">
            <i class="fas fa-dollar-sign"></i>
            <span class="nav-text">Transaction</span>
            <i class="fas fa-chevron-down arrow"></i>
          </a>
          <ul class="submenu" style="<?= $is_transaction_active ? 'display: block;' : '' ?>">
            <li>
              <a href="bookings.php" class="<?= $current_page === 'bookings.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar-check"></i>
                <span class="nav-text">Bookings</span>
              </a>
            </li>
            <li>
              <a href="add_property_tax.php" class="<?= $current_page === 'add_property_tax.php' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice-dollar"></i>
                <span class="nav-text">Add Property Tax</span>
              </a>
            </li>
          </ul>
        </li>


        <li>
          <a href="facilities.php" class="<?= basename($_SERVER['PHP_SELF']) === 'facilities.php' ? 'active' : '' ?>" data-tooltip="Facilities List">
            <i class="fas fa-tools"></i>
            <span class="nav-text">Facilities</span>
          </a>
        </li>
        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin'): ?>
          <?php
          // Cek apakah halaman saat ini adalah salah satu submenu Master Data
          $current_page = basename($_SERVER['PHP_SELF']);
          $master_data_pages = ['admin.php', 'users.php'];
          $is_master_data_active = in_array($current_page, $master_data_pages);
          ?>
          <li class="has-submenu <?= $is_master_data_active ? 'active open' : '' ?>">
            <a href="#" class="menu-toggle">
              <i class="fas fa-crown"></i>
              <span class="nav-text">Master Data</span>
              <i class="fas fa-chevron-down arrow"></i>
            </a>
            <ul class="submenu" style="<?= $is_master_data_active ? 'display: block;' : '' ?>">
              <li>
                <a href="admin.php" class="<?= $current_page === 'admin.php' ? 'active' : '' ?>">
                  <i class="fas fa-user-shield"></i>
                  <span class="nav-text">Admin</span>
                </a>
              </li>
              <li>
                <a href="users.php" class="<?= $current_page === 'users.php' ? 'active' : '' ?>">
                  <i class="fas fa-users"></i>
                  <span class="nav-text">Users</span>
                </a>
              </li>
            </ul>
          </li>
        <?php endif; ?>
        <li>
          <a href="approved.php" class="<?= basename($_SERVER['PHP_SELF']) === 'approved.php' ? 'active' : '' ?>" data-tooltip="Approved List">
            <i class="fas fa-check"></i>
            <span class="nav-text">Property Approval</span>
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
  <!-- User Menu -->
  <div class="user-menu">
    <div class="user-btn" onclick="toggleDropdown()">
      <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile" class="profile-avatar">
      <span class="username-text"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    <div class="dropdown" id="dropdownMenu">
      <a href="../../../logout.php">
        <i class="fas fa-sign-out-alt"></i>
        <span class="dropdown-text">Logout</span>
      </a>
    </div>
  </div>
</aside>
<link rel="stylesheet" href="../css/sidebar.css">
<script src="../js/sidebar.js"></script>