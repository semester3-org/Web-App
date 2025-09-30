<?php
session_start();
header('Content-Type: application/json');

// Include koneksi database
// Sesuaikan dengan file koneksi database Anda
require_once __DIR__ . '/../../config/db.php'; // atau path yang sesuai

// Hanya terima POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Cek apakah user sudah login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized - Silakan login terlebih dahulu'
    ]);
    exit;
}

try {
    // Ambil user ID dari session
    $userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'];
    
    // Cek apakah file dikirim
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] === UPLOAD_ERR_NO_FILE) {
        throw new Exception('Tidak ada file yang diupload');
    }
    
    $file = $_FILES['profile_picture'];
    
    // Cek error upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('Ukuran file terlalu besar');
            default:
                throw new Exception('Error upload file: ' . $file['error']);
        }
    }
    
    // Validasi tipe file
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Tipe file tidak diizinkan. Hanya JPG, PNG, dan GIF');
    }
    
    // Validasi ukuran file (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Ukuran file maksimal 5MB');
    }
    
    // Tentukan folder upload
    $uploadDir = __DIR__ . '/../../../uploads/profiles/';
    
    // Buat folder jika belum ada
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Gagal membuat folder upload');
        }
    }
    
    // Ambil foto lama dari database untuk dihapus
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $oldData = $result->fetch_assoc();
    $oldPhoto = $oldData['profile_picture'] ?? null;
    
    // Generate nama file unik
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('profile_', true) . '_' . time() . '.' . $extension;
    $uploadPath = $uploadDir . $fileName;
    
    // Upload file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Gagal memindahkan file');
    }
    
    // Path relatif untuk database dan frontend
    $relativePath = '/Web-App/uploads/profiles/' . $fileName;
    
    // Simpan ke database
    $stmt = $conn->prepare("UPDATE users SET profile_picture = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $relativePath, $userId);
    
    if (!$stmt->execute()) {
        // Jika gagal simpan ke database, hapus file yang sudah diupload
        unlink($uploadPath);
        throw new Exception('Gagal menyimpan ke database: ' . $stmt->error);
    }
    
    // Hapus foto lama jika ada
    if ($oldPhoto && $oldPhoto !== '' && file_exists(__DIR__ . '/../../../' . $oldPhoto)) {
        unlink(__DIR__ . '/../../../' . $oldPhoto);
    }
    
    // Update session jika ada
    if (isset($_SESSION['profile_picture'])) {
        $_SESSION['profile_picture'] = $relativePath;
    }
    
    // Response sukses
    echo json_encode([
        'success' => true,
        'message' => 'Upload berhasil',
        'filepath' => $relativePath,
        'filename' => $fileName
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
exit;
?>