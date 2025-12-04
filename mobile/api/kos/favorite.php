<?php
header("Content-Type: application/json");
include "../../config/db.php";

// =============================
// NORMALISASI INPUT
// =============================
$cleanPost = [];
foreach ($_POST as $key => $value) {
    $cleanKey = trim($key);
    $cleanVal = trim($value);
    $cleanPost[$cleanKey] = $cleanVal;
}
$_POST = $cleanPost;

// Ambil action
$action = isset($_POST['action'])
    ? trim($_POST['action'])
    : (isset($_GET['action']) ? trim($_GET['action']) : null);

if (!$action) {
    echo json_encode(["success" => false, "message" => "Action tidak ditemukan"]);
    exit;
}

// Base URL buat gambar (samain kayak endpoint home lu)
$base_url = "http://10.134.206.61/Web-App/";

// =============================
// 1. SAVE FAVORITE
// =============================
if ($action === "save") {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $kos_id  = isset($_POST['kos_id'])  ? intval($_POST['kos_id'])  : 0;

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
        "error"   => $stmt->error
    ]);
    exit;
}

// =============================
// 2. REMOVE FAVORITE
// =============================
if ($action === "remove") {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $kos_id  = isset($_POST['kos_id'])  ? intval($_POST['kos_id'])  : 0;

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
        "error"   => $stmt->error
    ]);
    exit;
}

// =============================
// 3. LIST FAVORITE (WITH IMAGES)
// =============================
if ($action === "list") {
    $user_id = isset($_GET['user_id'])
        ? intval($_GET['user_id'])
        : (isset($_POST['user_id']) ? intval($_POST['user_id']) : 0);

    if ($user_id <= 0) {
        echo json_encode(["success" => false, "message" => "user_id wajib"]);
        exit;
    }

    // NOTE:
    // - GROUP_CONCAT images -> jadi string dulu, nanti kita pecah jadi array images[]
    // - SEPARATOR '||' biar aman (lebih jarang muncul daripada koma)
    $sql = "
        SELECT
            k.id,
            k.name,
            k.description,
            k.address,
            k.city AS location_name,
            k.kos_type,
            k.price_monthly,
            k.latitude,
            k.longitude,

            (
                SELECT GROUP_CONCAT(f.name SEPARATOR ', ')
                FROM kos_facilities kf
                JOIN facilities f ON f.id = kf.facility_id
                WHERE kf.kos_id = k.id
            ) AS facilities,

            (
                SELECT AVG(r.rating)
                FROM reviews r
                WHERE r.kos_id = k.id
            ) AS avg_rating,

            GROUP_CONCAT(ki.image_url SEPARATOR '||') AS images_raw,

            1 AS isFavorite

        FROM saved_kos s
        JOIN kos k ON s.kos_id = k.id
        LEFT JOIN kos_images ki ON ki.kos_id = k.id
        WHERE s.user_id = ?
        GROUP BY k.id
        ORDER BY k.id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {

        // images_raw -> images[]
        $images = [];
        if (!empty($row["images_raw"])) {
            $parts = explode("||", $row["images_raw"]);
            foreach ($parts as $img) {
                $img = trim($img);
                if ($img !== "") {
                    $images[] = $base_url . $img;
                }
            }
        }

        $rating = $row["avg_rating"] ? round(floatval($row["avg_rating"]), 1) : 0;

        $data[] = [
            "id"            => intval($row["id"]),
            "name"          => $row["name"],
            "description"   => $row["description"],
            "location_name" => $row["location_name"],
            "address"       => $row["address"],
            "latitude"      => floatval($row["latitude"]),
            "longitude"     => floatval($row["longitude"]),
            "kos_type"      => $row["kos_type"],
            "price_monthly" => intval($row["price_monthly"]),
            "facilities"    => $row["facilities"] ?? "",
            "rating"        => $rating,
            "images"        => $images,
            "isFavorite"    => 1
        ];
    }

    echo json_encode(["success" => true, "data" => $data]);
    exit;
}

// Jika action tidak dikenali
echo json_encode(["success" => false, "message" => "Action tidak valid"]);
