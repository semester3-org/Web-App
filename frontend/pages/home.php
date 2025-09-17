<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit;
}

$page_title = "Cari Kos - Kosthub";
include '../components/header.php';

// Get filter parameters from URL
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
?>

<div class="page-header">
    <h1>Cari Kos Terbaik</h1>
    <p>Temukan kos impian Anda dengan berbagai pilihan dan fasilitas</p>
</div>

<?php include '../components/search_section.php'; ?>

<div class="kos-list-section">
    <div class="kos-filters">
        <div class="filter-group">
            <label>Urutkan:</label>
            <select id="sortSelect">
                <option value="newest">Terbaru</option>
                <option value="price_low">Harga Terendah</option>
                <option value="price_high">Harga Tertinggi</option>
                <option value="rating">Rating Tertinggi</option>
            </select>
        </div>
        
        <div class="results-count">
            Menampilkan <span id="resultsCount">0</span> hasil
        </div>
    </div>

    <div class="kos-grid" id="kosGrid">
        <!-- Kos cards will be loaded here via AJAX -->
    </div>

    <div class="pagination" id="pagination">
        <!-- Pagination will be loaded here -->
    </div>
</div>

<script>
let currentPage = 1;
const itemsPerPage = 9;

async function loadKos(page = 1) {
    const search = document.getElementById('searchInput').value;
    const type = document.getElementById('typeFilter').value;
    const minPrice = document.getElementById('minPrice').value;
    const maxPrice = document.getElementById('maxPrice').value;
    const sort = document.getElementById('sortSelect').value;

    const params = new URLSearchParams({
        page: page,
        limit: itemsPerPage,
        search: search,
        type: type,
        min_price: minPrice,
        max_price: maxPrice,
        sort: sort
    });

    try {
        const response = await fetch(`../../backend/kos/list_kos.php?${params}`);
        const result = await response.json();

        if (result.data) {
            displayKos(result.data);
            updatePagination(result.total || result.data.length, page);
        }
    } catch (error) {
        console.error('Error loading kos:', error);
    }
}

function displayKos(kosList) {
    const grid = document.getElementById('kosGrid');
    grid.innerHTML = '';

    if (kosList.length === 0) {
        grid.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-search fa-3x"></i>
                <h3>Tidak ada kos ditemukan</h3>
                <p>Coba ubah filter pencarian Anda</p>
            </div>
        `;
        return;
    }

    kosList.forEach(kos => {
        const card = document.createElement('div');
        card.className = 'kos-card';
        card.innerHTML = `
            <img src="../../uploads/kos/${kos.image_url || 'default.jpg'}" alt="${kos.name}">
            <div class="kos-info">
                <h3>${kos.name}</h3>
                <p class="location">${kos.address}</p>
                <p class="price">Rp ${new Intl.NumberFormat('id-ID').format(kos.price)}/bulan</p>
                <a href="kos_detail.php?id=${kos.id}" class="btn btn-primary">Lihat Detail</a>
            </div>
        `;
        grid.appendChild(card);
    });
}

function updatePagination(totalItems, currentPage) {
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    const pagination = document.getElementById('pagination');
    
    let html = '';
    if (totalPages > 1) {
        html = '<div class="pagination-buttons">';
        
        if (currentPage > 1) {
            html += `<button onclick="loadKos(${currentPage - 1})">Sebelumnya</button>`;
        }
        
        for (let i = 1; i <= totalPages; i++) {
            html += `<button class="${i === currentPage ? 'active' : ''}" onclick="loadKos(${i})">${i}</button>`;
        }
        
        if (currentPage < totalPages) {
            html += `<button onclick="loadKos(${currentPage + 1})">Selanjutnya</button>`;
        }
        
        html += '</div>';
    }
    
    pagination.innerHTML = html;
    document.getElementById('resultsCount').textContent = totalItems;
}

// Load initial data
loadKos();

// Add event listeners for filters
document.getElementById('searchForm').addEventListener('submit', (e) => {
    e.preventDefault();
    loadKos(1);
});

document.getElementById('sortSelect').addEventListener('change', () => {
    loadKos(1);
});
</script>

<style>
.page-header {
    text-align: center;
    margin-bottom: 2rem;
}

.kos-filters {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-group select {
    padding: 0.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
}

.results-count {
    color: #6b7280;
    font-weight: 500;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
}

.empty-state i {
    margin-bottom: 1rem;
    color: #d1d5db;
}

.pagination-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    margin-top: 2rem;
}

.pagination-buttons button {
    padding: 0.5rem 1rem;
    border: 2px solid #e5e7eb;
    background: white;
    border-radius: 6px;
    cursor: pointer;
}

.pagination-buttons button.active {
    background: #4f46e5;
    color: white;
    border-color: #4f46e5;
}

.pagination-buttons button:hover:not(.active) {
    background: #f3f4f6;
}
</style>

<?php include '../includes/footer.php'; ?>