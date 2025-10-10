<?php
session_start();
require_once("../../../../backend/config/db.php");

// Ambil user id dari session (support user_id atau admin_id/owner id)
$userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;

$profilePic = '/Web-App/frontend/assets/default-avatar.png';
$fullName = 'Owner';

// Jika ada user id, ambil data terbaru dari DB
if ($userId) {
    $stmt = $conn->prepare("SELECT full_name, profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    $profilePic = $user['profile_picture'] ?? $profilePic;
    $fullName = htmlspecialchars($user['full_name'] ?? $fullName);

    // Jika file tidak ada di server, pakai default
    if (empty($profilePic) || !file_exists($_SERVER['DOCUMENT_ROOT'] . $profilePic)) {
        $profilePic = '/Web-App/frontend/assets/default-avatar.png';
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4 fixed-top" style="height:65px;">
  <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
    <img src="../../../assets/logo_kos.png" alt="logo" style="height:30px;" class="me-2">
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

  <!-- Profile Dropdown (sesuai code customer) -->
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
      <li><a class="dropdown-item text-danger" href="../../../../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Log Out</a></li>
    </ul>
  </div>
</nav>
<?php
session_start();
require_once("../../../../backend/config/db.php");

// Ambil user id dari session (support user_id atau admin_id/owner id)
$userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;

$profilePic = '/Web-App/frontend/assets/default-avatar.png';
$fullName = 'Owner';

// Jika ada user id, ambil data terbaru dari DB
if ($userId) {
    $stmt = $conn->prepare("SELECT full_name, profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    $profilePic = $user['profile_picture'] ?? $profilePic;
    $fullName = htmlspecialchars($user['full_name'] ?? $fullName);

    // Jika file tidak ada di server, pakai default
    if (empty($profilePic) || !file_exists($_SERVER['DOCUMENT_ROOT'] . $profilePic)) {
        $profilePic = '/Web-App/frontend/assets/default-avatar.png';
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4 fixed-top" style="height:65px;">
  <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
    <img src="../../../assets/logo_kos.png" alt="logo" style="height:30px;" class="me-2">
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

  <!-- Profile Dropdown (sesuai code customer) -->
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
      <li><a class="dropdown-item text-danger" href="../../../../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Log Out</a></li>
    </ul>
  </div>
</nav>