<?php
session_start();
require_once "../../../backend/config/db.php";

// Ambil semua fasilitas dari DB
$result = $conn->query("SELECT * FROM facilities ORDER BY created_at DESC");
$facilities = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Daftar Fasilitas</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f9f9f9; padding:20px; }
    .container { max-width:900px; margin:auto; background:#fff; padding:20px; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
    h2 { margin-bottom:20px; }
    table { width:100%; border-collapse: collapse; margin-top:15px; }
    th, td { border:1px solid #ddd; padding:10px; text-align:left; }
    th { background:#f1f1f1; }
    .btn { padding:8px 14px; border:none; border-radius:6px; cursor:pointer; }
    .btn-primary { background:#4CAF50; color:white; }
    .btn-danger { background:#f44336; color:white; }
    .btn-secondary { background:#ccc; color:#333; }
    /* Modal */
    .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; }
    .modal-content { background:#fff; padding:20px; border-radius:8px; width:400px; max-width:90%; }
    .modal-header { display:flex; justify-content:space-between; align-items:center; }
    .close { cursor:pointer; font-size:20px; }
    label { display:block; margin:10px 0 5px; font-weight:bold; }
    input[type="text"], select {
      width:100%; padding:8px; border:1px solid #ccc; border-radius:6px;
    }
  </style>
</head>
<body>
   <?php include __DIR__ . "/../includes/sidebar.php"; ?>

  <div class="container">
    <h2>Daftar Fasilitas</h2>
    <button class="btn btn-primary" id="btnAdd">+ Tambah Fasilitas</button>

    <!-- Tabel daftar fasilitas -->
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Nama</th>
          <th>Ikon</th>
          <th>Kategori</th>
          <th>Dibuat</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($facilities) > 0): ?>
          <?php foreach ($facilities as $row): ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= htmlspecialchars($row['icon']) ?></td>
              <td><?= ucfirst($row['category']) ?></td>
              <td><?= $row['created_at'] ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5" style="text-align:center;">Belum ada fasilitas</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Modal Add Fasilitas -->
  <div class="modal" id="modalAdd">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Tambah Fasilitas Baru</h3>
        <span class="close" id="closeModal">&times;</span>
      </div>
      <form action="../../../backend/admin/classes/add_facilities_process.php" method="POST">
        <label for="name">Nama Fasilitas</label>
        <input type="text" id="name" name="name" required placeholder="Contoh: AC, WiFi, Parkir Motor">

        <label for="icon">Ikon (opsional)</label>
        <input type="text" id="icon" name="icon" placeholder="Contoh: wifi.png atau fa fa-wifi">

        <label for="category">Kategori</label>
        <select id="category" name="category" required>
          <option value="">-- Pilih Kategori --</option>
          <option value="room">Room</option>
          <option value="bathroom">Bathroom</option>
          <option value="common">Common</option>
          <option value="parking">Parking</option>
          <option value="security">Security</option>
        </select>

        <button type="submit" class="btn btn-primary">Simpan</button>
      </form>
    </div>
  </div>

  <script>
    const modal = document.getElementById("modalAdd");
    const btn = document.getElementById("btnAdd");
    const close = document.getElementById("closeModal");

    btn.onclick = () => modal.style.display = "flex";
    close.onclick = () => modal.style.display = "none";
    window.onclick = (e) => { if (e.target == modal) modal.style.display = "none"; }
  </script>
</body>
</html>
