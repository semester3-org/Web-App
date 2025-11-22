<?php
// backend/admin/auth/google_login.php
session_start();
require_once __DIR__ . '/../../../vendor/autoload.php';

// Ganti nanti kalau kamu buat OAuth client terpisah untuk admin
$clientID     = '577682748223-5mp95vu1rr79v8dmode5hb4u43n4pj35.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri  = 'http://localhost/Web-App/backend/admin/auth/google_callback.php'; // ← INI YANG BERBEDA!

$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope(['email', 'profile']);
$client->setAccessType('offline');
$client->setPrompt('select_account consent'); // memaksa user pilih akun (penting untuk admin)

$authUrl = $client->createAuthUrl();
header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
exit;