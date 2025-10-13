<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$endpoint = $_GET['endpoint'] ?? '';
$params = $_SERVER['QUERY_STRING'] ?? '';

if (!$endpoint) {
    echo json_encode(['error' => 'Missing endpoint']);
    exit;
}

$baseUrl = "https://nominatim.openstreetmap.org/" . $endpoint . "?";
$queryString = str_replace("endpoint=$endpoint&", "", $params);

$url = $baseUrl . $queryString;

// Set custom user-agent (wajib untuk Nominatim)
$opts = [
    'http' => [
        'header' => "User-Agent: KostHub App\r\n"
    ]
];
$context = stream_context_create($opts);

$response = @file_get_contents($url, false, $context);
if ($response === FALSE) {
    echo json_encode(['error' => 'Failed to fetch from Nominatim']);
    exit;
}

echo $response;
