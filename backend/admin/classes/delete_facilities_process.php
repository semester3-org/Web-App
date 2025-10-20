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

// Ambil ID dari POST
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

// Validasi ID
if (empty($id) || $id <= 0) {
    $_SESSION['error'] = "ID fasilitas tidak valid";
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
$checkQuery = "SELECT name FROM facilities WHERE id = ?";
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

$facility = $result->fetch_assoc();
$facilityName = $facility['name'];
$stmt->close();

// Cek apakah fasilitas sedang digunakan di property_facilities
// Skip jika tabel tidak ada
$checkUsageQuery = "SELECT COUNT(*) as count FROM kos_facilities WHERE facility_id = ?";
$stmt = $conn->prepare($checkUsageQuery);

if ($stmt) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usage = $result->fetch_assoc();
    $stmt->close();
    
    if ($usage['count'] > 0) {
        $_SESSION['error'] = "Fasilitas '$facilityName' tidak dapat dihapus karena sedang digunakan oleh " . $usage['count'] . " properti";
        header("Location: " . $redirectPath);
        exit();
    }
}
// Jika tabel property_facilities tidak ada, lanjutkan penghapusan

// Hapus fasilitas dari database
$deleteQuery = "DELETE FROM facilities WHERE id = ?";
$stmt = $conn->prepare($deleteQuery);

if (!$stmt) {
    $_SESSION['error'] = "Error preparing delete statement: " . $conn->error;
    header("Location: " . $redirectPath);
    exit();
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $conn->commit(); 
    $_SESSION['success'] = "Fasilitas '$facilityName' berhasil dihapus";
} else {
    $_SESSION['error'] = "Gagal menghapus fasilitas: " . $conn->error;
}

$stmt->close();
$conn->close();

// Redirect
header("Location: " . $redirectPath);
exit();
?>