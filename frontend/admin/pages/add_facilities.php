<?php
session_start();

require_once "../../../backend/config/db.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Facility</title>
  <link rel="stylesheet" href="../../assets/css/admin.css"> <!-- opsional -->
  <style>
    body { font-family: Arial, sans-serif; background:#f9f9f9; padding:20px; }
    .container { max-width:600px; margin:auto; background:#fff; padding:20px; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
    h2 { margin-bottom:20px; }
    label { display:block; margin:10px 0 5px; font-weight:bold; }
    input[type="text"], select {
      width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;
    }
    .btn { margin-top:15px; padding:10px 20px; border:none; border-radius:6px; cursor:pointer; }
    .btn-primary { background:#4CAF50; color:white; }
    .btn-secondary { background:#ccc; color:#333; margin-left:10px; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Tambah Fasilitas Baru</h2>
    <form action="../../../backend/admin/classes/add_facilities_process.php" method="POST">
      <!-- Nama Fasilitas -->
      <label for="name">Nama Fasilitas</label>
      <input type="text" id="name" name="name" required placeholder="Contoh: AC, WiFi, Parkir Motor">

      <!-- Ikon -->
      <label for="icon">Ikon (opsional)</label>
      <input type="text" id="icon" name="icon" placeholder="Contoh: wifi.png atau fa fa-wifi">

      <!-- Kategori -->
      <label for="category">Kategori</label>
      <select id="category" name="category" required>
        <option value="">-- Pilih Kategori --</option>
        <option value="room">Room</option>
        <option value="bathroom">Bathroom</option>
        <option value="common">Common</option>
        <option value="parking">Parking</option>
        <option value="security">Security</option>
      </select>

      <!-- Tombol -->
      <button type="submit" class="btn btn-primary">Simpan</button>
      <a href="facilities_list.php" class="btn btn-secondary">Batal</a>
    </form>
  </div>
</body>
</html>
