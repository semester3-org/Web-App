<?php
// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    // Jika belum login, redirect ke login admin
    header("Location: auth/login.php?type=admin&error=Silakan login terlebih dahulu");
    exit();
}

// Cek apakah user adalah admin
if ($_SESSION['user_type'] !== 'admin') {
    // Jika bukan admin, destroy session dan redirect
    session_destroy();
    header("Location: auth/login.php?type=admin&error=Hanya admin yang boleh mengakses");
    exit();
}
