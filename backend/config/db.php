<?php
/**
 * ============================================
 * DATABASE CONFIGURATION
 * File: backend/config/db.php
 * ============================================
 * Konfigurasi koneksi database MySQL
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // Sesuaikan dengan username MySQL Anda
define('DB_PASS', '');               // Sesuaikan dengan password MySQL Anda
define('DB_NAME', 'db_koshub');        // Sesuaikan dengan nama database Anda

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Disable autocommit untuk transaction
$conn->autocommit(FALSE);

// Optional: Set timezone
date_default_timezone_set('Asia/Jakarta');

?>