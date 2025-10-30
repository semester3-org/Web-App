<?php
/**
 * ============================================
 * API UPDATE PROFILE (MOBILE)
 * File: mobile/api/user/update_profile.php
 * ============================================
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../../config/db.php';
require_once '../../utils/response.php';

// Pastikan method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse('error', 'Invalid request method');
}

// Cek apakah multipart (karena ada upload foto)
if (isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
} else {
    jsonResponse('error', 'Data form tidak lengkap');
}

// Validasi dasar
if (empty($user_id) || empty($username) || empty($full_name) || empty($email)) {
    jsonResponse('error', 'Field wajib tidak boleh kosong');
}

// Ambil data user lama
$query = "SELECT profile_picture FROM users WHERE id = ? AND user_type = 'user' LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    jsonResponse('error', 'User tidak ditemukan');
}

$user = $result->fetch_assoc();
$old_picture = $user['profile_picture'];

// Upload foto jika ada
$upload_dir = '../../../uploads/profiles/';
$profile_picture = $old_picture;

if (!empty($_FILES['profile_picture']['name'])) {
    $file_name = time() . '_' . basename($_FILES['profile_picture']['name']);
    $target_file = $upload_dir . $file_name;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validasi file
    $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($file_type, $allowed_types)) {
        jsonResponse('error', 'Format gambar tidak diizinkan');
    }

    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
        // Hapus foto lama jika ada
        if (!empty($old_picture) && file_exists($upload_dir . $old_picture)) {
            unlink($upload_dir . $old_picture);
        }
        $profile_picture = $file_name;
    } else {
        jsonResponse('error', 'Gagal mengunggah foto');
    }
}

// Update data user
$update = "UPDATE users 
           SET username = ?, full_name = ?, email = ?, phone = ?, profile_picture = ?, updated_at = NOW() 
           WHERE id = ? AND user_type = 'user'";

$stmt = $conn->prepare($update);
$stmt->bind_param('sssssi', $username, $full_name, $email, $phone, $profile_picture, $user_id);

if ($stmt->execute()) {
    jsonResponse('success', 'Profil berhasil diperbarui', [
        'profile_picture' => $profile_picture
    ]);
} else {
    jsonResponse('error', 'Gagal memperbarui profil');
}
?>
