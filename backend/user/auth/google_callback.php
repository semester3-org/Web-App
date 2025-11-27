<?php
// backend/user/auth/google_callback.php
session_start();
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/db.php';

$clientID     = '577682748223-5mp95vu1rr79v8dmode5hb4u43n4pj35.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri  = 'http://localhost/Web-App/backend/user/auth/google_callback.php';

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
        throw new Exception("Gagal mengambil token: " . ($token['error_description'] ?? 'Unknown'));
    }

    $client->setAccessToken($token['access_token']);
    $oauth2 = new Google_Service_Oauth2($client);
    $googleUser = $oauth2->userinfo->get();

    $email   = $googleUser->email ?? null;
    $name    = $googleUser->name ?? '';
    $picture = $googleUser->picture ?? null;

    if (!$email) {
        throw new Exception("Email tidak ditemukan dari Google.");
    }

    // Cek apakah email ini sudah terdaftar di database
    $stmt = $conn->prepare("SELECT id, username, user_type FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // USER SUDAH ADA → LOGIN LANGSUNG SESUAI ROLE
        $user = $result->fetch_assoc();

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_type'] = $user['user_type'];

        // Redirect sesuai role
        switch ($user['user_type']) {
            case 'superadmin':
            case 'admin':
                header("Location: ../../../frontend/admin/pages/dashboard.php");
                break;
            case 'owner':
                header("Location: ../../../frontend/user/owner/pages/dashboard.php");
                break;
            case 'customer':
            default:
                header("Location: ../../../frontend/user/customer/home.php");
                break;
        }
        exit;

    } else {
        // USER BELUM TERDAFTAR → kembali ke login dengan notifikasi
        $stmt->close();
        $conn->close();
        
        $_SESSION['error_notif'] = 'Akun Google Anda belum terdaftar. Silakan daftarkan akun terlebih dahulu.';
        
        header("Location: ../../../frontend/auth/login.php");
        exit;
    }

} catch (Exception $e) {
    $_SESSION['error_notif'] = "Login dengan Google gagal: " . $e->getMessage();
    header("Location: ../../../frontend/auth/login.php");
    exit;
}