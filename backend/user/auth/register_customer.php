<?php
session_start();
require_once("../../config/db.php"); // koneksi database

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama       = trim($_POST['nama']);
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']);
    $no_hp      = trim($_POST['no_hp']);
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm_password'];

    // 1. Validasi input kosong
    if (empty($nama) || empty($username) || empty($email) || empty($no_hp) || empty($password) || empty($confirm)) {
        header("Location: ../../../frontend/auth/register_user.php?error=Semua field wajib diisi");
        exit;
    }

    // 2. Validasi password dan konfirmasi
    if ($password !== $confirm) {
        header("Location: ../../../frontend/auth/register_user.php?error=Password dan konfirmasi tidak sama");
        exit;
    }

    // 3. Cek apakah username/email sudah ada
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        header("Location: ../../../frontend/auth/register_user.php?error=Username atau email sudah digunakan");
        exit;
    }

    // 4. Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 5. Insert ke tabel users
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, user_type) VALUES (?, ?, ?, ?, ?, 'user')");
    $stmt->bind_param("sssss", $username, $email, $hashedPassword, $nama, $no_hp);

    if ($stmt->execute()) {
        // Redirect ke login kalau berhasil
        $conn->commit();
        $stmt->close();
        header("Location: ../../../frontend/auth/login.php");
        exit;
    } else {
        header(header: "Location: ../../../frontend/auth/register_user.php?error=Gagal mendaftarkan user");
        exit;
    }
} else {
    header("Location: ../../../frontend/auth/register_user.php");
    exit;
}
