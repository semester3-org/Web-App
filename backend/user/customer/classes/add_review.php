<?php
// File: /Web-App/backend/user/customer/classes/add_review.php
session_start();
header('Content-Type: application/json');

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$kos_id = isset($data['kos_id']) ? intval($data['kos_id']) : 0;
$rating = isset($data['rating']) ? intval($data['rating']) : 0;
$comment = isset($data['comment']) ? trim($data['comment']) : '';

// Validasi
if ($kos_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Kos tidak valid']);
    exit();
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating harus antara 1-5']);
    exit();
}

if (empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Komentar tidak boleh kosong']);
    exit();
}

// Cek apakah kos exists dan approved
$check_kos = $conn->prepare("SELECT id FROM kos WHERE id = ? AND status = 'approved'");
$check_kos->bind_param("i", $kos_id);
$check_kos->execute();
if ($check_kos->get_result()->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Kos tidak ditemukan']);
    exit();
}

// Cek apakah user sudah pernah review kos ini
$check_review = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND kos_id = ?");
$check_review->bind_param("ii", $user_id, $kos_id);
$check_review->execute();
$existing_review = $check_review->get_result();

if ($existing_review->num_rows > 0) {
    // Update existing review
    $stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ?, created_at = NOW() WHERE user_id = ? AND kos_id = ?");
    $stmt->bind_param("isii", $rating, $comment, $user_id, $kos_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Review berhasil diperbarui!',
            'action' => 'updated'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui review']);
    }
} else {
    // Insert new review
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, kos_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiis", $user_id, $kos_id, $rating, $comment);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Review berhasil ditambahkan!',
            'action' => 'created'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan review']);
    }
}

$stmt->close();
$conn->close();
?>