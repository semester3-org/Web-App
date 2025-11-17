<?php
session_start();

// Jika sudah login, arahkan ke halaman sesuai role
if (isset($_SESSION['user_id'])) {
  switch ($_SESSION['user_type']) {
    case 'superadmin':
      header("Location: /Web-App/frontend/admin/pages/dashboard.php");
      exit;
    case 'admin':
      header("Location: /Web-App/frontend/admin/pages/dashboard.php");
      exit;
    case 'owner':
      header("Location: /Web-App/frontend/user/owner/pages/dashboard.php");
      exit;
    case 'customer':
      // Jika customer, langsung tampilkan halaman home customer
      include_once(__DIR__ . "/frontend/user/customer/home.php");
      exit;
  }
}

// Jika belum login, tetap tampilkan tampilan home customer (read-only)
include_once(__DIR__ . "/frontend/user/customer/home.php");
exit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<!-- Gunakan navbar customer -->
<?php include __DIR__ . '/frontend/user/customer/navbar.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
