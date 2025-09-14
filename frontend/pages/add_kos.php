<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: login.php');
    exit;
}

$page_title = "Tambah Kos - Kosthub";
include '../components/header.php';
?>

<div class="page-header">
    <h1>Tambah Kos Baru</h1>
    <p>Tambahkan kos Anda untuk disewakan kepada pencari kos</p>
</div>

<div class="form-container">
    <form id="addKosForm" enctype="multipart/form-data">
        <div class="form-section">
            <h3>Informasi Dasar</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Nama Kos *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="type">Tipe Kos *</label>
                    <select id="type" name="type" required>
                        <option value="">Pilih Tipe</option>
                        <option value="male">Putra</option>
                        <option value="female">Putri</option>
                        <option value="mixed">Campur</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>Lokasi</h3>
            
            <div class="form-group">
                <label for="address">Alamat Lengkap *</label>
                <textarea id="address" name="address" rows="3" required></textarea>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="latitude">Latitude</label>
                    <input type="number" id="latitude" name="latitude" step="any">
                </div>
                
                <div class="form-group">
                    <label for="longitude">Longitude</label>
                    <input type="number" id="longitude" name="longitude" step="any">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>Harga dan Fasilitas</h3>
            
            <div class="form-group">
                <label for="price">Harga per Bulan (Rp) *</label>
                <input type="number" id="price" name="price" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="facilities">Fasilitas (pisahkan dengan koma)</label>
                <input type="text" id="facilities" name="facilities" 
                       placeholder="Contoh: wifi, ac, parkir, kamar mandi dalam">
            </div>
        </div>

        <div class="form-section">
            <h3>Deskripsi dan Gambar</h3>
            
            <div class="form-group">
                <label for="description">Deskripsi Kos</label>
                <textarea id="description" name="description" rows="4" 
                          placeholder="Jelaskan detail kos, aturan, dan keunggulan..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="image">Gambar Kos</label>
                <input type="file" id="image" name="image" accept="image/*">
                <small>Format: JPG, PNG, GIF. Maksimal 2MB</small>
            </div>
            
            <div class="image-preview">
                <img id="imagePreview" src="#" alt="Preview gambar" style="display: none;">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Tambah Kos</button>
            <button type="reset" class="btn btn-outline">Reset</button>
        </div>
    </form>
</div>

<script>
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

document.getElementById('addKosForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('../../backend/kos/create_kos.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Kos berhasil ditambahkan!');
            window.location.href = 'owner_dashboard.php';
        } else {
            alert(result.error || 'Gagal menambahkan kos');
        }
    } catch (error) {
        alert('Terjadi kesalahan jaringan');
    }
});
</script>

<style>
.form-container {
    max-width: 800px;
    margin: 0 auto;
}

.form-section {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-section h3 {
    margin-bottom: 1.5rem;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 0.5rem;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    font-size: 1rem;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #4f46e5;
}

.form-group small {
    color: #6b7280;
    font-size: 0.875rem;
}

.image-preview {
    margin-top: 1rem;
}

.image-preview img {
    max-width: 300px;
    max-height: 200px;
    border-radius: 8px;
    border: 2px dashed #e5e7eb;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}
</style>

<?php include '../includes/footer.php'; ?>