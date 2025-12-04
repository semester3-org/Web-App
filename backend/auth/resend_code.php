<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';

// LOAD PHPMailer TANPA COMPOSER
require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Pastikan email sudah ada di session
if (!isset($_SESSION['reset_email'])) {
    header("Location: ../../frontend/auth/forgot_password.php?error=Session reset tidak ditemukan");
    exit();
}

$email = $_SESSION['reset_email'];

// Ambil data user dari database
$stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../../frontend/auth/forgot_password.php?error=Email tidak ditemukan di database");
    exit();
}

$user = $result->fetch_assoc();
$userId = $user['id'];
$name = $user['full_name'];

// Generate kode baru
$code = rand(1000, 9999);
$expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Update database
$stmt = $conn->prepare("
    INSERT INTO password_resets (user_id, code, expires_at)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE code = VALUES(code), expires_at = VALUES(expires_at)
");
$stmt->bind_param("iss", $userId, $code, $expiresAt);
$stmt->execute();

// Update session
$_SESSION['reset_code'] = $code;
$_SESSION['reset_expires'] = time() + (10 * 60);

// KIRIM EMAIL ULANG
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
    $mail->Subject = 'Kode Reset Password Baru - KostHub';
    $mail->Body = "
      <h3>Hai, {$name}</h3>
      <p>Berikut kode verifikasi reset password terbaru Anda.</p>
      <p><b>Kode Verifikasi Baru:</b></p>
      <h2 style='background:#28a745;color:white;padding:10px;border-radius:8px;display:inline-block;'>{$code}</h2>
      <p>Kode ini hanya berlaku selama 10 menit.</p>
    ";

    $mail->send();

    header("Location: ../../frontend/auth/confirm_code.php?success=Kode baru telah dikirim ke email Anda");
    exit();

} catch (Exception $e) {
    header("Location: ../../frontend/auth/confirm_code.php?error=" . urlencode("Gagal mengirim ulang kode: " . $mail->ErrorInfo));
    exit();
}

