<?php
session_start();
require_once "../../../../backend/config/db.php";

// Check authentication
if (!isset($_SESSION['user_id'])) {
    die("ERROR: Session tidak ditemukan! <a href='../../auth/login.php'>Login</a>");
}

if ($_SESSION['user_type'] !== 'owner') {
    die("ERROR: Anda bukan owner! <a href='../../auth/login.php'>Login</a>");
}

// Get property ID
$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$owner_id = $_SESSION['user_id'];

if ($property_id == 0) {
    die("ERROR: Property ID tidak valid!");
}

// Fetch property data
$sql = "SELECT * FROM kos WHERE id = ? AND owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $property_id, $owner_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("ERROR: Property tidak ditemukan atau Anda tidak memiliki akses!");
}

$property = $result->fetch_assoc();

// Fetch images - FIX PATH
$sql_images = "SELECT image_url FROM kos_images WHERE kos_id = ?";
$stmt_images = $conn->prepare($sql_images);
$stmt_images->bind_param("i", $property_id);
$stmt_images->execute();
$result_images = $stmt_images->get_result();
$images = [];
while ($img = $result_images->fetch_assoc()) {
    $images[] = $img['image_url'];
}

// Fetch facilities
$sql_facilities = "SELECT facility_id FROM kos_facilities WHERE kos_id = ?";
$stmt_facilities = $conn->prepare($sql_facilities);
$stmt_facilities->bind_param("i", $property_id);
$stmt_facilities->execute();
$result_facilities = $stmt_facilities->get_result();
$selected_facilities = [];
while ($fac = $result_facilities->fetch_assoc()) {
    $selected_facilities[] = $fac['facility_id'];
}

$existing_rules = [];
if (!empty($property['rules'])) {
    $decoded = json_decode($property['rules'], true);

    // Jika hasil decode pertama masih string JSON, decode lagi
    if (is_string($decoded)) {
        $decoded = json_decode($decoded, true);
    }

    if (is_array($decoded)) {
        $existing_rules = $decoded;
    }
}


?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Edit Property - KostHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="../css/add_property.css" rel="stylesheet">
</head>

