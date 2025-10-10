<?php
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
        die("Code tidak diterima.");
    }

    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) throw new Exception("Gagal mengambil token dari Google");

    $client->setAccessToken($token['access_token']);
    $googleService = new Google_Service_Oauth2($client);
    $googleUser = $googleService->userinfo->get();

    $email = $googleUser->email ?? null;
    $name  = $googleUser->name ?? '';
    $picture = $googleUser->picture ?? null;

    if (!$email) throw new Exception("Email tidak ditemukan di profil Google.");

    // Cek user di DB
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_type'] = $user['user_type'];

        // Redirect otomatis sesuai role
        switch($user['user_type']){
            case 'owner':
                header("Location: ../../../frontend/user/owner/pages/dashboard.php");
                break;
            case 'customer':
                header("Location: ../../../frontend/user/customer/home.php");
                break;
            case 'admin':
                header("Location: ../../../frontend/admin/dashboard.php");
                break;
            default:
                header("Location: ../../../frontend/auth/login.php");
                break;
        }
        exit;
    } else {
        // Jika belum terdaftar, redirect ke register page
        header("Location: ../../../frontend/auth/register_google.php?email=$email&name=$name&picture=$picture");
        exit;
    }

} catch(Exception $e) {
    header("Location: ../../../frontend/auth/login.php?error=Google%20login%20failed");
    exit;
}
