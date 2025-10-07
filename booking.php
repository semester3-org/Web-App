<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Booking - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      padding-top: 80px; /* jarak agar navbar tidak menutupi konten */
    }

    .table {
      border-radius: 12px;
      overflow: hidden;
      background: white;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .status-badge {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-confirmed { background: #d4edda; color: #155724; }
    .status-completed { background: #d1ecf1; color: #0c5460; }
    .status-canceled { background: #f8d7da; color: #721c24; }

    .cancel-btn {
      color: #dc3545;
      text-decoration: none;
      font-weight: 600;
      cursor: pointer;
    }

    .cancel-btn:hover {
      text-decoration: underline;
    }

    .table-hover tbody tr:hover {
      background-color: #f1f3f5;
      transition: 0.2s ease;
    }
  </style>
</head>
<body>

<?php include("frontend/component/navbar.php"); ?>

<div class="container my-5">
  <h4 class="fw-bold mb-4">Booking KostHub</h4>

  <div class="table-responsive">
    <table class="table table-hover align-middle text-center">
      <thead class="table-light">
        <tr>
          <th>Booking ID</th>
          <th>Nama Kost</th>
          <th>Tipe Kamar</th>
          <th>Check-in</th>
          <th>Check-out</th>
          <th>Total Harga</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <!-- Booking 1 -->
        <tr>
          <td><a href="#" onclick="alert('Silakan login untuk melihat detail booking!')" class="text-decoration-none text-primary">#B12345</a></td>
          <td>Kos Harmoni</td>
          <td>Standar</td>
          <td>2024-08-15</td>
          <td>2024-08-20</td>
          <td>Rp 500.000</td>
          <td><span class="status-badge status-pending">Pending</span></td>
          <td><span class="cancel-btn" onclick="alert('Silakan login untuk membatalkan booking!')">Cancel</span></td>
        </tr>
        <!-- Booking 2 -->
        <tr>
          <td><a href="#" onclick="alert('Silakan login untuk melihat detail booking!')" class="text-decoration-none text-primary">#B67890</a></td>
          <td>Kos Mawar Indah</td>
          <td>Premium</td>
          <td>2024-07-20</td>
          <td>2024-07-27</td>
          <td>Rp 1.200.000</td>
          <td><span class="status-badge status-confirmed">Confirmed</span></td>
          <td>-</td>
        </tr>
        <!-- Booking 3 -->
        <tr>
          <td><a href="#" onclick="alert('Silakan login untuk melihat detail booking!')" class="text-decoration-none text-primary">#B24680</a></td>
          <td>Kos Sejahtera</td>
          <td>Deluxe</td>
          <td>2024-06-10</td>
          <td>2024-06-15</td>
          <td>Rp 800.000</td>
          <td><span class="status-badge status-completed">Completed</span></td>
          <td>-</td>
        </tr>
        <!-- Booking 4 -->
        <tr>
          <td><a href="#" onclick="alert('Silakan login untuk melihat detail booking!')" class="text-decoration-none text-primary">#B13579</a></td>
          <td>Kos Bahagia</td>
          <td>Single</td>
          <td>2024-05-01</td>
          <td>2024-05-05</td>
          <td>Rp 400.000</td>
          <td><span class="status-badge status-canceled">Canceled</span></td>
          <td>-</td>
        </tr>
        <!-- Booking 5 -->
        <tr>
          <td><a href="#" onclick="alert('Silakan login untuk melihat detail booking!')" class="text-decoration-none text-primary">#B97531</a></td>
          <td>Kos Pelangi</td>
          <td>Standar</td>
          <td>2024-09-01</td>
          <td>2024-09-07</td>
          <td>Rp 650.000</td>
          <td><span class="status-badge status-pending">Pending</span></td>
          <td><span class="cancel-btn" onclick="alert('Silakan login untuk membatalkan booking!')">Cancel</span></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
