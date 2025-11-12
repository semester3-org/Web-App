<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

$input = json_decode(file_get_contents('php://input'), true);

$kos_id = intval($input['kos_id'] ?? 0);
$user_id = intval($_SESSION['user_id']);
$check_in_date = $input['check_in_date'] ?? '';
$check_out_date = $input['check_out_date'] ?? null;
$booking_type = $input['booking_type'] ?? '';
$duration_months = !empty($input['duration_months']) ? intval($input['duration_months']) : null;
$total_price = intval($input['total_price'] ?? 0);
$notes = $input['notes'] ?? '';

// Validation
if ($kos_id == 0 || empty($check_in_date) || empty($booking_type) || $total_price == 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

if ($booking_type === 'monthly' && empty($duration_months)) {
    echo json_encode(['success' => false, 'message' => 'Durasi bulan harus diisi untuk booking bulanan']);
    exit;
}

if ($booking_type === 'daily' && empty($check_out_date)) {
    echo json_encode(['success' => false, 'message' => 'Tanggal check-out harus diisi untuk booking harian']);
    exit;
}

// Check kos exists and approved
$check_kos = $conn->prepare("SELECT id, available_rooms FROM kos WHERE id = ? AND status = 'approved'");
$check_kos->bind_param("i", $kos_id);
$check_kos->execute();
$kos_result = $check_kos->get_result();

if ($kos_result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Property tidak ditemukan atau belum disetujui']);
    exit;
}

$kos = $kos_result->fetch_assoc();
if ($kos['available_rooms'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Maaf, kamar sudah penuh']);
    exit;
}

// === MULAI TRANSAKSI ===
$conn->begin_transaction();

try {
    // Insert booking
    $sql = "INSERT INTO bookings (kos_id, user_id, check_in_date, check_out_date, booking_type, duration_months, total_price, notes, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisssiis", 
        $kos_id, 
        $user_id, 
        $check_in_date, 
        $check_out_date, 
        $booking_type, 
        $duration_months, 
        $total_price, 
        $notes
    );

    if (!$stmt->execute()) {
        throw new Exception("Gagal insert booking: " . $stmt->error);
    }

    $booking_id = $stmt->insert_id;

    // Kurangi kamar tersedia
    $update_rooms = $conn->prepare("UPDATE kos SET available_rooms = available_rooms - 1 WHERE id = ?");
    $update_rooms->bind_param("i", $kos_id);
    if (!$update_rooms->execute()) {
        throw new Exception("Gagal update kamar tersedia");
    }

    // === COMMIT: Simpan semua perubahan ===
    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Booking berhasil dibuat! Menunggu konfirmasi pemilik.',
        'booking_id' => $booking_id
    ]);

} catch (Exception $e) {
    // === ROLLBACK: Batalkan semua jika ada error ===
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>