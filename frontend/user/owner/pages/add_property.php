<?php
session_start();
require_once "../../../../backend/config/db.php";

// Debug - HAPUS SETELAH SELESAI TESTING
if (!isset($_SESSION['user_id'])) {
  die("ERROR: Session tidak ditemukan! Silakan login ulang. <a href='../../auth/login.php'>Login</a>");
}

if ($_SESSION['user_type'] !== 'owner') {
  die("ERROR: Anda bukan owner! User Type: " . $_SESSION['user_type'] . " <a href='../../auth/login.php'>Login</a>");
}

// Uncomment untuk melihat semua session data
// echo "<pre>"; print_r($_SESSION); echo "</pre>";
?>

<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Add New Property - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

  <!-- Custom CSS -->
  <link href="../css/add_property.css" rel="stylesheet">
  <style>
    #map {
      height: 400px;
      width: 100%;
      border-radius: 8px;
      border: 2px solid #dee2e6;
      z-index: 1;
    }

    .map-info {
      background-color: #e7f3ff;
      border-left: 4px solid #0d6efd;
      padding: 12px;
      border-radius: 4px;
      margin-top: 10px;
    }

    .search-box {
      position: relative;
      z-index: 1000;
    }

    #search-results {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: white;
      border: 1px solid #ddd;
      border-radius: 4px;
      max-height: 200px;
      overflow-y: auto;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      z-index: 1001;
    }

    .search-result-item {
      padding: 10px;
      cursor: pointer;
      border-bottom: 1px solid #eee;
    }

    .search-result-item:hover {
      background-color: #f8f9fa;
    }
  </style>
</head>

