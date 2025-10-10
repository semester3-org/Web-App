<?php
// ...existing code...
session_start();
require_once __DIR__ . '/../../../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$username  = trim($input['username']  ?? '');
$email     = trim($input['email']     ?? '');
$full_name = trim($input['full_name'] ?? '');
$phone     = trim($input['phone']     ?? '');

$errors = [];
$user_id = $_SESSION['user_id'];

// Username validation
if ($username === '') {
    $errors[] = 'Username wajib diisi';
} elseif (strlen($username) < 3) {
    $errors[] = 'Username minimal 3 karakter';
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = 'Username hanya boleh huruf, angka, underscore';
} else {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param('si', $username, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $errors[] = 'Username sudah dipakai';
    }
    $stmt->close();
}

// Email validation
if ($email === '') {
    $errors[] = 'Email wajib diisi';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Format email tidak valid';
} else {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param('si', $email, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $errors[] = 'Email sudah dipakai';
    }
    $stmt->close();
}

// Full name validation
if ($full_name === '') {
    $errors[] = 'Nama lengkap wajib diisi';
} elseif (mb_strlen($full_name) < 2) {
    $errors[] = 'Nama minimal 2 karakter';
} elseif (mb_strlen($full_name) > 100) {
    $errors[] = 'Nama maksimal 100 karakter';
}

// Phone validation (optional)
if ($phone !== '') {
    $phone_clean = preg_replace('/[^0-9]/', '', $phone);
    if ($phone_clean === '') {
        $errors[] = 'Nomor telepon hanya boleh berisi angka';
    } elseif (strlen($phone_clean) < 10) {
        $errors[] = 'Nomor telepon minimal 10 digit';
    } elseif (strlen($phone_clean) > 15) {
        $errors[] = 'Nomor telepon maksimal 15 digit';
    } else {
        $phone = $phone_clean;
    }
} else {
    $phone = null;
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors), 'errors' => $errors]);
    $conn->close();
    exit;
}

// Update DB
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, phone = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('ssssi', $username, $email, $full_name, $phone, $user_id);

    if (!$stmt->execute()) {
        throw new Exception('Gagal update: ' . $stmt->error);
    }

    $stmt->close();
    $conn->commit();

    // Update session values if digunakan
    $_SESSION['username']   = $username;
    $_SESSION['email']      = $email;
    $_SESSION['full_name']  = $full_name;
    $_SESSION['phone']      = $phone;

    echo json_encode([
        'success' => true,
        'message' => 'Profile berhasil diperbarui',
        'data' => [
            'username' => $username,
            'email' => $email,
            'full_name' => $full_name,
            'phone' => $phone
        ]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
exit;