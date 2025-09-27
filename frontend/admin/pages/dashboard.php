<?php
session_start();
require_once __DIR__ . "/../auth/auth_admin.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    /* Navbar */
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 20px;
      background: #18b868ff;
      color: white;
    }
    .navbar .logo {
      font-weight: bold;
    }
    .navbar nav ul {
      list-style: none;
      display: flex;
      gap: 20px;
    }
    .navbar nav ul li a {
      color: white;
      text-decoration: none;
    }

    /* User dropdown */
    .user-menu {
      position: relative;
      display: inline-block;
    }
    .user-btn {
      display: flex;
      align-items: center;
      cursor: pointer;
      gap: 8px;
    }
    .user-btn img {
      width: 30px;
      height: 30px;
      border-radius: 50%;
    }
    .dropdown {
      display: none;
      position: absolute;
      right: 0;
      top: 40px;
      background: white;
      color: black;
      border: 1px solid #ddd;
      border-radius: 6px;
      min-width: 150px;
      box-shadow: 0px 4px 6px rgba(0,0,0,0.1);
      z-index: 1000;
    }
    .dropdown a {
      display: block;
      padding: 10px;
      text-decoration: none;
      color: black;
    }
    .dropdown a:hover {
      background: #f0f0f0;
    }
  </style>
</head>
<body>
  <header class="navbar">
    <div class="logo">KostHub</div>
    <nav>
      <ul>
        <li><a href="#">Add Kos</a></li>
        <li><a href="#">Your Property</a></li>
        <li><a href="#">Booking List</a></li>
      </ul>
    </nav>
    <div class="user-menu">
      <div class="user-btn" onclick="toggleDropdown()">
        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Profile">
      </div>
      <div class="dropdown" id="dropdownMenu">
        <a href="../pages/setting.php">⚙️ Setting</a>
        <a href="../auth/logout.php">🚪 Logout</a>
      </div>
    </div>
  </header>

  <main>
    <h1>Dashboard Admin</h1>
    <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
  </main>

  <script>
    function toggleDropdown() {
      document.getElementById("dropdownMenu").style.display =
        document.getElementById("dropdownMenu").style.display === "block"
          ? "none"
          : "block";
    }

    // Tutup dropdown kalau klik di luar
    window.addEventListener("click", function(e) {
      if (!e.target.closest(".user-menu")) {
        document.getElementById("dropdownMenu").style.display = "none";
      }
    });
  </script>
</body>
</html>
