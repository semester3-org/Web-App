<?php
// get_facilities.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Koneksi database - sesuaikan dengan konfigurasi Anda
$host = 'localhost';
$dbname = 'db_koshub'; // sesuaikan nama database Anda
$username = 'root';  // sesuaikan username Anda
$password = '';      // sesuaikan password Anda

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query untuk mengambil semua fasilitas, diurutkan berdasarkan kategori dan nama
    $query = "SELECT id, name, icon, category, created_at 
              FROM facilities 
              ORDER BY 
                FIELD(category, 'room', 'bathroom', 'common', 'parking', 'security'),
                name ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return data sebagai JSON
    echo json_encode($facilities, JSON_PRETTY_PRINT);
    
} catch(PDOException $e) {
    // Log error ke file (opsional)
    error_log("Database Error: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Terjadi kesalahan saat mengambil data fasilitas',
        'details' => $e->getMessage() // Hapus ini di production
    ], JSON_PRETTY_PRINT);
}
?>