<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KostHub - Detail Kost</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <style>
    body { background-color: #f8f9fa; }
    .facility-icon { font-size: 1.5rem; margin-right: 10px; color: #28a745; }
    .review-card {
      background: #fff; border-radius: 10px; padding: 1.5rem;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05); margin-bottom: 1rem;
    }
  </style>
</head>
<body>
  <?php include '../dashboard/navbar.html'; ?>

  <div class="container py-5" style="margin-top:80px;">
    <div class="row">
      <!-- Gambar & Info Kost -->
      <div class="col-lg-8">
        <div class="row g-3">
          <div class="col-md-6">
            <img src="https://via.placeholder.com/500x350" class="img-fluid rounded" alt="Gambar Kost">
          </div>
          <div class="col-md-6">
            <img src="https://via.placeholder.com/500x350" class="img-fluid rounded" alt="Gambar Kost">
          </div>
        </div>

        <!-- Fasilitas -->
        <div class="mt-4 p-4 bg-white rounded shadow-sm">
          <h5 class="fw-bold mb-3">Facilities</h5>
          <div class="row">
            <div class="col-md-4"><i class="bi bi-wifi facility-icon"></i> High-Speed Wi-Fi</div>
            <div class="col-md-4"><i class="bi bi-tv facility-icon"></i> Smart TV</div>
            <div class="col-md-4"><i class="bi bi-snow facility-icon"></i> Air Conditioning</div>
            <div class="col-md-4"><i class="bi bi-p-circle facility-icon"></i> Free Parking</div>
            <div class="col-md-4"><i class="bi bi-droplet facility-icon"></i> Swimming Pool</div>
            <div class="col-md-4"><i class="bi bi-heart-pulse facility-icon"></i> Fitness Center</div>
          </div>
        </div>

        <!-- Tentang Kost -->
        <div class="mt-4 p-4 bg-white rounded shadow-sm">
          <h5 class="fw-bold mb-3">About The Haven</h5>
          <p class="text-muted">
            The Haven Boarding House offers a comfortable and modern living experience in the heart of Anytown.
            Featuring light blue and white color schemes to create a serene atmosphere. 
            Enjoy access to a range of facilities, including high-speed Wi-Fi, Smart TV, AC, free parking, swimming pool, and fitness center.  
            Conveniently located near local attractions and public transportation, making it an ideal choice for both short-term and long-term stays.
          </p>
        </div>

        <!-- Ratings & Reviews -->
        <div class="mt-4 p-4 bg-white rounded shadow-sm">
          <h5 class="fw-bold mb-3">Ratings & Reviews</h5>
          <div class="d-flex align-items-center mb-3">
            <h2 class="fw-bold me-3 text-success">4.5</h2>
            <div>
              <div class="text-warning">
                ★★★★☆
              </div>
              <p class="text-muted mb-0">25 reviews</p>
            </div>
          </div>
          <div class="mb-3">
            <div class="d-flex justify-content-between"><span>5</span><div class="progress flex-grow-1 mx-2"><div class="progress-bar bg-success" style="width:40%"></div></div><span>40%</span></div>
            <div class="d-flex justify-content-between"><span>4</span><div class="progress flex-grow-1 mx-2"><div class="progress-bar bg-success" style="width:30%"></div></div><span>30%</span></div>
            <div class="d-flex justify-content-between"><span>3</span><div class="progress flex-grow-1 mx-2"><div class="progress-bar bg-success" style="width:15%"></div></div><span>15%</span></div>
            <div class="d-flex justify-content-between"><span>2</span><div class="progress flex-grow-1 mx-2"><div class="progress-bar bg-success" style="width:10%"></div></div><span>10%</span></div>
            <div class="d-flex justify-content-between"><span>1</span><div class="progress flex-grow-1 mx-2"><div class="progress-bar bg-success" style="width:5%"></div></div><span>5%</span></div>
          </div>

          <!-- Review -->
          <div class="review-card">
            <div class="d-flex align-items-center mb-2">
              <img src="https://via.placeholder.com/40" class="rounded-circle me-2">
              <div>
                <strong>Sophia Clark</strong>
                <p class="text-muted small mb-0">1 month ago</p>
              </div>
            </div>
            <p>"The Haven is fantastic! Rooms are clean, modern, and staff very helpful. Highly recommended!"</p>
          </div>
          <div class="review-card">
            <div class="d-flex align-items-center mb-2">
              <img src="https://via.placeholder.com/40" class="rounded-circle me-2">
              <div>
                <strong>Ethan Miller</strong>
                <p class="text-muted small mb-0">2 months ago</p>
              </div>
            </div>
            <p>"I had a pleasant stay. The room was comfortable, the location convenient. Only minor issue: Wi-Fi a bit slow at times."</p>
          </div>
        </div>
      </div>

      <!-- Sisi kanan -->
      <div class="col-lg-4">
        <div class="p-4 bg-white rounded shadow-sm">
          <h4 class="fw-bold">The Haven Boarding House</h4>
          <p class="text-muted">123 Maple Street, Anytown, USA</p>
          <h5 class="fw-bold text-success">Starting from $50/night</h5>
          <a href="#" class="btn btn-success w-100 mt-3">Book Now</a>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
