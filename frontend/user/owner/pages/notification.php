<?php
// C:\laragon\www\Web-App\frontend\user\owner\pages\notification.php

session_start();
require_once '../../../../backend/config/db.php';
require_once '../../../../backend/user/owner/classes/Notification.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    header("Location: ../../auth/login.php");
    exit;
}

$notification = new Notification($conn);
$owner_id = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'all';
$unread_only = ($filter === 'unread');

$result = $notification->getOwnerNotifications($owner_id, null, $unread_only);
$notifications = [];
while ($row = $result->fetch_assoc()) $notifications[] = $row;

$stats = $notification->getNotificationStats($owner_id);

// Gunakan fungsi yang sama dari navbar.php
if (!function_exists('format_time_ago')) {
    function format_time_ago($datetime) {
        $now = new DateTime; $ago = new DateTime($datetime); $diff = $now->diff($ago);
        if ($diff->d == 0) {
            if ($diff->h == 0) return $diff->i == 0 ? 'Baru saja' : $diff->i . ' menit lalu';
            return $diff->h . ' jam lalu';
        }
        if ($diff->d == 1) return 'Kemarin';
        if ($diff->d < 7) return $diff->d . ' hari lalu';
        return date('d M Y', strtotime($datetime));
    }
}

$page_title = "Notifikasi - KostHub Owner";
include __DIR__ . '/../includes/header.php';
?>

