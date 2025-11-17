<?php
session_start();
$email = $_SESSION['reset_email'] ?? null;

// Jika belum ada email (akses langsung halaman ini tanpa forgot password)
if (!$email) {
  header("Location: /Web-App/frontend/auth/forgot_password.php?error=Silakan masukkan email terlebih dahulu");
  exit;
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Konfirmasi Kode - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="css/auth.css?v=<?php echo time(); ?>">
  <style>
    .code-input {
      width: 60px;
      height: 60px;
      text-align: center;
      font-size: 24px;
      font-weight: bold;
      border: 2px solid #ddd;
      border-radius: 8px;
    }

    .code-input:focus {
      border-color: #198754;
      box-shadow: 0 0 5px rgba(25, 135, 84, 0.5);
      outline: none;
    }
  </style>
</head>

<body>
  <div class="auth-wrapper d-flex justify-content-center align-items-center min-vh-100">
    <div class="login-box shadow-lg rounded overflow-hidden w-100" style="max-width: 1000px;">

      <!-- Header -->
      <div class="login-header text-center py-4 d-flex align-items-center justify-content-center">
        <button type="button" class="btn btn-outline-success position-absolute start-0 ms-3 d-flex align-items-center"
            onclick="window.location.href='forgot_password.php'">
            <i class="bi bi-arrow-left"></i>
        </button>
        <img src="../assets/logo_kos.png" alt="logo" class="logo me-2">
        <h2 class="fw-bold m-0">KostHub</h2>
      </div>

      <!-- Body -->
      <div class="login-body d-flex">

        <!-- Left Form -->
        <div class="form-section p-5 d-flex flex-column justify-content-center">
          <h3 class="fw-bold mb-2 text-center">Konfirmasi Kode</h3>
          <p class="text-muted mb-4 text-center">
            Kami telah mengirimkan kode ke <span class="text-success fw-semibold"><?= htmlspecialchars($email) ?></span>
          </p>

          <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($_GET['error']); ?></div>
          <?php elseif (isset($_GET['success'])): ?>
            <div class="alert alert-success py-2"><?= htmlspecialchars($_GET['success']); ?></div>
          <?php endif; ?>

          <form action="/Web-App/backend/auth/process_confirm_code.php" method="POST">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

            <!-- Code Input -->
            <div class="d-flex justify-content-between mb-4">
              <?php for ($i = 0; $i < 4; $i++): ?>
                <input type="text" maxlength="1" class="form-control code-input" name="code[]" required>
              <?php endfor; ?>

            </div>

            <!-- Button -->
            <button type="submit" class="btn btn-success w-100 py-2 fw-bold">Verifikasi Kode</button>
          </form>

          <p class="mt-4 text-center">
            Tidak menerima email? <a href="/Web-App/backend/auth/resend_code.php" class="text-success fw-bold">Kirim ulang</a>
          </p>
        </div>

        <!-- Right Illustration -->
        <div class="illustration-section d-none d-md-flex justify-content-center align-items-center bg-light p-4"
          style="background-color: #fff;">
          <img src="../assets/lupa_password.svg" alt="confirm code illustration" class="img-fluid" style="max-height: 420px;">
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const inputs = document.querySelectorAll(".code-input");
    inputs.forEach((input, index) => {
      input.addEventListener("input", () => {
        if (input.value.length === 1 && index < inputs.length - 1) {
          inputs[index + 1].focus();
        }
      });
    });
  </script>
</body>

</html>