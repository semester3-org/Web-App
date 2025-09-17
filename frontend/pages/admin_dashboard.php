<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

$page_title = "Dashboard Admin - Kosthub";
include_once '../components/header.php';
?>

<h1>Dashboard Admin</h1>
<p>Selamat datang, <?= htmlspecialchars($_SESSION['name']) ?>!</p>

<section class="admin-section">
    <h2>Manajemen Pengguna dan Kos</h2>
    <p>Fitur manajemen admin akan dikembangkan di sini.</p>
</section>

<style>
.admin-section {
    margin-top: 2rem;
    background: #f3f4f6;
    padding: 2rem;
    border-radius: 12px;
}
</style>

<?php include '../components/footer.php'; ?>