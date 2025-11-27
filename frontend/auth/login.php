<?php
session_start();

// Cek jika sudah login
if (isset($_SESSION['user_id'])) {
  switch ($_SESSION['user_type']) {
    case 'admin':
    case 'superadmin':
      header("Location: ../admin/pages/dashboard.php");
      break;
    case 'owner':
      header("Location: ../user/owner/pages/dashboard.php");
      break;
    case 'customer':
    default:
      header("Location: ../user/customer/home.php");
      break;
  }
  exit;
}

$login_type = $_GET['type'] ?? 'user';

// Ambil pesan error dari session (untuk Google login)
$error_from_session = '';
if (isset($_SESSION['error_notif'])) {
  $error_from_session = $_SESSION['error_notif'];
  unset($_SESSION['error_notif']);
}

// Ambil pesan success dari session
$success_from_session = '';
if (isset($_SESSION['success_notif'])) {
  $success_from_session = $_SESSION['success_notif'];
  unset($_SESSION['success_notif']);
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
    /* Custom styling untuk notifikasi Google login */
    .alert-google {
      animation: slideInDown 0.4s ease-out;
      margin-bottom: 20px;
    }
    
    @keyframes slideInDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .register-link-inline {
      color: #198754;
      font-weight: 600;
      text-decoration: none;
    }
    
    .register-link-inline:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <div class="auth-wrapper">
    <div class="login-box shadow-lg rounded overflow-hidden">

      <!-- Header Logo di Tengah -->
      <div class="login-header text-center py-4 w-100 d-flex align-items-center justify-content-center">
        <button type="button" class="btn btn-outline-success position-absolute start-0 ms-3 d-flex align-items-center"
            onclick="window.location.href='../../index.php'">
            <i class="bi bi-arrow-left"></i>
        </button>
        <img src="../assets/logo_kos.png" alt="logo" class="logo me-2">
        <h2 class="fw-bold m-0">KostHub</h2>
      </div>

      <!-- Body -->
      <div class="login-body d-flex">

        <!-- Left Form -->
        <div class="form-section p-5">
          <h3 class="fw-bold mb-2 text-center">Welcome Back</h3>
          <p class="text-muted mb-4 text-center">Sign in to continue to your account</p>

          <!-- Notifikasi Success dari URL -->
          <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success py-2 alert-google">
              <i class="bi bi-check-circle-fill me-2"></i>
              <?= htmlspecialchars($_GET['success']); ?>
            </div>
          <?php endif; ?>

          <!-- Notifikasi Success dari Session -->
          <?php if (!empty($success_from_session)): ?>
            <div class="alert alert-success py-2 alert-google">
              <i class="bi bi-check-circle-fill me-2"></i>
              <?= htmlspecialchars($success_from_session); ?>
            </div>
          <?php endif; ?>

          <!-- Notifikasi Info dari URL -->
          <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-info py-2 alert-google">
              <i class="bi bi-info-circle-fill me-2"></i>
              <?= htmlspecialchars($_GET['message']); ?>
            </div>
          <?php endif; ?>

          <!-- Notifikasi Error dari URL -->
          <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger py-2 alert-google">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              <?= htmlspecialchars($_GET['error']); ?>
            </div>
          <?php endif; ?>

          <!-- Notifikasi Error dari Session (Google Login) -->
          <?php if (!empty($error_from_session)): ?>
            <div class="alert alert-danger py-2 alert-google">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              <?= htmlspecialchars($error_from_session); ?>
              <?php if (strpos($error_from_session, 'belum terdaftar') !== false): ?>
                <br>
                <small>
                  <a href="register.php" class="register-link-inline">
                    <i class="bi bi-arrow-right-circle"></i> Klik di sini untuk mendaftar
                  </a>
                </small>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <form action="../../backend/user/auth/login.php?type=<?= $login_type ?>" method="POST">
            <!-- Username -->
            <div class="mb-3">
              <label for="username" class="form-label fw-semibold">Username</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control" id="username" name="username" placeholder="Masukan Username" required>
              </div>
            </div>

            <!-- Password (dengan ikon mata) -->
            <div class="mb-3">
              <label for="password" class="form-label fw-semibold">Password</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" placeholder="Masukan Password" required>
                <span class="input-group-text bg-light" id="togglePassword" style="cursor: pointer;">
                  <i class="bi bi-eye-slash" id="togglePasswordIcon"></i>
                </span>
              </div>
            </div>

            <!-- Forgot Password -->
            <div class="d-flex justify-content-between align-items-center mb-3">
              <a href="forgot_password.php" class="text-success small">Lupa Password?</a>
            </div>

            <!-- Button Login -->
            <button type="submit" class="btn btn-success w-100 py-2 fw-bold">Login</button>

            <!-- OR Divider -->
            <div class="text-center my-3 text-muted">— atau —</div>

            <a href="../../backend/user/auth/google_login.php"
              class="btn btn-outline-danger w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 mb-3">
              <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google" style="height:20px;">
              <span>Masuk dengan Google</span>
            </a>
            
            <p class="mt-4 text-center">
              Belum Punya Akun? <a href="register.php" class="text-success fw-bold">Buat Akun</a>
            </p>
          </form>

        </div>

        <!-- Right Illustration -->
        <div class="illustration-section d-none d-md-flex">
          <img src="../assets/logo_login.svg" alt="login illustration" class="img-fluid">
        </div>

      </div>
    </div>
  </div>

  <script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    togglePassword.addEventListener('click', () => {
      const type = passwordField.type === 'password' ? 'text' : 'password';
      passwordField.type = type;

      // Ganti ikon
      togglePasswordIcon.classList.toggle('bi-eye');
      togglePasswordIcon.classList.toggle('bi-eye-slash');
    });
  </script>
</body>

</html>