<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="logo">Kost<span>Hub</span></div>
    
    <ul class="nav-links">
        <li><a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">Beranda</a></li>
        <li><a href="home.php" class="<?= $current_page === 'home.php' ? 'active' : '' ?>">Cari Kos</a></li>
        <li><a href="about.php" class="<?= $current_page === 'about.php' ? 'active' : '' ?>">Tentang</a></li>
        <li><a href="contact.php" class="<?= $current_page === 'contact.php' ? 'active' : '' ?>">Kontak</a></li>
    </ul>
    
    <div class="auth-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span>Halo, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></span>
            
            <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'owner'): ?>
                <a href="owner_dashboard.php" class="btn btn-outline">Dashboard</a>
            <?php endif; ?>
            
            <a href="../../backend/auth/logout.php" class="btn btn-outline">Logout</a>
        <?php else: ?>
            <a href="../auth/login.php" class="btn btn-outline">Login</a>
            <a href="../auth/register.php" class="btn btn-primary">Daftar</a>
        <?php endif; ?>
    </div>
</nav>
