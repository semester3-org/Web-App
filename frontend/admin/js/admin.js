// frontend/admin/js/admin.js

// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('.data-table tbody tr');
    
    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Open add modal
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Admin';
    document.getElementById('adminForm').reset();
    document.getElementById('adminId').value = '';
    document.getElementById('passwordLabel').innerHTML = 'Password <span class="required">*</span>';
    document.getElementById('password').required = true;
    document.querySelector('.form-hint').style.display = 'none';
    document.getElementById('adminModal').style.display = 'block';
}

// Edit admin
function editAdmin(id) {
    fetch(`../../../backend/admin/actions/get_admin.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').textContent = 'Edit Admin';
                document.getElementById('adminId').value = data.admin.id;
                document.getElementById('username').value = data.admin.username;
                document.getElementById('email').value = data.admin.email;
                document.getElementById('fullName').value = data.admin.full_name;
                document.getElementById('phone').value = data.admin.phone || '';
                document.getElementById('userType').value = data.admin.user_type;
                document.getElementById('password').value = '';
                document.getElementById('passwordLabel').innerHTML = 'Password';
                document.getElementById('password').required = false;
                document.querySelector('.form-hint').style.display = 'block';
                document.getElementById('adminModal').style.display = 'block';
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengambil data admin');
        });
}

// Close modal
function closeModal() {
    document.getElementById('adminModal').style.display = 'none';
    document.getElementById('adminForm').reset();
}

// Submit form
document.getElementById('adminForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const adminId = document.getElementById('adminId').value;
    const url = adminId ? '../../../backend/admin/actions/update_admin.php' : '../../../backend/admin/actions/create_admin.php';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeModal();
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menyimpan data');
    });
});

// Delete admin
function deleteAdmin(id) {
    if (confirm('Apakah Anda yakin ingin menghapus admin ini?')) {
        fetch('../../../backend/admin/actions/delete_admin.php', {
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
            alert('Terjadi kesalahan saat menghapus admin');
        });
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('adminModal');
    if (event.target === modal) {
        closeModal();
    }
}