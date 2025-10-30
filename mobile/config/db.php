<?php
/**
 * ============================================
 * DATABASE CONFIGURATION (MOBILE)
 * File: mobile/config/db.php
 * ============================================
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Sesuaikan dengan username MySQL kamu
define('DB_PASS', '');          // Sesuaikan dengan password MySQL kamu
define('DB_NAME', 'db_koshub'); // Pastikan sama dengan DB web kamu

// Buat koneksi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

// Set karakter dan zona waktu
$conn->set_charset('utf8mb4');
date_default_timezone_set('Asia/Jakarta');
?>