<body>


  <!-- Header -->
  <div class="header-bar py-3 d-flex align-items-center justify-content-center position-relative border-bottom mb-4">
    <!-- Tombol kembali -->
    <button type="button"
      class="btn btn-outline-success position-absolute start-0 ms-3 d-flex align-items-center"
      onclick="window.history.back()">
      <i class="bi bi-arrow-left"></i>
    </button>

    <!-- Logo dan Judul -->
    <div class="d-flex align-items-center text-center">
      <img src="../../../assets/logo_kos.png" alt="logo" style="height:40px;" class="me-2">
      <h2 class="fw-bold m-0">KostHub</h2>
    </div>
  </div>


  <!-- Konten Form -->
  <div class="container mb-5" style="max-width: 850px;">
    <h3 class="fw-bold mb-2">Add New Property</h3>
    <p class="text-muted">
      Selamat Datang ke Halaman Pembuatan Property<br>
      Isi form di bawah ini dengan lengkap
    </p>


    <div class="card-form mt-4">
      <form id="propertyForm" action="../../../../backend/user/owner/classes/add_property_process.php" method="POST" enctype="multipart/form-data" class="row g-3">
        <!-- Nama & Kode Pos -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Nama Property</label>
          <input type="text" name="name" class="form-control" placeholder="cth. Kos The Raid" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Kode Pos</label>
          <input type="text" name="postal_code" class="form-control" placeholder="cth. 68121">
        </div>

        <!-- Alamat dengan Search -->
        <div class="col-12">
          <label class="form-label fw-semibold">Alamat</label>
          <div class="search-box">
            <div class="input-group">
              <input type="text" id="address-search" class="form-control" placeholder="Cari alamat... (cth: Jember, Jawa Timur)">
              <button type="button" id="search-btn" class="btn btn-success">
                <i class="bi bi-search"></i> Cari
              </button>
            </div>
            <div id="search-results" style="display: none;"></div>
          </div>
          <small class="text-muted">Ketik nama tempat/alamat dan klik "Cari", lalu pilih dari hasil pencarian</small>

          <input type="text" name="address" id="address" class="form-control mt-2" placeholder="Alamat lengkap akan terisi otomatis" required>
        </div>

        <!-- Provinsi & Kota -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Provinsi</label>
          <input type="text" name="province" class="form-control" placeholder="cth. Jawa Timur" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Kota</label>
          <input type="text" name="city" class="form-control" placeholder="cth. Jember" required>
        </div>

        <!-- Map Section -->
        <div class="col-12">
          <label class="form-label fw-semibold">
            <i class="bi bi-geo-alt-fill text-danger"></i> Lokasi di Peta
          </label>
          <div class="map-info"
            style="background-color: #e8f5e9; 
            border: 1px solid #c8e6c9; 
            border-radius: 10px; 
            padding: 12px 16px; 
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <small>
              <i class="bi bi-info-circle" style="color: #198754;"></i>
              <strong>Cara menggunakan:</strong>
              Cari lokasi menggunakan kotak pencarian, atau klik langsung di peta untuk menentukan lokasi.
              Marker merah bisa di-drag untuk menyesuaikan posisi.
            </small>
          </div>

          <div id="map" class="mt-2"></div>

          <!-- Hidden inputs -->
          <input type="hidden" id="latitude" name="latitude" value="">
          <input type="hidden" id="longitude" name="longitude" value="">

          <!-- Info koordinat -->
          <div class="mt-2 text-muted small">
            <i class="bi bi-pin-map"></i>
            Koordinat: <span id="coord-display">Belum dipilih</span>
          </div>
        </div>

        <!-- Total Kamar & Jenis Kos -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Total Kamar</label>
          <input type="number" name="total_rooms" class="form-control" placeholder="cth. 10" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Jenis Kos</label>
          <select name="kos_type" class="form-select" required>
            <option value="">Pilih Jenis Kos</option>
            <option value="campur">Campur</option>
            <option value="putra">Putra</option>
            <option value="putri">Putri</option>
          </select>
        </div>

        <!-- Fasilitas & Aturan -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Fasilitas</label>

          <div class="accordion" id="facilitiesAccordion">
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

          <div id="facility-tags" class="mt-3 p-2 border rounded bg-light">
            <small class="text-muted">Fasilitas yang dipilih akan muncul di sini</small>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Aturan</label>
          <select id="aturan-select" class="form-select">
            <option value="">Pilih Aturan</option>

            <optgroup label="Perilaku Penghuni">
              <option value="tidak_merokok">Tidak Boleh Merokok</option>
              <option value="tidak_membawa_hewan">Tidak Boleh Membawa Hewan Peliharaan</option>
              <option value="tidak_berisik">Tidak Boleh Berisik Setelah Jam 10 Malam</option>
            </optgroup>

            <optgroup label="Tamu & Akses">
              <option value="tamu_sampai_21">Tamu Hanya Sampai Pukul 21.00</option>
              <option value="tamu_tidak_menginap">Tamu Tidak Boleh Menginap</option>
              <option value="tamu_lawan_jenis">Tamu Lawan Jenis Dilarang Masuk Kamar</option>
            </optgroup>

            <optgroup label="Kebersihan & Fasilitas">
              <option value="jaga_kebersihan">Wajib Menjaga Kebersihan Kamar</option>
              <option value="larangan_cuci_umum">Dilarang Mencuci di Kamar Mandi Umum</option>
              <option value="modifikasi_fasilitas">Dilarang Memodifikasi Fasilitas Kos</option>
            </optgroup>

            <optgroup label="Keamanan">
              <option value="kunci_gerbang">Wajib Mengunci Pintu Gerbang Setelah Jam 22.00</option>
              <option value="kompor_menyala">Tidak Boleh Meninggalkan Kompor Menyala</option>
              <option value="lapor_kehilangan">Wajib Lapor Jika Kehilangan Barang</option>
            </optgroup>

            <optgroup label="Pembayaran & Administrasi">
              <option value="bayar_tepat_waktu">Pembayaran Wajib Sebelum Tanggal 5 Setiap Bulan</option>
              <option value="denda_keterlambatan">Denda Keterlambatan Rp50.000 per Hari</option>
              <option value="deposit_hangus">Deposit Hangus Jika Keluar Sebelum Kontrak Habis</option>
            </optgroup>
          </select>

          <!-- Tempat tag aturan -->
          <div id="aturan-tags" class="mt-2"></div>
        </div>


        <!-- Harga -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Harga Perbulan</label>
          <input type="text" id="price_monthly" name="price_monthly" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Harga Perhari (Opsional)</label>
          <input type="text" id="price_daily" name="price_daily" class="form-control">
        </div>

        <!-- Deskripsi -->
        <div class="col-12">
          <label class="form-label fw-semibold">Deskripsi</label>
          <textarea name="description" class="form-control" rows="3" placeholder="cth. Kos Nyaman Dekat Kampus"></textarea>
        </div>

        <!-- Upload -->
        <div class="col-12">
          <label class="form-label fw-semibold">Gambar</label>

          <!-- Upload Box -->
          <div class="upload-box text-center rounded p-4 position-relative border-dashed" id="dropArea">
            <i class="bi bi-image fs-1 text-success"></i>
            <p class="mb-1"><strong>Upload a file</strong> or Drag and Drop</p>
            <p class="text-muted small">PNG, JPG, GIF up to 10MB</p>
            <input
              type="file"
              id="imageInput"
              name="images[]"
              class="form-control mt-2"
              accept="image/*"
              multiple>
          </div>

          <!-- Preview Container -->
          <div id="previewContainer" class="d-flex flex-wrap gap-3 mt-3"></div>
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

  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <!-- Leaflet Maps JS -->
  <script src="../js/leaflet_maps.js"></script>

  <!-- Form JS -->
  <script src="../js/add_property.js"></script>
</body>

</html>