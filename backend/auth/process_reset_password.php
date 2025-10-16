<?php
session_start();
require_once __DIR__ . '/../config/db.php'; // ✅ perbaiki path (tambah / di depan config)
 
// Pastikan user sudah melalui tahap konfirmasi kode
if (!isset($_SESSION['reset_email'])) {
  header("Location: ../../frontend/auth/forgot_password.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_SESSION['reset_email'];
  $password = $_POST['password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  // ===== VALIDASI =====
  if (strlen($password) < 8) {
    header("Location: ../../frontend/auth/set_new_password.php?error=Password minimal 8 karakter");
    exit();
  }

  if ($password !== $confirm) {
    header("Location: ../../frontend/auth/set_new_password.php?error=Konfirmasi password tidak cocok");
    exit();
  }

  // ===== HASH PASSWORD BARU =====
  $hashed = password_hash($password, PASSWORD_DEFAULT);

  // ===== UPDATE PASSWORD KE DATABASE =====
  $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
  $stmt->bind_param("ss", $hashed, $email);

  if ($stmt->execute()) {
    // Hapus session reset agar tidak bisa digunakan ulang
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_code']);
    unset($_SESSION['reset_expires']);

    header("Location: ../../frontend/auth/login.php?success=Password berhasil diperbarui");
    exit();
  } else {
    header("Location: ../../frontend/auth/set_new_password.php?error=Gagal memperbarui password");
    exit();
  }

  $stmt->close();
  $conn->close();
} else {
  header("Location: ../../frontend/auth/forgot_password.php");
  exit();
}
