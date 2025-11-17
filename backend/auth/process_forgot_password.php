<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');

  if (empty($email)) {
    header("Location: ../../frontend/auth/forgot_password.php?error=Email tidak boleh kosong");
    exit();
  }

  // Cek apakah email terdaftar
  $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) {
    header("Location: ../../frontend/auth/forgot_password.php?error=Email belum terdaftar");
    exit();
  }

  // Ambil data user
  $user = $result->fetch_assoc();
  $name = $user['full_name'];
  $userId = $user['id'];

  // Generate 4-digit code
  $code = rand(1000, 9999);
  $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

  // Simpan ke database (buat tabel password_resets jika belum ada)
  $stmt = $conn->prepare("
    INSERT INTO password_resets (user_id, code, expires_at)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE code = VALUES(code), expires_at = VALUES(expires_at)
  ");
  $stmt->bind_param("iss", $userId, $code, $expiresAt);
  $stmt->execute();
  $conn->commit(); 

  // Simpan juga ke session agar bisa dicek di halaman berikutnya
  $_SESSION['reset_email']   = $email;
  $_SESSION['reset_code']    = $code;
  $_SESSION['reset_expires'] = time() + (10 * 60); // 10 menit

  // Konfigurasi PHPMailer
  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress($email, $name);

    $mail->isHTML(true);
    $mail->Subject = 'Kode Reset Password KostHub';
    $mail->Body = "
      <h3>Hai, {$name}</h3>
      <p>Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda di KostHub.</p>
      <p><b>Kode Verifikasi Anda:</b></p>
      <h2 style='background:#28a745;color:white;padding:10px;border-radius:8px;display:inline-block;'>{$code}</h2>
      <p>Kode ini hanya berlaku selama 10 menit.</p>
      <br>
      <small>Jika Anda tidak meminta reset password, abaikan email ini.</small>
    ";

    $mail->send();

    // Setelah email berhasil dikirim, arahkan ke halaman konfirmasi kode
    header("Location: ../../frontend/auth/confirm_code.php?success=Kode telah dikirim ke email Anda");
    exit();

  } catch (Exception $e) {
    header("Location: ../../frontend/auth/forgot_password.php?error=" . urlencode("Gagal mengirim email: " . $mail->ErrorInfo));
    exit();
  }
}
