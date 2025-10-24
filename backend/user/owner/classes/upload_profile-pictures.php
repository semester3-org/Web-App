<?php
// ...existing code...
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];

    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] === UPLOAD_ERR_NO_FILE) {
        throw new Exception('Tidak ada file yang diupload');
    }

    $file = $_FILES['profile_picture'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error upload file: ' . $file['error']);
    }

    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Tipe file tidak diizinkan. Hanya JPG, PNG, dan GIF');
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Ukuran file maksimal 5MB');
    }

    $uploadDir = __DIR__ . '/../../../../uploads/profiles/';
    if (!file_exists($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new Exception('Gagal membuat folder upload');
    }

    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();
    $oldPhoto = $old['profile_picture'] ?? null;
    $stmt->close();

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = uniqid('profile_', true) . '.' . preg_replace('/[^a-z0-9]/i', '', $extension);
    $uploadPath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Gagal memindahkan file');
    }

    @chmod($uploadPath, 0644);
    $relativePath = '/Web-App/uploads/profiles/' . $fileName;

    $stmt = $conn->prepare("UPDATE users SET profile_picture = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('si', $relativePath, $userId);
    if (!$stmt->execute()) {
        @unlink($uploadPath);
        throw new Exception('Gagal menyimpan ke database: ' . $stmt->error);
    }
    $conn->commit(); // <--- Tambahkan ini
    $stmt->close();

    if (!empty($oldPhoto)) {
        $oldPath = __DIR__ . '/../../../../' . ltrim($oldPhoto, '/');
        if (file_exists($oldPath) && is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    $_SESSION['profile_picture'] = $relativePath;

    echo json_encode(['success' => true, 'message' => 'Upload berhasil', 'filepath' => $relativePath, 'filename' => $fileName]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) $conn->close();
}
exit;