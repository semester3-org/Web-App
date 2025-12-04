// your_property.js – FINAL VERSION (Gambar Pasti Muncul!)

let deletePropertyId = null;

// === FUNGSI PEMBENAH PATH GAMBAR (INI YANG PALING PENTING) ===
function fixImageUrl(url) {
    if (!url || url.trim() === '') {
        return '/frontend/assets/no-image.png';
    }
    // Database menyimpan: uploads/kos/xxx.jpg (tanpa slash awal)
    // Kita harus pastikan selalu jadi: /uploads/kos/xxx.jpg
    return '/' + url.replace(/^\/+/g, ''); // tambah satu slash di depan, hapus yang berlebih
}

// Load properties on page load
document.addEventListener("DOMContentLoaded", function () {
    loadProperties();
    checkSuccessMessage();

    // Filter change event
    document.getElementById("filterStatus").addEventListener("change", function () {
        loadProperties(this.value);
    });

    // Delete confirmation
    document.getElementById("confirmDeleteBtn").addEventListener("click", function () {
        if (deletePropertyId) {
            deleteProperty(deletePropertyId);
        }
    });
});

// Load properties from server
function loadProperties(status = "") {
    const loadingState = document.getElementById("loadingState");
    const emptyState = document.getElementById("emptyState");
    const propertyGrid = document.getElementById("propertyGrid");

    loadingState.style.display = "block";
    emptyState.style.display = "none";
    propertyGrid.style.display = "none";

    fetch(`../../../../backend/user/owner/classes/get_properties.php?status=${status}`)
        .then(response => response.json())
        .then(data => {
            loadingState.style.display = "none";

            if (data.success && data.properties.length > 0) {
                propertyGrid.style.display = "flex"; // pastikan flex aktif
                propertyGrid.style.flexWrap = "wrap";
                renderProperties(data.properties);
            } else {
                emptyState.style.display = "block";
            }
        })
        .catch(error => {
            console.error("Error:", error);
            loadingState.style.display = "none";
            emptyState.style.display = "block";
        });
}

// Render properties to grid
function renderProperties(properties) {
    const grid = document.getElementById("propertyGrid");
    grid.innerHTML = "";

    properties.forEach(property => {
        const col = document.createElement("div");
        col.className = "col-xl-3 col-lg-4 col-md-6";

        const statusClass = `status-${property.status}`;
        const statusText = property.status.charAt(0).toUpperCase() + property.status.slice(1);

        // GUNAKAN fixImageUrl() DI SINI → GAMBAR PASTI MUNCUL!
        const imageSrc = fixImageUrl(property.image_url);

        col.innerHTML = `
            <div class="property-card">
                <img src="${imageSrc}" 
                     alt="${property.name}" 
                     class="property-image"
                     onerror="this.src='/frontend/assets/no-image.png'; this.onerror=null;">
                <div class="property-body">
                    <span class="status-badge ${statusClass}">${statusText}</span>
                    <h5 class="property-title">${property.name}</h5>
                    <div class="property-location">
                        <i class="bi bi-geo-alt-fill text-danger"></i>
                        ${property.city}, ${property.province}
                    </div>
                    <div class="property-price">
                        Rp ${formatNumber(property.price_monthly)} 
                        <small class="text-muted fs-6">/ bulan</small>
                    </div>
                    <div class="property-info">
                        <div class="info-item">
                            <i class="bi bi-door-closed-fill"></i>
                            <span>${property.available_rooms}/${property.total_rooms} Kamar</span>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-gender-ambiguous"></i>
                            <span>${property.kos_type}</span>
                        </div>
                    </div>
                    <div class="property-actions">
                        <button class="btn btn-action btn-edit" onclick="editProperty(${property.id})">
                            <i class="bi bi-pencil-fill"></i> Edit
                        </button>
                        <button class="btn btn-action btn-detail" onclick="viewDetail(${property.id})">
                            <i class="bi bi-eye-fill"></i> Detail
                        </button>
                        <button class="btn btn-action btn-delete" onclick="confirmDelete(${property.id}, '${property.name.replace(/'/g, "\\'")}')">
                            <i class="bi bi-trash-fill"></i> Hapus
                        </button>
                    </div>
                </div>
            </div>
        `;

        grid.appendChild(col);
    });
}

// Format number to Indonesian format
function formatNumber(num) {
    return new Intl.NumberFormat("id-ID").format(num);
}

// Edit property
function editProperty(id) {
    window.location.href = `edit_property.php?id=${id}`;
}

// View detail
function viewDetail(id) {
    window.location.href = `../pages/detail_property.php?id=${id}`;
}

// Confirm delete
function confirmDelete(id, name) {
    deletePropertyId = id;
    document.getElementById("deletePropertyName").textContent = name;
    const modal = new bootstrap.Modal(document.getElementById("deleteModal"));
    modal.show();
}

// Delete property
function deleteProperty(id) {
    const confirmBtn = document.getElementById("confirmDeleteBtn");
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menghapus...';

    fetch("../../../../backend/user/owner/action/delete_property.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: id })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById("deleteModal")).hide();
            showNotification("Properti berhasil dihapus!", "success");
            setTimeout(() => loadProperties(), 1000);
        } else {
            showNotification(data.message || "Gagal menghapus properti", "danger");
        }
    })
    .catch(err => {
        console.error(err);
        showNotification("Terjadi kesalahan server", "danger");
    })
    .finally(() => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="bi bi-trash"></i> Ya, Hapus';
    });
}

// Show notification
function showNotification(message, type) {
    const alert = document.createElement("div");
    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alert.style.cssText = "top:20px; right:20px; z-index:9999; min-width:300px;";
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    setTimeout(() => alert.remove(), 4000);
}

// Check success message from URL
function checkSuccessMessage() {
    const params = new URLSearchParams(window.location.search);
    const success = params.get("success");
    const message = params.get("message");

    if (success === "1" && message) {
        showNotification(decodeURIComponent(message), "success");
        history.replaceState({}, "", window.location.pathname);
    }
}