<body>
    <!-- Header -->
    <div class="header-bar py-3 d-flex align-items-center justify-content-center position-relative">
        <button type="button" class="btn btn-outline-success position-absolute start-0 ms-3 d-flex align-items-center"
            onclick="window.location.href='your_property.php'">
            <i class="bi bi-arrow-left"></i>
        </button>

        <div class="d-flex align-items-center text-center">
            <img src="../../../assets/logo_kos.png" alt="logo" style="height:40px;" class="me-2">
            <h2 class="fw-bold m-0">KostHub</h2>
        </div>
    </div>

    <!-- Content -->
    <div class="container mb-5" style="max-width: 850px;">
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            <strong>Edit Property:</strong> Setelah Anda mengedit property, status akan kembali ke <strong>Pending</strong> dan akan ditinjau kembali oleh admin.
        </div>

        <h3 class="fw-bold mb-2">Edit Property</h3>
        <p class="text-muted">Update informasi properti Anda</p>

        <div class="card-form mt-4">
            <form id="propertyForm" action="../../../../backend/user/owner/action/edit_property_process.php" method="POST" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                <input type="hidden" name="existing_rules" value='<?php echo htmlspecialchars(json_encode($existing_rules)); ?>'>

                <!-- Nama & Kode Pos -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nama Property</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($property['name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Kode Pos</label>
                    <input type="text" name="postal_code" class="form-control" value="<?php echo htmlspecialchars($property['postal_code'] ?? ''); ?>">
                </div>

                <!-- Alamat dengan Search -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Alamat</label>
                    <div class="search-box">
                        <div class="input-group">
                            <input type="text" id="address-search" class="form-control" placeholder="Cari alamat...">
                            <button type="button" id="search-btn" class="btn btn-success">
                                <i class="bi bi-search"></i> Cari
                            </button>
                        </div>
                        <div id="search-results" style="display: none;"></div>
                    </div>
                    <input type="text" name="address" id="address" class="form-control mt-2" value="<?php echo htmlspecialchars($property['address']); ?>" required>
                </div>

                <!-- Provinsi & Kota -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Provinsi</label>
                    <input type="text" name="province" class="form-control" value="<?php echo htmlspecialchars($property['province']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Kota</label>
                    <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($property['city']); ?>" required>
                </div>

                <!-- Map Section -->
                <div class="col-12">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-geo-alt-fill text-danger"></i> Lokasi di Peta
                    </label>
                    <div class="map-info" style="background-color: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 10px; padding: 12px 16px; margin-bottom: 10px;">
                        <small>
                            <i class="bi bi-info-circle" style="color: #198754;"></i>
                            <strong>Cara menggunakan:</strong> Klik di peta untuk menentukan lokasi, atau drag marker untuk menyesuaikan posisi.
                        </small>
                    </div>
                    <div id="map" class="mt-2"></div>
                    <input type="hidden" id="latitude" name="latitude" value="<?php echo $property['latitude']; ?>">
                    <input type="hidden" id="longitude" name="longitude" value="<?php echo $property['longitude']; ?>">
                    <div class="mt-2 text-muted small">
                        <i class="bi bi-pin-map"></i>
                        Koordinat: <span id="coord-display"><?php echo $property['latitude'] . ', ' . $property['longitude']; ?></span>
                    </div>
                </div>

                <!-- Total Kamar & Jenis Kos -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Total Kamar</label>
                    <input type="number" name="total_rooms" class="form-control" value="<?php echo $property['total_rooms']; ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Jenis Kos</label>
                    <select name="kos_type" class="form-select" required>
                        <option value="">Pilih Jenis Kos</option>
                        <option value="campur" <?php echo $property['kos_type'] == 'campur' ? 'selected' : ''; ?>>Campur</option>
                        <option value="putra" <?php echo $property['kos_type'] == 'putra' ? 'selected' : ''; ?>>Putra</option>
                        <option value="putri" <?php echo $property['kos_type'] == 'putri' ? 'selected' : ''; ?>>Putri</option>
                    </select>
                </div>

                <!-- Fasilitas -->
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

                <!-- Aturan -->
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
                    <div id="aturan-tags" class="mt-2"></div>
                </div>

                <!-- Harga -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Harga Perbulan</label>
                    <input type="text" id="price_monthly" name="price_monthly" class="form-control" value="<?php echo $property['price_monthly']; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Harga Perhari (Opsional)</label>
                    <input type="text" id="price_daily" name="price_daily" class="form-control" value="<?php echo $property['price_daily'] ?? ''; ?>">
                </div>

                <!-- Deskripsi -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($property['description'] ?? ''); ?></textarea>
                </div>

                <!-- Upload Gambar -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Gambar <small class="text-muted">(Maksimal 10 gambar total)</small></label>
                    <div class="upload-box text-center rounded p-4 position-relative border-dashed" id="dropArea">
                        <i class="bi bi-image fs-1 text-success"></i>
                        <p class="mb-1"><strong>Upload gambar baru</strong> atau Drag and Drop</p>
                        <p class="text-muted small">PNG, JPG, GIF up to 10MB</p>
                        <input type="file" id="imageInput" name="images[]" class="form-control mt-2" accept="image/*" multiple>
                    </div>
                    <div id="previewContainer" class="d-flex flex-wrap gap-3 mt-3">
                        <?php foreach ($images as $img): ?>
                            <div class="preview-item position-relative" style="width: 120px; height: 120px;">
                                <img src="../../../../<?php echo htmlspecialchars($img); ?>"
                                    class="img-thumbnail w-100 h-100"
                                    style="object-fit: cover; border-radius: 8px;"
                                    alt="existing"
                                    onerror="this.src='../../../assets/default-kos.jpg'">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                    <a href="your_property.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> Update Property
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Pass PHP data to JavaScript -->
    <script>
        // Existing coordinates for map
        const existingLat = <?php echo $property['latitude'] ?? '-8.172'; ?>;
        const existingLng = <?php echo $property['longitude'] ?? '113.697'; ?>;

        // Selected facilities
        const selectedFacilities = <?php echo json_encode($selected_facilities); ?>;


        // Flag for edit mode - TAMBAH INI
        window.isEditMode = true;
        window.editCoordinates = {
            lat: existingLat,
            lng: existingLng
        };
    </script>
    <script>
        // Ambil data dari PHP dan ubah ke variabel JS
        const existingRules = <?= json_encode($existing_rules ?? []); ?>;
        console.log("existingRules from PHP:", existingRules);
    </script>
    <!-- Load scripts in order -->
    <script src="../js/leaflet_maps.js"></script>
    <script src="../js/edit_property.js"></script>
</body>

</html>