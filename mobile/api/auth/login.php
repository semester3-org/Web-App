<?php
/**
 * ============================================
 * API LOGIN (MOBILE)
 * File: mobile/api/auth/login.php
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
$password = trim($input['password'] ?? '');

// Validasi field wajib
if (empty($username) || empty($password)) {
    jsonResponse('error', 'Field username dan password wajib diisi');
}

// Ambil user berdasarkan username (khusus user_type = 'user')
$query = "SELECT id, username, email, password, full_name, phone, user_type, profile_picture 
          FROM users 
          WHERE username = ? AND user_type = 'user' 
          LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    jsonResponse('error', 'Akun tidak ditemukan');
}

$user = $result->fetch_assoc();

// Verifikasi password
if (!password_verify($password, $user['password'])) {
    jsonResponse('error', 'Password salah');
}

// (Opsional) Hapus field password sebelum dikirim ke client
unset($user['password']);

// Kirim respon sukses
jsonResponse('success', 'Login berhasil', [
    'user' => $user
]);
?>
