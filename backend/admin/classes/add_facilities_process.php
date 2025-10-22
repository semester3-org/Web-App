<?php
session_start();

// Auto-detect database config path
$possiblePaths = [
    __DIR__ . "/../../config/db.php",
    __DIR__ . "/../../../config/db.php",
    dirname(dirname(__DIR__)) . "/config/db.php",
];

$dbConnected = false;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $dbConnected = true;
        break;
    }
}

if (!$dbConnected) {
    die("Error: Database configuration file not found. Checked paths: " . implode(", ", $possiblePaths));
}

// Redirect path untuk kembali ke halaman facilities
$redirectPath = "../../../frontend/admin/pages/facilities.php";

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Anda harus login terlebih dahulu";
    header("Location: " . $redirectPath);
    exit();
}

// Optional: Validasi role admin (uncomment jika diperlukan)
// if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
//     $_SESSION['error'] = "Akses ditolak. Hanya admin yang dapat mengakses";
//     header("Location: " . $redirectPath);
//     exit();
// }

// Cek apakah request method adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . $redirectPath);
    exit();
}

// Ambil data dari form
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$icon = isset($_POST['icon']) ? trim($_POST['icon']) : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : '';

// Validasi input
$errors = [];

if (empty($name)) {
    $errors[] = "Nama fasilitas harus diisi";
}

if (strlen($name) < 2) {
    $errors[] = "Nama fasilitas minimal 2 karakter";
}

if (strlen($name) > 100) {
    $errors[] = "Nama fasilitas maksimal 100 karakter";
}

if (empty($category)) {
    $errors[] = "Kategori harus dipilih";
}

// Validasi kategori yang valid
$validCategories = ['room', 'bathroom', 'common', 'parking', 'security'];
if (!in_array($category, $validCategories)) {
    $errors[] = "Kategori tidak valid";
}

// Jika ada error, redirect kembali dengan pesan error
if (!empty($errors)) {
    $_SESSION['error'] = implode(", ", $errors);
    header("Location: " . $redirectPath);
    exit();
}

// Cek koneksi database
if (!isset($conn) || $conn->connect_error) {
    $_SESSION['error'] = "Koneksi database gagal";
    header("Location: " . $redirectPath);
    exit();
}

// Cek apakah fasilitas dengan nama yang sama sudah ada di kategori yang sama
$checkQuery = "SELECT id FROM facilities WHERE name = ? AND category = ?";
$stmt = $conn->prepare($checkQuery);

if (!$stmt) {
    $_SESSION['error'] = "Error preparing statement: " . $conn->error;
    header("Location: " . $redirectPath);
    exit();
}

$stmt->bind_param("ss", $name, $category);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Fasilitas dengan nama '$name' sudah ada di kategori ini";
    $stmt->close();
    header("Location: " . $redirectPath);
    exit();
}
$stmt->close();

// Insert data ke database
$insertQuery = "INSERT INTO facilities (name, icon, category, created_at) VALUES (?, ?, ?, NOW())";
$stmt = $conn->prepare($insertQuery);

if (!$stmt) {
    $_SESSION['error'] = "Error preparing insert statement: " . $conn->error;
    header("Location: " . $redirectPath);
    exit();
}

$stmt->bind_param("sss", $name, $icon, $category);

if ($stmt->execute()) {
    $conn->commit(); 
    $_SESSION['success'] = "Fasilitas '$name' berhasil ditambahkan";
} else {
    $_SESSION['error'] = "Gagal menambahkan fasilitas: " . $conn->error;
}

$stmt->close();
$conn->close();

// Redirect kembali ke halaman facilities
header("Location: " . $redirectPath);
exit();
?>