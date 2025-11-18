<?php
// backend/user/auth/admin_google_login.php
// Dipanggil dari tombol "Login sebagai Admin"
session_start();
require_once __DIR__ . '/../../../vendor/autoload.php';

$clientID     = '577682748223-5mp95vu1rr79v8dmode5hb4u43n4pj35.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri  = 'http://localhost/Web-App/backend/user/auth/admin_google_callback.php';

$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope(['email', 'profile']);
$client->setPrompt('select_account consent');

$authUrl = $client->createAuthUrl();
header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
exit;