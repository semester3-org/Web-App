<?php
session_start();
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../backend/user/owner/classes/notification_process.php';

// Validasi owner login
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    header('Location: ' . BASE_URL . '/frontend/auth/login.php');
    exit;
}

$notification = new NotificationProcess();
$owner_id = $_SESSION['user_id'];

// Handle single notification view
$single_notification = null;
if (isset($_GET['id'])) {
    $notification_id = intval($_GET['id']);
    $single_notification = $notification->getNotificationById($notification_id, $owner_id);
    
    // Auto mark as read
    if ($single_notification && !$single_notification['is_read']) {
        $notification->markAsRead($notification_id, $owner_id);
    }
}

// Filter
$show_archived = isset($_GET['archived']) && $_GET['archived'] == '1';
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Ambil notifications
$notifications = $notification->getOwnerNotifications($owner_id, $show_archived);

// Filter berdasarkan type
if ($filter_type !== 'all') {
    $notifications = array_filter($notifications, function($notif) use ($filter_type) {
        return $notif['type'] === $filter_type;
    });
}

$page_title = "Notifikasi";
require_once __DIR__ . '/navbar.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - NusaKost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .notification-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .notification-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }
        .notification-card.unread {
            background-color: #f8f9fa;
            border-left-color: #0d6efd;
        }
        .notification-card.approved {
            border-left-color: #198754;
        }
        .notification-card.rejected {
            border-left-color: #dc3545;
        }
        .notification-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 1.5rem;
        }
        .filter-btn.active {
            background-color: #0d6efd;
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container my-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="mb-0">
                    <i class="bi bi-bell"></i> Notifikasi
                </h2>
                <p class="text-muted">Kelola semua notifikasi properti Anda</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group" role="group">
                    <a href="?archived=0" class="btn btn-sm <?php echo !$show_archived ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="bi bi-inbox"></i> Aktif
                    </a>
                    <a href="?archived=1" class="btn btn-sm <?php echo $show_archived ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="bi bi-archive"></i> Arsip
                    </a>
                </div>
            </div>
        </div>

        <?php if ($single_notification): ?>
            <!-- Detail Notifikasi -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <button onclick="history.back()" class="btn btn-sm btn-outline-secondary mb-3">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </button>
                    
                    <div class="d-flex align-items-start mb-3">
                        <div class="notification-icon bg-<?php 
                            echo match($single_notification['type']) {
                                'property_approved' => 'success',
                                'property_rejected' => 'danger',
                                'new_booking' => 'primary',
                                'new_review' => 'warning',
                                default => 'secondary'
                            }; 
                        ?> text-white me-3">
                            <i class="bi bi-<?php 
                                echo match($single_notification['type']) {
                                    'property_approved' => 'check-circle-fill',
                                    'property_rejected' => 'x-circle-fill',
                                    'new_booking' => 'calendar-check',
                                    'new_review' => 'star-fill',
                                    default => 'bell-fill'
                                }; 
                            ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h4><?php echo htmlspecialchars($single_notification['title']); ?></h4>
                            <p class="text-muted mb-2">
                                <i class="bi bi-clock"></i> 
                                <?php echo date('d M Y, H:i', strtotime($single_notification['created_at'])); ?>
                            </p>
                        </div>
                        <button class="btn btn-sm btn-outline-danger archive-btn" 
                                data-id="<?php echo $single_notification['id']; ?>">
                            <i class="bi bi-archive"></i> Arsipkan
                        </button>
                    </div>

                    <div class="alert alert-info">
                        <strong>Properti:</strong> <?php echo htmlspecialchars($single_notification['kos_name']); ?>
                        <br>
                        <strong>Lokasi:</strong> <?php echo htmlspecialchars($single_notification['kos_city']); ?>
                    </div>

                    <div class="mb-3">
                        <p><?php echo nl2br(htmlspecialchars($single_notification['message'])); ?></p>
                    </div>

                    <?php if ($single_notification['type'] === 'property_rejected' && !empty($single_notification['rejection_reason'])): ?>
                        <div class="alert alert-danger">
                            <h6><i class="bi bi-exclamation-triangle"></i> Alasan Penolakan:</h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($single_notification['rejection_reason'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a href="properties.php?id=<?php echo $single_notification['kos_id']; ?>" class="btn btn-primary">
                            <i class="bi bi-eye"></i> Lihat Properti
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- List Notifikasi -->
            <div class="row">
                <div class="col-md-12">
                    <!-- Filter -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-body py-2">
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-sm filter-btn <?php echo $filter_type === 'all' ? 'active' : 'btn-outline-secondary'; ?>" 
                                        onclick="location.href='?type=all<?php echo $show_archived ? '&archived=1' : ''; ?>'">
                                    Semua
                                </button>
                                <button class="btn btn-sm filter-btn <?php echo $filter_type === 'property_approved' ? 'active' : 'btn-outline-success'; ?>" 
                                        onclick="location.href='?type=property_approved<?php echo $show_archived ? '&archived=1' : ''; ?>'">
                                    <i class="bi bi-check-circle"></i> Disetujui
                                </button>
                                <button class="btn btn-sm filter-btn <?php echo $filter_type === 'property_rejected' ? 'active' : 'btn-outline-danger'; ?>" 
                                        onclick="location.href='?type=property_rejected<?php echo $show_archived ? '&archived=1' : ''; ?>'">
                                    <i class="bi bi-x-circle"></i> Ditolak
                                </button>
                                <button class="btn btn-sm filter-btn <?php echo $filter_type === 'new_booking' ? 'active' : 'btn-outline-primary'; ?>" 
                                        onclick="location.href='?type=new_booking<?php echo $show_archived ? '&archived=1' : ''; ?>'">
                                    <i class="bi bi-calendar-check"></i> Booking
                                </button>
                                <button class="btn btn-sm filter-btn <?php echo $filter_type === 'new_review' ? 'active' : 'btn-outline-warning'; ?>" 
                                        onclick="location.href='?type=new_review<?php echo $show_archived ? '&archived=1' : ''; ?>'">
                                    <i class="bi bi-star"></i> Review
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($notifications)): ?>
                        <div class="card shadow-sm">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-bell-slash fs-1 text-muted"></i>
                                <h5 class="mt-3 text-muted">Tidak ada notifikasi</h5>
                                <p class="text-muted">Notifikasi akan muncul di sini</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                            <div class="card notification-card <?php 
                                echo !$notif['is_read'] ? 'unread ' : '';
                                echo $notif['type'] === 'property_approved' ? 'approved ' : '';
                                echo $notif['type'] === 'property_rejected' ? 'rejected ' : '';
                            ?> shadow-sm mb-3">
                                <div class="card-body">
                                    <div class="d-flex align-items-start">
                                        <div class="notification-icon bg-<?php 
                                            echo match($notif['type']) {
                                                'property_approved' => 'success',
                                                'property_rejected' => 'danger',
                                                'new_booking' => 'primary',
                                                'new_review' => 'warning',
                                                default => 'secondary'
                                            }; 
                                        ?> text-white me-3">
                                            <i class="bi bi-<?php 
                                                echo match($notif['type']) {
                                                    'property_approved' => 'check-circle-fill',
                                                    'property_rejected' => 'x-circle-fill',
                                                    'new_booking' => 'calendar-check',
                                                    'new_review' => 'star-fill',
                                                    default => 'bell-fill'
                                                }; 
                                            ?>"></i>
                                        </div>
                                        
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <?php echo htmlspecialchars($notif['title']); ?>
                                                        <?php if (!$notif['is_read']): ?>
                                                            <span class="badge bg-primary ms-2">Baru</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <p class="text-muted mb-1 small">
                                                        <i class="bi bi-house-door"></i> 
                                                        <?php echo htmlspecialchars($notif['kos_name']); ?> - 
                                                        <?php echo htmlspecialchars($notif['kos_city']); ?>
                                                    </p>
                                                    <p class="mb-2">
                                                        <?php echo htmlspecialchars(substr($notif['message'], 0, 150)) . (strlen($notif['message']) > 150 ? '...' : ''); ?>
                                                    </p>
                                                    <small class="text-muted">
                                                        <i class="bi bi-clock"></i> 
                                                        <?php 
                                                        $time = strtotime($notif['created_at']);
                                                        $diff = time() - $time;
                                                        if ($diff < 3600) {
                                                            echo floor($diff / 60) . ' menit yang lalu';
                                                        } elseif ($diff < 86400) {
                                                            echo floor($diff / 3600) . ' jam yang lalu';
                                                        } else {
                                                            echo date('d M Y, H:i', $time);
                                                        }
                                                        ?>
                                                    </small>
                                                </div>
                                                
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="?id=<?php echo $notif['id']; ?>">
                                                                <i class="bi bi-eye"></i> Lihat Detail
                                                            </a>
                                                        </li>
                                                        <?php if (!$notif['is_read']): ?>
                                                            <li>
                                                                <button class="dropdown-item mark-read-btn" data-id="<?php echo $notif['id']; ?>">
                                                                    <i class="bi bi-check"></i> Tandai Dibaca
                                                                </button>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if (!$show_archived): ?>
                                                            <li>
                                                                <button class="dropdown-item archive-btn" data-id="<?php echo $notif['id']; ?>">
                                                                    <i class="bi bi-archive"></i> Arsipkan
                                                                </button>
                                                            </li>
                                                        <?php endif; ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item text-danger delete-btn" data-id="<?php echo $notif['id']; ?>">
                                                                <i class="bi bi-trash"></i> Hapus
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <?php if ($notif['type'] === 'property_rejected' && !empty($notif['rejection_reason'])): ?>
                                                <div class="alert alert-danger alert-sm mb-0 py-2">
                                                    <small>
                                                        <strong><i class="bi bi-exclamation-triangle"></i> Alasan:</strong>
                                                        <?php echo htmlspecialchars(substr($notif['rejection_reason'], 0, 100)) . (strlen($notif['rejection_reason']) > 100 ? '...' : ''); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tandai sebagai dibaca
        document.querySelectorAll('.mark-read-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const notificationId = this.dataset.id;
                
                fetch('<?php echo BASE_URL; ?>/backend/user/owner/classes/notification_process.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=mark_as_read&notification_id=${notificationId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            });
        });

        // Arsipkan notifikasi
        document.querySelectorAll('.archive-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Arsipkan notifikasi ini?')) return;
                
                const notificationId = this.dataset.id;
                
                fetch('<?php echo BASE_URL; ?>/backend/user/owner/classes/notification_process.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=archive&notification_id=${notificationId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            });
        });

        // Hapus notifikasi
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Hapus notifikasi ini secara permanen?')) return;
                
                const notificationId = this.dataset.id;
                
                fetch('<?php echo BASE_URL; ?>/backend/user/owner/classes/notification_process.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete&notification_id=${notificationId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            });
        });
    </script>
</body>
</html>