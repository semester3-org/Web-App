<?php
session_start();

$email = $_POST['email'] ?? '';
$codeArray = $_POST['code'] ?? [];
$enteredCode = implode('', $codeArray);

// Cek apakah sesi reset masih ada
if (!isset($_SESSION['reset_email'], $_SESSION['reset_code'], $_SESSION['reset_expires'])) {
  header("Location: /Web-App/frontend/auth/forgot_password.php?error=Sesi reset tidak valid. Silakan ulangi.");
  exit;
}

// Validasi email cocok dengan session
if ($_SESSION['reset_email'] !== $email) {
  header("Location: /Web-App/frontend/auth/confirm_code.php?error=Email tidak cocok dengan sesi aktif.");
  exit;
}

// Cek waktu kadaluarsa kode
if (time() > $_SESSION['reset_expires']) {
  unset($_SESSION['reset_code']);
  header("Location: /Web-App/frontend/auth/forgot_password.php?error=Kode sudah kadaluarsa. Silakan kirim ulang.");
  exit;
}

// Validasi kode
if ($enteredCode != $_SESSION['reset_code']) {
  header("Location: /Web-App/frontend/auth/confirm_code.php?error=Kode yang Anda masukkan salah.");
  exit;
}

// Jika kode cocok, arahkan ke halaman set password baru
header("Location: /Web-App/frontend/auth/set_new_password.php?success=Kode terverifikasi. Silakan buat password baru.");
exit;
