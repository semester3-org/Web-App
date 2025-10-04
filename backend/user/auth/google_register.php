<?php
// backend/user/auth/google_register.php
session_start();

// Autoload composer
require_once __DIR__ . '/../../../vendor/autoload.php';

// === Konfigurasi OAuth Google ===
$clientID     = '577682748223-5mp95vu1rr79v8dmode5hb4u43n4pj35.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri  = 'http://localhost/Web-App/backend/user/auth/google_register_callback.php';

// Inisialisasi client Google
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope(['email', 'profile']);
$client->setAccessType('offline');
$client->setPrompt('select_account consent');

// Redirect user ke halaman login Google
$authUrl = $client->createAuthUrl();
header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
exit;
