<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

$input = json_decode(file_get_contents('php://input'), true);

$kos_id          = intval($input['kos_id'] ?? 0);
$user_id         = intval($_SESSION['user_id']);
$check_in_date   = $input['check_in_date'] ?? '';
$check_out_date  = $input['check_out_date'] ?? null;  // BISA null
$booking_type    = $input['booking_type'] ?? '';
$duration_months = isset($input['duration_months']) ? intval($input['duration_months']) : null;
$total_price     = intval($input['total_price'] ?? 0);
$notes           = trim($input['notes'] ?? '');

// === VALIDASI INPUT ===
if ($kos_id == 0 || empty($check_in_date) || empty($booking_type) || $total_price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap atau harga tidak valid']);
    exit;
}

if ($booking_type === 'monthly') {
    if ($duration_months === null || $duration_months < 1 || $duration_months > 12) {
        echo json_encode(['success' => false, 'message' => 'Durasi bulanan harus 1-12 bulan']);
        exit;
    }

    // HITUNG check_out_date = check_in_date + $duration_months bulan
    $check_in = new DateTime($check_in_date);
    $check_out = clone $check_in;
    $check_out->modify("+$duration_months months");
    $check_out_date = $check_out->format('Y-m-d'); // Format: 2025-06-05

} elseif ($booking_type === 'daily') {
    if (empty($check_out_date)) {
        echo json_encode(['success' => false, 'message' => 'Tanggal check-out wajib diisi untuk harian']);
        exit;
    }
    $duration_months = null;
} else {
    echo json_encode(['success' => false, 'message' => 'Tipe booking tidak valid']);
    exit;
}

// Validasi tanggal check-in tidak boleh lewat
if (strtotime($check_in_date) < strtotime('today')) {
    echo json_encode(['success' => false, 'message' => 'Tanggal check-in tidak boleh masa lalu']);
    exit;
}

// Jika harian, check-out harus setelah check-in
if ($booking_type === 'daily' && strtotime($check_out_date) <= strtotime($check_in_date)) {
    echo json_encode(['success' => false, 'message' => 'Tanggal check-out harus setelah check-in']);
    exit;
}

// === CEK KOS ===
$check_kos = $conn->prepare("SELECT id, available_rooms, price_monthly, price_daily FROM kos WHERE id = ? AND status = 'approved'");
$check_kos->bind_param("i", $kos_id);
$check_kos->execute();
$kos_result = $check_kos->get_result();

if ($kos_result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Kos tidak ditemukan atau belum disetujui']);
    exit;
}

$kos = $kos_result->fetch_assoc();
if ($kos['available_rooms'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Maaf, kamar sudah penuh']);
    exit;
}

// Validasi harga sesuai tipe
if ($booking_type === 'monthly' && $total_price != ($kos['price_monthly'] * $duration_months)) {
    echo json_encode(['success' => false, 'message' => 'Harga bulanan tidak sesuai']);
    exit;
}

if ($booking_type === 'daily') {
    $days = (strtotime($check_out_date) - strtotime($check_in_date)) / (60*60*24);
    if ($total_price != ($kos['price_daily'] * $days)) {
        echo json_encode(['success' => false, 'message' => 'Harga harian tidak sesuai']);
        exit;
    }
}

// === CEK APAKAH USER SUDAH PUNYA BOOKING PENDING ===
$check_pending = $conn->prepare("
    SELECT id FROM bookings 
    WHERE user_id = ? AND status = 'pending' 
    LIMIT 1
");
$check_pending->bind_param("i", $user_id);
$check_pending->execute();
$pending_result = $check_pending->get_result();

if ($pending_result->num_rows > 0) {
    $check_pending->close();
    echo json_encode([
        'success' => false, 
        'message' => 'Anda sudah memiliki booking yang belum dikonfirmasi. Silakan tunggu atau batalkan booking sebelumnya.'
    ]);
    exit;
}
$check_pending->close();

// === MULAI TRANSAKSI ===
$conn->begin_transaction();

try {
    // Insert booking
    $sql = "INSERT INTO bookings 
            (kos_id, user_id, check_in_date, check_out_date, booking_type, duration_months, total_price, notes, status) 
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

    // === HAPUS BAGIAN INI ===
    /*
    // Kurangi kamar
    $update_rooms = $conn->prepare("UPDATE kos SET available_rooms = available_rooms - 1 WHERE id = ?");
    $update_rooms->bind_param("i", $kos_id);
    if (!$update_rooms->execute()) {
        throw new Exception("Gagal mengurangi kamar tersedia");
    }
    */
    // === END HAPUS ===

    // === COMMIT ===
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Booking berhasil! Menunggu konfirmasi pemilik.',
        'booking_id' => $booking_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Tutup statement
if (isset($stmt)) $stmt->close();
if (isset($update_rooms)) $update_rooms->close();
$conn->close();
?>