<?php
session_start();
$login_type = $_GET['type'] ?? 'user'; // default user

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/pages/dashboard.php");
    } else {
        header("Location: ../pages/home.php");
    }
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Login - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="css/auth.css?v=<?php echo time(); ?>">
  <style>
    .toggle-password {
        cursor: pointer;
        user-select: none;
    }
    .toggle-password:hover {
        background-color: #e9ecef !important;
    }
  </style>
</head>
<body>
<div class="auth-wrapper">
  <div class="login-box shadow-lg rounded overflow-hidden">
    
    <!-- Header Logo di Tengah -->
    <div class="login-header text-center py-4 w-100 d-flex align-items-center justify-content-center">
  <img src="../../assets/logo_kos.png" alt="logo" class="logo me-2">
  <h2 class="fw-bold m-0">KostHub</h2>
</div>

  
    <!-- Body -->
    <div class="login-body d-flex">
      
      <!-- Left Form -->
      <div class="form-section p-5">
        <h3 class="fw-bold mb-2 text-center">Welcome Back</h3>
        <p class="text-muted mb-4 text-center">Sign in to continue to your account</p>

        <?php if (isset($_GET['error'])): ?>
          <div class="alert alert-danger py-2"><?= htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <form action="../../../backend/admin/auth/login_process.php?type=<?= $login_type ?>" method="POST">
  <!-- Username -->
 <div class="mb-3">
  <label for="username" class="form-label fw-semibold">Username</label>
  <div class="input-group">
    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
    <input type="text" class="form-control" id="username" name="username" placeholder="Masukan Username" required>
  </div>
</div>

<div class="mb-3">
  <label for="password" class="form-label fw-semibold">Password</label>
  <div class="input-group">
    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
    <input type="password" class="form-control" id="password" name="password" placeholder="Masukan Password" required>
    <span class="input-group-text bg-light toggle-password" onclick="togglePassword()">
      <i class="bi bi-eye" id="toggleIcon"></i>
    </span>
  </div>
</div>


  <!-- Forgot Password -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="forgot_password.php" class="text-success small">Lupa Password?</a>
  </div>

  <!-- Button -->
  <button type="submit" class="btn btn-success w-100 py-2 fw-bold">Login</button>
</form>

<p class="mt-4 text-center">
  Belum Punya Akun? <a href="register_admin.php" class="text-success fw-bold">Buat Akun</a>
</p>

      </div>

      <!-- Right Illustration -->
      <div class="illustration-section d-none d-md-flex">
        <img src="../../assets/logo_login.svg" alt="login illustration" class="img-fluid">
      </div>

    </div>
  </div>
</div>

<script>
function togglePassword() {
    const passwordField = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.remove('bi-eye');
        toggleIcon.classList.add('bi-eye-slash');
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.remove('bi-eye-slash');
        toggleIcon.classList.add('bi-eye');
    }
}
</script>
</body>
</html>