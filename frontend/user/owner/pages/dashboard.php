<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Dashboard Owner - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="../../css/style.css?v=<?php echo time(); ?>">
</head>

<body>

  <?php include "navbar.php"; ?>

  <div class="container mt-4">
    <h3 class="fw-bold mb-3">Dashboard Owner</h3>

    <div class="card card-square shadow-sm p-4">
      <h5 class="fw-bold mb-3">Add Your Property</h5>
      <a href="add_property.php" class="btn btn-outline-success w-100 py-3">
        <i class="bi bi-plus-circle me-2"></i> Add Property
      </a>
    </div>

  </div>

  <!-- Load Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Load SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Toast Notification -->
  <?php if (isset($_SESSION['success'])): ?>
    <!-- Toast Notification -->
    <script>
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer);
          toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
      });
    </script>

    <?php if (isset($_SESSION['success'])): ?>
      <script>
        Toast.fire({
          icon: 'success',
          title: '<?php echo addslashes($_SESSION['success']); ?>'
        });
      </script>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
      <script>
        Toast.fire({
          icon: 'error',
          title: '<?php echo addslashes($_SESSION['error']); ?>'
        });
      </script>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

</body>

</html>