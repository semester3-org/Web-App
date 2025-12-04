<?php
header('Content-Type: application/json');

require_once '../../config/db.php';
require_once '../../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse('error', 'Invalid method');
}

$user_id = intval($_POST['user_id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if ($user_id == 0 || $username == '' || $full_name == '' || $email == '') {
    jsonResponse('error', 'Field wajib tidak boleh kosong');
}

// cek user
$stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    jsonResponse('error', 'User tidak ditemukan');
}

$data = $res->fetch_assoc();
$old_pic = $data['profile_picture'];

$upload_dir = "../../../uploads/profiles/";
$profile_picture = $old_pic;

// upload foto
if (!empty($_FILES['profile_picture']['name'])) {

    $file_name = time() . "_" . basename($_FILES['profile_picture']['name']);
    $target = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target)) {
        if ($old_pic && file_exists($upload_dir . $old_pic)) {
            unlink($upload_dir . $old_pic);
        }
        $profile_picture = $file_name;
    }
}

$update = $conn->prepare("
    UPDATE users SET 
    username=?, full_name=?, email=?, phone=?, profile_picture=?, updated_at=NOW()
    WHERE id=?
");
$update->bind_param("sssssi", $username, $full_name, $email, $phone, $profile_picture, $user_id);

if ($update->execute()) {
    jsonResponse("success", "Profil berhasil diperbarui", [
        "profile_picture" => $profile_picture
    ]);
} else {
    jsonResponse("error", "Gagal memperbarui profil");
}
?>
