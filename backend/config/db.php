<?php
// backend/config/db.php

$host = "localhost";
$user = "root";       // default laragon
$pass = "";           // default kosong
$db   = "db_koshub";  // nama database kamu

// Buat koneksi
$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// (Opsional) Set charset biar aman untuk UTF-8
$conn->set_charset("utf8");
?>
