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
    die("Error: Database configuration file not found");
}

// Redirect path
$redirectPath = "../../../frontend/admin/pages/facilities.php";

// Cek login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Anda harus login terlebih dahulu";
    header("Location: " . $redirectPath);
    exit();
}

// Cek method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . $redirectPath);
    exit();
}

// Ambil data dari form
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$icon = isset($_POST['icon']) ? trim($_POST['icon']) : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : '';

// Validasi input
$errors = [];

if (empty($id) || $id <= 0) {
    $errors[] = "ID fasilitas tidak valid";
}

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

// Jika ada error
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

// Cek apakah fasilitas dengan ID tersebut ada
$checkQuery = "SELECT id, name FROM facilities WHERE id = ?";
$stmt = $conn->prepare($checkQuery);

if (!$stmt) {
    $_SESSION['error'] = "Error preparing statement: " . $conn->error;
    header("Location: " . $redirectPath);
    exit();
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Fasilitas tidak ditemukan";
    $stmt->close();
    header("Location: " . $redirectPath);
    exit();
}
$stmt->close();

// Cek duplikasi nama
$checkDuplicateQuery = "SELECT id FROM facilities WHERE name = ? AND category = ? AND id != ?";
$stmt = $conn->prepare($checkDuplicateQuery);

if (!$stmt) {
    $_SESSION['error'] = "Error preparing statement: " . $conn->error;
    header("Location: " . $redirectPath);
    exit();
}

$stmt->bind_param("ssi", $name, $category, $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Fasilitas dengan nama '$name' sudah ada di kategori ini";
    $stmt->close();
    header("Location: " . $redirectPath);
    exit();
}
$stmt->close();

// Update data di database
$updateQuery = "UPDATE facilities SET name = ?, icon = ?, category = ? WHERE id = ?";
$stmt = $conn->prepare($updateQuery);

if (!$stmt) {
    $_SESSION['error'] = "Error preparing update statement: " . $conn->error;
    header("Location: " . $redirectPath);
    exit();
}

$stmt->bind_param("sssi", $name, $icon, $category, $id);

if ($stmt->execute()) {
    $conn->commit(); 
    $_SESSION['success'] = "Fasilitas '$name' berhasil diupdate";
} else {
    $_SESSION['error'] = "Gagal mengupdate fasilitas: " . $conn->error;
}

$stmt->close();
$conn->close();

// Redirect
header("Location: " . $redirectPath);
exit();
?>