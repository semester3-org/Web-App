<?php
/**
 * ============================================
 * API GET PROFILE (MOBILE)
 * File: mobile/api/user/profile.php
 * ============================================
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Content-Type: application/json');

require_once '../../config/db.php';
require_once '../../utils/response.php';

// Ambil input JSON
$input = json_decode(file_get_contents("php://input"), true);

// Validasi JSON
if (!$input) {
    jsonResponse('error', 'Invalid JSON input');
}

$user_id = intval($input['user_id'] ?? 0);

// Validasi field wajib
if (empty($user_id)) {
    jsonResponse('error', 'Field user_id wajib diisi');
}

// Query ambil data user
$query = "SELECT id, username, email, full_name, phone, user_type, profile_picture, created_at, updated_at 
          FROM users 
          WHERE id = ? AND user_type = 'user' 
          LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Cek apakah user ditemukan
if ($result->num_rows === 0) {
    jsonResponse('error', 'User tidak ditemukan');
}

$user = $result->fetch_assoc();

// Kirim response sukses
jsonResponse('success', 'Data profil berhasil diambil', [
    'user' => $user
]);
?>
