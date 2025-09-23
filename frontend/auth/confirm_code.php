<?php
session_start();
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
      <img src="../assets/logo_kos.png" alt="logo" class="logo me-2">
      <h2 class="fw-bold m-0">KostHub</h2>
    </div>

    <!-- Body -->
    <div class="login-body d-flex">
      
      <!-- Left Form -->
      <div class="form-section p-5 d-flex flex-column justify-content-center">
        <h3 class="fw-bold mb-2 text-center">Password Reset</h3>
        <p class="text-muted mb-4 text-center">Kami mengirimkan kode ke <span class="text-success fw-semibold">emailanda@gmail.com</span></p>

        <form action="set_new_password.php" method="POST">
          <!-- Code Input -->
          <div class="d-flex justify-content-between mb-4">
            <input type="text" maxlength="1" class="form-control code-input" name="code[]">
            <input type="text" maxlength="1" class="form-control code-input" name="code[]">
            <input type="text" maxlength="1" class="form-control code-input" name="code[]">
            <input type="text" maxlength="1" class="form-control code-input" name="code[]">
          </div>

          <!-- Button -->
          <button type="submit" class="btn btn-success w-100 py-2 fw-bold">Reset Password</button>
        </form>

        <p class="mt-4 text-center">
          Tidak menerima email? <a href="#" class="text-success fw-bold">Kirim ulang</a>
        </p>
      </div>

      <!-- Right Illustration -->
      <div class="illustration-section d-none d-md-flex justify-content-center align-items-center bg-light p-4">
        <img src="../assets/lupa_password.svg" alt="confirm code illustration" class="img-fluid" style="max-height: 420px;">
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto focus pindah ke input berikutnya
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
