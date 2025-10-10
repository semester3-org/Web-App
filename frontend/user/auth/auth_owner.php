<?php
// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    // Jika belum login, redirect ke login admin
    header("Location: auth/login.php?type=user&error=Silakan login terlebih dahulu");
    exit();
}

// Cek apakah user adalah customer
if ($_SESSION['user_type'] !== 'owner') {
    // Jika bukan customer, destroy session dan redirect
    session_destroy();
    header("Location: auth/login.php?type=user&error=Hanya user yang boleh mengakses");
    exit();
}
