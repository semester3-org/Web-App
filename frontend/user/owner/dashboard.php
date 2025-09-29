<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Dashboard Admin - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include "navbar.php"; ?>

<div class="container mt-4">
  <h3 class="fw-bold mb-3">Dashboard Admin</h3>
  <p>Selamat datang di halaman <b>Admin</b>! (Frontend demo mode, tidak perlu login)</p>

<div class="card card-square shadow-sm p-4">
  <h5 class="fw-bold mb-3">Add Your Property</h5>
  <a href="add_property.php" class="btn btn-outline-success w-100 py-3">
    <i class="bi bi-plus-circle me-2"></i> Add Property
  </a>
</div>



</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
