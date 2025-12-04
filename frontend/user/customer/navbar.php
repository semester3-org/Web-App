<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . "/backend/config/db.php");

$isLoggedIn = isset($_SESSION['user_id']) && ($_SESSION['user_type'] ?? '') === 'user';
$profilePic = '/frontend/assets/default-avatar.png';  // fallback
$fullName   = 'Guest';

if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT full_name, profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $fullName = htmlspecialchars($user['full_name'] ?? 'User');

    // PERBAIKAN UTAMA & FINAL – SUPPORT GOOGLE PHOTO + LOKAL
    if (!empty($user['profile_picture'])) {
        $pic = trim($user['profile_picture']);

        // Kalau sudah URL lengkap (Google, Facebook, dll)
        if (preg_match('#^https?://#i', $pic)) {
            $profilePic = $pic;
        } 
        // Kalau path lokal (uploads/profile/xxx.jpg)
        else {
            // Bersihkan dari folder lama /Web-App atau backslash
            $pic = str_replace(['\\', '/Web-App'], ['', ''], $pic);
            // Pastikan dimulai dengan satu slashaba slash
            $profilePic = '/' . ltrim($pic, '/');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        :root {--green:#16a34a;--green-light:#f0fdf4;--green-lighter:#ecfdf5;--red:#dc2626}
        .navbar{min-height:70px!important;height:70px!important;padding:0!important;background:#fff!important;border-bottom:1px solid #e5e7eb;box-shadow:0 4px 20px rgba(0,0,0,.06);z-index:1050;position:fixed;top:0;width:100%}
        .navbar .container-fluid{height:100%;display:flex;align-items:center}
        .navbar-brand{font-weight:700;font-size:1.7rem;color:var(--green)!important;display:flex;align-items:center}
        .nav-link{color:#374151!important;font-weight:500;padding:.6rem 1.3rem!important;border-radius:12px;transition:.3s;display:flex;align-items:center;height:44px}
        .nav-link:hover,.nav-link.active{background:var(--green-light)!important;color:var(--green)!important;font-weight:600!important}
        .nav-link.active{font-weight:700!important}
        .bell{width:48px;height:48px;background:var(--green-light);border-radius:50%;display:flex;align-items:center;justify-content:center;transition:.3s;cursor:pointer}
        .bell:hover{background:var(--green);transform:scale(1.12)}
        .bell:hover i{color:#fff!important}
        .bell i{font-size:1.5rem;color:var(--green)}
        .badge-notif{position:absolute;top:-6px;right:-6px;min-width:24px;height:24px;background:var(--red);color:#fff;font-size:.75rem;font-weight:700;border-radius:12px;border:3px solid #fff;display:flex;align-items:center;justify-content:center;animation:pulse 2s infinite}
        @keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.2)}}
        .profile-img{width:46px;height:46px;object-fit:cover;border:3.5px solid var(--green);border-radius:50%;transition:.3s;cursor:pointer}
        .profile-img:hover{transform:scale(1.1)}
        .notif-dropdown{width:420px!important;max-width:95vw;border:none;border-radius:18px;box-shadow:0 25px 50px rgba(0,0,0,.18);margin-top:15px}
        .notif-header{background:linear-gradient(135deg,#16a34a,#22c55e);color:#fff;padding:1.3rem 1.5rem}
        .notif-list{max-height:480px;overflow-y:auto}
        .notif-item{padding:1rem 1.5rem;border-bottom:1px solid #f1f3f5;transition:.25s}
        .notif-item:hover{background:var(--green-lighter)!important}
        .notif-item.unread{background:linear-gradient(90deg,#ecfdf5,#fff);font-weight:500}
        .notif-icon{width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0}
        .icon-success{background:#d1f4e0;color:#059669}
        .icon-danger{background:#fee2e2;color:#dc2626}
        .icon-info{background:#dbeafe;color:#2563eb}
        .text-kosthub{color:var(--green)!important;font-weight:600}
        .btn-mark-all{background:rgba(255,255,255,.2);border:none;color:#fff;width:38px;height:38px;border-radius:50%;transition:.3s}
        .btn-mark-all:hover{background:rgba(255,255,255,.35);transform:scale(1.1)}
        @media(max-width:768px){.notif-dropdown{width:100vw!important;border-radius:0;margin-top:0}}
        @media (min-width: 992px) {
            .navbar .d-flex.gap-4 > .dropdown + .dropdown {margin-left: 8px !important;}
        }
        .bell:hover {background: var(--green) !important;transform: scale(1.15) !important;}
        .bell:hover i {color: white !important;}
        .profile-img:hover {transform: scale(1.12) !important;border-color: #13a144 !important;box-shadow: 0 0 15px rgba(22,163,74,0.3);}
    </style>
</head>
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid px-4">
        <!-- Logo -->
        <a class="navbar-brand" href="/frontend/user/customer/home.php">
            <img src="/frontend/assets/logo_kos.png" height="38" class="me-2" alt="Logo"> KostHub
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto gap-4">
                <li><a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='home.php' ? 'active' : '' ?>" href="/frontend/user/customer/home.php">Home</a></li>
                <li><a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='explore.php' ? 'active' : '' ?>" href="/frontend/user/customer/explore.php">Explore</a></li>
                <li><a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='wishlist.php' ? 'active' : '' ?>"
                       href="<?= $isLoggedIn ? '/frontend/user/customer/wishlist.php' : '#' ?>"
                       <?= !$isLoggedIn ? 'data-bs-toggle="modal" data-bs-target="#loginModal"' : '' ?>>Wishlist</a></li>
                <li><a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='booking.php' ? 'active' : '' ?>"
                       href="<?= $isLoggedIn ? '/frontend/user/customer/booking.php' : '#' ?>"
                       <?= !$isLoggedIn ? 'data-bs-toggle="modal" data-bs-target="#loginModal"' : '' ?>>Your Booking</a></li>
            </ul>

            <?php if (!$isLoggedIn): ?>
                <a href="/frontend/auth/login.php" class="btn btn-success px-4">Login</a>
            <?php else: ?>
                <div class="d-flex align-items-center gap-4">
                    <!-- BELL NOTIFIKASI -->
                    <div class="dropdown bell-wrapper position-relative">
                        <a class="bell" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell-fill"></i>
                            <span id="notifBadge" class="badge-notif" style="display:none;">0</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end notif-dropdown">
                            <li class="notif-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 fw-bold">Notifikasi</h6>
                                    <small id="notifSubtitle">Memuat...</small>
                                </div>
                                <button class="btn-mark-all" id="markAllReadBtn" title="Tandai semua dibaca">
                                    <i class="bi bi-check-all"></i>
                                </button>
                            </li>
                            <div id="notificationList" class="notif-list"></div>
                            <li class="p-3 bg-light text-center border-top">
                                <a href="/frontend/user/customer/notifications.php" class="text-kosthub fw-bold">Lihat Semua Notifikasi</a>
                            </li>
                        </ul>
                    </div>

                    <!-- PROFILE DROPDOWN -->
                    <div class="dropdown">
                        <a data-bs-toggle="dropdown" aria-expanded="false" class="d-block">
                            <img src="<?= htmlspecialchars($profilePic) ?>" 
                                 class="profile-img" 
                                 alt="Profile"
                                 onerror="this.src='/frontend/assets/default-avatar.png'; this.onerror=null;">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3">
                            <li class="px-4 py-3 text-center fw-bold"><?= $fullName ?></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/frontend/user/customer/profile.php">Profile</a></li>
                            <li><a class="dropdown-item text-danger" href="/logout.php">Log Out</a></li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Modal Login Alert -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title">Login Diperlukan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-5">
                <i class="bi bi-lock-fill text-success" style="font-size:3.5rem"></i>
                <p class="mt-3 fs-5">Silakan login untuk mengakses fitur ini</p>
                <a href="/frontend/auth/login.php" class="btn btn-success px-5 py-3">Login Sekarang</a>
            </div>
        </div>
    </div>
</div>

<?php if ($isLoggedIn): ?>
<script>
function updateBadge(){
    fetch("/backend/user/customer/classes/notifications.php?action=get_count")
    .then(r=>r.json())
    .then(d=>{
        const b=document.getElementById("notifBadge"), s=document.getElementById("notifSubtitle");
        if(d.success && d.count>0){
            b.textContent = d.count>99?"99+":d.count;
            b.style.display="flex";
            s.textContent = `${d.count} notifikasi baru`;
        }else{
            b.style.display="none";
            s.textContent = "Tidak ada notifikasi baru";
        }
    });
}
function loadNotifications(){
    fetch("/backend/user/customer/classes/notifications.php?action=get_notifications&limit=10")
    .then(r=>r.json())
    .then(d=>{
        const c=document.getElementById("notificationList");
        if(!d.success || d.notifications.length===0){
            c.innerHTML='<div class="text-center py-5 text-muted"><i class="bi bi-bell-slash fs-1"></i><p class="small mt-3">Belum ada notifikasi</p></div>';
            return;
        }
        let h="";
        d.notifications.forEach(n=>{
            const i = n.type.includes("confirmed")||n.type.includes("approved") ? "icon-success bi-check-circle-fill" :
                     n.type.includes("rejected") ? "icon-danger bi-x-circle-fill" : "icon-info bi-bell-fill";
            h+=`<a href="#" class="d-block notif-item ${n.is_read==0?"unread":""}" data-id="${n.id}" data-kos="${n.kos_id||""}">
                <div class="d-flex gap-3">
                    <div class="notif-icon ${i.split(" ")[0]}"><i class="bi ${i.split(" ")[1]}"></i></div>
                    <div class="flex-grow-1">
                        <strong class="d-block small text-dark">${n.title}</strong>
                        <p class="small text-muted mb-1">${n.message}</p>
                        ${n.property_name?`<small class="text-kosthub fw-semibold d-block mb-1">Kos ${n.property_name}</small>`:""}
                        <small class="text-muted d-block">${n.time_ago}</small>
                    </div>
                </div>
            </a>`;
        });
        c.innerHTML=h;
        c.querySelectorAll("a").forEach(a=>{
            a.onclick=e=>{
                e.preventDefault();
                const id=a.dataset.id, kos=a.dataset.kos;
                if(id) fetch("/backend/user/customer/classes/notifications.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:`action=mark_read&notification_id=${id}`}).then(()=>{updateBadge();});
                if(kos) location.href=`/frontend/user/customer/detail_kos.php?id=${kos}`;
                else location.href="/frontend/user/customer/notifications.php";
            };
        });
    });
}
document.getElementById("notifDropdown")?.addEventListener("show.bs.dropdown", loadNotifications);
document.getElementById("markAllReadBtn")?.addEventListener("click", e=>{
    e.stopPropagation();
    fetch("/backend/user/customer/classes/notifications.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"action=mark_all_read"})
    .then(()=>{updateBadge(); loadNotifications();});
});
document.addEventListener("DOMContentLoaded",()=>{updateBadge(); setInterval(updateBadge,30000);});
</script>
<?php endif; ?>