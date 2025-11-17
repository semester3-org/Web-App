<?php
// backend/user/auth/google_register_callback.php
session_start();
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/db.php';

$clientID     = '577682748223-5mp95vu1rr79v8dmode5hb4u43n4pj35.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri  = 'http://localhost/Web-App/backend/user/auth/google_register_callback.php';

$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope(['email', 'profile']);
$client->setAccessType('offline');
$client->setPrompt('select_account consent');

$logFile = __DIR__ . '/google_register_log.txt';
function gLog($msg) {
    global $logFile;
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] $msg\n", FILE_APPEND);
}

try {
    if (!isset($_GET['code']) || empty($_GET['code'])) {
        gLog("Code tidak diterima: " . json_encode($_GET));
        throw new Exception("Kode autentikasi tidak ditemukan.");
    }

    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) throw new Exception("Gagal mengambil token dari Google.");

    $client->setAccessToken($token['access_token']);
    $google_service = new Google_Service_Oauth2($client);
    $google_info = $google_service->userinfo->get();

    $email = $google_info->email ?? null;
    $name = $google_info->name ?? '';
    $profile_picture = $google_info->picture ?? null;

    if (!$email) throw new Exception("Email tidak ditemukan di profil Google.");

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        gLog("User sudah terdaftar: $email");
        header("Location: ../../../frontend/auth/login.php?message=Account%20already%20exists");
        exit;
    }

    // === MULAI TRANSAKSI ===
    $conn->begin_transaction();

    try {
        $username = explode('@', $email)[0];
        $user_type = 'user';

        $insert = $conn->prepare("INSERT INTO users (username, email, password, full_name, profile_picture, user_type) VALUES (?, ?, '', ?, ?, ?)");
        $insert->bind_param("sssss", $username, $email, $name, $profile_picture, $user_type);
        $insert->execute();

        // COMMIT WAJIB!
        $conn->commit();

        gLog("Akun baru dibuat: $email");
        header("Location: ../../../frontend/auth/login.php?success=Registration%20successful");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        gLog("Insert gagal: " . $e->getMessage());
        throw $e;
    }

} catch (Exception $e) {
    gLog("Exception: " . $e->getMessage());
    header("Location: ../../../frontend/auth/register_customer.php?error=Google%20register%20failed");
    exit;
}