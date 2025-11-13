<?php
// backend/user/auth/register_customer.php
session_start();
require_once("../../config/db.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama       = trim($_POST['nama']);
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']);
    $no_hp      = trim($_POST['no_hp']);
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm_password'];

    // Validasi
    if (empty($nama) || empty($username) || empty($email) || empty($no_hp) || empty($password) || empty($confirm)) {
        header("Location: ../../../frontend/auth/register_user.php?error=Semua field wajib diisi");
        exit;
    }

    if ($password !== $confirm) {
        header("Location: ../../../frontend/auth/register_user.php?error=Password dan konfirmasi tidak sama");
        exit;
    }

    // Cek username/email
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: ../../../frontend/auth/register_user.php?error=Username atau email sudah digunakan");
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // === MULAI TRANSAKSI ===
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, user_type) VALUES (?, ?, ?, ?, ?, 'user')");
        $stmt->bind_param("sssss", $username, $email, $hashedPassword, $nama, $no_hp);
        $stmt->execute();

        // COMMIT WAJIB!
        $conn->commit();

        header("Location: ../../../frontend/auth/login.php?success=Registrasi berhasil");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../../../frontend/auth/register_user.php?error=Gagal mendaftar");
        exit;
    }

} else {
    header("Location: ../../../frontend/auth/register_user.php");
    exit;
}