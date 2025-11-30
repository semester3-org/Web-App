<?php
// backend/user/auth/register_owner.php
session_start();
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['nama']);
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['no_hp']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];
    $user_type = "owner";

    // Array untuk menyimpan error
    $errors = [];

    // 1. Validasi field kosong
    if (empty($full_name)) {
        $errors[] = "Nama lengkap tidak boleh kosong";
    }
    if (empty($username)) {
        $errors[] = "Username tidak boleh kosong";
    }
    if (empty($email)) {
        $errors[] = "Email tidak boleh kosong";
    }
    if (empty($phone)) {
        $errors[] = "Nomor handphone tidak boleh kosong";
    }
    if (empty($password)) {
        $errors[] = "Password tidak boleh kosong";
    }
    if (empty($confirm)) {
        $errors[] = "Konfirmasi password tidak boleh kosong";
    }

    // 2. Validasi panjang minimal
    if (!empty($full_name) && strlen($full_name) < 3) {
        $errors[] = "Nama lengkap minimal 3 karakter";
    }

    if (!empty($username) && strlen($username) < 4) {
        $errors[] = "Username minimal 4 karakter";
    }

    if (!empty($password) && strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter";
    }

    // 3. Validasi format username (hanya alfanumerik dan underscore)
    if (!empty($username) && !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username hanya boleh mengandung huruf, angka, dan underscore";
    }

    // 4. Validasi format email
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }

    // 5. Validasi nomor HP (10-13 digit, hanya angka)
    if (!empty($phone) && !preg_match('/^[0-9]{10,13}$/', $phone)) {
        $errors[] = "Nomor handphone harus 10-13 digit angka";
    }

    // 6. Validasi password match
    if (!empty($password) && !empty($confirm) && $password !== $confirm) {
        $errors[] = "Password dan konfirmasi password tidak cocok";
    }

    // Jika ada error, redirect dengan pesan error
    if (!empty($errors)) {
        $error_message = implode(", ", $errors);
        header("Location: ../../../frontend/auth/register_owner.php?error=" . urlencode($error_message));
        exit;
    }

    // 7. Cek username sudah digunakan
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: ../../../frontend/auth/register_owner.php?error=Username sudah digunakan, silakan pilih username lain");
        exit;
    }

    // 8. Cek email sudah digunakan
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: ../../../frontend/auth/register_owner.php?error=Email sudah terdaftar, silakan gunakan email lain");
        exit;
    }

    // 9. Cek nomor HP sudah digunakan (opsional, sesuaikan dengan kebutuhan)
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: ../../../frontend/auth/register_owner.php?error=Nomor handphone sudah terdaftar");
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // === MULAI TRANSAKSI ===
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, full_name, phone, user_type, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("ssssss", $username, $email, $hashed_password, $full_name, $phone, $user_type);
        $stmt->execute();

        // COMMIT
        $conn->commit();

        header("Location: ../../../frontend/auth/login.php?success=Registrasi owner berhasil, silakan login");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Registration error: " . $e->getMessage());
        header("Location: ../../../frontend/auth/register_owner.php?error=Gagal mendaftarkan owner, silakan coba lagi");
        exit;
    }

} else {
    header("Location: ../../../frontend/auth/register_owner.php");
    exit;
}
?>