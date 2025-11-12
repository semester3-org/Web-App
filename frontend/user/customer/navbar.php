<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

// Cek login
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user';

// Default profile
$profilePic = '/Web-App/frontend/assets/default-avatar.png';
$fullName = 'Guest';

// Jika login, ambil data user
if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT full_name, profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        $profilePic = (!empty($user['profile_picture']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $user['profile_picture']))
            ? $user['profile_picture']
            : '/Web-App/frontend/assets/default-avatar.png';
        $fullName = htmlspecialchars($user['full_name']);
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top" style="height:65px; z-index:1050;">
  <div class="container-fluid px-4">

    <!-- Brand -->
    <a class="navbar-brand fw-bold d-flex align-items-center" href="/Web-App/frontend/user/customer/home.php">
      <img src="/Web-App/frontend/assets/logo_kos.png" alt="logo" style="height:30px;" class="me-2">
      KostHub
    </a>

    <!-- Toggle (mobile) -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Menu -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'home.php' ? 'fw-bold text-success' : ''; ?>" 
             href="/Web-App/frontend/user/customer/home.php">
            Home
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'explore.php' ? 'fw-bold text-success' : ''; ?>" 
             href="/Web-App/frontend/user/customer/explore.php">
            Explore
          </a>
        </li>

        <!-- Wishlist -->
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'wishlist.php' ? 'fw-bold text-success' : ''; ?>"
             href="<?php echo $isLoggedIn ? '/Web-App/frontend/user/customer/wishlist.php' : '#'; ?>"
             <?php if (!$isLoggedIn): ?>onclick="showLoginAlert(event)"<?php endif; ?>>
            Wishlist
          </a>
        </li>

        <!-- Booking -->
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'booking.php' ? 'fw-bold text-success' : ''; ?>"
             href="<?php echo $isLoggedIn ? '/Web-App/frontend/user/customer/booking.php' : '#'; ?>"
             <?php if (!$isLoggedIn): ?>onclick="showLoginAlert(event)"<?php endif; ?>>
            Your Booking
          </a>
        </li>
      </ul>

      <!-- Bagian kanan navbar -->
      <div class="d-flex align-items-center">
        <?php if (!$isLoggedIn): ?>
          <!-- Jika belum login -->
          <a href="/Web-App/frontend/auth/login.php" class="btn btn-success btn-sm">Login</a>
        <?php else: ?>
          <!-- Notifikasi Dropdown -->
          <div class="dropdown me-3">
            <a href="#" class="notification-bell-wrapper" 
               id="notificationDropdown"
               data-bs-toggle="dropdown"
               aria-expanded="false">
              <div class="notification-bell">
                <i class="bi bi-bell-fill"></i>
                <span id="notifBadge" class="notification-badge" style="display: none;">0</span>
                <span class="bell-ring"></span>
              </div>
            </a>
            
            <div class="dropdown-menu dropdown-menu-end notification-dropdown shadow-lg" 
                 aria-labelledby="notificationDropdown">
              
              <!-- Header -->
              <div class="notification-header">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <h5 class="mb-0 fw-bold">Notifikasi</h5>
                    <small class="text-muted" id="notifSubtitle">Tidak ada notifikasi baru</small>
                  </div>
                  <button class="btn-mark-all" id="markAllReadBtn" title="Tandai semua dibaca">
                    <i class="bi bi-check-all"></i>
                  </button>
                </div>
              </div>
              
              <!-- Notification List -->
              <div id="notificationList" class="notification-list">
                <div class="notification-empty">
                  <div class="empty-illustration">
                    <i class="bi bi-bell-slash"></i>
                  </div>
                  <h6 class="mb-1">Belum ada notifikasi</h6>
                  <small class="text-muted">Notifikasi Anda akan muncul di sini</small>
                </div>
              </div>

              <!-- Footer -->
              <div class="notification-footer">
                <a href="#" class="text-center d-block text-decoration-none">
                  <small class="text-success fw-semibold">Lihat Semua Notifikasi</small>
                </a>
              </div>
            </div>
          </div>

          <!-- Dropdown profil -->
          <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-dark text-decoration-none"
               id="profileDropdown"
               data-bs-toggle="dropdown"
               aria-expanded="false">
              <img src="<?= htmlspecialchars($profilePic) ?>" alt="profile"
                   class="rounded-circle border"
                   style="height:35px; width:35px; object-fit:cover;">
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="profileDropdown">
              <li class="dropdown-item-text fw-semibold text-center"><?= $fullName ?></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="/Web-App/frontend/user/customer/profile.php">
                <i class="bi bi-person-circle me-2"></i> Profile
              </a></li>
              <li><a class="dropdown-item text-danger" href="/Web-App/logout.php">
                <i class="bi bi-box-arrow-right me-2"></i> Log Out
              </a></li>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- 🔒 Modal: Login Diperlukan -->
