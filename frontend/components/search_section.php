<div class="search-section">
    <form id="searchForm" onsubmit="event.preventDefault(); searchKos();">
        <div class="search-grid">
            <div class="form-group">
                <label for="searchInput">Cari Kos</label>
                <input type="text" id="searchInput" name="search" placeholder="Nama kos atau lokasi..." 
                       class="form-control" value="<?= $_GET['search'] ?? '' ?>">
            </div>
            
            <div class="form-group">
                <label for="typeFilter">Tipe Kos</label>
                <select id="typeFilter" name="type" class="form-control">
                    <option value="">Semua Tipe</option>
                    <option value="male" <?= ($_GET['type'] ?? '') === 'male' ? 'selected' : '' ?>>Putra</option>
                    <option value="female" <?= ($_GET['type'] ?? '') === 'female' ? 'selected' : '' ?>>Putri</option>
                    <option value="mixed" <?= ($_GET['type'] ?? '') === 'mixed' ? 'selected' : '' ?>>Campur</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="minPrice">Harga Minimal</label>
                <input type="number" id="minPrice" name="min_price" placeholder="Min harga" 
                       class="form-control" value="<?= $_GET['min_price'] ?? '' ?>">
            </div>
            
            <div class="form-group">
                <label for="maxPrice">Harga Maksimal</label>
                <input type="number" id="maxPrice" name="max_price" placeholder="Max harga" 
                       class="form-control" value="<?= $_GET['max_price'] ?? '' ?>">
            </div>
        </div>
        
        <div class="text-center mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Cari Kos
            </button>
            <button type="reset" class="btn btn-outline">
                <i class="fas fa-refresh"></i> Reset
            </button>
        </div>
    </form>
</div>

<script>
function searchKos() {
    const form = document.getElementById('searchForm');
    const formData = new FormData(form);
    const params = new URLSearchParams();
    
    for (let [key, value] of formData) {
        if (value) params.append(key, value);
    }
    
    window.location.href = `home.php?${params.toString()}`;
}
</script>