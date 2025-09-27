<?php
session_start();
require_once '../../config/db.php';

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle POST request (update profile)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);

    $username   = trim($input['username'] ?? '');
    $email      = trim($input['email'] ?? '');
    $full_name  = trim($input['full_name'] ?? '');
    $phone      = trim($input['phone'] ?? '');

    $errors = [];

    // Validasi Username
    if ($username === '') {
        $errors[] = "Username wajib diisi";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username minimal 3 karakter";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username hanya boleh huruf, angka, underscore";
    } else {
        // Check duplicate username
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $errors[] = "Username sudah dipakai";
        }
        $stmt->close();
    }

    // Validasi Email
    if ($email === '') {
        $errors[] = "Email wajib diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    } else {
        // Additional check for common email domains
        $validDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com', 'mail.com', 'aol.com', 'protonmail.com'];
        $emailDomain = substr(strrchr($email, "@"), 1);
        
        // Allow any valid email format, not just specific domains
        if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            $errors[] = "Format email tidak valid";
        } else {
            // Check duplicate email
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                $errors[] = "Email sudah dipakai";
            }
            $stmt->close();
        }
    }

    // Validasi Nama
    if ($full_name === '') {
        $errors[] = "Nama lengkap wajib diisi";
    } elseif (strlen($full_name) < 2) {
        $errors[] = "Nama minimal 2 karakter";
    } elseif (strlen($full_name) > 100) {
        $errors[] = "Nama maksimal 100 karakter";
    }

    // Validasi Phone
    if ($phone !== '') {
        // Remove all non-numeric characters
        $phone_clean = preg_replace('/[^0-9]/', '', $phone);
        
        if ($phone_clean === '') {
            $errors[] = "Nomor telepon hanya boleh berisi angka";
        } elseif (strlen($phone_clean) < 10) {
            $errors[] = "Nomor telepon minimal 10 digit";
        } elseif (strlen($phone_clean) > 15) {
            $errors[] = "Nomor telepon maksimal 15 digit";
        } else {
            $phone = $phone_clean;
        }
    } else {
        $phone = null;
    }

    // Kalau ada error validasi
    if (!empty($errors)) {
        echo json_encode([
            "success" => false, 
            "message" => implode(", ", $errors),
            "errors" => $errors
        ]);
        exit;
    }

    // Begin transaction for data integrity
    $conn->begin_transaction();
    
    try {
        // Update ke DB
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, full_name=?, phone=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("ssssi", $username, $email, $full_name, $phone, $user_id);
        
        if ($stmt->execute()) {
            // Update session data
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['full_name'] = $full_name;
            
            // Commit transaction
            $conn->commit();
            
            echo json_encode([
                "success" => true, 
                "message" => "Profile berhasil diperbarui",
                "data" => [
                    "username" => $username,
                    "email" => $email,
                    "full_name" => $full_name,
                    "phone" => $phone
                ]
            ]);
        } else {
            throw new Exception("Gagal update data");
        }
        
        $stmt->close();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        echo json_encode([
            "success" => false, 
            "message" => "Gagal update: " . $e->getMessage()
        ]);
    }
    
    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
}