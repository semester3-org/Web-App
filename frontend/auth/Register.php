<?php
session_start();
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Register - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="css/auth.css?v=<?php echo time(); ?>">
  <style>
    .role-btn {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 1rem;
      padding: 1.2rem;
      border: 2px solid #28a745;
      border-radius: 12px;
      background: #fff;
      font-weight: 600;
      font-size: 1.1rem;
      color: #333;
      transition: all 0.3s;
      width: 100%;
      margin-bottom: 1rem;
      text-decoration: none;
      /* 🔑 Hapus underline */
    }

    .role-btn img {
      width: 40px;
      height: 40px;
    }

    .role-btn:hover {
      background: #e6f4ea;
      border-color: #218838;
      text-decoration: none;
      /* 🔑 Pastikan saat hover juga tidak muncul underline */
      color: #000;
    }
  </style>
</head>

<body>
  <div class="auth-wrapper d-flex justify-content-center align-items-center min-vh-100">
    <div class="login-box shadow-lg rounded overflow-hidden w-100" style="max-width: 1000px;">

      <!-- Header Logo -->
      <div class="login-header text-center py-4 d-flex align-items-center justify-content-center">
        <img src="../assets/logo_kos.png" alt="logo" class="logo me-2">
        <h2 class="fw-bold m-0">KostHub</h2>
      </div>

      <!-- Body -->
      <div class="login-body d-flex">

        <!-- Left Section -->
        <div class="form-section p-5 d-flex flex-column justify-content-center">
          <h3 class="fw-bold mb-2 text-center">Choose Your Role</h3>
          <p class="text-muted mb-4 text-center">Choose a role that suits your goals</p>

          <!-- Pilihan role -->
          <a href="register_customer.php" class="role-btn">
            <img src="../assets/pencari.png" alt="Pencari Kos">
            Pencari Kos
          </a>
          <a href="register_owner.php" class="role-btn">
            <img src="../assets/owner.png" alt="Pemilik Kos">
            Pemilik Kos
          </a>
        </div>

        <!-- Right Illustration -->
        <div class="illustration-section d-none d-md-flex justify-content-center align-items-center p-4"
          style="background-color: #fff;">
          <img src="../assets/logo_login.svg" alt="register illustration" class="img-fluid" style="max-height: 420px;">
        </div>


      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>