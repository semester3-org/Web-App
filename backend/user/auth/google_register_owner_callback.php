<?php
session_start();
require_once("../../config/db.php");
require_once __DIR__ . '/../../../vendor/autoload.php';

// Konfigurasi Google
$clientID     = '577682748223-5mp95vu1rr79v8dmode5hb4u43n4pj35.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri  = 'http://localhost/Web-App/backend/user/auth/google_register_owner_callback.php';

$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope(['email', 'profile']);

try {
    if (!isset($_GET['code'])) {
        throw new Exception("Kode autentikasi tidak ditemukan.");
    }

    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        throw new Exception("Gagal mengambil token dari Google.");
    }

    $client->setAccessToken($token['access_token']);
    $google_service = new Google_Service_Oauth2($client);
    $google_info = $google_service->userinfo->get();

    $email = $google_info->email ?? null;
    $name  = $google_info->name ?? '';
    $profile_picture = $google_info->picture ?? null;

    if (!$email) {
        throw new Exception("Email tidak ditemukan di profil Google.");
    }

    // === Cek apakah user sudah ada ===
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User sudah ada, redirect ke login dengan pesan info
        header("Location: ../../../frontend/auth/login.php?message=Akun sudah terdaftar, silakan login");
        exit;
    } else {
        // Buat akun baru owner
        $username  = explode('@', $email)[0];
        $user_type = 'owner';

        $insert = $conn->prepare("INSERT INTO users (username, email, password, full_name, profile_picture, user_type, created_at) VALUES (?, ?, '', ?, ?, ?, NOW())");
        $insert->bind_param("sssss", $username, $email, $name, $profile_picture, $user_type);
        $insert->execute();

        // Redirect ke login page
        header("Location: ../../../frontend/auth/login.php?success=Registrasi berhasil, silakan login dengan Google");
        exit;
    }
} catch (Exception $e) {
    header("Location: ../../../frontend/auth/register_owner.php?error=Google register gagal: " . urlencode($e->getMessage()));
    exit;
}
