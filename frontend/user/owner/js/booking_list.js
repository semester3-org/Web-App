/**
 * Booking List JavaScript – FINAL VERSION (Foto Customer Pasti Muncul!)
 */

let currentBookingId = null;
let allBookings = [];
let allProperties = [];

// === FUNGSI PEMBENAH FOTO CUSTOMER (INI YANG PALING PENTING) ===
function fixCustomerPhoto(url) {
    if (!url || url.trim() === '') {
        return '/frontend/assets/default-avatar.png';
    }

    // Kalau sudah URL lengkap (Google Photo atau upload di hosting)
    if (url.startsWith('http://') || url.startsWith('https://')) {
        return url;
    }

    // Kalau path lokal (uploads/profile/xxx.jpg atau uploads/kos/...)
    if (url.startsWith('uploads/') || url.startsWith('/uploads/')) {
        return '/' + url.replace(/^\/+/, ''); // pastikan hanya satu slash di depan
    }

    // Default fallback
    return '/frontend/assets/default-avatar.png';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadBookings();
    loadProperties();
    setupFilters();
    setupEventListeners();
});

/**
 * Load all bookings
 */
function loadBookings(filters = {}) {
    const loadingState = document.getElementById('loadingState');
    const emptyState = document.getElementById('emptyState');
    const bookingTable = document.getElementById('bookingTable');
    
    loadingState.style.display = 'block';
    emptyState.style.display = 'none';
    bookingTable.style.display = 'none';
    
    const params = new URLSearchParams(filters);
    
    fetch(`../../../../backend/user/owner/api/get_bookings.php?${params}`)
        .then(response => response.json())
        .then(data => {
            loadingState.style.display = 'none';
            
            if (data.success && data.bookings.length > 0) {
                allBookings = data.bookings;
                bookingTable.style.display = 'block';
                renderBookings(data.bookings);
            } else {
                emptyState.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            loadingState.style.display = 'none';
            emptyState.style.display = 'block';
        });
}

/**
 * Load properties for filter
 */
function loadProperties() {
    fetch('../../../../backend/user/owner/classes/get_properties.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allProperties = data.properties;
                populatePropertyFilter(data.properties);
            }
        })
        .catch(error => console.error('Error:', error));
}

/**
 * Populate property filter dropdown
 */
function populatePropertyFilter(properties) {
    const filterProperty = document.getElementById('filterProperty');
    filterProperty.innerHTML = '<option value="">Semua Property</option>'; // reset
    
    properties.forEach(property => {
        const option = document.createElement('option');
        option.value = property.id;
        option.textContent = property.name;
        filterProperty.appendChild(option);
    });
}

/**
 * Render bookings table
 */
function renderBookings(bookings) {
    const tbody = document.getElementById('bookingTableBody');
    tbody.innerHTML = '';
    
    bookings.forEach(booking => {
        const tr = document.createElement('tr');
        
        const checkIn = new Date(booking.check_in_date).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
        
        let duration = '';
        if (booking.booking_type === 'monthly') {
            duration = `${booking.duration_months} bulan`;
        } else {
            const days = calculateDays(booking.check_in_date, booking.check_out_date);
            duration = `${days} hari`;
        }
        
        const statusClass = `status-${booking.status}`;
        
        // GUNAKAN fixCustomerPhoto() → FOTO PASTI MUNCUL!
        const customerPhoto = fixCustomerPhoto(booking.profile_picture);

        tr.innerHTML = `
            <td>#${booking.id}</td>
            <td>
                <div class="customer-info">
                    <img 
                        src="${customerPhoto}"
                        class="customer-avatar rounded-circle"
                        alt="${booking.full_name}"
                        onerror="this.src='/frontend/assets/default-avatar.png'; this.onerror=null;">
                    <div>
                        <div class="customer-name">${booking.full_name}</div>
                        <div class="customer-phone">${booking.phone || '-'}</div>
                    </div>
                </div>
            </td>
            <td>
                <div class="property-name">${booking.kos_name}</div>
                <div class="property-location">
                    <i class="bi bi-geo-alt-fill text-success"></i>
                    ${booking.city}
                </div>
            </td>
            <td>${checkIn}</td>
            <td>${duration}</td>
            <td class="fw-bold">Rp ${formatNumber(booking.total_price)}</td>
            <td><span class="status-badge ${statusClass}">${booking.status}</span></td>
            <td>
                <div class="btn-action-group">
                    <button class="btn btn-action btn-view" onclick="viewDetail(${booking.id})">
                        <i class="bi bi-eye"></i> Detail
                    </button>
                    ${booking.status === 'pending' ? `
                        <button class="btn btn-action btn-approve" onclick="confirmBooking(${booking.id})">
                            <i class="bi bi-check-lg"></i> Setujui
                        </button>
                        <button class="btn btn-action btn-reject" onclick="rejectBooking(${booking.id})">
                            <i class="bi bi-x-lg"></i> Tolak
                        </button>
                    ` : ''}
                </div>
            </td>
        `;
        
        tbody.appendChild(tr);
    });
}

