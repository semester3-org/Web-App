<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Add New Property - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f4f9f6; }
    .card-form {
      background: #fff;
      border-radius: 12px;
      padding: 2rem;
      box-shadow: 0 4px 12px rgba(0,0,0,.08);
    }
    .upload-box {
      border: 2px dashed #28a745;
      border-radius: 8px;
      padding: 2rem;
      text-align: center;
      background: #f8fdf9;
      cursor: pointer;
    }
    .upload-box:hover { background: #f1fbf3; }
    .btn-success { border-radius: 20px; padding: 8px 24px; }
    .btn-secondary { border-radius: 20px; padding: 8px 24px; }
  </style>
</head>
<body>

  <!-- Header -->
  <div class="text-center py-3 d-flex align-items-center justify-content-center">
    <img src="../../assets/logo_kos.png" alt="logo" style="height:40px;" class="me-2">
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
          <input type="text" class="form-control" placeholder="cth. Wifi, AC, Dapur">
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

  <!-- Tempat menampilkan tag -->
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
  <script>
document.addEventListener("DOMContentLoaded", function () {
  const select = document.getElementById("aturan-select");
  const container = document.getElementById("aturan-tags");

  select.addEventListener("change", function () {
    const value = this.value;
    const text = this.options[this.selectedIndex].text;

    if (value && !document.querySelector(`[data-value="${value}"]`)) {
      // Buat badge/tag
      const tag = document.createElement("span");
      tag.className = "badge bg-success me-2 mb-2 p-2";
      tag.dataset.value = value;
      tag.innerHTML = `${text} <i class="bi bi-x-circle ms-1" style="cursor:pointer"></i>`;

      // Tambahkan ke container
      container.appendChild(tag);

      // Reset dropdown ke default
      this.value = "";

      // Hapus tag kalau diklik X
      tag.querySelector("i").addEventListener("click", function () {
        tag.remove();
      });
    }
  });
});
</script>
</body>
</html>
