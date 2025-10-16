<?php
session_start();
require_once '../../../config/db.php';

// Enable error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log file untuk debugging
$log_file = '../../../logs/property_add.log';
$log_dir = dirname($log_file);
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Cek apakah user sudah login dan merupakan owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'owner') {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - User not logged in or not owner. User ID: " . ($_SESSION['user_id'] ?? 'not set') . ", User Type: " . ($_SESSION['user_type'] ?? 'not set') . "\n", FILE_APPEND);
    header('Location: ../../../../frontend/auth/login.php');
    exit();
}

$owner_id = $_SESSION['user_id'];

// Validasi method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Not POST method\n", FILE_APPEND);
    header('Location: ../../../../frontend/user/owner/add_property.php');
    exit();
}

// Log semua POST data
file_put_contents($log_file, date('Y-m-d H:i:s') . " - POST Data: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents($log_file, date('Y-m-d H:i:s') . " - FILES Data: " . print_r($_FILES, true) . "\n", FILE_APPEND);

// Ambil dan sanitasi data dari form
$name = trim($_POST['name']);
$postal_code = trim($_POST['postal_code'] ?? '');
$address = trim($_POST['address']);
$province = trim($_POST['province']);
$city = trim($_POST['city']);
$latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
$longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
$total_rooms = intval($_POST['total_rooms']);
$kos_type = $_POST['kos_type'];
$price_monthly = intval(preg_replace('/[^0-9]/', '', $_POST['price_monthly']));
$price_daily = !empty($_POST['price_daily']) ? intval(preg_replace('/[^0-9]/', '', $_POST['price_daily'])) : null;
$description = trim($_POST['description'] ?? '');

// Ambil fasilitas dan aturan dari form (data JSON dari JavaScript)
$facilities = isset($_POST['facilities']) ? json_decode($_POST['facilities'], true) : [];
$rules = isset($_POST['rules']) ? json_decode($_POST['rules'], true) : [];

// Convert rules array ke JSON string untuk disimpan di database
$rules_json = !empty($rules) ? json_encode($rules) : null;

// Validasi data wajib
if (empty($name) || empty($address) || empty($province) || empty($city) || empty($kos_type) || $total_rooms <= 0 || $price_monthly <= 0) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Validation failed: name=$name, address=$address, province=$province, city=$city, kos_type=$kos_type, total_rooms=$total_rooms, price_monthly=$price_monthly\n", FILE_APPEND);
    $_SESSION['error'] = 'Mohon lengkapi semua field yang wajib diisi!';
    header('Location: ../../../../frontend/user/owner/add_property.php');
    exit();
}

// Validasi jenis kos
$valid_kos_types = ['putra', 'putri', 'campur'];
if (!in_array($kos_type, $valid_kos_types)) {
    $_SESSION['error'] = 'Jenis kos tidak valid!';
    header('Location: ../../../../frontend/user/owner/add_property.php');
    exit();
}
// Tangani facilities
if (isset($_POST['facilities'])) {
    $facilities = is_string($_POST['facilities'])
        ? json_decode($_POST['facilities'], true)
        : $_POST['facilities'];
} else {
    $facilities = [];
}

// Tangani rules
if (isset($_POST['rules'])) {
    $rules = is_string($_POST['rules'])
        ? json_decode($_POST['rules'], true)
        : $_POST['rules'];
} else {
    $rules = [];
}


