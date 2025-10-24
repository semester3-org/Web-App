// frontend/admin/js/users.js

// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    filterTable();
});

// Filter by type
document.getElementById('filterType').addEventListener('change', function() {
    filterTable();
});

function filterTable() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const filterType = document.getElementById('filterType').value.toLowerCase();
    const tableRows = document.querySelectorAll('.data-table tbody tr');
    
    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const userType = row.querySelector('.badge').textContent.toLowerCase();
        
        const matchSearch = text.includes(searchTerm);
        const matchType = !filterType || userType.includes(filterType);
        
        row.style.display = (matchSearch && matchType) ? '' : 'none';
    });
}

// View user detail
function viewUser(id) {
    fetch(`../../../backend/admin/actions/get_user_detail.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayUserDetail(data.user, data.properties);
                document.getElementById('userModal').style.display = 'block';
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengambil data user');
        });
}

function displayUserDetail(user, properties) {
    const content = `
        <div class="user-detail-section">
            <h3><i class="fas fa-user-circle"></i> Informasi User</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Username</div>
                    <div class="detail-value">${user.username}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Email</div>
                    <div class="detail-value">${user.email}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Nama Lengkap</div>
                    <div class="detail-value">${user.full_name}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">No. Telepon</div>
                    <div class="detail-value">${user.phone || '-'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Tipe User</div>
                    <div class="detail-value">
                        <span class="badge ${user.user_type === 'owner' ? 'badge-owner' : 'badge-user'}">
                            ${user.user_type === 'owner' ? 'Owner' : 'User'}
                        </span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Tanggal Daftar</div>
                    <div class="detail-value">${formatDate(user.created_at)}</div>
                </div>
            </div>
        </div>
        
        <div class="user-detail-section">
            <h3><i class="fas fa-home"></i> Properti yang Dimiliki (${properties.length})</h3>
            ${properties.length > 0 ? `
                <div class="property-list">
                    ${properties.map(property => `
                        <div class="property-item">
                            <h4>${property.name}</h4>
                            <p><i class="fas fa-map-marker-alt"></i> ${property.address}</p>
                            <p><i class="fas fa-calendar"></i> Ditambahkan: ${formatDate(property.created_at)}</p>
                            ${property.status ? `<p><i class="fas fa-info-circle"></i> Status: ${property.status}</p>` : ''}
                        </div>
                    `).join('')}
                </div>
            ` : `
                <div class="no-properties">
                    <i class="fas fa-home" style="font-size: 48px; color: var(--text-gray); margin-bottom: 16px;"></i>
                    <p>User ini belum memiliki properti</p>
                </div>
            `}
        </div>
    `;
    
    document.getElementById('userDetailContent').innerHTML = content;
}

function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
}

// Close modal
function closeModal() {
    document.getElementById('userModal').style.display = 'none';
}

// Delete user
function deleteUser(id) {
    if (confirm('Apakah Anda yakin ingin menghapus user ini? User yang memiliki properti tidak dapat dihapus.')) {
        fetch('../../../backend/admin/actions/delete_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus user');
        });
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('userModal');
    if (event.target === modal) {
        closeModal();
    }
}