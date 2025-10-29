<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Booking List - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">  
  <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">

  <style>
    .status-badge {
      padding: 5px 10px;
      border-radius: 12px;
      font-size: 0.85rem;
      cursor: pointer;
    }
    .status-pending { background: #fff3cd; color: #856404; cursor: pointer; }
    .status-confirmed { background: #d4edda; color: #155724; cursor: default; }
    .status-canceled { background: #f8d7da; color: #721c24; cursor: default; }
    .action-btn { cursor: pointer; color: #dc3545; }
    .action-btn:hover { color: #a71d2a; }
  </style>
</head>
<body class="bg-light">

<?php include "../includes/navbar.php"; ?>


<div class="container py-4">
  <h3 class="fw-bold mb-4"><i class="bi bi-calendar-check me-2"></i>Booking List</h3>

  <div class="card shadow-sm">
    <div class="card-body">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>ID Booking</th>
            <th>Username</th>
            <th>Check-In</th>
            <th>Check-Out</th>
            <th>Tipe</th>
            <th>Total Harga</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>#B001</td>
            <td>iqbaaltg</td>
            <td>2025-09-25</td>
            <td>2025-09-28</td>
            <td>Kos</td>
            <td>Rp 400.000</td>
            <td>
              <span class="status-badge status-pending" data-bs-toggle="modal" data-bs-target="#statusModal">Pending</span>
            </td>
            <td><i class="bi bi-trash action-btn" onclick="deleteBooking(this)"></i></td>
          </tr>
          <tr>
            <td>#B002</td>
            <td>naufalzr</td>
            <td>2025-10-01</td>
            <td>2025-10-10</td>
            <td>Apartemen</td>
            <td>Rp 1.200.000</td>
            <td><span class="status-badge status-confirmed">Confirmed</span></td>
            <td><i class="bi bi-trash action-btn" onclick="deleteBooking(this)"></i></td>
          </tr>
          <tr>
            <td>#B003</td>
            <td>andikaptr</td>
            <td>2025-09-20</td>
            <td>2025-09-22</td>
            <td>Rumah</td>
            <td>Rp 700.000</td>
            <td><span class="status-badge status-canceled">Canceled</span></td>
            <td><i class="bi bi-trash action-btn" onclick="deleteBooking(this)"></i></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Update Status -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Status Booking</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <p>Pilih status baru untuk booking ini:</p>
        <div class="d-flex justify-content-center gap-2">
          <button class="btn btn-success" onclick="updateStatus('Confirmed')">Confirm</button>
          <button class="btn btn-danger" onclick="updateStatus('Canceled')">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  let currentBadge = null;

  // hanya untuk status Pending
  document.querySelectorAll(".status-pending").forEach(badge => {
    badge.addEventListener("click", function() {
      currentBadge = this;
    });
  });

  function updateStatus(newStatus) {
    if (currentBadge) {
      currentBadge.classList.remove("status-pending");
      currentBadge.removeAttribute("data-bs-toggle");
      currentBadge.removeAttribute("data-bs-target");

      if (newStatus === "Confirmed") {
        currentBadge.classList.add("status-confirmed");
        currentBadge.textContent = "Confirmed";
      } else {
        currentBadge.classList.add("status-canceled");
        currentBadge.textContent = "Canceled";
      }

      // setelah diubah, tidak bisa diklik lagi
      currentBadge.style.cursor = "default";

      // tutup modal
      let modal = bootstrap.Modal.getInstance(document.getElementById('statusModal'));
      modal.hide();
    }
  }

  function deleteBooking(element) {
    if (confirm("Apakah Anda yakin ingin menghapus booking ini?")) {
      element.closest("tr").remove();
    }
  }
</script>

</body>
</html>
