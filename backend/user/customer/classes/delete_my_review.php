<?php
// File: /Web-App/backend/user/customer/classes/delete_my_review.php
session_start();
header('Content-Type: application/json');

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

// Pastikan user sudah login dan bertipe 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu']);
    exit();
}

$user_id = $_SESSION['user_id'];

// --- Ambil input, baik dari JSON maupun dari form biasa ---
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Ambil dari form POST biasa jika tidak ada JSON
if (empty($data)) {
    $data = $_POST;
}

// Bisa pakai kos_id atau review_id
$kos_id = isset($data['kos_id']) ? intval($data['kos_id']) : 0;
$review_id = isset($data['review_id']) ? intval($data['review_id']) : 0;

// Cek minimal salah satu harus ada
if ($kos_id <= 0 && $review_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid (kos_id atau review_id kosong)']);
    exit();
}

// Tentukan query berdasarkan input
if ($review_id > 0) {
    // Hapus berdasarkan ID review
    $check = $conn->prepare("SELECT id FROM reviews WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $review_id, $user_id);
} else {
    // Hapus berdasarkan user_id dan kos_id
    $check = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND kos_id = ?");
    $check->bind_param("ii", $user_id, $kos_id);
}

$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Review tidak ditemukan']);
    $check->close();
    $conn->close();
    exit();
}

// Hapus review sesuai kondisi
if ($review_id > 0) {
    $delete = $conn->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
    $delete->bind_param("ii", $review_id, $user_id);
} else {
    $delete = $conn->prepare("DELETE FROM reviews WHERE user_id = ? AND kos_id = ?");
    $delete->bind_param("ii", $user_id, $kos_id);
}

if ($delete->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Review berhasil dihapus!'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menghapus review'
    ]);
}

$check->close();
$delete->close();
$conn->close();
?>
