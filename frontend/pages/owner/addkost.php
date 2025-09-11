<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Kost - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <!-- Navbar -->
  <?php include 'navbar.html'; ?>

  <div class="container my-5 pt-5">
    <h2 class="fw-bold mb-4">List your boarding house</h2>
    <p class="text-muted">Fill in the details below to get started.</p>

    <form action="process_addkost.php" method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded shadow-sm">

      <!-- Boarding House Details -->
      <h5 class="fw-bold mb-3">Boarding House Details</h5>
      <div class="mb-3">
        <label class="form-label">Address</label>
        <input type="text" name="address" class="form-control" placeholder="e.g., Jl. Jend. Sudirman No. 123">
      </div>
      <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3" placeholder="Tell about your amazing boarding house..."></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Boarding House Photos</label>
        <input type="file" name="house_photo" class="form-control">
      </div>

      <!-- Room Details -->
      <h5 class="fw-bold mt-4 mb-3">Room Details</h5>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Room Type</label>
          <input type="text" name="room_type" class="form-control" placeholder="e.g., Shared Room">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Room Size</label>
          <input type="text" name="room_size" class="form-control" placeholder="e.g., 3x4 meters">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Room Description</label>
        <textarea name="room_description" class="form-control" rows="3" placeholder="Describe the room's features and amenities."></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Room Photos</label>
        <input type="file" name="room_photo" class="form-control">
      </div>

   <!-- Facilities -->
<h5 class="fw-bold mt-4 mb-3">Facilities</h5>
<div id="facility-container" class="mb-3">
  <div class="d-flex align-items-center mb-2">
    <button type="button" class="btn btn-outline-success btn-sm" id="add-facility-btn">
      <i class="bi bi-plus-lg"></i> Add Facility
    </button>
  </div>
  <!-- Daftar fasilitas terpilih -->
  <div id="selected-facilities" class="d-flex flex-wrap gap-2"></div>
</div>



      <!-- Availability & Pricing -->
      <h5 class="fw-bold mt-4 mb-3">Availability & Pricing</h5>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Number of Rooms Available</label>
          <input type="number" name="num_rooms" class="form-control" placeholder="e.g., 5">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Price per Month (IDR)</label>
          <input type="text" name="price" class="form-control" placeholder="Rp 1.500.000">
        </div>
      </div>

      <!-- Submit -->
      <div class="text-end">
        <button type="submit" class="btn btn-success">Submit Listing</button>
      </div>

    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/facilities.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
