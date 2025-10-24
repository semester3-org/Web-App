<?php
// frontend/admin/pages/users.php
session_start();
require_once '../../../backend/admin/classes/UserManager.php';

// Check if user is superadmin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'superadmin') {
    header('Location: ../../index.php');
    exit();
}

$userManager = new UserManager();
$users = $userManager->getAllUsers();
$stats = $userManager->getUserStats();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Users - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/users.css">
    
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-users"></i> Master Users</h1>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['total_users'] ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['total_regular_users'] ?></h3>
                    <p>User Biasa</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-home"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['total_owners'] ?></h3>
                    <p>Owner</p>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari nama, username, atau email...">
            </div>
            <select id="filterType" class="filter-select">
                <option value="">Semua Tipe</option>
                <option value="user">User Biasa</option>
                <option value="owner">Owner</option>
            </select>
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <div class="table-header">
                <h2>Daftar Users</h2>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Nama Lengkap</th>
                        <th>No. Telepon</th>
                        <th>Tipe</th>
                        <th>Total Properti</th>
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $index => $user): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['full_name']) ?></td>
                                <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                                <td>
                                    <span class="badge <?= $user['user_type'] === 'owner' ? 'badge-owner' : 'badge-user' ?>">
                                        <?= $user['user_type'] === 'owner' ? 'Owner' : 'User' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['total_properties'] > 0): ?>
                                        <span class="property-count"><?= $user['total_properties'] ?> Properti</span>
                                    <?php else: ?>
                                        <span class="no-property">0 Properti</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewUser(<?= $user['id'] ?>)" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-action btn-delete" onclick="deleteUser(<?= $user['id'] ?>)" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="empty-state">Tidak ada data user</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- User Detail Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2><i class="fas fa-user-circle"></i> Detail User</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="userDetailContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>

    <script src="../js/users.js"></script>
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