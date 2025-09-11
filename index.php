<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <style>
    html { scroll-behavior: smooth; }
    .navbar-nav .nav-link.active {
      font-weight: 700;
      color: #000 !important;
    }
  </style>
</head>
<body data-bs-spy="scroll" data-bs-target="#navbar" data-bs-offset="80" tabindex="0">

  <!-- Navbar -->
  <?php include 'frontend/pages/dashboard/navbar.html'; ?>

  <!-- Hero -->
  <?php include 'frontend/pages/dashboard/hero.html'; ?>

  <!-- Explore -->
  <?php include 'frontend/pages/dashboard/explore.html'; ?>

  <!-- Favorite -->
  <?php include 'frontend/pages/dashboard/favorite.html'; ?>

  <!-- List Your Kost -->
  <?php include 'frontend/pages/dashboard/listkost.html'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
