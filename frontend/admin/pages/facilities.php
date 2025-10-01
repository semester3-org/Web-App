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
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <title>Daftar Fasilitas</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f9f9f9;
      padding: 20px;
    }

    .container {
      max-width: 1200px;
      margin: auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    h2 {
      margin-bottom: 20px;
      color: #333;
    }

    /* Category Grid */
    .category-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 30px;
    }

    .category-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 20px;
      border-radius: 10px;
      color: white;
      cursor: pointer;
      transition: transform 0.3s, box-shadow 0.3s;
      text-align: center;
    }

    .category-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .category-card i {
      font-size: 32px;
      margin-bottom: 10px;
    }

    .category-card h3 {
      margin: 10px 0 5px;
      font-size: 18px;
    }

    .category-card .count {
      font-size: 24px;
      font-weight: bold;
    }

    /* Different colors for each category */
    .category-card.room {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .category-card.bathroom {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .category-card.common {
      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .category-card.parking {
      background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    .category-card.security {
      background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }

    /* Action Buttons */
    .action-buttons {
      display: flex;
      gap: 10px;
      margin-bottom: 30px;
    }

    /* Category Section */
    .category-section {
      margin-bottom: 40px;
      display: none;
    }

    .category-section.active {
      display: block;
    }

    .category-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 2px solid #4CAF50;
    }

    .category-header h3 {
      display: flex;
      align-items: center;
      gap: 10px;
      color: #333;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    th,
    td {
      border: 1px solid #ddd;
      padding: 12px;
      text-align: left;
    }

    th {
      background: #f8f9fa;
      font-weight: 600;
      color: #555;
    }

    tr:hover {
      background: #f5f5f5;
    }

    .btn {
      padding: 10px 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      transition: all 0.3s;
    }

    .btn:hover {
      opacity: 0.9;
      transform: translateY(-1px);
    }

    .btn-primary {
      background: #4CAF50;
      color: white;
    }

    .btn-danger {
      background: #f44336;
      color: white;
    }

    .btn-secondary {
      background: #6c757d;
      color: white;
    }

    .btn-warning {
      background: #ff9800;
      color: white;
    }

    .btn-small {
      padding: 6px 12px;
      font-size: 12px;
    }

    /* Modal */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }

    .modal-content {
      background: #fff;
      padding: 25px;
      border-radius: 10px;
      width: 450px;
      max-width: 90%;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .modal-header h3 {
      margin: 0;
      color: #333;
    }

    .close {
      cursor: pointer;
      font-size: 24px;
      color: #999;
      transition: color 0.3s;
    }

    .close:hover {
      color: #333;
    }

    label {
      display: block;
      margin: 15px 0 5px;
      font-weight: 600;
      color: #555;
    }

    input[type="text"],
    select {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
      transition: border 0.3s;
    }

    input[type="text"]:focus,
    select:focus {
      outline: none;
      border-color: #4CAF50;
    }

    .form-actions {
      display: flex;
      gap: 10px;
      margin-top: 20px;
    }

    .empty-state {
      text-align: center;
      padding: 40px;
      color: #999;
    }

    .empty-state i {
      font-size: 48px;
      margin-bottom: 15px;
    }

    /* View Selector */
    .view-selector {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }

    .view-btn {
      padding: 8px 16px;
      border: 2px solid #ddd;
      background: white;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.3s;
    }

    .view-btn.active {
      background: #4CAF50;
      color: white;
      border-color: #4CAF50;
    }
  </style>
</head>

<body>
  <?php include __DIR__ . "/../includes/sidebar.php"; ?>

  <div class="container">
    <h2><i class="fas fa-list"></i> Daftar Fasilitas</h2>

    <!-- View Selector -->
    <div class="view-selector">
      <button class="view-btn active" id="viewOverview">
        <i class="fas fa-th"></i> Overview
      </button>
      <button class="view-btn" id="viewCategories">
        <i class="fas fa-list-ul"></i> By Category
      </button>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
      <button class="btn btn-primary" id="btnAdd">
        <i class="fas fa-plus"></i> Tambah Fasilitas
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
            <small><?= $count > 1 ? 'Fasilitas' : 'Fasilitas' ?></small>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Category Sections -->
    <div id="categorySections" style="display:none;">
      <button class="btn btn-secondary" id="btnBackOverview" style="margin-bottom:20px;">
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
              <i class="fas fa-plus"></i> Tambah
            </button>
          </div>

          <?php if (isset($categorizedFacilities[$key]) && count($categorizedFacilities[$key]) > 0): ?>
            <table>
              <thead>
                <tr>
                  <th width="60">ID</th>
                  <th>Nama</th>
                  <th>Ikon</th>
                  <th width="150">Dibuat</th>
                  <th width="100">Aksi</th>
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
                      <button class="btn btn-warning btn-small" onclick="editFacility(<?= $row['id'] ?>)">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-small" onclick="deleteFacility(<?= $row['id'] ?>)">
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <div class="empty-state">
              <i class="fas <?= $cat['icon'] ?>"></i>
              <p>Belum ada fasilitas di kategori ini</p>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Modal Add Fasilitas -->
  <div class="modal" id="modalAdd">
    <div class="modal-content">
      <div class="modal-header">
        <h3><i class="fas fa-plus-circle"></i> Tambah Fasilitas Baru</h3>
        <span class="close" id="closeModal">&times;</span>
      </div>
      <form action="../../../backend/admin/classes/add_facilities_process.php" method="POST">
        <label for="name">Nama Fasilitas *</label>
        <input type="text" id="name" name="name" required placeholder="Contoh: AC, WiFi, Parkir Motor">

        <label for="icon">Ikon (opsional)</label>
        <input type="text" id="icon" name="icon" placeholder="Contoh: fa-wifi atau wifi.png">
        <small style="color:#999;">Gunakan Font Awesome class (fa-wifi) atau nama file gambar</small>

        <label for="category">Kategori *</label>
        <select id="category" name="category" required>
          <option value="">-- Pilih Kategori --</option>
          <?php foreach ($categories as $key => $cat): ?>
            <option value="<?= $key ?>"><?= $cat['name'] ?></option>
          <?php endforeach; ?>
        </select>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary" style="flex:1;">
            <i class="fas fa-save"></i> Simpan
          </button>
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAdd').style.display='none'">
            <i class="fas fa-times"></i> Batal
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const modal = document.getElementById("modalAdd");
    const btnAdd = document.getElementById("btnAdd");
    const closeModal = document.getElementById("closeModal");
    const overviewSection = document.getElementById("overviewSection");
    const categorySections = document.getElementById("categorySections");
    const viewOverview = document.getElementById("viewOverview");
    const viewCategories = document.getElementById("viewCategories");
    const btnBackOverview = document.getElementById("btnBackOverview");

    // Open modal
    btnAdd.onclick = () => {
      document.getElementById('category').value = '';
      modal.style.display = "flex";
    };

    closeModal.onclick = () => modal.style.display = "none";

    window.onclick = (e) => {
      if (e.target == modal) modal.style.display = "none";
    };

    // Open modal with pre-selected category
    function openModalWithCategory(category) {
      document.getElementById('category').value = category;
      modal.style.display = "flex";
    }

    // View switcher
    viewOverview.onclick = () => {
      viewOverview.classList.add('active');
      viewCategories.classList.remove('active');
      overviewSection.style.display = 'block';
      categorySections.style.display = 'none';
    };

    viewCategories.onclick = () => {
      viewCategories.classList.add('active');
      viewOverview.classList.remove('active');
      overviewSection.style.display = 'none';
      categorySections.style.display = 'block';
      // Show all categories
      document.querySelectorAll('.category-section').forEach(section => {
        section.classList.add('active');
      });
    };

    btnBackOverview.onclick = () => {
      viewOverview.click();
    };

    // Category card click - show specific category
    document.querySelectorAll('.category-card').forEach(card => {
      card.addEventListener('click', function() {
        const category = this.dataset.category;

        // Switch to category view
        viewCategories.classList.add('active');
        viewOverview.classList.remove('active');
        overviewSection.style.display = 'none';
        categorySections.style.display = 'block';

        // Hide all sections first
        document.querySelectorAll('.category-section').forEach(section => {
          section.classList.remove('active');
        });

        // Show only selected category
        document.getElementById('section-' + category).classList.add('active');
      });
    });

    // Edit and delete functions (placeholder - implement with your backend)
    function editFacility(id) {
      alert('Edit facility ID: ' + id + '\n\nImplement edit functionality in your backend.');
    }

    function deleteFacility(id) {
      if (confirm('Apakah Anda yakin ingin menghapus fasilitas ini?')) {
        // Implement delete via AJAX or form submission
        window.location.href = '../../../backend/admin/classes/delete_facility.php?id=' + id;
      }
    }
  </script>
</body>

</html>