<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../pages/login.php');
    exit;
}

$page_title = "Dashboard Owner - Kosthub";
include_once '../components/header.php';
?>

<h1>Dashboard Pemilik Kos</h1>
<p>Selamat datang, <?= htmlspecialchars($_SESSION['name']) ?>!</p>

<section class="my-kos-section">
    <h2>Daftar Kos Anda</h2>
    <div id="myKosList">
        <p>Memuat data kos...</p>
    </div>
    <a href="add_kos.php" class="btn btn-primary mt-4">Tambah Kos Baru</a>
</section>

<script>
async function loadMyKos() {
    try {
        const response = await fetch('../../backend/kos/my_kos.php');
        const result = await response.json();

        const container = document.getElementById('myKosList');
        container.innerHTML = '';

        if (result.data && result.data.length > 0) {
            result.data.forEach(kos => {
                const div = document.createElement('div');
                div.className = 'my-kos-card';
                div.innerHTML = `
                    <h3>${kos.name}</h3>
                    <p>Alamat: ${kos.address}</p>
                    <p>Harga: Rp ${new Intl.NumberFormat('id-ID').format(kos.price)}/bulan</p>
                    <a href="kos_detail.php?id=${kos.id}" class="btn btn-outline btn-sm">Detail</a>
                `;
                container.appendChild(div);
            });
        } else {
            container.innerHTML = '<p>Anda belum memiliki kos yang terdaftar.</p>';
        }
    } catch (error) {
        console.error('Error loading kos:', error);
        document.getElementById('myKosList').innerHTML = '<p>Gagal memuat data kos.</p>';
    }
}

document.addEventListener('DOMContentLoaded', loadMyKos);
</script>

<style>
.my-kos-section {
    margin-top: 2rem;
}

.my-kos-card {
    background: #f9fafb;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.my-kos-card h3 {
    margin-bottom: 0.5rem;
}

.btn-sm {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
}
</style>

<?php include '../includes/footer.php'; ?>