try {
    // Log start transaction
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Starting transaction\n", FILE_APPEND);
    
    // Mulai transaction
    $conn->begin_transaction();
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Preparing INSERT query\n", FILE_APPEND);
    
    // Insert data ke tabel kos
    $stmt = $conn->prepare("INSERT INTO kos (owner_id, name, description, address, latitude, longitude, city, province, postal_code, kos_type, total_rooms, available_rooms, price_monthly, price_daily, rules, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Binding parameters: owner_id=$owner_id, name=$name, kos_type=$kos_type\n", FILE_APPEND);
    
    $stmt->bind_param("isssddssssiiiis", 
        $owner_id, 
        $name, 
        $description, 
        $address, 
        $latitude, 
        $longitude, 
        $city, 
        $province, 
        $postal_code, 
        $kos_type, 
        $total_rooms, 
        $total_rooms, // available_rooms sama dengan total_rooms saat pertama dibuat
        $price_monthly, 
        $price_daily, 
        $rules_json
    );
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Executing INSERT\n", FILE_APPEND);
    
    if (!$stmt->execute()) {
        throw new Exception("Gagal menyimpan data property: " . $stmt->error);
    }
    
    $kos_id = $conn->insert_id;
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Insert success, kos_id=$kos_id\n", FILE_APPEND);
    $stmt->close();
    
    // Insert fasilitas jika ada
    if (!empty($facilities) && is_array($facilities)) {
        $stmt_facility = $conn->prepare("INSERT INTO kos_facilities (kos_id, facility_id) VALUES (?, ?)");
        
        if (!$stmt_facility) {
            throw new Exception("Prepare facility failed: " . $conn->error);
        }
        
        foreach ($facilities as $facility_id) {
            $facility_id = intval($facility_id);
            $stmt_facility->bind_param("ii", $kos_id, $facility_id);
            
            if (!$stmt_facility->execute()) {
                throw new Exception("Gagal menyimpan fasilitas: " . $stmt_facility->error);
            }
        }
        
        $stmt_facility->close();
    }
    
    // Upload dan simpan gambar
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $upload_dir = '../../../../uploads/kos/';
        
        // Buat folder jika belum ada
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 10 * 1024 * 1024; // 10MB
        
        $stmt_image = $conn->prepare("INSERT INTO kos_images (kos_id, image_url) VALUES (?, ?)");
        
        if (!$stmt_image) {
            throw new Exception("Prepare image failed: " . $conn->error);
        }
        
        $total_files = count($_FILES['images']['name']);
        $uploaded_count = 0;
        
        for ($i = 0; $i < $total_files; $i++) {
            // Cek error upload
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $file_name = $_FILES['images']['name'][$i];
            $file_tmp = $_FILES['images']['tmp_name'][$i];
            $file_size = $_FILES['images']['size'][$i];
            $file_type = $_FILES['images']['type'][$i];
            
            // Validasi tipe file
            if (!in_array($file_type, $allowed_types)) {
                continue;
            }
            
            // Validasi ukuran file
            if ($file_size > $max_size) {
                continue;
            }
            
            // Generate nama file unik
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $new_file_name = 'kos_' . $kos_id . '_' . time() . '_' . uniqid() . '.' . $file_ext;
            $file_path = $upload_dir . $new_file_name;
            
            // Upload file
            if (move_uploaded_file($file_tmp, $file_path)) {
                // Simpan path relatif ke database
                $relative_path = 'uploads/kos/' . $new_file_name;
                $stmt_image->bind_param("is", $kos_id, $relative_path);
                
                if ($stmt_image->execute()) {
                    $uploaded_count++;
                }
            }
        }
        
        $stmt_image->close();
        
        // Cek apakah ada gambar yang berhasil diupload
        if ($uploaded_count === 0) {
            throw new Exception("Tidak ada gambar yang berhasil diupload. Pastikan format dan ukuran file sesuai.");
        }
    }
    
    // Commit transaction
    $conn->commit();
    $conn->close();
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Transaction committed successfully\n", FILE_APPEND);
    
    $_SESSION['success'] = 'Property berhasil ditambahkan dan menunggu persetujuan admin!';
    header('Location: ../../../../frontend/user/owner/pages/dashboard.php');
    exit();
    
} catch (Exception $e) {
    // Rollback jika terjadi error
    $conn->rollback();
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    
    $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
    header('Location: ../../../../frontend/user/owner/pages/add_property.php');
    exit();
}


?>