<div class="modal fade" id="loginAlertModal" tabindex="-1" aria-labelledby="loginAlertLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="loginAlertLabel"><i class="bi bi-lock-fill me-2"></i>Login Diperlukan</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <p class="mb-4 fs-6">Anda harus login terlebih dahulu untuk mengakses halaman ini.</p>
        <div class="d-flex justify-content-center gap-3">
          <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
          <a href="/Web-App/frontend/auth/login.php" class="btn btn-success px-4">Login Sekarang</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- CSS untuk Notifikasi -->
<style>
/* ==================== Notification Bell ==================== */
.notification-bell-wrapper {
  position: relative;
  display: inline-block;
  text-decoration: none;
  cursor: pointer;
}

.notification-bell {
  position: relative;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  transition: all 0.3s ease;
}

.notification-bell:hover {
  background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
  transform: scale(1.05);
}

.notification-bell i {
  font-size: 1.25rem;
  color: #495057;
  transition: all 0.3s ease;
}

.notification-bell:hover i {
  color: #28a745;
  animation: bellRing 0.5s ease-in-out;
}

@keyframes bellRing {
  0%, 100% { transform: rotate(0deg); }
  25% { transform: rotate(15deg); }
  50% { transform: rotate(-15deg); }
  75% { transform: rotate(10deg); }
}

.notification-badge {
  position: absolute;
  top: -2px;
  right: -2px;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
  font-size: 0.65rem;
  font-weight: 700;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
  animation: badgePulse 2s infinite;
}

@keyframes badgePulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

.bell-ring {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
  height: 100%;
  border-radius: 50%;
  border: 2px solid #28a745;
  opacity: 0;
}

.notification-bell:hover .bell-ring {
  animation: ringPulse 1.5s ease-out;
}

@keyframes ringPulse {
  0% {
    transform: translate(-50%, -50%) scale(1);
    opacity: 0.5;
  }
  100% {
    transform: translate(-50%, -50%) scale(1.8);
    opacity: 0;
  }
}

/* ==================== Dropdown ==================== */
.notification-dropdown {
  width: 420px;
  max-width: 95vw;
  border: none;
  border-radius: 16px;
  overflow: hidden;
  margin-top: 12px;
  animation: dropdownSlide 0.3s ease-out;
}

@keyframes dropdownSlide {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ==================== Header ==================== */
.notification-header {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  color: white;
  padding: 20px;
  position: relative;
  overflow: hidden;
}

.notification-header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -20%;
  width: 200px;
  height: 200px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
}

.notification-header h5 {
  color: white;
  font-size: 1.1rem;
  margin-bottom: 2px;
}

.notification-header small {
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.8rem;
}

.btn-mark-all {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  cursor: pointer;
}

.btn-mark-all:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: scale(1.1);
}

.btn-mark-all i {
  font-size: 1.2rem;
}

/* ==================== List ==================== */
.notification-list {
  max-height: 420px;
  overflow-y: auto;
  background: #fff;
}

.notification-list::-webkit-scrollbar {
  width: 6px;
}

.notification-list::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.notification-list::-webkit-scrollbar-thumb {
  background: #28a745;
  border-radius: 10px;
}

.notification-list::-webkit-scrollbar-thumb:hover {
  background: #218838;
}

