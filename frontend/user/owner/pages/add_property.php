<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Add New Property - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link href="../css/add_property.css" rel="stylesheet">
</head>

<body>

  <!-- Header -->
  <div class="text-center py-3 d-flex align-items-center justify-content-center">
    <img src="../../../assets/logo_kos.png" alt="logo" style="height:40px;" class="me-2">
    <h2 class="fw-bold m-0">KostHub</h2>
  </div>

  <div class="container mb-5" style="max-width: 850px;">
    <h3 class="fw-bold mb-2">Add New Property</h3>
    <p class="text-muted">Selamat Datang ke Halaman Pembuatan Property<br>Isi form di bawah ini dengan lengkap</p>

    <div class="card-form mt-4">
      <form class="row g-3">
        <!-- Nama & Kode Pos -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Nama Property</label>
          <input type="text" class="form-control" placeholder="cth. Kos The Raid">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Kode Pos</label>
          <input type="text" class="form-control" placeholder="cth. 68121">
        </div>

        <!-- Alamat -->
        <div class="col-12">
          <label class="form-label fw-semibold">Alamat</label>
          <input type="text" class="form-control" placeholder="cth. Perum Mastrip Blok 0/18">
        </div>

        <!-- Provinsi & Kota -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Provinsi</label>
          <input type="text" class="form-control" placeholder="cth. Jawa Timur">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Kota</label>
          <input type="text" class="form-control" placeholder="cth. Jember">
        </div>

        <!-- Total Kamar & Jenis Kos -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Total Kamar</label>
          <input type="number" class="form-control" placeholder="cth. 10">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Jenis Kos</label>
          <select class="form-select">
            <option>Pilih Jenis Kos</option>
            <option>Campur</option>
            <option>Putra</option>
            <option>Putri</option>
          </select>
        </div>

        <!-- Fasilitas & Aturan -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Fasilitas</label>
          
          <!-- Accordion untuk kategori fasilitas -->
          <div class="accordion" id="facilitiesAccordion">
            
            <!-- Fasilitas Kamar -->
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#roomFacilities">
                  <i class="bi bi-door-closed category-icon me-2"></i> Fasilitas Kamar
                </button>
              </h2>
              <div id="roomFacilities" class="accordion-collapse collapse" data-bs-parent="#facilitiesAccordion">
                <div class="accordion-body p-2">
                  <select id="facility-room" class="form-select form-select-sm">
                    <option value="">Pilih Fasilitas Kamar...</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Fasilitas Kamar Mandi -->
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#bathroomFacilities">
                  <i class="bi bi-droplet category-icon me-2"></i> Fasilitas Kamar Mandi
                </button>
              </h2>
              <div id="bathroomFacilities" class="accordion-collapse collapse" data-bs-parent="#facilitiesAccordion">
                <div class="accordion-body p-2">
                  <select id="facility-bathroom" class="form-select form-select-sm">
                    <option value="">Pilih Fasilitas Kamar Mandi...</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Fasilitas Umum -->
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#commonFacilities">
                  <i class="bi bi-house category-icon me-2"></i> Fasilitas Umum
                </button>
              </h2>
              <div id="commonFacilities" class="accordion-collapse collapse" data-bs-parent="#facilitiesAccordion">
                <div class="accordion-body p-2">
                  <select id="facility-common" class="form-select form-select-sm">
                    <option value="">Pilih Fasilitas Umum...</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Fasilitas Parkir -->
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#parkingFacilities">
                  <i class="bi bi-car-front category-icon me-2"></i> Fasilitas Parkir
                </button>
              </h2>
              <div id="parkingFacilities" class="accordion-collapse collapse" data-bs-parent="#facilitiesAccordion">
                <div class="accordion-body p-2">
                  <select id="facility-parking" class="form-select form-select-sm">
                    <option value="">Pilih Fasilitas Parkir...</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Fasilitas Keamanan -->
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#securityFacilities">
                  <i class="bi bi-shield-check category-icon me-2"></i> Fasilitas Keamanan
                </button>
              </h2>
              <div id="securityFacilities" class="accordion-collapse collapse" data-bs-parent="#facilitiesAccordion">
                <div class="accordion-body p-2">
                  <select id="facility-security" class="form-select form-select-sm">
                    <option value="">Pilih Fasilitas Keamanan...</option>
                  </select>
                </div>
              </div>
            </div>

          </div>

          <!-- Tempat menampilkan tag fasilitas yang dipilih -->
          <div id="facility-tags" class="mt-3 p-2 border rounded bg-light">
            <small class="text-muted">Fasilitas yang dipilih akan muncul di sini</small>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Aturan</label>
          <select id="aturan-select" class="form-select">
            <option value="">Pilih Aturan</option>
            <option value="bebas">Bebas</option>
            <option value="jam_malam">Ada Jam Malam</option>
            <option value="tidak_merokok">Tidak Boleh Merokok</option>
            <option value="tamu_terbatas">Tamu Terbatas</option>
          </select>

          <!-- Tempat menampilkan tag aturan -->
          <div id="aturan-tags" class="mt-2"></div>
        </div>

        <!-- Harga -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Harga Perbulan</label>
          <input type="text" class="form-control" placeholder="Rp. 800.000">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Harga Perhari (Opsional)</label>
          <input type="text" class="form-control" placeholder="Rp. 80.000">
        </div>

        <!-- Deskripsi -->
        <div class="col-12">
          <label class="form-label fw-semibold">Deskripsi</label>
          <textarea class="form-control" rows="3" placeholder="cth. Kos Nyaman Dekat Kampus"></textarea>
        </div>

        <!-- Upload -->
        <div class="col-12">
          <label class="form-label fw-semibold">Gambar</label>
          <div class="upload-box">
            <i class="bi bi-image fs-1 text-success"></i>
            <p class="mb-1"><strong>Upload a file</strong> or Drag and Drop</p>
            <p class="text-muted small">PNG, JPG, GIF up to 10MB</p>
            <input type="file" class="form-control mt-2" accept="image/*">
          </div>
        </div>

        <!-- Buttons -->
        <div class="col-12 d-flex justify-content-end gap-2 mt-3">
          <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-success">Add Property</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- JavaScript terpisah -->
  <script src="../js/add_property.js"></script>
</body>

</html>