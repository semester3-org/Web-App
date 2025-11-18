<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // biar bisa diakses dari Android
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include "../../config/db.php";

// Ambil data JSON dari request
$data = json_decode(file_get_contents("php://input"), true);

// Validasi input wajib
if (
    !isset($data["username"]) ||
    !isset($data["email"]) ||
    !isset($data["password"]) ||
    !isset($data["full_name"]) ||
    !isset($data["phone"])
) {
    echo json_encode([
        "status" => "error",
        "message" => "Semua field wajib diisi",
        "data" => null
    ]);
    exit;
}

$username = $data["username"];
$email = $data["email"];
$password = $data["password"];
$full_name = $data["full_name"];
$phone = $data["phone"];
$user_type = isset($data["user_type"]) ? $data["user_type"] : "user"; // default 'user'

// Cek apakah email sudah ada
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Email sudah terdaftar",
        "data" => null
    ]);
    exit;
}

// Hash password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Simpan data user baru
$stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, user_type) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $username, $email, $hashed, $full_name, $phone, $user_type);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Registrasi berhasil",
        "data" => [
            "username" => $username,
            "email" => $email,
            "full_name" => $full_name,
            "phone" => $phone,
            "user_type" => $user_type
        ]
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Gagal menyimpan data ke database",
        "data" => null
    ]);
}

$conn->close();
?>
