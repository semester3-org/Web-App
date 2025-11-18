<?php
// backend/admin/auth/google_callback.php
session_start();
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/db.php';

// ==== GANTI DENGAN CLIENT ID & SECRET KHUSUS ADMIN (REKOMENDASI) ====
// Kalau kamu ingin benar-benar memisahkan OAuth app untuk admin, buat project baru di Google Cloud.
// Untuk sementara bisa pakai yang sama, tapi pastikan redirect URI sudah ditambahkan di Google Console:
// http://localhost/Web-App/backend/admin/auth/google_callback.php

$clientID     = '577682748223-5mp95vu1rr79v8dmode5hb4u43n4pj35.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri  = 'http://localhost/Web-App/backend/admin/auth/google_callback.php'; // <--- BEDANYA DI SINI

$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope(['email', 'profile']);

try {
    if (!isset($_GET['code'])) {
        throw new Exception("Authorization code tidak diterima.");
    }

    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        throw new Exception("Gagal mengambil access token: " . $token['error_description'] ?? $token['error']);
    }

    $client->setAccessToken($token['access_token']);

    $googleService = new Google_Service_Oauth2($client);
    $googleUser    = $gmailService->userinfo->get();

    $email   = $googleUser->email ?? null;
    $name    = $googleUser->name ?? '';
    $picture = $googleUser->picture ?? null;

    if (!$email) {
        throw new Exception("Email tidak ditemukan dari akun Google.");
    }

    // Cek apakah email ini adalah admin
    $stmt = $conn->prepare("SELECT id, username, user_type FROM users WHERE email = ? AND user_type = 'admin' LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Login berhasil → set session
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_type'] = $user['user_type'];

        // Langsung ke dashboard admin
        header("Location: ../../../frontend/admin/pages/dashboard.php");        exit;
    } else {
        // Bukan admin → tolak akses
        // (bisa user lain atau belum terdaftar sama sekali)
        $error = urlencode("Akses ditolak. Hanya akun admin yang dapat login melalui link ini.");
        header("Location: ../../../frontend/auth/login.php?error=$error");
        exit;
    }

} catch (Exception $e) {
    $errorMsg = urlencode("Google Login Gagal: " . $e->getMessage());
    header("Location: ../../../frontend/auth/login.php?error=$errorMsg");
    exit;
}