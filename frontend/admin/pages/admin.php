<?php
// frontend/admin/pages/admin.php
session_start();
require_once '../../../backend/admin/classes/AdminManager.php';

// Check if user is superadmin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'superadmin') {
    header('Location: ../../index.php');
    exit();
}

$adminManager = new AdminManager();
$admins = $adminManager->getAllAdmins();
$stats = $adminManager->getAdminStats();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Admin - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-user-shield"></i> Master Admin</h1>
            <button class="btn-add" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Tambah Admin
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['total_admins'] ?></h3>
                    <p>Total Admin</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['total_superadmin'] ?></h3>
                    <p>Super Admin</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['total_admin'] ?></h3>
                    <p>Admin Biasa</p>
                </div>
            </div>
        </div>

        <!-- Admin Table -->
        <div class="table-container">
            <div class="table-header">
                <h2>Daftar Admin</h2>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari admin...">
                </div>
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
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($admins) > 0): ?>
                        <?php foreach ($admins as $index => $admin): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($admin['username']) ?></td>
                                <td><?= htmlspecialchars($admin['email']) ?></td>
                                <td><?= htmlspecialchars($admin['full_name']) ?></td>
                                <td><?= htmlspecialchars($admin['phone'] ?? '-') ?></td>
                                <td>
                                    <span class="badge <?= $admin['user_type'] === 'superadmin' ? 'badge-superadmin' : 'badge-admin' ?>">
                                        <?= $admin['user_type'] === 'superadmin' ? 'Super Admin' : 'Admin' ?>
                                    </span>
                                </td>
                                <td><?= date('d M Y', strtotime($admin['created_at'])) ?></td>
                                <td>
                                    <button class="btn-action btn-edit" onclick="editAdmin(<?= $admin['id'] ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($admin['user_type'] !== 'superadmin'): ?>
                                        <button class="btn-action btn-delete" onclick="deleteAdmin(<?= $admin['id'] ?>)" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="empty-state">Tidak ada data admin</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="adminModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Admin</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="adminForm">
                <input type="hidden" id="adminId" name="id">

                <div class="form-group">
                    <label>Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Nama Lengkap <span class="required">*</span></label>
                    <input type="text" id="fullName" name="full_name" required>
                </div>

                <div class="form-group">
                    <label>No. Telepon</label>
                    <input type="text" id="phone" name="phone">
                </div>

                <div class="form-group">
                    <label>Tipe Admin <span class="required">*</span></label>
                    <select id="userType" name="user_type" required>
                        <option value="admin">Admin</option>
                        <option value="superadmin">Super Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label id="passwordLabel">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password">
                    <small class="form-hint">Kosongkan jika tidak ingin mengubah password</small>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/admin.js"></script>
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