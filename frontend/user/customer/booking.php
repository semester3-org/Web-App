<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Your Bookings - KostHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
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
    }
    .cancel-btn:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body style="padding-top: 50px;"> <!-- kasih jarak sesuai tinggi navbar -->

<?php include("navbar.php"); ?>

<div class="container my-5">
  <h4 class="fw-bold mb-4">Your Bookings</h4>

  <div class="table-responsive">
    <table class="table align-middle text-center">
      <thead class="table-light">
        <tr>
          <th>Booking ID</th>
          <th>Property Name</th>
          <th>Room Type</th>
          <th>Check-in</th>
          <th>Check-out</th>
          <th>Total Price</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <!-- Booking 1 -->
        <tr>
          <td><a href="#" class="text-decoration-none text-primary">#12345</a></td>
          <td>Cozy Apartment in Downtown</td>
          <td>Studio</td>
          <td>2024-08-15</td>
          <td>2024-08-20</td>
          <td>$500</td>
          <td><span class="status-badge status-pending">Pending</span></td>
          <td><a href="#" class="cancel-btn">Cancel</a></td>
        </tr>
        <!-- Booking 2 -->
        <tr>
          <td><a href="#" class="text-decoration-none text-primary">#67890</a></td>
          <td>Luxury Villa by the Beach</td>
          <td>3-Bedroom</td>
          <td>2024-07-20</td>
          <td>2024-07-27</td>
          <td>$2000</td>
          <td><span class="status-badge status-confirmed">Confirmed</span></td>
          <td>-</td>
        </tr>
        <!-- Booking 3 -->
        <tr>
          <td><a href="#" class="text-decoration-none text-primary">#24680</a></td>
          <td>Mountain View Cabin</td>
          <td>2-Bedroom</td>
          <td>2024-06-10</td>
          <td>2024-06-15</td>
          <td>$750</td>
          <td><span class="status-badge status-completed">Completed</span></td>
          <td>-</td>
        </tr>
        <!-- Booking 4 -->
        <tr>
          <td><a href="#" class="text-decoration-none text-primary">#13579</a></td>
          <td>City Center Loft</td>
          <td>1-Bedroom</td>
          <td>2024-05-01</td>
          <td>2024-05-05</td>
          <td>$400</td>
          <td><span class="status-badge status-canceled">Canceled</span></td>
          <td>-</td>
        </tr>
        <!-- Booking 5 -->
        <tr>
          <td><a href="#" class="text-decoration-none text-primary">#97531</a></td>
          <td>Lakeside Cottage</td>
          <td>1-Bedroom</td>
          <td>2024-09-01</td>
          <td>2024-09-07</td>
          <td>$600</td>
          <td><span class="status-badge status-pending">Pending</span></td>
          <td><a href="#" class="cancel-btn">Cancel</a></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
