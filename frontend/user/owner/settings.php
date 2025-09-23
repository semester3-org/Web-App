<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>My Profile - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .profile-card {
      max-width: 600px;
      margin: auto;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 2rem;
      background: #fff;
    }
    .profile-card .avatar {
      font-size: 5rem;
      color: #6c757d;
    }
    .profile-card .form-control {
      border-radius: 8px;
    }
    .profile-card .btn-success {
      border-radius: 20px;
      width: 100%;
    }
  </style>
</head>
<body class="bg-light">

<?php include("navbar.php"); ?>

<div class="container my-5">
  <div class="d-flex align-items-center mb-3">
    <h4 class="m-0"><i class="bi bi-person-circle me-2"></i>My Profile</h4>
  </div>

  <div class="profile-card shadow-sm">
    <div class="text-center mb-4">
      <i class="bi bi-person-circle avatar"></i>
      <h5 class="mt-2">iqbaaltg</h5>
      <p class="text-muted">riqbal.maulana.ibrahim@gmail.com</p>
    </div>

    <form>
      <div class="mb-3">
        <label class="form-label fw-semibold">Nama</label>
        <input type="text" class="form-control" value="R. Iqbal Maulana Ibrahim">
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Username</label>
        <input type="text" class="form-control" value="iqbaaltg">
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" class="form-control" value="riqbal.maulana.ibrahim@gmail.com">
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Nomor Handphone</label>
        <input type="text" class="form-control" value="0812-9071-3388">
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Role</label>
        <input type="text" class="form-control" value="Customer" readonly>
      </div>

      <button type="submit" class="btn btn-success">Save</button>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
