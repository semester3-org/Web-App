<?php
session_start();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Lupa Password - KostHub</title>
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
        <h3 class="fw-bold mb-2 text-center">Lupa Password?</h3>
        <p class="text-muted mb-4 text-center">Masukkan email Anda untuk menerima link reset password</p>

        <!-- ✅ Alert Notifikasi -->
        <?php if (isset($_GET['error'])): ?>
          <div class="alert alert-danger py-2 text-center">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <?= htmlspecialchars($_GET['error']); ?>
          </div>
        <?php elseif (isset($_GET['success'])): ?>
          <div class="alert alert-success py-2 text-center">
            <i class="bi bi-check-circle-fill me-1"></i>
            <?= htmlspecialchars($_GET['success']); ?>
          </div>
        <?php endif; ?>

        <form action="../../backend/auth/process_forgot_password.php" method="POST">
  <div class="mb-3">
    <label for="email" class="form-label fw-semibold">Email</label>
    <div class="input-group">
      <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
      <input type="email" class="form-control" id="email" name="email" placeholder="Masukkan Email" required>
    </div>
  </div>

  <button type="submit" class="btn btn-success w-100 py-2 fw-bold">Kirim Link Reset</button>
</form>


        <p class="mt-4 text-center">
          Ingat password Anda? <a href="login.php" class="text-success fw-bold">Login</a>
        </p>
      </div>

      <!-- Right Illustration -->
      <div class="illustration-section d-none d-md-flex justify-content-center align-items-center bg-light p-4">
        <img src="../assets/lupa_password.svg" alt="forgot password illustration" class="img-fluid" style="max-height: 420px;">
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