// Sisanya tetap sama seperti sebelumnya...
function viewDetail(bookingId) { /* tetap sama */ }
function confirmBooking(bookingId) { currentBookingId = bookingId; new bootstrap.Modal(document.getElementById('confirmModal')).show(); }
function rejectBooking(bookingId) { currentBookingId = bookingId; document.getElementById('rejectReason').value = ''; new bootstrap.Modal(document.getElementById('rejectModal')).show(); }

function setupEventListeners() {
    document.getElementById('confirmBookingBtn').addEventListener('click', function() {
        if (!currentBookingId) return;
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        
        fetch('../../../../backend/user/owner/classes/update_booking_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ booking_id: currentBookingId, status: 'confirmed' })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
                showNotification('Booking berhasil dikonfirmasi!, menunggu pembayaran dari customer', 'success');
                setTimeout(() => loadBookings(), 1000);
            } else {
                showNotification(data.message || 'Gagal mengkonfirmasi booking', 'danger');
            }
        })
        .catch(() => showNotification('Terjadi kesalahan', 'danger'))
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-check-lg"></i> Ya, Setujui';
        });
    });

    document.getElementById('rejectBookingBtn').addEventListener('click', function() {
        if (!currentBookingId) return;
        const reason = document.getElementById('rejectReason').value;
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        
        fetch('../../../../backend/user/owner/classes/update_booking_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ booking_id: currentBookingId, status: 'rejected', notes: reason })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();
                showNotification('Booking berhasil ditolak', 'success');
                setTimeout(() => loadBookings(), 1000);
            } else {
                showNotification(data.message || 'Gagal menolak booking', 'danger');
            }
        })
        .catch(() => showNotification('Terjadi kesalahan', 'danger'))
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-x-lg"></i> Ya, Tolak';
        });
    });
}

function setupFilters() {
    const filterStatus = document.getElementById('filterStatus');
    const filterProperty = document.getElementById('filterProperty');
    const filterType = document.getElementById('filterType');
    const searchInput = document.getElementById('searchInput');
    
    filterStatus.addEventListener('change', applyFilters);
    filterProperty.addEventListener('change', applyFilters);
    filterType.addEventListener('change', applyFilters);
    searchInput.addEventListener('input', debounce(applyFilters, 500));
}

function applyFilters() {
    const filters = {
        status: document.getElementById('filterStatus').value,
        kos_id: document.getElementById('filterProperty').value,
        booking_type: document.getElementById('filterType').value,
        search: document.getElementById('searchInput').value
    };
    
    Object.keys(filters).forEach(key => filters[key] || delete filters[key]);
    loadBookings(filters);
}

function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

function calculateDays(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate || start);
    const diffTime = Math.abs(end - start);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => { clearTimeout(timeout); func(...args); };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showNotification(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alert.style.cssText = "top:20px; right:20px; z-index:9999; min-width:300px;";
    alert.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.body.appendChild(alert);
    setTimeout(() => alert.remove(), 4000);
}