<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Your Property - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/add_property.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<div class="container mt-4">
  <h4 class="fw-bold mb-4"><i class="bi bi-buildings me-2"></i>Your Own Properties</h4>

  <div class="row g-4">
    <!-- Property 1 - Status Aktif -->
    <div class="col-md-3">
      <a href="detail_property.php" class="text-decoration-none">
        <div class="card shadow-sm rounded-3">
          <img src="../assets/kos1.jpg" class="card-img-top" alt="Kos">
          <div class="card-body">
            <div class="d-flex align-items-center mb-1">
              <small class="text-muted me-2">Jember</small>
              <span class="badge bg-success">Aktif</span>
            </div>
            <p class="mb-1">Kos The Raid</p>
            <p class="fw-bold">Rp 400.000 <small class="text-muted">/ bulan</small></p>
          </div>
        </div>
      </a>
    </div>

    <!-- Property 2 - Status Nonaktif -->
    <div class="col-md-3">
      <a href="detail_property.php" class="text-decoration-none">
        <div class="card shadow-sm rounded-3">
          <img src="../assets/kos2.jpg" class="card-img-top" alt="Kos">
          <div class="card-body">
            <div class="d-flex align-items-center mb-1">
              <small class="text-muted me-2">Jember</small>
              <span class="badge bg-danger">Nonaktif</span>
            </div>
            <p class="mb-1">Kos Sakura</p>
            <p class="fw-bold">Rp 600.000 <small class="text-muted">/ bulan</small></p>
          </div>
        </div>
      </a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
