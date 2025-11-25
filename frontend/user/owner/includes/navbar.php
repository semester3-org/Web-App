<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../../backend/config/db.php';
require_once __DIR__ . '/../../../../backend/user/owner/classes/Notification.php';

$userId = $_SESSION['user_id'] ?? null;

$profilePic = '/Web-App/frontend/assets/default-avatar.png';
$fullName = 'Owner';

if ($userId) {
    $stmt = $conn->prepare("SELECT full_name, profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        $fullName = htmlspecialchars($user['full_name'] ?? 'Owner');
        $profilePic = $user['profile_picture'] ? $user['profile_picture'] : $profilePic;
    }
    $stmt->close();
}

// Ambil notifikasi langsung → pasti muncul
$notification = new Notification($conn);
$unread_count = $notification->getUnreadCount($userId);
$recent_notifications = [];
$result = $notification->getOwnerNotifications($userId, 6);
while ($row = $result->fetch_assoc()) {
    $recent_notifications[] = $row;
}

function format_time_ago($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->d == 0) {
        if ($diff->h == 0) return $diff->i == 0 ? 'Baru saja' : $diff->i . ' menit lalu';
        return $diff->h . ' jam lalu';
    }
    if ($diff->d == 1) return 'Kemarin';
    if ($diff->d < 7) return $diff->d . ' hari lalu';
    return date('d M Y', strtotime($datetime));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        :root {
            --green: #16a34a;
            --green-light: #f0fdf4;
            --green-lighter: #ecfdf5;
        }
        .navbar { height: 70px; border-bottom: 1px solid #e5e7eb; z-index: 1050; }
        .navbar-brand { font-weight: 700; color: var(--green) !important; font-size: 1.5rem; }
        .nav-link { color: #374151 !important; font-weight: 500; padding: 0.5rem 1rem !important; border-radius: 8px; transition: all 0.2s; }
        .nav-link:hover, .nav-link.active { background: var(--green-light) !important; color: var(--green) !important; font-weight: 600 !important; }

        .notification-bell:hover { transform: scale(1.1); }
        .notification-badge {
            font-size: 0.65rem !important; min-width: 18px; height: 18px;
            animation: pulse 2s infinite; background: #dc2626 !important;
        }
        @keyframes pulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.2); } }

        /* DROPDOWN NOTIFIKASI — INI YANG DI-FIX TOTAL */
        #notifDropdown + .dropdown-menu {
            width: 380px !important;
            max-height: 80vh;
            overflow-y: auto;
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            padding: 0;
        }
        #notifDropdown + .dropdown-menu .dropdown-header {
            background: var(--green-light);
            border-bottom: 1px solid #bbf7d0;
            border-radius: 16px 16px 0 0;
            padding: 1rem 1.25rem;
        }
        #notifDropdown + .dropdown-menu .dropdown-item {
            padding: 0.9rem 1.25rem;
            border-bottom: 1px solid #f3f4f6;
            white-space: normal !important;     /* PENTING: biar wrap */
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        #notifDropdown + .dropdown-menu .dropdown-item:last-child { border-bottom: none; }
        #notifDropdown + .dropdown-menu .dropdown-item:hover { background: var(--green-lighter) !important; }
        #notifDropdown + .dropdown-menu .unread { background: linear-gradient(90deg, #ecfdf5 0%, #fff 100%); font-weight: 500; }

        .notification-icon {
            width: 44px; height: 44px; border-radius: 12px; display: flex;
            align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;
        }
        .icon-success { background: #d1f4e0; color: #059669; }
        .icon-danger  { background: #fee2e2; color: #dc2626; }
        .icon-warning { background: #fef3c7; color: #d97706; }
        .icon-info    { background: #dbeafe; color: #2563eb; }

        .text-kosthub { color: var(--green) !important; }
        .profile-img { width: 42px; height: 42px; object-fit: cover; border: 3px solid var(--green); border-radius: 50%; }
        .btn-kosthub-bottom:hover { background: var(--green) !important; color: white !important; }

        /* ================================
   WRAPPER UTAMA DROPDOWN
================================*/
.notif-dropdown {
    width: 380px;
    max-height: 450px;
    border-radius: 12px;
    overflow: hidden;
    padding: 0 !important;
    border: 1px solid #eaeaea;
}

/* ================================
   HEADER
================================*/
.notif-dropdown .dropdown-header {
    padding: 14px 16px;
    border-bottom: 1px solid #eaeaea;
    background: #fff;
    position: sticky;
    top: 0;
    z-index: 5;
}

/* ================================
   AREA SCROLL
================================*/
.notif-scroll {
    max-height: 1320px; 
    overflow-y: auto;
    background: #fff;
    padding-bottom: 10px;
}

/* Scrollbar lebih halus */
.notif-scroll::-webkit-scrollbar {
    width: 6px;
}
.notif-scroll::-webkit-scrollbar-thumb {
    background: #d3d3d3;
    border-radius: 50px;
}

/* ================================
   LIST ITEM NOTIFIKASI
================================*/
.notif-dropdown li a.dropdown-item {
    padding: 14px 16px;
    border-bottom: 1px solid #f2f2f2;
    transition: background 0.2s ease;
    display: block;
    white-space: normal !important;
}

.notif-dropdown li a.dropdown-item:hover {
    background: #f7f7f7;
}

/* Icon bulat */
.notification-icon {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.icon-success { background: #d2f3d2; color: #188f18; }
.icon-danger { background: #ffd6d6; color: #d11a1a; }
.icon-warning { background: #fff2cc; color: #da9a00; }
.icon-info    { background: #d8ecff; color: #1277d1; }

/* Bold untuk unread */
.unread {
    background: #f0fff0 !important;
}

/* ================================
   FOOTER FIXED DI BAWAH
================================*/
.notif-footer {
    background: #fff;
    border-top: 1px solid #eaeaea;
    position: sticky;
    bottom: 0;
    z-index: 10;
}

.notif-footer a {
    padding: 14px 16px;
    display: block;
    font-weight: 600;
}

    </style>
</head>

<nav class="navbar navbar-expand-lg bg-white fixed-top shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <img src="../../../assets/logo_kos.png" alt="Logo" height="36" class="me-2">
            KostHub
        </a>

        <div class="navbar-nav me-auto d-flex gap-2">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a>
            <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='your_property.php' ? 'active' : '' ?>" href="your_property.php">Your Property</a>
            <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='booking_list.php' ? 'active' : '' ?>" href="booking_list.php">Booking List</a>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- Notification Bell -->
            <div class="dropdown">
                <a href="#" class="text-dark notification-bell position-relative" id="notifDropdown" data-bs-toggle="dropdown">
                    <i class="bi bi-bell fs-4"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill notification-badge" id="notifBadge">
                            <?= $unread_count > 99 ? '99+' : $unread_count ?>
                        </span>
                    <?php endif; ?>
                </a>

                <ul class="dropdown-menu dropdown-menu-end p-0 notif-dropdown">

    <!-- HEADER -->
    <li class="dropdown-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">Notifikasi</h6>
        <div>
            <?php if ($unread_count > 0): ?>
                <span class="badge bg-success text-white"><?= $unread_count ?> Baru</span>
            <?php endif; ?>
            <a href="../pages/notification.php" class="small text-kosthub fw-semibold text-decoration-none ms-2">Lihat semua</a>
        </div>
    </li>

    <!-- SCROLL WRAPPER MULAI -->
    <div class="notif-scroll">

        <?php if (empty($recent_notifications)): ?>
            <li class="text-center py-5 text-muted">
                <i class="bi bi-bell-slash fs-1"></i>
                <p class="small mt-2">Belum ada notifikasi</p>
            </li>

        <?php else: foreach ($recent_notifications as $n): 
            $iconClass = match($n['type'] ?? '') {
                'property_approved' => 'icon-success bi-check-circle-fill',
                'property_rejected' => 'icon-danger bi-x-circle-fill',
                'new_review'        => 'icon-warning bi-star-fill',
                'new_wishlist'      => 'icon-danger bi-heart-fill',
                'new_booking'       => 'icon-info bi-calendar-check',
                default             => 'icon-success bi-bell-fill'
            };
        ?>
            <li>
                <a class="dropdown-item <?= $n['is_read']==0?'unread':'' ?>" href="../pages/notification.php">
                    <div class="d-flex gap-3">
                        <div class="notification-icon <?= explode(' ', $iconClass)[0] ?>">
                            <i class="bi <?= explode(' ', $iconClass)[1] ?>"></i>
                        </div>
                        <div class="flex-grow-1" style="min-width:0;">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <strong class="small text-dark"><?= htmlspecialchars($n['title']) ?></strong>
                                <?php if($n['is_read']==0): ?>
                                    <span class="badge bg-success text-white" style="font-size:0.65rem">Baru</span>
                                <?php endif; ?>
                            </div>
                            <p class="small text-muted mb-1">
                                <?= htmlspecialchars($n['message']) ?>
                            </p>

                            <?php if(!empty($n['kos_name'])): ?>
                                <small class="text-kosthub fw-semibold d-block mb-1">
                                    <i class="bi bi-building me-1"></i><?= htmlspecialchars($n['kos_name']) ?>
                                </small>
                            <?php endif; ?>

                            <small class="text-muted d-block">
                                <i class="bi bi-clock-history me-1"></i><?= format_time_ago($n['created_at']) ?>
                            </small>
                        </div>
                    </div>
                </a>
            </li>
        <?php endforeach; endif; ?>

        <li><hr class="dropdown-divider my-0"></li>

    </div> <!-- SCROLL WRAPPER AKHIR -->

    <!-- FOOTER ABSOLUTE -->
    <li class="notif-footer">
        <a class="dropdown-item text-center fw-bold py-3 text-kosthub btn-kosthub-bottom"
           href="../pages/notification.php">
            Lihat Semua Notifikasi
        </a>
    </li>

</ul>

            </div>

            <!-- Profile -->
            <div class="dropdown">
                <a href="#" data-bs-toggle="dropdown">
                    <img src="<?= htmlspecialchars($profilePic) ?>" class="profile-img rounded-circle" alt="Profile">
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li class="dropdown-item-text text-center fw-bold"><?= $fullName ?></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                    <li><a class="dropdown-item text-danger" href="../../../../logout.php">Log Out</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const API = '/Web-App/backend/user/owner/api/notification_api.php';
    function updateBadge() {
        fetch(API + '?action=get_unread_count', {cache: 'no-store'})
            .then(r => r.json())
            .then(d => {
                const badge = document.getElementById('notifBadge');
                if (d.success && d.count > 0) {
                    if (badge) {
                        badge.textContent = d.count > 99 ? '99+' : d.count;
                        badge.style.display = 'flex';
                    } else location.reload();
                } else if (badge) badge.style.display = 'none';
            });
    }
    updateBadge();
    setInterval(updateBadge, 10000);
});
</script>