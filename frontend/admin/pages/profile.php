<?php
require_once __DIR__ . "/../auth/auth_admin.php";

// Ambil data user dari session
$full_name = $_SESSION['full_name'];
$username  = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">Profil Admin</div>
        <div class="card-body">
            <p><strong>Nama Lengkap:</strong> <?= htmlspecialchars($full_name) ?></p>
            <p><strong>Username:</strong> <?= htmlspecialchars($username) ?></p>
            
            <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</div>

</body>
</html>
