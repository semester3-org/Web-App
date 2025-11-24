<?php
header("Content-Type: application/json");
include "../../config/db.php";

// =============================
// NORMALISASI INPUT
// =============================

$cleanPost = [];
foreach ($_POST as $key => $value) {
    $cleanKey = trim($key);    // Hilangkan spasi di depan & belakang key
    $cleanVal = trim($value);  // Hilangkan spasi di value
    $cleanPost[$cleanKey] = $cleanVal;
}

$_POST = $cleanPost;

// Ambil action (tanpa spasi & tanpa karakter tersembunyi)
$action = isset($_POST['action']) ? trim($_POST['action']) : (isset($_GET['action']) ? trim($_GET['action']) : null);

// Jika masih null → action tidak ditemukan
if (!$action) {
    echo json_encode(["success" => false, "message" => "Action tidak ditemukan"]);
    exit;
}

// =============================
// 1. SAVE FAVORITE
// =============================
if ($action === "save") {
    // Memastikan parameter diterima dengan benar
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $kos_id  = isset($_POST['kos_id']) ? intval($_POST['kos_id']) : 0;

    // Validasi user_id dan kos_id
    if ($user_id <= 0 || $kos_id <= 0) {
        echo json_encode(["success" => false, "message" => "user_id & kos_id wajib"]);
        exit;
    }

    // Cek apakah sudah ada
    $check = $conn->prepare("SELECT id FROM saved_kos WHERE user_id = ? AND kos_id = ?");
    $check->bind_param("ii", $user_id, $kos_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Sudah difavoritkan"]);
        exit;
    }

    // Insert
    $stmt = $conn->prepare("INSERT INTO saved_kos (user_id, kos_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $kos_id);
    $success = $stmt->execute();

    echo json_encode([
        "success" => $success,
        "message" => $success ? "Berhasil menambah favorite" : "Gagal menambah favorite",
        "error" => $stmt->error
    ]);
    exit;
}

// =============================
// 2. REMOVE FAVORITE
// =============================
if ($action === "remove") {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $kos_id  = isset($_POST['kos_id']) ? intval($_POST['kos_id']) : 0;

    // Validasi user_id dan kos_id
    if ($user_id <= 0 || $kos_id <= 0) {
        echo json_encode(["success" => false, "message" => "user_id & kos_id wajib"]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM saved_kos WHERE user_id = ? AND kos_id = ?");
    $stmt->bind_param("ii", $user_id, $kos_id);
    $success = $stmt->execute();

    echo json_encode([
        "success" => $success,
        "message" => $success ? "Berhasil menghapus favorite" : "Gagal menghapus favorite",
        "error" => $stmt->error
    ]);
    exit;
}

// =============================
// 3. LIST FAVORITE
// =============================
if ($action === "list") {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : (isset($_POST['user_id']) ? intval($_POST['user_id']) : 0);

    // Validasi user_id
    if ($user_id <= 0) {
        echo json_encode(["success" => false, "message" => "user_id wajib"]);
        exit;
    }
    
    // Query untuk mendapatkan list favorit
    $sql = "
    SELECT 
        k.id,
        k.name,
        k.description,
        k.address,
        k.city AS location_name,
        k.price_monthly,
        k.latitude,
        k.longitude,

        -- Fasilitas (string)
        (
            SELECT GROUP_CONCAT(f.name SEPARATOR ', ')
            FROM kos_facilities kf
            JOIN facilities f ON f.id = kf.facility_id
            WHERE kf.kos_id = k.id
        ) AS facilities,

        -- Rating
        (
            SELECT AVG(r.rating)
            FROM reviews r
            WHERE r.kos_id = k.id
        ) AS avg_rating,

        1 AS isFavorite

    FROM saved_kos s
    JOIN kos k ON s.kos_id = k.id
    WHERE s.user_id = ?
    ORDER BY k.id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(["success" => true, "data" => $data]);
    exit;
}

// Jika action tidak dikenali
echo json_encode(["success" => false, "message" => "Action tidak valid"]);