/* ==================== Empty State ==================== */
.notification-empty {
  padding: 60px 20px;
  text-align: center;
}

.empty-illustration {
  width: 80px;
  height: 80px;
  margin: 0 auto 20px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-illustration i {
  font-size: 2.5rem;
  color: #adb5bd;
}

.notification-empty h6 {
  color: #495057;
  font-weight: 600;
}

.notification-empty small {
  color: #6c757d;
}

/* ==================== Notification Item ==================== */
.notification-item {
  display: block;
  padding: 16px 20px;
  border-bottom: 1px solid #f1f3f5;
  text-decoration: none;
  color: inherit;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.notification-item::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  width: 4px;
  background: transparent;
  transition: all 0.3s ease;
}

.notification-item:hover {
  background: linear-gradient(90deg, #f8f9fa 0%, #ffffff 100%);
  transform: translateX(4px);
}

.notification-item:hover::before {
  background: #28a745;
}

.notification-item.unread {
  background: linear-gradient(90deg, #e8f5e9 0%, #f1f9f2 100%);
}

.notification-item.unread::before {
  background: #28a745;
}

.notification-item.unread:hover {
  background: linear-gradient(90deg, #d4edda 0%, #e8f5e9 100%);
}

/* ==================== Icon Styles ==================== */
.notification-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  flex-shrink: 0;
  position: relative;
  overflow: hidden;
}

.notification-icon::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -50%;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(45deg);
}

.notification-icon.approved {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.notification-icon.rejected {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.notification-icon.general {
  background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
  box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
}

.notification-icon i {
  font-size: 1.5rem;
  color: white;
  position: relative;
  z-index: 1;
}

/* ==================== Content ==================== */
.notification-content {
  flex-grow: 1;
}

.notification-title {
  font-size: 0.95rem;
  font-weight: 600;
  color: #212529;
  margin-bottom: 4px;
  line-height: 1.4;
}

.notification-message {
  font-size: 0.85rem;
  color: #6c757d;
  margin-bottom: 6px;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.notification-property {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 10px;
  background: linear-gradient(135deg, #e8f5e9 0%, #f1f9f2 100%);
  border-radius: 12px;
  font-size: 0.75rem;
  color: #28a745;
  font-weight: 600;
  margin-bottom: 6px;
}

.notification-property i {
  font-size: 0.8rem;
}

.notification-time {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.75rem;
  color: #adb5bd;
  font-weight: 500;
}

.notification-time i {
  font-size: 0.7rem;
}

.notification-item.unread .notification-time {
  color: #28a745;
  font-weight: 600;
}

/* ==================== Unread Dot ==================== */
.unread-dot {
  width: 8px;
  height: 8px;
  background: #28a745;
  border-radius: 50%;
  margin-left: auto;
  flex-shrink: 0;
  animation: dotPulse 2s infinite;
}

@keyframes dotPulse {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
  }
  50% {
    box-shadow: 0 0 0 6px rgba(40, 167, 69, 0);
  }
}

/* ==================== Footer ==================== */
.notification-footer {
  padding: 12px 20px;
  background: #f8f9fa;
  border-top: 1px solid #e9ecef;
}

.notification-footer a {
  transition: all 0.3s ease;
}

.notification-footer a:hover {
  color: #28a745 !important;
  text-decoration: underline !important;
}

/* ==================== Responsive ==================== */
@media (max-width: 768px) {
  .notification-dropdown {
    width: 100vw;
    max-width: 100vw;
    border-radius: 0;
    margin-top: 0;
  }

  .notification-list {
    max-height: 60vh;
  }

  .notification-item {
    padding: 14px 16px;
  }

  .notification-icon {
    width: 42px;
    height: 42px;
  }
}

/* ==================== Loading State ==================== */
.notification-loading {
  padding: 40px 20px;
  text-align: center;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #28a745;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>

<script>
<?php if ($isLoggedIn): ?>
// Fungsi untuk load notifikasi
function loadNotifications() {
    fetch('/Web-App/backend/user/customer/classes/notifications.php?action=get_notifications&limit=10')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.notifications) {
                updateNotificationUI(data.notifications);
            }
        })
        .catch(error => console.error('Error loading notifications:', error));
}

// Fungsi untuk update unread count
function updateUnreadCount() {
    fetch('/Web-App/backend/user/customer/classes/notifications.php?action=get_count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById('notifBadge');
                const subtitle = document.getElementById('notifSubtitle');
                
                if (data.count > 0) {
                    badge.textContent = data.count > 99 ? '99+' : data.count;
                    badge.style.display = 'flex';
                    subtitle.textContent = `${data.count} notifikasi baru`;
                } else {
                    badge.style.display = 'none';
                    subtitle.textContent = 'Tidak ada notifikasi baru';
                }
            }
        })
        .catch(error => console.error('Error updating count:', error));
}

// Update UI notifikasi
function updateNotificationUI(notifications) {
    const listContainer = document.getElementById('notificationList');
    
    if (notifications.length === 0) {
        listContainer.innerHTML = `
            <div class="notification-empty">
                <div class="empty-illustration">
                    <i class="bi bi-bell-slash"></i>
                </div>
                <h6 class="mb-1">Belum ada notifikasi</h6>
                <small class="text-muted">Notifikasi Anda akan muncul di sini</small>
            </div>
        `;
        return;
    }
    
    let html = '';
    notifications.forEach(notif => {
        const iconClass = notif.type === 'property_approved' ? 'approved' : 
                         notif.type === 'property_rejected' ? 'rejected' : 'general';
        const icon = notif.type === 'property_approved' ? 'bi-check-circle-fill' : 
                    notif.type === 'property_rejected' ? 'bi-x-circle-fill' : 'bi-info-circle-fill';
        const unreadClass = notif.is_read == 0 ? 'unread' : '';
        
        html += `
            <a href="#" class="notification-item ${unreadClass}" 
               data-notif-id="${notif.id}" data-kos-id="${notif.kos_id || ''}">
                <div class="d-flex align-items-start gap-3">
                    <div class="notification-icon ${iconClass}">
                        <i class="${icon}"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${notif.title}</div>
                        <div class="notification-message">${notif.message}</div>
                        ${notif.property_name ? `
                            <div class="notification-property">
                                <i class="bi bi-house-door-fill"></i>
                                <span>${notif.property_name}</span>
                            </div>
                        ` : ''}
                        <div class="notification-time">
                            <i class="bi bi-clock"></i>
                            <span>${notif.time_ago}</span>
                        </div>
                    </div>
                    ${notif.is_read == 0 ? '<div class="unread-dot"></div>' : ''}
                </div>
            </a>
        `;
    });
    
    listContainer.innerHTML = html;
    
    // Add click handlers
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const notifId = this.dataset.notifId;
            const kosId = this.dataset.kosId;
            
            markAsRead(notifId);
            
            // Redirect ke detail property jika ada
            if (kosId) {
                window.location.href = `/Web-App/frontend/user/customer/detail_kos.php?id=${kosId}`;
            }
        });
    });
}

// Mark notification as read
function markAsRead(notifId) {
    fetch('/Web-App/backend/user/customer/classes/notifications.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=mark_read&notification_id=${notifId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateUnreadCount();
            loadNotifications();
        }
    })
    .catch(error => console.error('Error marking as read:', error));
}

// Mark all as read
document.getElementById('markAllReadBtn')?.addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    fetch('/Web-App/backend/user/customer/classes/notifications.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=mark_all_read'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateUnreadCount();
            loadNotifications();
        }
    })
    .catch(error => console.error('Error marking all as read:', error));
});

// Load notifications on dropdown open
document.getElementById('notificationDropdown')?.addEventListener('show.bs.dropdown', function() {
    loadNotifications();
});

// Initial load
document.addEventListener('DOMContentLoaded', function() {
    updateUnreadCount();
    
    // Auto refresh setiap 30 detik
    setInterval(updateUnreadCount, 30000);
});
<?php endif; ?>

function showLoginAlert(e) {
    e.preventDefault();
    const modal = new bootstrap.Modal(document.getElementById('loginAlertModal'));
    modal.show();
}
</script>