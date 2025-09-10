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

  <!-- Navbar + Hero (ambil dari file index.html lama) -->
  <nav id="navbar" class="navbar navbar-expand-lg bg-white border-bottom py-3 fixed-top">
    <div class="container">
      <a class="navbar-brand fw-bold d-flex align-items-center" href="#home">
        <span class="brand-circle"></span> KostHub
      </a>
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav ms-3">
          <li class="nav-item"><a href="#explore" class="nav-link">Explore</a></li>
          <li class="nav-item"><a href="#" class="nav-link">List your kost</a></li>
          <li class="nav-item"><a href="#" class="nav-link">Help</a></li>
        </ul>
        <div class="d-flex ms-auto">
          <a href="#" class="btn btn-light me-2">Log in</a>
          <a href="#" class="btn btn-success">Sign up</a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section id="home" class="hero pt-5" style="margin-top:70px;">
    <div class="container text-center">
      <h1>
        Find your next <span class="accent">kost</span> with ease.
      </h1>
      <p class="lead">Your one-stop platform for discovering the perfect student housing.</p>
      <div class="search-box">
        <div class="input-group input-group-lg rounded-pill shadow-sm overflow-hidden">
          <span class="input-group-text bg-white border-0"><i class="bi bi-search"></i></span>
          <input type="text" class="form-control border-0" placeholder="Search by city, university, or area...">
        </div>
      </div>
    </div>
  </section>

  <!-- Explore Section (dipanggil dari file explore.html) -->
  <?php include 'explore.html'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
