<?php
session_start();
require_once("../../config/db.php"); // koneksi database

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        header("Location: ../../../frontend/admin/auth/login.php?error=Username dan password wajib diisi&username=" . urlencode($username));
        exit;
    }

    // Query cek user berdasarkan username
    $stmt = $conn->prepare("SELECT id, username, password, full_name, user_type FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verifikasi password
        if (password_verify($password, $user['password'])) {

            // Hanya izinkan admin login ke area admin
            if ($user['user_type'] !== 'admin') {
                header("Location: ../../../frontend/admin/auth/login.php?error=Hanya admin yang bisa login&username=" . urlencode($username));
                exit;
            }

            // Simpan ke session
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['full_name']  = $user['full_name'];
            $_SESSION['user_type']  = $user['user_type'];

            // Redirect ke dashboard admin
            header("Location: ../../../frontend/admin/pages/dashboard.php");
            exit;

        } else {
            header("Location: ../../../frontend/admin/auth/login.php?error=Password salah&username=" . urlencode($username));
            exit;
        }
    } else {
        header("Location: ../../../frontend/admin/auth/login.php?error=Username tidak ditemukan&username=" . urlencode($username));
        exit;
    }
} else {
    header("Location: ../../../frontend/admin/auth/login.php");
    exit;
}
