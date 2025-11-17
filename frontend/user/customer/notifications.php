<?php
session_start();
require_once "../../../backend/config/db.php";

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: /Web-App/frontend/auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - KostHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            padding-top: 65px; /* Sesuai tinggi navbar */
            margin: 0;
        }
        
        .page-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-top: 0; /* Hapus margin top */
        }
        
        .filter-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        
        .notification-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .notification-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .notification-card.unread {
            background: linear-gradient(90deg, #e8f5e9 0%, #f1f9f2 100%);
            border-left: 4px solid #28a745;
        }
        
        .notif-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            flex-shrink: 0;
        }
        
        .notif-icon.approved {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .notif-icon.rejected {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .notif-title {
            font-size: 1rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 0.5rem;
        }
        
        .notif-message {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.75rem;
            line-height: 1.6;
        }
        
        .notif-property {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: #e8f5e9;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #28a745;
            font-weight: 600;
        }
        
        .notif-time {
            font-size: 0.85rem;
            color: #adb5bd;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .badge-unread {
            background: #28a745;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-state i {
            font-size: 5rem;
            color: #dee2e6;
            margin-bottom: 1.5rem;
        }
        
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }
    </style>
</head>
<body>

    <!-- Navbar Customer -->
    <?php 
    // Include navbar customer
    $navbar_path = __DIR__ . '/navbar.php';
    if (file_exists($navbar_path)) {
        include 'navbar.php';
    } else {
        // Jika tidak ada, gunakan navbar sederhana
        echo '<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top" style="height:65px; z-index:1050;">
                <div class="container-fluid px-4">
                    <a class="navbar-brand fw-bold" href="/Web-App/frontend/user/customer/home.php">
                        <img src="/Web-App/frontend/assets/logo_kos.png" alt="logo" style="height:30px;" class="me-2">
                        KostHub
                    </a>
                    <div class="d-flex">
                        <a href="/Web-App/frontend/user/customer/home.php" class="btn btn-outline-success btn-sm me-2">
                            <i class="bi bi-house-door"></i> Home
                        </a>
                        <a href="/Web-App/logout.php" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
              </nav>';
    }
    ?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"><i class="bi bi-bell-fill me-2"></i>Notifikasi</h2>
                    <p class="mb-0 opacity-75">Semua pemberitahuan untuk Anda</p>
                </div>
                <button id="markAllReadBtn" class="btn btn-light">
                    <i class="bi bi-check-all me-2"></i>Tandai Semua Dibaca
                </button>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="unread">Belum Dibaca</option>
                        <option value="read">Sudah Dibaca</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Tipe</label>
                    <select id="filterType" class="form-select">
                        <option value="">Semua Tipe</option>
                        <option value="property_approved">Booking Disetujui</option>
                        <option value="property_rejected">Booking Ditolak</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">&nbsp;</label>
                    <button id="resetFilter" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-clockwise me-2"></i>Reset Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Notifications List -->
        <div id="notificationsList">
            <!-- Loading State -->
            <div id="loadingState" class="text-center py-5">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Memuat notifikasi...</p>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="empty-state" style="display: none;">
                <i class="bi bi-bell-slash"></i>
                <h4>Belum Ada Notifikasi</h4>
                <p class="text-muted">Notifikasi Anda akan muncul di sini</p>
            </div>

            <!-- Notifications Container -->
            <div id="notificationsContainer"></div>
        </div>

        <!-- Pagination -->
        <div class="pagination-container" id="paginationContainer" style="display: none;">
            <nav>
                <ul class="pagination" id="pagination"></ul>
            </nav>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentPage = 1;
        const limit = 10;
        
        // Load notifications
        function loadNotifications() {
            const status = document.getElementById('filterStatus').value;
            const type = document.getElementById('filterType').value;
            
            let url = `/Web-App/backend/user/customer/classes/notifications.php?action=get_notifications&limit=${limit}&page=${currentPage}`;
            if (status) url += `&status=${status}`;
            if (type) url += `&type=${type}`;
            
            document.getElementById('loadingState').style.display = 'block';
            document.getElementById('notificationsContainer').innerHTML = '';
            document.getElementById('emptyState').style.display = 'none';
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loadingState').style.display = 'none';
                    
                    if (data.success && data.notifications.length > 0) {
                        renderNotifications(data.notifications);
                        renderPagination(data.total, data.current_page, data.total_pages);
                    } else {
                        document.getElementById('emptyState').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('loadingState').style.display = 'none';
                    document.getElementById('emptyState').style.display = 'block';
                });
        }
        
        // Render notifications
        function renderNotifications(notifications) {
            const container = document.getElementById('notificationsContainer');
            container.innerHTML = '';
            
            notifications.forEach(notif => {
                const iconClass = notif.type === 'property_approved' ? 'approved' : 'rejected';
                const icon = notif.type === 'property_approved' ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
                const unreadClass = notif.is_read == 0 ? 'unread' : '';
                
                const card = document.createElement('div');
                card.className = `notification-card ${unreadClass}`;
                card.onclick = () => handleNotificationClick(notif.id, notif.kos_id);
                
                card.innerHTML = `
                    <div class="d-flex gap-3">
                        <div class="notif-icon ${iconClass}">
                            <i class="bi ${icon}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="notif-title">${notif.title}</div>
                                ${notif.is_read == 0 ? '<span class="badge-unread">Baru</span>' : ''}
                            </div>
                            <div class="notif-message">${notif.message}</div>
                            ${notif.property_name ? `
                                <div class="mb-2">
                                    <span class="notif-property">
                                        <i class="bi bi-house-door-fill"></i>
                                        ${notif.property_name}
                                    </span>
                                </div>
                            ` : ''}
                            <div class="notif-time">
                                <i class="bi bi-clock"></i>
                                <span>${notif.time_ago}</span>
                            </div>
                        </div>
                    </div>
                `;
                
                container.appendChild(card);
            });
        }
        
        // Render pagination
        function renderPagination(total, current, totalPages) {
            if (totalPages <= 1) {
                document.getElementById('paginationContainer').style.display = 'none';
                return;
            }
            
            document.getElementById('paginationContainer').style.display = 'flex';
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';
            
            // Previous button
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${current === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${current - 1}); return false;">Previous</a>`;
            pagination.appendChild(prevLi);
            
            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= current - 2 && i <= current + 2)) {
                    const li = document.createElement('li');
                    li.className = `page-item ${i === current ? 'active' : ''}`;
                    li.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>`;
                    pagination.appendChild(li);
                } else if (i === current - 3 || i === current + 3) {
                    const li = document.createElement('li');
                    li.className = 'page-item disabled';
                    li.innerHTML = '<a class="page-link">...</a>';
                    pagination.appendChild(li);
                }
            }
            
            // Next button
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${current === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${current + 1}); return false;">Next</a>`;
            pagination.appendChild(nextLi);
        }
        
        // Change page
        function changePage(page) {
            currentPage = page;
            loadNotifications();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Handle notification click
        function handleNotificationClick(notifId, kosId) {
            // Mark as read
            fetch('/Web-App/backend/user/customer/classes/notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=mark_read&notification_id=${notifId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && kosId) {
                    window.location.href = `/Web-App/frontend/user/customer/detail_kos.php?id=${kosId}`;
                } else {
                    loadNotifications();
                }
            });
        }
        
        // Mark all as read
        document.getElementById('markAllReadBtn').addEventListener('click', function() {
            if (!confirm('Tandai semua notifikasi sebagai sudah dibaca?')) return;
            
            fetch('/Web-App/backend/user/customer/classes/notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=mark_all_read'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                    alert('Semua notifikasi telah ditandai sebagai dibaca');
                }
            });
        });
        
        // Filter event listeners
        document.getElementById('filterStatus').addEventListener('change', () => {
            currentPage = 1;
            loadNotifications();
        });
        
        document.getElementById('filterType').addEventListener('change', () => {
            currentPage = 1;
            loadNotifications();
        });
        
        document.getElementById('resetFilter').addEventListener('click', () => {
            document.getElementById('filterStatus').value = '';
            document.getElementById('filterType').value = '';
            currentPage = 1;
            loadNotifications();
        });
        
        // Initial load
        document.addEventListener('DOMContentLoaded', () => {
            loadNotifications();
        });
    </script>
</body>
</html>