<?php
require_once '../utils/auth_session.php';
require_once '../config/db.php';

check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kos_id = isset($_POST['kos_id']) ? (int)$_POST['kos_id'] : 0;
    $user_id = $_SESSION['user_id'];
    $booking_date = $_POST['booking_date'];

    if ($kos_id <= 0) {
        echo json_encode(['error' => 'ID kos tidak valid']);
        exit;
    }

    // Check if kos exists
    $stmt = $pdo->prepare("SELECT id FROM kos WHERE id = ?");
    $stmt->execute([$kos_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Kos tidak ditemukan']);
        exit;
    }

    // Check if already booked
    $stmt = $pdo->prepare("SELECT id FROM booking WHERE kos_id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$kos_id, $user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Anda sudah melakukan booking untuk kos ini']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO booking (kos_id, user_id, booking_date) VALUES (?, ?, ?)");
    $stmt->execute([$kos_id, $user_id, $booking_date]);

    echo json_encode(['success' => true, 'booking_id' => $pdo->lastInsertId()]);
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>