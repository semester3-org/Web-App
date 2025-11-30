<?php
session_start();
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Register Owner - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="css/auth.css?v=<?php echo time(); ?>">
  <style>
    .toast-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
    }
    .toast {
      min-width: 300px;
    }
    .toast.show {
      animation: slideIn 0.3s ease-out;
    }
    @keyframes slideIn {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
    .field-error {
      display: block;
      color: #dc3545;
      font-size: 0.875rem;
      margin-top: 0.25rem;
    }
    .is-invalid-custom {
      border-color: #dc3545 !important;
    }
  </style>
</head>

<body>
  <!-- Toast Container -->
  <div class="toast-container" id="toastContainer"></div>

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
          <h3 class="fw-bold mb-2 text-center">Welcome! Owner</h3>
          <p class="text-muted mb-4 text-center">Sign up to add your property</p>

          <form id="registerForm" action="../../backend/user/auth/register_owner.php" method="POST" class="row g-3" novalidate>
            <!-- Nama Lengkap -->
            <div class="col-md-6">
              <label for="nama" class="form-label fw-semibold mb-1">Nama Lengkap</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control" id="nama" name="nama" placeholder="Masukkan Nama Lengkap" required minlength="3">
              </div>
              <div class="invalid-feedback">Nama lengkap minimal 3 karakter.</div>
              <span class="field-error" id="error-nama"></span>
            </div>

            <!-- Username -->
            <div class="col-md-6">
              <label for="username" class="form-label fw-semibold mb-1">Username</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person-badge"></i></span>
                <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan Username" required minlength="4" pattern="[a-zA-Z0-9_]+">
              </div>
              <div class="invalid-feedback">Username minimal 4 karakter, hanya huruf, angka, dan underscore.</div>
              <span class="field-error" id="error-username"></span>
            </div>

            <!-- Email -->
            <div class="col-md-6">
              <label for="email" class="form-label fw-semibold mb-1">Email</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control" id="email" name="email" placeholder="Masukkan Email" required>
              </div>
              <div class="invalid-feedback">Masukkan email yang valid.</div>
              <span class="field-error" id="error-email"></span>
            </div>

            <!-- Nomor Handphone -->
            <div class="col-md-6">
              <label for="no_hp" class="form-label fw-semibold mb-1">Nomor Handphone</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-phone"></i></span>
                <input type="text" class="form-control" id="no_hp" name="no_hp" placeholder="Masukkan Nomor Handphone" required pattern="[0-9]{10,13}" minlength="10" maxlength="13">
              </div>
              <div class="invalid-feedback">Nomor handphone harus 10-13 digit angka.</div>
              <span class="field-error" id="error-no_hp"></span>
            </div>

            <!-- Password -->
            <div class="col-md-6">
              <label for="password" class="form-label fw-semibold mb-1">Password</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan Password" required minlength="6">
                <span class="input-group-text bg-light" id="togglePassword" style="cursor: pointer;">
                  <i class="bi bi-eye-slash" id="togglePasswordIcon"></i>
                </span>
              </div>
              <div class="invalid-feedback">Password minimal 6 karakter.</div>
              <span class="field-error" id="error-password"></span>
            </div>

            <!-- Konfirmasi Password -->
            <div class="col-md-6">
              <label for="confirm_password" class="form-label fw-semibold mb-1">Konfirmasi Password</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock-fill"></i></span>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Konfirmasi Password" required minlength="6">
                <span class="input-group-text bg-light" id="toggleConfirmPassword" style="cursor: pointer;">
                  <i class="bi bi-eye-slash" id="toggleConfirmPasswordIcon"></i>
                </span>
              </div>
              <div class="invalid-feedback" id="confirmPasswordFeedback">Password tidak cocok.</div>
              <span class="field-error" id="error-confirm_password"></span>
            </div>

            <!-- Button -->
            <div class="col-12">
              <button type="submit" class="btn btn-success w-100 py-2 fw-bold">Daftar</button>
            </div>
          </form>

          <!-- OR Divider -->
          <div class="text-center my-3 text-muted">— atau —</div>

          <!-- Google Register -->
          <a href="../../backend/user/auth/google_register_owner.php"
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
    // Function untuk menampilkan toast notification
    function showToast(message, type = 'danger') {
      const toastContainer = document.getElementById('toastContainer');
      
      const toastId = 'toast-' + Date.now();
      const iconClass = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
      const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
      
      const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
          <div class="d-flex">
            <div class="toast-body d-flex align-items-center">
              <i class="bi ${iconClass} me-2"></i>
              <span>${message}</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        </div>
      `;
      
      toastContainer.insertAdjacentHTML('beforeend', toastHTML);
      
      const toastElement = document.getElementById(toastId);
      const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 5000
      });
      
      toast.show();
      
      toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
      });
    }

    // Function untuk clear semua error
    function clearAllErrors() {
      document.querySelectorAll('.field-error').forEach(el => el.textContent = '');
      document.querySelectorAll('.is-invalid-custom').forEach(el => el.classList.remove('is-invalid-custom'));
    }

    // Function untuk set error pada field tertentu
    function setFieldError(fieldName, message) {
      const errorSpan = document.getElementById('error-' + fieldName);
      const inputField = document.getElementById(fieldName);
      
      if (errorSpan && inputField) {
        errorSpan.textContent = '⚠ ' + message;
        inputField.classList.add('is-invalid-custom');
      }
    }

    // Parsing error dari backend
    function parseBackendErrors(errorString) {
      clearAllErrors();
      
      // Mapping error message ke field name (lebih detail)
      const errorMappings = [
        { keywords: ['nama lengkap', 'nama'], field: 'nama' },
        { keywords: ['username'], field: 'username' },
        { keywords: ['email'], field: 'email' },
        { keywords: ['nomor handphone', 'nomor hp', 'no_hp', 'handphone'], field: 'no_hp' },
        { keywords: ['konfirmasi password'], field: 'confirm_password' },
        { keywords: ['password'], field: 'password' }
      ];

      // Split errors by comma
      const errors = errorString.split(',').map(e => e.trim());
      
      errors.forEach(error => {
        let fieldFound = false;
        const errorLower = error.toLowerCase();
        
        // Cek setiap mapping dengan urutan prioritas
        for (const mapping of errorMappings) {
          for (const keyword of mapping.keywords) {
            if (errorLower.includes(keyword)) {
              setFieldError(mapping.field, error);
              fieldFound = true;
              break;
            }
          }
          if (fieldFound) break;
        }
        
        // Selalu tampilkan toast juga untuk visibility
        showToast(error, 'danger');
      });
    }

    // Cek error dari URL parameter saat halaman load
    window.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      const error = urlParams.get('error');
      const success = urlParams.get('success');
      
      if (error) {
        parseBackendErrors(error);
        // Hapus parameter dari URL tanpa reload
        window.history.replaceState({}, document.title, window.location.pathname);
      }
      
      if (success) {
        showToast(success, 'success');
        window.history.replaceState({}, document.title, window.location.pathname);
      }
    });

    // Validasi form dengan Bootstrap validation
    const form = document.getElementById('registerForm');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const noHp = document.getElementById('no_hp');

    // Clear error saat user mulai mengetik
    document.querySelectorAll('input').forEach(input => {
      input.addEventListener('input', function() {
        const errorSpan = document.getElementById('error-' + this.id);
        if (errorSpan) {
          errorSpan.textContent = '';
          this.classList.remove('is-invalid-custom');
        }
      });
    });

    // Validasi real-time untuk konfirmasi password
    confirmPassword.addEventListener('input', function() {
      if (password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity('Password tidak cocok');
        document.getElementById('confirmPasswordFeedback').textContent = 'Password tidak cocok.';
      } else {
        confirmPassword.setCustomValidity('');
      }
    });

    password.addEventListener('input', function() {
      if (confirmPassword.value && password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity('Password tidak cocok');
      } else {
        confirmPassword.setCustomValidity('');
      }
    });

    // Validasi nomor HP hanya angka
    noHp.addEventListener('input', function() {
      this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Validasi saat submit
    form.addEventListener('submit', function(e) {
      clearAllErrors();
      
      let hasError = false;
      
      // Validasi manual setiap field
      if (form.querySelector('#nama').value.trim().length < 3) {
        setFieldError('nama', 'Nama lengkap minimal 3 karakter');
        hasError = true;
      }
      
      if (form.querySelector('#username').value.trim().length < 4) {
        setFieldError('username', 'Username minimal 4 karakter');
        hasError = true;
      }
      
      if (!form.querySelector('#email').value.includes('@')) {
        setFieldError('email', 'Format email tidak valid');
        hasError = true;
      }
      
      const noHpValue = form.querySelector('#no_hp').value;
      if (noHpValue.length < 10 || noHpValue.length > 13) {
        setFieldError('no_hp', 'Nomor handphone harus 10-13 digit');
        hasError = true;
      }
      
      if (password.value.length < 6) {
        setFieldError('password', 'Password minimal 6 karakter');
        hasError = true;
      }
      
      if (password.value !== confirmPassword.value) {
        setFieldError('confirm_password', 'Password dan konfirmasi password tidak cocok');
        confirmPassword.setCustomValidity('Password tidak cocok');
        hasError = true;
      }
      
      if (!form.checkValidity() || hasError) {
        e.preventDefault();
        e.stopPropagation();
        showToast('Mohon perbaiki field yang ditandai merah', 'danger');
      }
      
      form.classList.add('was-validated');
    });

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