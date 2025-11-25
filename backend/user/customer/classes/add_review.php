<?php
session_start();
header('Content-Type: application/json');
require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$kos_id  = intval($data['kos_id'] ?? 0);
$rating  = intval($data['rating'] ?? 0);
$comment = trim($data['comment'] ?? '');

if ($kos_id <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

$kos_check = $conn->prepare("SELECT id, owner_id FROM kos WHERE id = ? AND status = 'approved'");
$kos_check->bind_param("i", $kos_id);
$kos_check->execute();
if ($kos_check->get_result()->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Kos tidak ditemukan']);
    exit;
}
$kos_owner = $kos_check->get_result()->fetch_assoc();
$owner_id = $kos_owner['owner_id'];

$check = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND kos_id = ?");
$check->bind_param("ii", $user_id, $kos_id);
$check->execute();
$exists = $check->get_result()->num_rows > 0;

if ($exists) {
    $stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ?, created_at = NOW() WHERE user_id = ? AND kos_id = ?");
    $stmt->bind_param("isii", $rating, $comment, $user_id, $kos_id);
    $action = 'diperbarui';
} else {
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, kos_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $user_id, $kos_id, $rating, $comment);
    $action = 'ditambahkan';
}

if ($stmt->execute()) {
    // KIRIM NOTIFIKASI KE OWNER
    require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/user/owner/classes/Notification.php");
    $notif = new Notification($conn);
    $notif->createNewReviewNotification($kos_id, $owner_id, $rating, $comment);

    echo json_encode(['success' => true, 'message' => "Review berhasil $action!"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan review']);
}

$stmt->close();
$conn->close();
?>