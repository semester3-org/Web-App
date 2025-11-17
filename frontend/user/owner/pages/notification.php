<?php
// frontend/user/owner/pages/notification.php

session_start();
require_once '../../../../backend/config/db.php';
require_once '../../../../backend/user/owner/classes/Notification.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    header("Location: ../../auth/login.php");
    exit;
}


$notification = new Notification($conn);
$owner_id = $_SESSION['user_id'];

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Get notifications
$unread_only = ($filter === 'unread');
$result = $notification->getOwnerNotifications($owner_id, null, $unread_only);
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Get statistics
$stats = $notification->getNotificationStats($owner_id);

$page_title = "Notifikasi";
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1 fw-bold">Notifikasi</h2>
                    <p class="text-muted mb-0">Kelola semua notifikasi properti Anda</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="markAllAsRead()">
                        <i class="bi bi-check-all me-1"></i> Tandai Semua Dibaca
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                                <i class="bi bi-bell fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Total Notifikasi</h6>
                            <h3 class="mb-0 fw-bold"><?= $stats['total'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                                <i class="bi bi-envelope-exclamation fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Belum Dibaca</h6>
                            <h3 class="mb-0 fw-bold"><?= $stats['unread'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 text-success rounded-3 p-3">
                                <i class="bi bi-star fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Review Baru</h6>
                            <h3 class="mb-0 fw-bold"><?= $stats['reviews'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 text-info rounded-3 p-3">
                                <i class="bi bi-heart fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Wishlist Baru</h6>
                            <h3 class="mb-0 fw-bold"><?= $stats['wishlists'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">Daftar Notifikasi</h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="?filter=all" class="btn btn-<?= $filter === 'all' ? 'primary' : 'outline-secondary' ?>">
                                Semua
                            </a>
                            <a href="?filter=unread" class="btn btn-<?= $filter === 'unread' ? 'primary' : 'outline-secondary' ?>">
                                Belum Dibaca
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-bell-slash text-muted" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3 mb-0">Belum ada notifikasi</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notif): ?>
                                <?php
                                // Icon and color based on type
                                $icon_config = [
                                    'property_approved' => ['icon' => 'check-circle-fill', 'color' => 'success'],
                                    'property_rejected' => ['icon' => 'x-circle-fill', 'color' => 'danger'],
                                    'new_review' => ['icon' => 'star-fill', 'color' => 'warning'],
                                    'new_wishlist' => ['icon' => 'heart-fill', 'color' => 'danger'],
                                    'new_booking' => ['icon' => 'calendar-check-fill', 'color' => 'info']
                                ];
                                $config = $icon_config[$notif['type']] ?? ['icon' => 'bell-fill', 'color' => 'secondary'];
                                
                                $time_ago = time_elapsed_string($notif['created_at']);
                                ?>
                                <div class="list-group-item list-group-item-action <?= $notif['is_read'] == 0 ? 'bg-light' : '' ?>" 
                                     style="cursor: pointer;" 
                                     onclick="viewNotification(<?= $notif['id'] ?>)">
                                    <div class="d-flex w-100">
                                        <div class="flex-shrink-0">
                                            <div class="bg-<?= $config['color'] ?> bg-opacity-10 text-<?= $config['color'] ?> rounded-circle p-2" 
                                                 style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-<?= $config['icon'] ?> fs-5"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1 fw-semibold">
                                                        <?= htmlspecialchars($notif['title']) ?>
                                                        <?php if ($notif['is_read'] == 0): ?>
                                                            <span class="badge bg-primary rounded-pill ms-2" style="font-size: 0.65rem;">Baru</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <p class="mb-1 text-muted small"><?= htmlspecialchars($notif['message']) ?></p>
                                                    <?php if ($notif['kos_name']): ?>
                                                        <p class="mb-0 small">
                                                            <i class="bi bi-building me-1"></i>
                                                            <span class="fw-medium"><?= htmlspecialchars($notif['kos_name']) ?></span>
                                                            <?php if ($notif['city']): ?>
                                                                <span class="text-muted"> • <?= htmlspecialchars($notif['city']) ?></span>
                                                            <?php endif; ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-end">
                                                    <small class="text-muted"><?= $time_ago ?></small>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-link text-muted p-0 ms-2" 
                                                                type="button" 
                                                                data-bs-toggle="dropdown" 
                                                                onclick="event.stopPropagation()">
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                            <?php if ($notif['is_read'] == 0): ?>
                                                                <li>
                                                                    <a class="dropdown-item small" href="#" onclick="event.preventDefault(); event.stopPropagation(); markAsRead(<?= $notif['id'] ?>)">
                                                                        <i class="bi bi-check2 me-2"></i>Tandai Dibaca
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            <li>
                                                                <a class="dropdown-item small" href="#" onclick="event.preventDefault(); event.stopPropagation(); archiveNotification(<?= $notif['id'] ?>)">
                                                                    <i class="bi bi-archive me-2"></i>Arsipkan
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item small text-danger" href="#" onclick="event.preventDefault(); event.stopPropagation(); deleteNotification(<?= $notif['id'] ?>)">
                                                                    <i class="bi bi-trash me-2"></i>Hapus
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Detail Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="notificationModalTitle">Detail Notifikasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="notificationModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function time_elapsed_string($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d == 0) {
        if ($diff->h == 0) {
            if ($diff->i == 0) {
                return 'Baru saja';
            }
            return $diff->i . ' menit lalu';
        }
        return $diff->h . ' jam lalu';
    } elseif ($diff->d == 1) {
        return 'Kemarin';
    } elseif ($diff->d < 7) {
        return $diff->d . ' hari lalu';
    } elseif ($diff->d < 30) {
        return floor($diff->d / 7) . ' minggu lalu';
    } elseif ($diff->m < 12) {
        return $diff->m . ' bulan lalu';
    }
    return $diff->y . ' tahun lalu';
}
?>

<script>
// Mark single notification as read
function markAsRead(notificationId) {
    fetch('../../../../backend/user/owner/api/notification_api.php?action=mark_as_read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

// Mark all notifications as read
function markAllAsRead() {
    if (!confirm('Tandai semua notifikasi sebagai dibaca?')) return;
    
    fetch('../../../../backend/user/owner/api/notification_api.php?action=mark_all_read', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

// Archive notification
function archiveNotification(notificationId) {
    if (!confirm('Arsipkan notifikasi ini?')) return;
    
    fetch('../../../../backend/user/owner/api/notification_api.php?action=archive', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

// Delete notification
function deleteNotification(notificationId) {
    if (!confirm('Hapus notifikasi ini secara permanen?')) return;
    
    fetch('../../../../backend/user/owner/api/notification_api.php?action=delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

// View notification detail
function viewNotification(notificationId) {
    const modal = new bootstrap.Modal(document.getElementById('notificationModal'));
    modal.show();
    
    fetch('../../../../backend/user/owner/api/notification_api.php?action=get_detail&id=' + notificationId)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayNotificationDetail(data.data);
        }
    });
}

// Display notification detail in modal
function displayNotificationDetail(notif) {
    let content = `
        <div class="mb-3">
            <h6 class="fw-semibold mb-2">${notif.title}</h6>
            <p class="text-muted mb-0">${notif.message}</p>
        </div>
    `;
    
    if (notif.kos_name) {
        content += `
            <div class="alert alert-light border mb-3">
                <h6 class="small fw-semibold mb-1">Properti</h6>
                <p class="mb-0"><strong>${notif.kos_name}</strong></p>
                ${notif.address ? `<p class="small text-muted mb-0">${notif.address}</p>` : ''}
            </div>
        `;
    }
    
    if (notif.type === 'property_rejected' && notif.rejection_reason) {
        content += `
            <div class="alert alert-danger">
                <h6 class="small fw-semibold mb-1">Alasan Penolakan</h6>
                <p class="mb-0">${notif.rejection_reason}</p>
            </div>
        `;
    }
    
    if (notif.type === 'new_review') {
        const stars = '⭐'.repeat(notif.rating || 0);
        content += `
            <div class="alert alert-warning">
                <h6 class="small fw-semibold mb-1">Review</h6>
                <p class="mb-1">${stars} (${notif.rating}/5)</p>
                ${notif.review_comment ? `<p class="mb-0 small">"${notif.review_comment}"</p>` : ''}
                ${notif.reviewer_name ? `<p class="mb-0 small text-muted mt-1">- ${notif.reviewer_name}</p>` : ''}
            </div>
        `;
    }
    
    content += `
        <div class="text-muted small">
            <i class="bi bi-clock me-1"></i>${new Date(notif.created_at).toLocaleString('id-ID')}
        </div>
    `;
    
    document.getElementById('notificationModalBody').innerHTML = content;
}
</script>

<?php include '../includes/footer.php'; ?>