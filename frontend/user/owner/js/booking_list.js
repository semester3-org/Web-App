/**
 * Booking List JavaScript
 */

let currentBookingId = null;
let allBookings = [];
let allProperties = [];

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
    
    // Build query string
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
        
        // Format dates
        const checkIn = new Date(booking.check_in_date).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
        
        // Duration
        let duration = '';
        if (booking.booking_type === 'monthly') {
            duration = `${booking.duration_months} bulan`;
        } else {
            const days = calculateDays(booking.check_in_date, booking.check_out_date);
            duration = `${days} hari`;
        }
        
        // Status class
        const statusClass = `status-${booking.status}`;
        
        tr.innerHTML = `
            <td>#${booking.id}</td>
            <td>
                <div class="customer-info">
                        <img 
                        src="http://localhost/${booking.profile_picture || 'frontend/assets/default-avatar.png'}"
                        class="customer-avatar"
                        alt="Customer"
                        onerror="this.src='http://localhost/Web-App/frontend/assets/default-avatar.png'">
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

/**
 * View booking detail
 */
function viewDetail(bookingId) {
    const booking = allBookings.find(b => b.id == bookingId);
    if (!booking) return;
    
    const checkIn = new Date(booking.check_in_date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
    
    let checkOut = '-';
    if (booking.check_out_date) {
        checkOut = new Date(booking.check_out_date).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    }
    
    const statusClass = `status-${booking.status}`;
    
    const modalContent = `
        <div class="detail-section">
            <h6><i class="bi bi-person-fill"></i> Informasi Customer</h6>
            <div class="detail-row">
                <span class="detail-label">Nama Lengkap</span>
                <span class="detail-value">${booking.full_name}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email</span>
                <span class="detail-value">${booking.email}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">No. Telepon</span>
                <span class="detail-value">${booking.phone || '-'}</span>
            </div>
        </div>
        
        <div class="detail-section">
            <h6><i class="bi bi-house-fill"></i> Informasi Property</h6>
            <div class="detail-row">
                <span class="detail-label">Nama Kos</span>
                <span class="detail-value">${booking.kos_name}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Lokasi</span>
                <span class="detail-value">${booking.city}, ${booking.province}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Alamat</span>
                <span class="detail-value">${booking.address}</span>
            </div>
        </div>
        
        <div class="detail-section">
            <h6><i class="bi bi-calendar-check-fill"></i> Detail Booking</h6>
            <div class="detail-row">
                <span class="detail-label">Booking ID</span>
                <span class="detail-value">#${booking.id}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Tipe Booking</span>
                <span class="detail-value">${booking.booking_type === 'monthly' ? 'Bulanan' : 'Harian'}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Check In</span>
                <span class="detail-value">${checkIn}</span>
            </div>
            ${booking.check_out_date ? `
                <div class="detail-row">
                    <span class="detail-label">Check Out</span>
                    <span class="detail-value">${checkOut}</span>
                </div>
            ` : ''}
            <div class="detail-row">
                <span class="detail-label">Durasi</span>
                <span class="detail-value">
                    ${booking.booking_type === 'monthly' ? 
                        `${booking.duration_months} bulan` : 
                        `${calculateDays(booking.check_in_date, booking.check_out_date)} hari`}
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value"><span class="status-badge ${statusClass}">${booking.status}</span></span>
            </div>
        </div>
        
        <div class="detail-section">
            <h6><i class="bi bi-cash-coin"></i> Informasi Pembayaran</h6>
            <div class="detail-row">
                <span class="detail-label">Total Harga</span>
                <span class="detail-value price-total">Rp ${formatNumber(booking.total_price)}</span>
            </div>
        </div>
        
        ${booking.notes ? `
            <div class="detail-section">
                <h6><i class="bi bi-file-text-fill"></i> Catatan</h6>
                <div class="notes-box">
                    <p>${booking.notes}</p>
                </div>
            </div>
        ` : ''}
        
        <div class="detail-row">
            <span class="detail-label">Booking Date</span>
            <span class="detail-value">${new Date(booking.created_at).toLocaleString('id-ID')}</span>
        </div>
    `;
    
    document.getElementById('modalContent').innerHTML = modalContent;
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();
}

/**
 * Confirm booking
 */
function confirmBooking(bookingId) {
    currentBookingId = bookingId;
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
}

/**
 * Reject booking
 */
function rejectBooking(bookingId) {
    currentBookingId = bookingId;
    document.getElementById('rejectReason').value = '';
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Confirm booking button
    document.getElementById('confirmBookingBtn').addEventListener('click', function() {
        if (!currentBookingId) return;
        
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        
        fetch('../../../../backend/user/owner/classes/update_booking_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                booking_id: currentBookingId,
                status: 'confirmed'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
                showNotification('Booking berhasil dikonfirmasi!, menunggu pembayaran dari customer', 'success');
                setTimeout(() => loadBookings(), 1000);
            } else {
                showNotification(data.message || 'Gagal mengkonfirmasi booking', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan', 'danger');
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-check-lg"></i> Ya, Setujui';
        });
    });
    
    // Reject booking button
    document.getElementById('rejectBookingBtn').addEventListener('click', function() {
        if (!currentBookingId) return;
        
        const reason = document.getElementById('rejectReason').value;
        
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        
        fetch('../../../../backend/user/owner/classes/update_booking_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                booking_id: currentBookingId,
                status: 'rejected',
                notes: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();
                showNotification('Booking berhasil ditolak', 'success');
                setTimeout(() => loadBookings(), 1000);
            } else {
                showNotification(data.message || 'Gagal menolak booking', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan', 'danger');
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-x-lg"></i> Ya, Tolak';
        });
    });
}

/**
 * Setup filters
 */
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

/**
 * Apply filters
 */
function applyFilters() {
    const filters = {
        status: document.getElementById('filterStatus').value,
        kos_id: document.getElementById('filterProperty').value,
        booking_type: document.getElementById('filterType').value,
        search: document.getElementById('searchInput').value
    };
    
    // Remove empty filters
    Object.keys(filters).forEach(key => {
        if (!filters[key]) delete filters[key];
    });
    
    loadBookings(filters);
}

/**
 * Helper functions
 */
function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

function calculateDays(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showNotification(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}