<!-- Ganti warna primer jadi hijau -->
<style>
    :root{--bs-primary:#28a745;--bs-primary-rgb:40,167,69}
    .btn-primary,.bg-primary,.text-primary,.badge.bg-primary{background-color:var(--bs-primary)!important;border-color:var(--bs-primary)!important;color:#fff!important}
    .navbar.fixed-top{display:none!important}
    body{padding-top:20px}
</style>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-success mb-1">Notifikasi</h2>
            <p class="text-muted">Kelola semua notifikasi properti Anda</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" onclick="history.back()">
                <i class="bi bi-arrow-left"></i> Kembali
            </button>
            <button class="btn btn-success btn-sm" onclick="showMarkAllModal()">
                <i class="bi bi-check-all"></i> Tandai Semua Dibaca
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <?php $cards = [
            ['Total', $stats['total'], 'bell', 'primary'],
            ['Belum Dibaca', $stats['unread'], 'envelope-exclamation', 'warning'],
            ['Review', $stats['reviews'], 'star', 'success'],
            ['Wishlist', $stats['wishlists'], 'heart', 'info']
        ]; foreach($cards as $c): ?>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="bg-<?= $c[3] ?> bg-opacity-10 text-<?= $c[3] ?> rounded-3 p-3 me-3">
                        <i class="bi bi-<?= $c[2] ?> fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted"><?= $c[0] ?></small>
                        <h4 class="mb-0 fw-bold"><?= $c[1] ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- List -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Notifikasi</h5>
            <div class="btn-group btn-group-sm">
                <a href="?filter=all" class="btn btn-<?= $filter==='all'?'success':'outline-secondary' ?>">Semua</a>
                <a href="?filter=unread" class="btn btn-<?= $filter==='unread'?'success':'outline-secondary' ?>">Belum Dibaca</a>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if(empty($notifications)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bell-slash fs-1"></i>
                    <p class="mt-3">Belum ada notifikasi</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach($notifications as $n):
                        $icon = ['property_approved'=>'success','property_rejected'=>'danger','new_review'=>'warning','new_wishlist'=>'danger','new_booking'=>'info'][$n['type']] ?? 'secondary';
                    ?>
                    <div class="list-group-item list-group-item-action <?= $n['is_read']==0?'bg-light':'' ?>" style="cursor:pointer" onclick="viewNotif(<?= $n['id'] ?>)">
                        <div class="d-flex">
                            <div class="bg-<?= $icon ?> bg-opacity-10 text-<?= $icon ?> rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:45px;height:45px">
                                <i class="bi bi-bell-fill"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?= htmlspecialchars($n['title']) ?> <?= $n['is_read']==0?'<span class="badge bg-success ms-2">Baru</span>':'' ?></strong>
                                        <p class="small text-muted mb-1"><?= htmlspecialchars($n['message']) ?></p>
                                        <?php if($n['kos_name']): ?>
                                            <small class="text-muted"><i class="bi bi-building"></i> <?= htmlspecialchars($n['kos_name']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted"><?= format_time_ago($n['created_at']) ?></small>
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

<!-- Modal Detail -->
<div class="modal fade" id="notifModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0"><h5 class="modal-title fw-bold">Detail Notifikasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="notifBody"><div class="text-center py-5"><div class="spinner-border text-success"></div></div></div>
        </div>
    </div>
</div>

<!-- Modal Confirm Mark All -->
<div class="modal fade" id="markAllModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center p-4">
            <i class="bi bi-check-all text-success mb-3" style="font-size:3rem"></i>
            <h5>Tandai Semua Dibaca?</h5>
            <div class="d-flex gap-2 justify-content-center mt-3">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-success" onclick="markAllRead()">Ya, Tandai</button>
            </div>
        </div>
    </div>
</div>

<script>
// API BASE — biar gak capek nulis panjang
const API = '/Web-App/backend/user/owner/api/notification_api.php';
// ====================================================================
// 1. Buka detail notifikasi + otomatis tandai dibaca
// ====================================================================
function viewNotif(id) {
    const modal = new bootstrap.Modal('#notifModal');
    modal.show();

    fetch(`${API}?action=get_detail&id=${id}`)
        .then(r => r.json())
        .then(d => {
            if (!d.success) return;

            let html = `
                <h6 class="fw-bold mb-2">${d.data.title}</h6>
                <p class="text-muted mb-3">${d.data.message}</p>
            `;

            if (d.data.kos_name) {
                html += `<div class="alert alert-light py-2 px-3 small mb-3"><strong>${d.data.kos_name}</strong></div>`;
            }

            if (d.data.type === 'new_review' && d.data.rating) {
                const stars = '★★★★★'.substring(0, d.data.rating) + '☆☆☆☆☆'.substring(d.data.rating);
                html += `
                    <div class="alert alert-warning py-2 px-3 small mb-3">
                        <strong>Rating: ${d.data.rating}/5</strong> ${stars}<br>
                        "${d.data.review_comment || '-'}"
                    </div>
                `;
            }

            html += `<small class="text-muted d-block"><i class="bi bi-clock"></i> ${new Date(d.data.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })}</small>`;

            document.getElementById('notifBody').innerHTML = html;

            // Update UI item di list (hilangkan badge + background)
            const item = document.querySelector(`[onclick="viewNotif(${id})"]`);
            if (item) {
                item.classList.remove('bg-light');
                const badge = item.querySelector('.badge.bg-success');
                if (badge) badge.remove();
            }

            // Update badge navbar
            refreshBadge();
        })
        .catch(() => {
            document.getElementById('notifBody').innerHTML = '<p class="text-danger">Gagal memuat detail notifikasi.</p>';
        });
}

// ====================================================================
// 2. Tandai SEMUA sebagai dibaca → TANPA RELOAD!
// ====================================================================
function markAllRead() {
    fetch(`${API}?action=mark_all_read`, {
        method: 'POST',
        credentials: 'include'
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            // 1. Hilangkan semua badge "Baru"
            document.querySelectorAll('.badge.bg-success').forEach(b => b.remove());

            // 2. Hilangkan background light di semua item
            document.querySelectorAll('.list-group-item.bg-light').forEach(item => {
                item.classList.remove('bg-light');
            });

            // 3. Update kartu "Belum Dibaca" jadi 0
            const unreadStat = Array.from(document.querySelectorAll('small.text-muted'))
                .find(el => el.textContent.includes('Belum Dibaca'));
            if (unreadStat) {
                unreadStat.closest('.card-body').querySelector('h4').textContent = '0';
            }

            // 4. Update badge navbar
            refreshBadge();

            // 5. Tutup modal
            bootstrap.Modal.getInstance('#markAllModal').hide();

            // 6. Optional toast (jika ada fungsi showToast)
            if (typeof showToast === 'function') {
                showToast('Semua notifikasi telah ditandai sebagai dibaca', 'success');
            }
        } else {
            alert('Gagal menandai semua sebagai dibaca.');
        }
    })
    .catch(() => {
        alert('Terjadi kesalahan jaringan.');
    });
}

// ====================================================================
// 3. Modal konfirmasi
// ====================================================================
function showMarkAllModal() {
    new bootstrap.Modal('#markAllModal').show();
}

// ====================================================================
// 4. Refresh badge di navbar (real-time)
// ====================================================================
function refreshBadge() {
    fetch(`${API}?action=get_unread_count`)
        .then(r => r.json())
        .then(d => {
            const badge = document.getElementById('notifBadge');
            if (!badge) return;

            if (d.success && d.count > 0) {
                badge.textContent = d.count > 99 ? '99+' : d.count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(() => {});
}

// Jalankan saat halaman dimuat
refreshBadge();
setInterval(refreshBadge, 15000); // Update tiap 15 detik
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>