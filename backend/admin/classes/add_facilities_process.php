<?php
// add_facilities_process.php
session_start();
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $icon = trim($_POST['icon']);
    $category = $_POST['category'];

    // Validasi sederhana
    if (empty($name) || empty($category)) {
        die("Nama dan kategori wajib diisi!");
    }

    try {
        // Cek duplikat nama fasilitas
        $check = $conn->prepare("SELECT id FROM facilities WHERE name = ?");
        $check->bind_param("s", $name);
        $check->execute();
        $result = $check->get_result();
        if ($result->num_rows > 0) {
            die("Fasilitas dengan nama '$name' sudah ada!");
        }
        $check->close();

        // Insert ke DB
        $stmt = $conn->prepare("INSERT INTO facilities (name, icon, category, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $name, $icon, $category);

        if ($stmt->execute()) {
            // Redirect ke daftar fasilitas
            header("Location: facilities_list.php?success=1");
            exit;
        } else {
            die("Gagal menyimpan fasilitas: " . $conn->error);
        }
    } catch (Exception $e) {
        die("Terjadi error: " . $e->getMessage());
    }
} else {
    header("Location: add_facilities.php");
    exit;
}
