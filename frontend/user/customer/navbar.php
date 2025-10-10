<?php
session_start();
require_once("../../../backend/config/db.php");

// Jika belum login, kembalikan ke login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// 🔹 Ambil data user langsung dari database agar selalu paling baru
$stmt = $conn->prepare("SELECT full_name, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$profilePic = $user['profile_picture'] ?? '';
$fullName = htmlspecialchars($user['full_name'] ?? 'Customer');

// 🔹 Jika foto belum diupload, gunakan default
if (empty($profilePic) || !file_exists($_SERVER['DOCUMENT_ROOT'] . $profilePic)) {
    $profilePic = '/Web-App/frontend/assets/default-avatar.png';
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top" style="height:65px;">
  <div class="container-fluid px-4">

    <!-- Brand -->
    <a class="navbar-brand fw-bold d-flex align-items-center" href="home.php">
      <img src="../../assets/logo_kos.png" alt="logo" style="height:30px;" class="me-2">
      KostHub
    </a>

    <!-- Toggle (mobile) -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Nav links -->
    <div class="collapse navbar-collapse" id="navbarNav">
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

    <!-- Right section -->
    <div class="d-flex align-items-center">
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
          <img src="<?= htmlspecialchars($profilePic) ?>" alt="profile"
               class="rounded-circle border"
               style="height:35px; width:35px; object-fit:cover;">
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="profileDropdown">
          <li class="dropdown-item-text fw-semibold text-center"><?= $fullName ?></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-circle me-2"></i> Profile</a></li>
          <li><a class="dropdown-item text-danger" href="../../../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Log Out</a></li>
        </ul>
      </div>
    </div>

  </div>
</nav>
