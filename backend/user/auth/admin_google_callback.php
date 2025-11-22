<?php
// backend/user/auth/admin_google_callback.php
// KHUSUS UNTUK ADMIN — otomatis tolak yang bukan admin
session_start();
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/db.php';

$clientID     = '577682748223-5mp95vu1rr79v8dmode5hb4u43n4pj35.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri  = 'http://localhost/Web-App/backend/user/auth/admin_google_callback.php'; // URI BARU

$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope(['email', 'profile']);

try {
    if (!isset($_GET['code'])) throw new Exception("Kode tidak diterima");

    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) throw new Exception("Token error");

    $client->setAccessToken($token['access_token']);
    $googleService = new Google_Service_Oauth2($client);
    $googleUser = $googleService->userinfo->get();

    $email = $googleUser->email ?? null;
    if (!$email) throw new Exception("Email tidak ditemukan");

    // HARUS ADMIN!
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ? AND user_type = 'admin' LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_type'] = 'admin';

        header("Location: ../../../frontend/admin/pages/dashboard.php");
        exit;
    } else {
        // BUKAN ADMIN → tolak!
        header("Location: ../../../frontend/auth/login.php?error=" . urlencode("Akses ditolak. Gunakan akun admin untuk login dari link ini."));
        exit;
    }

} catch (Exception $e) {
    header("Location: ../../../frontend/auth/login.php?error=" . urlencode("Login Admin Gagal"));
    exit;
}