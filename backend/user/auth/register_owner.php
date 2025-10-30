<?php
session_start();
require_once("../../config/db.php"); // sesuaikan path ke config db.php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['nama']);
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['no_hp']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];
    $user_type = "owner"; // role default owner

    // Validasi field kosong
    if (empty($full_name) || empty($username) || empty($email) || empty($phone) || empty($password) || empty($confirm)) {
        header("Location: ../../../frontend/auth/register_owner.php?error=Semua field wajib diisi");
        exit;
    }

    // Validasi konfirmasi password
    if ($password !== $confirm) {
        header("Location: ../../../frontend/auth/register_owner.php?error=Password tidak cocok");
        exit;
    }

    // Cek username / email sudah ada
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        header("Location: ../../../frontend/auth/register_owner.php?error=Username atau email sudah digunakan");
        exit;
    }
    $stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert data ke tabel users
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, user_type, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssss", $username, $email, $hashed_password, $full_name, $phone, $user_type);

    if ($stmt->execute()) {
        $conn->commit();

        $stmt->close();
        header("Location: ../../../frontend/auth/login.php?success=Registrasi owner berhasil, silakan login");
        exit;
    } else {
        $stmt->close();
        header("Location: ../../../frontend/auth/register_owner.php?error=Gagal mendaftarkan owner");
        exit;
    }
} else {
    header("Location: ../../../frontend/auth/register_owner.php");
    exit;
}
