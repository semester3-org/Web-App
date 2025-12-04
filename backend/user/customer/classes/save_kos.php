<?php
session_start();
header('Content-Type: application/json');

require_once($_SERVER['DOCUMENT_ROOT'] . "/backend/config/db.php");

// Pastikan koneksi aktif
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal']);
    exit;
}

// Harus login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login terlebih dahulu']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$data    = json_decode(file_get_contents("php://input"), true);
$kos_id  = (int)($data['kos_id'] ?? 0);

if ($kos_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID kos tidak valid']);
    exit;
}

// MULAI TRANSAKSI — WAJIB karena autocommit(false)
$conn->begin_transaction();

try {
    // Cek kos ada & approved
    $stmt = $conn->prepare("SELECT id, owner_id FROM kos WHERE id = ? AND status = 'approved'");
    $stmt->bind_param("i", $kos_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Kos tidak ditemukan atau belum disetujui');
    }

    $kos = $result->fetch_assoc();
    $owner_id = (int)$kos['owner_id'];
    $stmt->close();

    // Cek apakah sudah di-save (pakai unique_user_kos)
    $stmt = $conn->prepare("SELECT 1 FROM saved_kos WHERE user_id = ? AND kos_id = ?");
    $stmt->bind_param("ii", $user_id, $kos_id);
    $stmt->execute();
    $already_saved = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($already_saved) {
        // HAPUS DARI WISHLIST
        $stmt = $conn->prepare("DELETE FROM saved_kos WHERE user_id = ? AND kos_id = ?");
        $stmt->bind_param("ii", $user_id, $kos_id);
        $stmt->execute();
        $stmt->close();

        // ✅ UPDATE NOTIFIKASI SUMMARY SETELAH HAPUS
        // Summary akan otomatis terhapus jika count = 0
        if ($owner_id != $user_id) {
            require_once($_SERVER['DOCUMENT_ROOT'] . "/backend/user/owner/classes/Notification.php");
            $notif = new Notification($conn);
            $notif->updateOrCreateWishlistSummaryNotification($kos_id, $owner_id);
        }

        $conn->commit(); // COMMIT TRANSAKSI

        echo json_encode([
            'success'   => true,
            'favorited' => false,
            'message'   => 'Dihapus dari wishlist'
        ]);
    } else {
        // TAMBAH KE WISHLIST
        $stmt = $conn->prepare("INSERT INTO saved_kos (user_id, kos_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $kos_id);
        $stmt->execute();
        $stmt->close();

        // ✅ UPDATE NOTIFIKASI SUMMARY SETELAH TAMBAH
        // Summary akan dibuat atau di-update countnya
        if ($owner_id != $user_id) {
            require_once($_SERVER['DOCUMENT_ROOT'] . "/backend/user/owner/classes/Notification.php");
            $notif = new Notification($conn);
            $notif->updateOrCreateWishlistSummaryNotification($kos_id, $owner_id);
        }

        $conn->commit(); // COMMIT TRANSAKSI

        echo json_encode([
            'success'   => true,
            'favorited' => true,
            'message'   => 'Ditambahkan ke wishlist'
        ]);
    }

} catch (Exception $e) {
    $conn->rollback(); // ROLLBACK kalau ada error
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
exit;
?>