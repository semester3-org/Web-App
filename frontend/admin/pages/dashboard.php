<?php
session_start();
require_once __DIR__ . "/../auth/auth_admin.php";
require_once "../../../backend/config/db.php"; // koneksi pakai $conn (MySQLi)

// ======================
// Ambil data dari database
// ======================

// Total customer
$q_customer = $conn->query("SELECT COUNT(*) AS total FROM users WHERE user_type = 'user'");
$total_customer = $q_customer->fetch_assoc()['total'];

// Total owner
$q_owner = $conn->query("SELECT COUNT(*) AS total FROM users WHERE user_type = 'owner'");
$total_owner = $q_owner->fetch_assoc()['total'];

// Total property
$q_property = $conn->query("SELECT COUNT(*) AS total FROM kos");
$total_property = $q_property->fetch_assoc()['total'];

// Grafik property per bulan
$q_chart = $conn->query("
    SELECT MONTH(created_at) AS bulan, COUNT(*) AS total 
    FROM kos 
    GROUP BY MONTH(created_at)
    ORDER BY bulan
");

$bulan_labels = [];
$data_property = [];

// inisialisasi 12 bulan (1–12) dengan 0
for ($i = 1; $i <= 12; $i++) {
    $bulan_labels[$i] = 0;
}

// isi data dari query
while ($row = $q_chart->fetch_assoc()) {
    $bulan_labels[(int)$row['bulan']] = (int)$row['total'];
}

$labels = json_encode(["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"]);
$data_property = json_encode(array_values($bulan_labels));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
</head>
<body>
  <!-- Sidebar -->
  <?php include __DIR__ . "/../includes/sidebar.php"; ?>

  <!-- Content -->
  <main class="content">
  <div class="dashboard-wrapper">
    <h1>Dashboard</h1>

    <!-- Cards -->
    <div class="cards">
      <div class="card">
        <i class="fas fa-users"></i>
        <div><h3>Total Customer</h3><p><?= $total_customer ?></p></div>
      </div>
      <div class="card">
        <i class="fas fa-user-tie"></i>
        <div><h3>Total Owner</h3><p><?= $total_owner ?></p></div>
      </div>
      <div class="card">
        <i class="fas fa-building"></i>
        <div><h3>Total Property</h3><p><?= $total_property ?></p></div>
      </div>
    </div> 

    <!-- Grafik Property -->
    <div class="chart-container">
      <h2>Property Bulan ke Bulan</h2>
      <canvas id="propertyChart"></canvas>
    </div>
  </div>
</main>


  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    const ctx = document.getElementById('propertyChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= $labels ?>,
        datasets: [{
          label: 'Jumlah Property',
          data: <?= $data_property ?>,
          borderColor: "#18b868",
          backgroundColor: "rgba(24, 184, 104, 0.2)",
          fill: true,
          tension: 0.3,
          pointBackgroundColor: "#18b868",
          pointRadius: 5
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 5 } } }
      }
    });
  </script>

  <!-- Script dropdown user -->
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
