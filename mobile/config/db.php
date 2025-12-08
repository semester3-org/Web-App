<?php
$host = "localhost";
$user = "koshubmy_koshub";
$pass = "KoshubJaya123";
$dbname = "koshubmy_koshub"; 

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode([
        "status" => "error",
        "message" => "Koneksi database gagal: " . $conn->connect_error
    ]));
}
?>====