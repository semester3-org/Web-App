<?php
/**
 * ============================================
 * API REGISTER (MOBILE)
 * File: mobile/api/auth/register.php
 * ============================================
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Content-Type: application/json');

require_once '../../config/db.php';
require_once '../../utils/response.php';

// Ambil input JSON dari body request
$input = json_decode(file_get_contents("php://input"), true);

// Validasi input JSON
if (!$input) {
    jsonResponse('error', 'Invalid JSON input');
}

$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');
$full_name = trim($input['full_name'] ?? '');
$phone = trim($input['phone'] ?? '');

// Validasi field wajib
if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
    jsonResponse('error', 'Field username, email, password, dan full_name wajib diisi');
}

// Cek apakah email atau username sudah digunakan
$checkQuery = "SELECT id FROM users WHERE email = ? OR username = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param('ss', $email, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    jsonResponse('error', 'Email atau username sudah digunakan');
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Masukkan data ke database
$insertQuery = "INSERT INTO users (username, email, password, full_name, phone, user_type) VALUES (?, ?, ?, ?, ?, 'user')";
$stmt = $conn->prepare($insertQuery);
$stmt->bind_param('sssss', $username, $email, $hashedPassword, $full_name, $phone);

if ($stmt->execute()) {
    $user_id = $conn->insert_id;

    jsonResponse('success', 'Registrasi berhasil', [
        'id' => $user_id,
        'username' => $username,
        'email' => $email,
        'full_name' => $full_name,
        'phone' => $phone,
        'user_type' => 'user'
    ]);
} else {
    jsonResponse('error', 'Terjadi kesalahan saat registrasi');
}
?>
