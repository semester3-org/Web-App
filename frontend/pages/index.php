<?php
$page_title = "Kosthub - Temukan Kos Terbaik untuk Anda";
include_once '../components/header.php';
?>

<section class="hero">
    <div class="hero-content">
        <h1>Temukan Kos Ideal untuk Kebutuhan Anda</h1>
        <p>Dengan ribuan pilihan kos di seluruh Indonesia, kami membantu Anda menemukan tempat tinggal yang nyaman dengan harga terjangkau.</p>
        
        <div class="hero-search">
            <form action="home.php" method="GET" class="hero-search-form">
                <div class="search-inputs">
                    <input type="text" name="search" placeholder="Cari berdasarkan nama atau lokasi..." required>
                    <select name="type">
                        <option value="">Semua Tipe</option>
                        <option value="male">Putra</option>
                        <option value="female">Putri</option>
                        <option value="mixed">Campur</option>
                    </select>
                    <button type="submit"><i class="fas fa-search"></i> Cari</button>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="features-section">
    <div class="container">
        <h2 class="section-title">Mengapa Memilih Kosthub?</h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>Pencarian Mudah</h3>
                <p>Temukan kos impian Anda dengan filter pencarian yang lengkap</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Terpercaya</h3>
                <p>Semua kos melalui proses verifikasi untuk keamanan dan kualitas</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3>Dukungan 24/7</h3>
                <p>Tim customer service siap membantu kapan saja</p>
            </div>
        </div>
    </div>
</section>

<section class="popular-kos-section">
    <div class="container">
        <h2 class="section-title">Kos Populer</h2>
        
        <div class="kos-grid">
            <?php
            // Sample popular kos data (in real app, fetch from API)
            $popularKos = [
                [
                    'id' => 1,
                    'name' => 'Kos Mutiara Indah',
                    'address' => 'Jl. Merdeka No. 123, Jakarta',
                    'price' => 1200000,
                    'type' => 'male',
                    'facilities' => 'wifi,ac,parkir',
                    'image_url' => 'default.jpg'
                ],
                [
                    'id' => 2,
                    'name' => 'Kos Melati Asri',
                    'address' => 'Jl. Sudirman No. 45, Bandung',
                    'price' => 1500000,
                    'type' => 'female',
                    'facilities' => 'wifi,dapur,tv',
                    'image_url' => 'default.jpg'
                ],
                [
                    'id' => 3,
                    'name' => 'Kos Sejahtera Bersama',
                    'address' => 'Jl. Gatot Subroto No. 78, Surabaya',
                    'price' => 1800000,
                    'type' => 'mixed',
                    'facilities' => 'wifi,ac,parkir',
                    'image_url' => 'default.jpg'
                ]
            ];
            
            foreach ($popularKos as $kos) {
                include_once '../components/kos_card.php';
            }
            ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="home.php" class="btn btn-primary">Lihat Semua Kos</a>
        </div>
    </div>
</section>

<?php include_once '../includes/footer.php'; ?>

<style>
.hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.hero-content h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.hero-content p {
    font-size: 1.2rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.hero-search {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    margin: 0 auto;
    max-width: 600px;
}

.search-inputs {
    display: flex;
    gap: 1rem;
}

.search-inputs input,
.search-inputs select {
    flex: 1;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
}

.search-inputs button {
    background: #4f46e5;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    cursor: pointer;
}

.features-section {
    padding: 4rem 0;
    background: #f8f9fa;
}

.section-title {
    text-align: center;
    font-size: 2rem;
    margin-bottom: 3rem;
    color: #1f2937;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.feature-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.feature-icon {
    width: 60px;
    height: 60px;
    background: #4f46e5;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 1rem;
}

.popular-kos-section {
    padding: 4rem 0;
}
</style>