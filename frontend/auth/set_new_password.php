<?php
session_start();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Buat Password Baru - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="css/auth.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="auth-wrapper d-flex justify-content-center align-items-center min-vh-100">
  <div class="login-box shadow-lg rounded overflow-hidden w-100" style="max-width: 1000px;">

    <!-- Header -->
    <div class="login-header text-center py-4 d-flex align-items-center justify-content-center">
      <img src="../assets/logo_kos.png" alt="logo" class="logo me-2">
      <h2 class="fw-bold m-0">KostHub</h2>
    </div>

    <!-- Body -->
    <div class="login-body d-flex">
      
      <!-- Left Form -->
      <div class="form-section p-5 d-flex flex-column justify-content-center">
        <h3 class="fw-bold mb-2 text-center">Set New Password</h3>
        <p class="text-muted mb-4 text-center">Must be at least 8 characters</p>

        <form action="login.php" method="POST">
          <!-- Password -->
          <div class="mb-3">
            <label for="password" class="form-label fw-semibold">Password</label>
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control" id="password" name="password" placeholder="Masukan Password" required>
            </div>
          </div>

          <!-- Confirm Password -->
          <div class="mb-3">
            <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-lock-fill"></i></span>
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Masukan Password" required>
            </div>
          </div>

          <!-- Button -->
          <button type="submit" class="btn btn-success w-100 py-2 fw-bold">Reset Password</button>
        </form>

        <p class="mt-4 text-center">
          Kembali ke <a href="login.php" class="text-success fw-bold">Login?</a>
        </p>
      </div>

      <!-- Right Illustration -->
      <div class="illustration-section d-none d-md-flex justify-content-center align-items-center bg-light p-4">
        <img src="../assets/lupa_password.svg" alt="reset password illustration" class="img-fluid" style="max-height: 420px;">
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
