<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Update Property - KostHub</title>
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
    }
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
    <h3 class="fw-bold mb-2">Update Property</h3>
    <p class="text-muted">Ubah detail property sesuai kebutuhan</p>

    <div class="card-form mt-4">
      <form class="row g-3">
        <!-- Nama & Kode Pos -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Nama Property</label>
          <input type="text" class="form-control" value="Kos The Raid">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Kode Pos</label>
          <input type="text" class="form-control" value="68121">
        </div>

        <!-- Alamat -->
        <div class="col-12">
          <label class="form-label fw-semibold">Alamat</label>
          <input type="text" class="form-control" value="Jl. Tawang Mangu 6 No.20, Jember">
        </div>

        <!-- Provinsi & Kota -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Provinsi</label>
          <input type="text" class="form-control" value="Jawa Timur">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Kota</label>
          <input type="text" class="form-control" value="Jember">
        </div>

        <!-- Total Kamar & Jenis Kos -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Total Kamar</label>
          <input type="number" class="form-control" value="10">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Jenis Kos</label>
          <select class="form-select">
            <option>Campur</option>
            <option selected>Putra</option>
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

      <!-- Tempat untuk tag aturan -->
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
          <textarea class="form-control" rows="3">Kos nyaman dekat kampus dengan fasilitas lengkap.</textarea>
        </div>

        <!-- Upload -->
        <div class="col-12">
          <label class="form-label fw-semibold">Gambar</label>
          <div class="upload-box">
            <i class="bi bi-image fs-1 text-success"></i>
            <p class="mb-1"><strong>Upload a file</strong> or Drag and Drop</p>
            <p class="text-muted small">PNG, JPG, GIF up to 10MB</p>
            <input type="file" class="form-control mt-2" accept="image/*">
            <small class="text-muted">Gambar saat ini: kos1.jpg</small>
          </div>
        </div>

        <!-- Buttons -->
        <div class="col-12 d-flex justify-content-end gap-2 mt-3">
          <a href="your_property.php" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-success">Update Property</button>
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
            // wrapper untuk tiap tag aturan
            const wrapper = document.createElement("div");
            wrapper.className = "d-inline-block me-2 mb-2";
            wrapper.dataset.value = value;

            wrapper.innerHTML = `
              <span class="badge bg-success p-2">
                ${text}
                <i class="bi bi-x-circle ms-1" style="cursor:pointer"></i>
              </span>
              <input type="hidden" name="aturan[]" value="${value}">
            `;

            container.appendChild(wrapper);

            // reset dropdown ke default
            this.value = "";

            // klik X hapus tag
            wrapper.querySelector("i").addEventListener("click", function () {
              wrapper.remove();
            });
          }
        });
      });
</script>

</body>
</html>
