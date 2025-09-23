<?php
require_once '../utils/auth_session.php';
require_once '../config/db.php';

check_login();
check_role('owner');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $owner_id = $_SESSION['user_id'];
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $price = (int)$_POST['price'];
    $type = $_POST['type'];
    $facilities = trim($_POST['facilities']); // misal: "wifi,ac,parkir"
    $description = trim($_POST['description']);
    $latitude = isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;

    // Validasi sederhana
    if (!$name || !$address || !$price || !in_array($type, ['male', 'female', 'mixed'])) {
        echo json_encode(['error' => 'Data tidak lengkap atau tidak valid']);
        exit;
    }

    // Upload gambar
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            echo json_encode(['error' => 'Format gambar tidak didukung']);
            exit;
        }

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $target_dir = '../../uploads/kos/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $target_file = $target_dir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_url = 'uploads/kos/' . $filename;
        } else {
            echo json_encode(['error' => 'Gagal upload gambar']);
            exit;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO kos (owner_id, name, address, price, type, facilities, description, image_url, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$owner_id, $name, $address, $price, $type, $facilities, $description, $image_url, $latitude, $longitude]);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Invalid request method']);
}