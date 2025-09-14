<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'] === 'owner' ? 'owner' : 'user';

    if (!$email || !$password || !$name) {
        echo json_encode(['error' => 'Email, password, and name are required']);
        exit;
    }

    // Cek email sudah terdaftar
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Email sudah terdaftar']);
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, name, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$email, $password_hash, $role, $name, $phone]);

    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['role'] = $role;
    $_SESSION['name'] = $name;

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Invalid request method']);
}