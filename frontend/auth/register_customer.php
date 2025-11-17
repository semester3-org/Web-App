<?php
session_start();
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Register User - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="css/auth.css?v=<?php echo time(); ?>">
</head>

<body>
  <div class="auth-wrapper d-flex justify-content-center align-items-center min-vh-100">
    <div class="login-box shadow-lg rounded overflow-hidden w-100" style="max-width: 950px;">

      <!-- Header Logo di Tengah -->
      <div class="login-header text-center py-4 d-flex align-items-center justify-content-center">
        <button type="button" class="btn btn-outline-success position-absolute start-0 ms-3 d-flex align-items-center"
            onclick="window.location.href='register.php'">
            <i class="bi bi-arrow-left"></i>
        </button>
        <img src="../assets/logo_kos.png" alt="logo" class="logo me-2">
        <h2 class="fw-bold m-0">KostHub</h2>
      </div>

      <!-- Body -->
      <div class="login-body d-flex">

        <!-- Left Form -->
        <div class="form-section p-4 flex-fill" style="flex:0.55; max-width:520px;">
          <h3 class="fw-bold mb-2 text-center">Welcome! Customer</h3>
          <p class="text-muted mb-4 text-center">Sign up to find your best kost</p>

          <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($_GET['error']); ?></div>
          <?php endif; ?>

          <form id="registerForm" action="../../backend/user/auth/register_customer.php" method="POST" class="row g-3" novalidate>
            <!-- Nama Lengkap -->
            <div class="col-md-6">
              <label for="nama" class="form-label fw-semibold mb-1">Nama Lengkap</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control" id="nama" name="nama" placeholder="Masukkan Nama Lengkap" required>
              </div>
            </div>

            <!-- Username -->
            <div class="col-md-6">
              <label for="username" class="form-label fw-semibold mb-1">Username</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person-badge"></i></span>
                <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan Username" required>
              </div>
            </div>

            <!-- Email -->
            <div class="col-md-6">
              <label for="email" class="form-label fw-semibold mb-1">Email</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control" id="email" name="email" placeholder="Masukkan Email" required>
              </div>
            </div>

            <!-- Nomor Handphone -->
            <div class="col-md-6">
              <label for="no_hp" class="form-label fw-semibold mb-1">Nomor Handphone</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-phone"></i></span>
                <input type="text" class="form-control" id="no_hp" name="no_hp" placeholder="Masukkan Nomor Handphone" required>
              </div>
            </div>

            <!-- Password -->
            <div class="col-md-6">
              <label for="password" class="form-label fw-semibold mb-1">Password</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan Password" required>
                <span class="input-group-text bg-light" id="togglePassword" style="cursor: pointer;">
                  <i class="bi bi-eye-slash" id="togglePasswordIcon"></i>
                </span>
              </div>
            </div>

            <!-- Konfirmasi Password -->
            <div class="col-md-6">
              <label for="confirm_password" class="form-label fw-semibold mb-1">Konfirmasi Password</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock-fill"></i></span>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Konfirmasi Password" required>
                <span class="input-group-text bg-light" id="toggleConfirmPassword" style="cursor: pointer;">
                  <i class="bi bi-eye-slash" id="toggleConfirmPasswordIcon"></i>
                </span>
              </div>
            </div>


            <!-- Button Daftar -->
            <div class="col-12">
              <button type="submit" class="btn btn-success w-100 py-2 fw-bold">Daftar</button>
            </div>
          </form>

          <!-- OR Divider -->
          <div class="text-center my-3 text-muted">— atau —</div>

          <!-- Google Register -->
          <a href="../../backend/user/auth/google_register.php"
            class="btn btn-outline-danger w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 mb-3">
            <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google" style="height:20px;">
            <span>Masuk dengan Google</span>
          </a>

          <p class="mt-4 text-center">
            Sudah punya akun? <a href="login.php" class="text-success fw-bold">Masuk</a>
          </p>
        </div>

        <div class="illustration-section d-none d-md-flex justify-content-center align-items-center p-4"
          style="background-color: #fff;">
          <img src="../assets/logo_login.svg" alt="register illustration" class="img-fluid" style="max-height: 420px;">
        </div>


      </div>
    </div>
  </div>

  <script>
    // validasi konfirmasi password sederhana
    document.getElementById('registerForm').addEventListener('submit', function(e) {
      const pw = document.getElementById('password').value;
      const cpw = document.getElementById('confirm_password').value;
      if (pw !== cpw) {
        e.preventDefault();
        alert('Password dan konfirmasi tidak cocok.');
        document.getElementById('confirm_password').focus();
      }
    });
  </script>
  <script>
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    togglePassword.addEventListener('click', () => {
      const type = passwordField.type === 'password' ? 'text' : 'password';
      passwordField.type = type;
      togglePasswordIcon.classList.toggle('bi-eye');
      togglePasswordIcon.classList.toggle('bi-eye-slash');
    });

    // Toggle confirm password visibility
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPasswordField = document.getElementById('confirm_password');
    const toggleConfirmPasswordIcon = document.getElementById('toggleConfirmPasswordIcon');

    toggleConfirmPassword.addEventListener('click', () => {
      const type = confirmPasswordField.type === 'password' ? 'text' : 'password';
      confirmPasswordField.type = type;
      toggleConfirmPasswordIcon.classList.toggle('bi-eye');
      toggleConfirmPasswordIcon.classList.toggle('bi-eye-slash');
    });
  </script>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>