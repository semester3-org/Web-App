<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // biar bisa diakses dari Android
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include "../../config/db.php";

// Ambil data JSON dari request
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["email"]) || !isset($data["password"])) {
    echo json_encode(["status" => "error", "message" => "Field email dan password wajib diisi", "data" => null]);
    exit;
}


$email = $data["email"];
$password = $data["password"];

// Ambil data user dari database
$stmt = $conn->prepare("SELECT id, username, email, password, full_name, phone, user_type FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
    
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verifikasi password
    if (password_verify($password, $user["password"])) {
        echo json_encode([
            "status" => "success",
            "code" => 200,
            "message" => "Hallo dek",
            "user" => [
                "id" => $user["id"],
                "username" => $user["username"],
                "email" => $user["email"],
                "full_name" => $user["full_name"],
                "phone" => $user["phone"],
                "user_type" => $user["user_type"]
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Password salah"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "User tidak ditemukan"]);
}

$conn->close();
?>
