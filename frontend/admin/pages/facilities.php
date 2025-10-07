<?php
session_start();
require_once "../../../backend/config/db.php";

// Ambil semua fasilitas dari DB
$result = $conn->query("SELECT * FROM facilities ORDER BY category, created_at DESC");
$facilities = $result->fetch_all(MYSQLI_ASSOC);

// Kelompokkan fasilitas berdasarkan kategori
$categorizedFacilities = [];
foreach ($facilities as $facility) {
    $categorizedFacilities[$facility['category']][] = $facility;
}

// Daftar kategori
$categories = [
    'room' => ['name' => 'Room', 'icon' => 'fa-bed'],
    'bathroom' => ['name' => 'Bathroom', 'icon' => 'fa-bath'],
    'common' => ['name' => 'Common', 'icon' => 'fa-home'],
    'parking' => ['name' => 'Parking', 'icon' => 'fa-car'],
    'security' => ['name' => 'Security', 'icon' => 'fa-shield-alt']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/facilities.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <title>Daftar Fasilitas</title>
</head>
<body>
   <?php include __DIR__ . "/../includes/sidebar.php"; ?>
   <?php include __DIR__ . "/../includes/notification.php"; ?>

  <div class="main-content">
    <div class="facilities-container">
      <div class="page-header">
        <h2><i class="fas fa-list"></i> Daftar Fasilitas</h2>
      </div>
      
      <!-- View Selector -->
      <div class="view-selector">
        <button class="view-btn active" id="viewOverview">
          <i class="fas fa-th"></i> Overview
        </button>
        <button class="view-btn" id="viewCategories">
          <i class="fas fa-list-ul"></i> By Category
        </button>
      </div>

      <!-- Overview Section -->
      <div id="overviewSection">
        <div class="category-grid">
          <?php foreach ($categories as $key => $cat): ?>
            <?php $count = isset($categorizedFacilities[$key]) ? count($categorizedFacilities[$key]) : 0; ?>
            <div class="category-card <?= $key ?>" data-category="<?= $key ?>">
              <i class="fas <?= $cat['icon'] ?>"></i>
              <h3><?= $cat['name'] ?></h3>
              <div class="count"><?= $count ?></div>
              <small>Fasilitas</small>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Category Sections -->
      <div id="categorySections" style="display:none;">
        <button class="btn btn-secondary" id="btnBackOverview">
          <i class="fas fa-arrow-left"></i> Kembali ke Overview
        </button>
        
        <?php foreach ($categories as $key => $cat): ?>
          <div class="category-section" id="section-<?= $key ?>">
            <div class="category-header">
              <h3>
                <i class="fas <?= $cat['icon'] ?>"></i>
                <?= $cat['name'] ?>
              </h3>
              <button class="btn btn-primary btn-small" onclick="openModalWithCategory('<?= $key ?>')">
                <i class="fas fa-plus"></i> Tambah Fasilitas
              </button>
            </div>
            
            <?php if (isset($categorizedFacilities[$key]) && count($categorizedFacilities[$key]) > 0): ?>
              <div class="table-responsive">
                <table class="facilities-table">
                  <thead>
                    <tr>
                      <th width="60">ID</th>
                      <th>Nama</th>
                      <th>Ikon</th>
                      <th width="150">Dibuat</th>
                      <th width="120">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($categorizedFacilities[$key] as $row): ?>
                      <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td>
                          <?php if (strpos($row['icon'], 'fa-') !== false): ?>
                            <i class="fas <?= htmlspecialchars($row['icon']) ?>"></i>
                          <?php else: ?>
                            <?= htmlspecialchars($row['icon']) ?>
                          <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                        <td>
                          <button class="btn btn-warning btn-small" onclick="editFacility(<?= $row['id'] ?>, '<?= addslashes($row['name']) ?>', '<?= addslashes($row['icon']) ?>', '<?= $row['category'] ?>')">
                            <i class="fas fa-edit"></i>
                          </button>
                          <button class="btn btn-danger btn-small" onclick="deleteFacility(<?= $row['id'] ?>, '<?= addslashes($row['name']) ?>')">
                            <i class="fas fa-trash"></i>
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="empty-state">
                <i class="fas <?= $cat['icon'] ?>"></i>
                <p>Belum ada fasilitas di kategori ini</p>
                <button class="btn btn-primary" onclick="openModalWithCategory('<?= $key ?>')">
                  <i class="fas fa-plus"></i> Tambah Fasilitas Pertama
                </button>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Modal Add/Edit Fasilitas -->
  <div class="modal" id="modalFacility">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="modalTitle"><i class="fas fa-plus-circle"></i> Tambah Fasilitas Baru</h3>
        <span class="close" id="closeModal">&times;</span>
      </div>
      <form id="formFacility" method="POST">
        <input type="hidden" id="facilityId" name="id" value="">
        <input type="hidden" id="icon" name="icon" value="">
        
        <label for="name">Nama Fasilitas *</label>
        <input type="text" id="name" name="name" required placeholder="Contoh: AC, WiFi, Parkir Motor">

        <label>Pilih Icon *</label>
        <div class="icon-picker-container">
          <div class="selected-icon-display" id="selectedIconDisplay">
            <i class="fas fa-question-circle"></i>
            <span>Pilih Icon</span>
          </div>
          <button type="button" class="btn btn-secondary btn-small" id="btnOpenIconPicker">
            <i class="fas fa-icons"></i> Browse Icons
          </button>
        </div>

        <label for="category">Kategori *</label>
        <select id="category" name="category" required>
          <option value="">-- Pilih Kategori --</option>
          <?php foreach ($categories as $key => $cat): ?>
            <option value="<?= $key ?>"><?= $cat['name'] ?></option>
          <?php endforeach; ?>
        </select>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Simpan
          </button>
          <button type="button" class="btn btn-secondary" onclick="closeModalFacility()">
            <i class="fas fa-times"></i> Batal
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Icon Picker -->
  <div class="modal" id="modalIconPicker">
    <div class="modal-content modal-icon-picker">
      <div class="modal-header">
        <h3><i class="fas fa-icons"></i> Pilih Icon</h3>
        <span class="close" id="closeIconPicker">&times;</span>
      </div>
      
      <div class="icon-search">
        <input type="text" id="iconSearch" placeholder="Cari icon... (contoh: bed, wifi, car)">
      </div>

      <div class="icon-categories-tabs">
        <button class="icon-tab active" data-category="all">Semua</button>
        <button class="icon-tab" data-category="room">Room</button>
        <button class="icon-tab" data-category="bathroom">Bathroom</button>
        <button class="icon-tab" data-category="common">Fasilitas Umum</button>
        <button class="icon-tab" data-category="parking">Parking</button>
        <button class="icon-tab" data-category="security">Security</button>
      </div>

      <div class="icon-grid" id="iconGrid">
        <!-- Icons will be populated by JavaScript -->
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeIconPickerModal()">
          <i class="fas fa-times"></i> Tutup
        </button>
      </div>
    </div>
  </div>

  <script src="../js/facilities.js"></script>
    <script>
    function toggleDropdown() {
      const menu = document.getElementById("dropdownMenu");
      menu.style.display = menu.style.display === "block" ? "none" : "block";
    }
    window.addEventListener("click", function(e) {
      if (!e.target.closest(".user-menu")) {
        document.getElementById("dropdownMenu").style.display = "none";
      }
    });
  </script>
</body>
</html>
    