// ============================================
// APPROVAL PROPERTY JAVASCRIPT WITH PAGINATION
// ============================================

let currentPage = 1;
let currentStatus = 'pending';
const itemsPerPage = 5;

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  // Get status from PHP or URL parameter or default to pending
  const urlParams = new URLSearchParams(window.location.search);
  currentStatus = window.initialFilterStatus || urlParams.get('status') || 'pending';
  
  // Load properties with pagination
  loadProperties(currentStatus, currentPage);
});

// ============================================
// LOAD PROPERTIES WITH PAGINATION
// ============================================
function loadProperties(status, page) {
  const container = document.querySelector('.properties-container');
  
  // Show loading
  container.innerHTML = `
    <div class="loading-state">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Memuat data...</p>
    </div>
  `;
  
  // Fetch properties with pagination
  fetch(`/Web-App/backend/admin/classes/approved_process.php?action=get_properties&status=${status}&page=${page}&limit=${itemsPerPage}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        currentPage = page;
        currentStatus = status;
        
        displayProperties(data.data.properties);
        displayPagination(data.data.pagination);
        
        // Update tab counts
        updateTabCounts(status);
        
        // Reinitialize sliders after loading
        setTimeout(() => {
          initializeSliders();
        }, 100);
      } else {
        container.innerHTML = `
          <div class="error-state">
            <i class="fas fa-exclamation-circle"></i>
            <p>${data.message || 'Gagal memuat data'}</p>
          </div>
        `;
      }
    })
    .catch(error => {
      console.error('Error:', error);
      container.innerHTML = `
        <div class="error-state">
          <i class="fas fa-exclamation-circle"></i>
          <p>Terjadi kesalahan saat memuat data</p>
        </div>
      `;
    });
}

// ============================================
// UPDATE TAB COUNTS
// ============================================
function updateTabCounts(currentStatus) {
  // Fetch fresh statistics
  fetch('/Web-App/backend/admin/classes/approved_process.php?action=get_stats')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const stats = data.stats;
        
        // Update stat cards
        document.getElementById('stat-pending').textContent = stats.pending;
        document.getElementById('stat-approved').textContent = stats.approved;
        document.getElementById('stat-rejected').textContent = stats.rejected;
        document.getElementById('stat-total').textContent = stats.total;
        
        // Update tab counts
        document.getElementById('tab-pending').textContent = stats.pending;
        document.getElementById('tab-approved').textContent = stats.approved;
        document.getElementById('tab-rejected').textContent = stats.rejected;
        document.getElementById('tab-all').textContent = stats.total;
      }
    })
    .catch(error => {
      console.error('Error updating stats:', error);
    });
}

// ============================================
// DISPLAY PROPERTIES
// ============================================
function displayProperties(properties) {
  const container = document.querySelector('.properties-container');
  
  if (properties.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>Tidak ada property</h3>
        <p>Belum ada property dengan status ${currentStatus}</p>
      </div>
    `;
    return;
  }
  
  let html = '';
  
  properties.forEach(property => {
    html += generatePropertyCard(property);
  });
  
  container.innerHTML = html;
}

// ============================================
// GENERATE PROPERTY CARD HTML
// ============================================
function generatePropertyCard(property) {
  let imagesHTML = '';
  
  if (property.images && property.images.length > 0) {
    let sliderImages = property.images.map((img, index) => {
      const imgName = img.split('/').pop();
      return `<img src="../../../uploads/kos/${imgName}" 
                   alt="${property.name}" 
                   class="${index === 0 ? 'active' : ''}">`;
    }).join('');
    
    let sliderControls = property.images.length > 1 ? `
      <button class="slider-btn prev-btn"><i class="fas fa-chevron-left"></i></button>
      <button class="slider-btn next-btn"><i class="fas fa-chevron-right"></i></button>
      <div class="image-counter">${property.images.length} Foto</div>
    ` : '';
    
    imagesHTML = `
      <div class="image-slider">
        ${sliderImages}
        ${sliderControls}
      </div>
    `;
  } else {
    imagesHTML = `
      <div class="no-image">
        <i class="fas fa-image"></i>
        <p>Tidak ada foto</p>
      </div>
    `;
  }
  
  let facilitiesHTML = '';
  if (property.facilities && property.facilities.length > 0) {
    facilitiesHTML = `
      <div class="facilities">
        <strong><i class="fas fa-check"></i> Fasilitas:</strong>
        <div class="facility-tags">
          ${property.facilities.map(f => `<span class="facility-tag">${f}</span>`).join('')}
        </div>
      </div>
    `;
  }
  
  let descriptionHTML = property.description ? `
    <div class="description">
      <strong><i class="fas fa-align-left"></i> Deskripsi:</strong>
      <p>${property.description.replace(/\n/g, '<br>')}</p>
    </div>
  ` : '';
  
  let rejectionHTML = '';
  if (property.status === 'rejected' && property.rejection_reason) {
    rejectionHTML = `
      <div class="rejection-info">
        <strong><i class="fas fa-exclamation-triangle"></i> Alasan Penolakan:</strong>
        <p>${property.rejection_reason.replace(/\n/g, '<br>')}</p>
        <small>Ditolak oleh: ${property.rejected_by} pada ${formatDateTime(property.rejected_at)}</small>
      </div>
    `;
  }
  
  let approvalHTML = '';
  if (property.status === 'approved') {
    approvalHTML = `
      <div class="approval-info">
        <i class="fas fa-check-circle"></i>
        <span>Disetujui oleh: ${property.verified_by_name} pada ${formatDateTime(property.verified_at)}</span>
      </div>
    `;
  }
  
  let actionsHTML = `
    <button class="btn-detail" onclick="viewDetail(${property.id})">
      <i class="fas fa-eye"></i> Detail Lengkap
    </button>
  `;
  
  if (property.status === 'pending') {
    actionsHTML += `
      <button class="btn-approve" onclick="approveProperty(${property.id})">
        <i class="fas fa-check"></i> Setujui
      </button>
      <button class="btn-reject" onclick="showRejectModal(${property.id}, '${property.name.replace(/'/g, "\\'")}')">
        <i class="fas fa-times"></i> Tolak
      </button>
    `;
  }
  
  return `
    <div class="property-card" data-property-id="${property.id}">
      <div class="property-images">
        ${imagesHTML}
      </div>
      
      <div class="property-info">
        <div class="property-header">
          <h3>${property.name}</h3>
          <span class="status-badge status-${property.status}">
            ${property.status.charAt(0).toUpperCase() + property.status.slice(1)}
          </span>
        </div>
        
        <div class="property-details">
          <div class="detail-item">
            <i class="fas fa-user"></i>
            <span>Pemilik: ${property.owner_name}</span>
          </div>
          <div class="detail-item">
            <i class="fas fa-map-marker-alt"></i>
            <span>${property.city}, ${property.province}</span>
          </div>
          <div class="detail-item">
            <i class="fas fa-venus-mars"></i>
            <span>Tipe: ${property.kos_type.charAt(0).toUpperCase() + property.kos_type.slice(1)}</span>
          </div>
          <div class="detail-item">
            <i class="fas fa-door-open"></i>
            <span>${property.available_rooms} / ${property.total_rooms} Kamar Tersedia</span>
          </div>
          <div class="detail-item">
            <i class="fas fa-money-bill-wave"></i>
            <span>Rp ${Number(property.price_monthly).toLocaleString('id-ID')} / bulan</span>
          </div>
          <div class="detail-item">
            <i class="fas fa-calendar"></i>
            <span>Diajukan: ${formatDateTime(property.created_at)}</span>
          </div>
        </div>

        ${facilitiesHTML}
        ${descriptionHTML}
        ${rejectionHTML}
        ${approvalHTML}

        <div class="property-actions">
          ${actionsHTML}
        </div>
      </div>
    </div>
  `;
}

// ============================================
// DISPLAY PAGINATION
// ============================================
function displayPagination(pagination) {
  const container = document.querySelector('.properties-container');
  
  if (pagination.total_pages <= 1) {
    return; // No pagination needed
  }
  
  let paginationHTML = '<div class="pagination-container">';
  
  // Previous button
  if (pagination.current_page > 1) {
    paginationHTML += `
      <button class="pagination-btn" onclick="loadProperties('${currentStatus}', ${pagination.current_page - 1})">
        <i class="fas fa-chevron-left"></i> Sebelumnya
      </button>
    `;
  }
  
  // Page numbers
  paginationHTML += '<div class="pagination-numbers">';
  
  const maxVisible = 5;
  let startPage = Math.max(1, pagination.current_page - Math.floor(maxVisible / 2));
  let endPage = Math.min(pagination.total_pages, startPage + maxVisible - 1);
  
  if (endPage - startPage < maxVisible - 1) {
    startPage = Math.max(1, endPage - maxVisible + 1);
  }
  
  if (startPage > 1) {
    paginationHTML += `
      <button class="pagination-num" onclick="loadProperties('${currentStatus}', 1)">1</button>
    `;
    if (startPage > 2) {
      paginationHTML += '<span class="pagination-dots">...</span>';
    }
  }
  
  for (let i = startPage; i <= endPage; i++) {
    const activeClass = i === pagination.current_page ? 'active' : '';
    paginationHTML += `
      <button class="pagination-num ${activeClass}" onclick="loadProperties('${currentStatus}', ${i})">${i}</button>
    `;
  }
  
  if (endPage < pagination.total_pages) {
    if (endPage < pagination.total_pages - 1) {
      paginationHTML += '<span class="pagination-dots">...</span>';
    }
    paginationHTML += `
      <button class="pagination-num" onclick="loadProperties('${currentStatus}', ${pagination.total_pages})">${pagination.total_pages}</button>
    `;
  }
  
  paginationHTML += '</div>';
  
  // Next button
  if (pagination.current_page < pagination.total_pages) {
    paginationHTML += `
      <button class="pagination-btn" onclick="loadProperties('${currentStatus}', ${pagination.current_page + 1})">
        Selanjutnya <i class="fas fa-chevron-right"></i>
      </button>
    `;
  }
  
  paginationHTML += `
    <div class="pagination-info">
      Menampilkan ${((pagination.current_page - 1) * pagination.per_page) + 1} - ${Math.min(pagination.current_page * pagination.per_page, pagination.total_items)} dari ${pagination.total_items} property
    </div>
  `;
  
  paginationHTML += '</div>';
  
  container.insertAdjacentHTML('beforeend', paginationHTML);
}

// ============================================
// FILTER TAB CLICK
// ============================================
document.addEventListener('DOMContentLoaded', function() {
  const filterTabs = document.querySelectorAll('.tab-btn');
  
  filterTabs.forEach(tab => {
    tab.addEventListener('click', function(e) {
      e.preventDefault();
      
      const url = new URL(this.href);
      const status = url.searchParams.get('status') || 'pending';
      
      // Update active tab
      filterTabs.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      
      // Load properties with new status
      loadProperties(status, 1);
      
      // Update URL without reload
      window.history.pushState({}, '', `?status=${status}`);
    });
  });
});

// ============================================
// IMAGE SLIDER FUNCTIONALITY
// ============================================
function initializeSliders() {
  const propertyCards = document.querySelectorAll(".property-card");

  propertyCards.forEach((card) => {
    const slider = card.querySelector(".image-slider");
    if (!slider) return;

    const images = slider.querySelectorAll("img");
    const prevBtn = slider.querySelector(".prev-btn");
    const nextBtn = slider.querySelector(".next-btn");

    if (images.length <= 1) return;

    let currentIndex = 0;

    function showImage(index) {
      images.forEach((img) => img.classList.remove("active"));
      images[index].classList.add("active");
    }

    if (prevBtn) {
      prevBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        currentIndex = (currentIndex - 1 + images.length) % images.length;
        showImage(currentIndex);
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        currentIndex = (currentIndex + 1) % images.length;
        showImage(currentIndex);
      });
    }
  });
}

// ============================================
// APPROVE PROPERTY
// ============================================
function approveProperty(propertyId) {
  if (!confirm("Apakah Anda yakin ingin menyetujui property ini?")) {
    return;
  }

  // Show loading
  const btn = event.target.closest("button");
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
  btn.disabled = true;

  // Send AJAX request
  fetch("/Web-App/backend/admin/classes/approved_process.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `action=approve&property_id=${propertyId}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification("Property berhasil disetujui!", "success");
        setTimeout(() => {
          // Reload current page
          loadProperties(currentStatus, currentPage);
          // Refresh stats
          location.reload();
        }, 1500);
      } else {
        showNotification(data.message || "Gagal menyetujui property", "error");
        btn.innerHTML = originalText;
        btn.disabled = false;
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("Terjadi kesalahan sistem", "error");
      btn.innerHTML = originalText;
      btn.disabled = false;
    });
}

// ============================================
// REJECT PROPERTY MODAL
// ============================================
function showRejectModal(propertyId, propertyName) {
  const modal = document.getElementById("rejectModal");
  const propertyNameEl = document.getElementById("propertyName");
  const propertyIdInput = document.getElementById("rejectPropertyId");
  const reasonTextarea = document.getElementById("rejectReason");

  propertyNameEl.textContent = propertyName;
  propertyIdInput.value = propertyId;
  reasonTextarea.value = "";

  modal.classList.add("active");
  reasonTextarea.focus();
}

function closeRejectModal() {
  const modal = document.getElementById("rejectModal");
  modal.classList.remove("active");
}

// Handle reject form submission
document.addEventListener("DOMContentLoaded", function () {
  const rejectForm = document.getElementById("rejectForm");

  if (rejectForm) {
    rejectForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const propertyId = document.getElementById("rejectPropertyId").value;
      const reason = document.getElementById("rejectReason").value.trim();

      if (!reason) {
        showNotification("Alasan penolakan harus diisi", "error");
        return;
      }

      // Show loading
      const submitBtn = rejectForm.querySelector(".btn-confirm-reject");
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Memproses...';
      submitBtn.disabled = true;

      // Send AJAX request with JSON
      const data = {
        action: "reject",
        property_id: propertyId,
        reason: reason,
      };

      fetch("../../../backend/admin/classes/approved_process.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            showNotification("Property berhasil ditolak", "success");
            closeRejectModal();
            setTimeout(() => {
              // Reload current page
              loadProperties(currentStatus, currentPage);
              // Refresh stats
              location.reload();
            }, 1500);
          } else {
            showNotification(data.message || "Gagal menolak property", "error");
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showNotification("Terjadi kesalahan sistem", "error");
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        });
    });
  }
});

// Close modal when clicking outside
document.addEventListener("click", function (e) {
  const modal = document.getElementById("rejectModal");
  if (e.target === modal) {
    closeRejectModal();
  }

  const detailModal = document.getElementById("detailModal");
  if (e.target === detailModal) {
    closeDetailModal();
  }
});

// ============================================
// VIEW DETAIL PROPERTY
// ============================================
function viewDetail(propertyId) {
  const modal = document.getElementById("detailModal");
  const detailContent = document.getElementById("detailContent");

  modal.classList.add("active");
  detailContent.innerHTML =
    '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>';

  // Fetch property details
  fetch(
    "/Web-App/backend/admin/classes/approved_process.php?action=detail&property_id=" +
      propertyId,
    {
      method: "GET",
    }
  )
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        displayPropertyDetail(data.property);
      } else {
        detailContent.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-circle"></i> ${data.message}</div>`;
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      detailContent.innerHTML =
        '<div class="error-message"><i class="fas fa-exclamation-circle"></i> Gagal memuat data</div>';
    });
}

function displayPropertyDetail(property) {
  const detailContent = document.getElementById("detailContent");

  let imagesHTML = "";
  if (property.images && property.images.length > 0) {
    imagesHTML = `
<<<<<<< HEAD
      <div class="detail-images">
        <div class="main-image">
          <img src="../../../uploads/kos/${property.images[0].split("/").pop()}" 
               alt="${property.name}" 
               id="mainDetailImage">
        </div>
        <div class="thumbnail-images">
          ${property.images
            .map(
              (img, index) => `
                <img src="../../../uploads/kos/${img.split("/").pop()}" 
                     alt="${property.name}" 
                     onclick="changeDetailImage('../../../uploads/kos/${img.split("/").pop()}')" 
                     class="${index === 0 ? "active" : ""}">
              `
            )
            .join("")}
        </div>
      </div>
    `;
  }
  
  let facilitiesHTML = "";
  if (property.facilities && property.facilities.length > 0) {
    facilitiesHTML = `
      <div class="detail-section">
        <h3><i class="fas fa-check-circle"></i> Fasilitas</h3>
        <div class="facility-grid">
          ${property.facilities
            .map(
              (facility) => `
                <div class="facility-item">
                  <i class="fas fa-check"></i>
                  <span>${facility}</span>
                </div>
              `
            )
            .join("")}
        </div>
      </div>
    `;
  }

  detailContent.innerHTML = `
    <div class="property-detail">
      ${imagesHTML}
      
      <div class="detail-section">
        <div class="detail-header">
          <h2>${property.name}</h2>
          <span class="status-badge status-${property.status}">${property.status}</span>
=======
    <div class="detail-images">
      <div class="main-image">
        <img src="../../../uploads/kos/${property.images[0].split("/").pop()}" 
             alt="${property.name}" 
             id="mainDetailImage">
      </div>
      <div class="thumbnail-images">
        ${property.images
          .map(
            (img, index) => `
              <img src="../../../uploads/kos/${img.split("/").pop()}" 
                   alt="${property.name}" 
                   onclick="changeDetailImage('../../../uploads/kos/${img
                     .split("/")
                     .pop()}')" 
                   class="${index === 0 ? "active" : ""}">
            `
          )
          .join("")}
      </div>
    </div>
  
  `;
  }
  let facilitiesHTML = "";
  if (property.facilities && property.facilities.length > 0) {
    facilitiesHTML = `
    <div class="detail-section">
      <h3><i class="fas fa-check-circle"></i> Fasilitas</h3>
      <div class="facility-grid">
        ${property.facilities
          .map(
            (facility) => `
              <div class="facility-item">
                <i class="fas ${facility.icon || "fa-check"}"></i>
                <span>${facility.name}</span>
              </div>
            `
          )
          .join("")}
      </div>
    </div>
  `;
  }

  detailContent.innerHTML = `
        <div class="property-detail">
            ${imagesHTML}
            
            <div class="detail-section">
                <div class="detail-header">
                    <h2>${property.name}</h2>
                    <span class="status-badge status-${property.status}">${
    property.status
  }</span>
                </div>
            </div>
            
            <div class="detail-section">
                <h3><i class="fas fa-info-circle"></i> Informasi Dasar</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Pemilik:</span>
                        <span class="info-value">${property.owner_name}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value">${property.owner_email}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Telepon:</span>
                        <span class="info-value">${
                          property.owner_phone || "-"
                        }</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tipe Kos:</span>
                        <span class="info-value">${property.kos_type}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Kamar:</span>
                        <span class="info-value">${property.total_rooms}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Kamar Tersedia:</span>
                        <span class="info-value">${
                          property.available_rooms
                        }</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Harga/Bulan:</span>
                        <span class="info-value">Rp ${Number(
                          property.price_monthly
                        ).toLocaleString("id-ID")}</span>
                    </div>
                    ${
                      property.price_daily
                        ? `
                    <div class="info-item">
                        <span class="info-label">Harga/Hari:</span>
                        <span class="info-value">Rp ${Number(
                          property.price_daily
                        ).toLocaleString("id-ID")}</span>
                    </div>
                    `
                        : ""
                    }
                </div>
            </div>
            
            <div class="detail-section">
                <h3><i class="fas fa-map-marker-alt"></i> Lokasi</h3>
                <div class="info-grid">
                    <div class="info-item full-width">
                        <span class="info-label">Alamat:</span>
                        <span class="info-value">${property.address}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Kota:</span>
                        <span class="info-value">${property.city}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Provinsi:</span>
                        <span class="info-value">${property.province}</span>
                    </div>
                    ${
                      property.postal_code
                        ? `
                    <div class="info-item">
                        <span class="info-label">Kode Pos:</span>
                        <span class="info-value">${property.postal_code}</span>
                    </div>
                    `
                        : ""
                    }
                    ${
                      property.latitude && property.longitude
                        ? `
                    <div class="info-item">
                        <span class="info-label">Koordinat:</span>
                        <span class="info-value">${property.latitude}, ${property.longitude}</span>
                    </div>
                    <div class="info-item full-width">
                        <a href="https://www.google.com/maps?q=${property.latitude},${property.longitude}" 
                          target="_blank" 
                          class="btn-maps">
                          <i class="fas fa-map-marked-alt"></i> Lihat di Google Maps
                        </a>
                   </div>
                    `
                        : ""
                    }
                </div>
            </div>
            
            ${
              property.description
                ? `
            <div class="detail-section">
                <h3><i class="fas fa-align-left"></i> Deskripsi</h3>
                <p class="description-text">${property.description.replace(
                  /\n/g,
                  "<br>"
                )}</p>
            </div>
            `
                : ""
            }
            
            ${facilitiesHTML}
            
            ${
              property.rules
                ? `
            <div class="detail-section">
                <h3><i class="fas fa-list"></i> Peraturan</h3>
                <p class="rules-text">${property.rules.replace(
                  /\n/g,
                  "<br>"
                )}</p>
            </div>
            `
                : ""
            }
            
            <div class="detail-section">
                <h3><i class="fas fa-clock"></i> Informasi Waktu</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Dibuat:</span>
                        <span class="info-value">${formatDateTime(
                          property.created_at
                        )}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Diupdate:</span>
                        <span class="info-value">${formatDateTime(
                          property.updated_at
                        )}</span>
                    </div>
                </div>
            </div>
>>>>>>> 99e59ed2a7a58e7bbee6bf1838809084885d5afc
        </div>
      </div>
      
      <div class="detail-section">
        <h3><i class="fas fa-info-circle"></i> Informasi Dasar</h3>
        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">Pemilik:</span>
            <span class="info-value">${property.owner_name}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Email:</span>
            <span class="info-value">${property.owner_email}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Telepon:</span>
            <span class="info-value">${property.owner_phone || "-"}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Tipe Kos:</span>
            <span class="info-value">${property.kos_type}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Total Kamar:</span>
            <span class="info-value">${property.total_rooms}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Kamar Tersedia:</span>
            <span class="info-value">${property.available_rooms}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Harga/Bulan:</span>
            <span class="info-value">Rp ${Number(property.price_monthly).toLocaleString("id-ID")}</span>
          </div>
          ${property.price_daily ? `
            <div class="info-item">
              <span class="info-label">Harga/Hari:</span>
              <span class="info-value">Rp ${Number(property.price_daily).toLocaleString("id-ID")}</span>
            </div>
          ` : ""}
        </div>
      </div>
      
      <div class="detail-section">
        <h3><i class="fas fa-map-marker-alt"></i> Lokasi</h3>
        <div class="info-grid">
          <div class="info-item full-width">
            <span class="info-label">Alamat:</span>
            <span class="info-value">${property.address}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Kota:</span>
            <span class="info-value">${property.city}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Provinsi:</span>
            <span class="info-value">${property.province}</span>
          </div>
          ${property.postal_code ? `
            <div class="info-item">
              <span class="info-label">Kode Pos:</span>
              <span class="info-value">${property.postal_code}</span>
            </div>
          ` : ""}
          ${property.latitude && property.longitude ? `
            <div class="info-item">
              <span class="info-label">Koordinat:</span>
              <span class="info-value">${property.latitude}, ${property.longitude}</span>
            </div>
          ` : ""}
        </div>
      </div>
      
      ${property.description ? `
        <div class="detail-section">
          <h3><i class="fas fa-align-left"></i> Deskripsi</h3>
          <p class="description-text">${property.description.replace(/\n/g, "<br>")}</p>
        </div>
      ` : ""}
      
      ${facilitiesHTML}
      
      ${property.rules ? `
        <div class="detail-section">
          <h3><i class="fas fa-list"></i> Peraturan</h3>
          <p class="rules-text">${property.rules.replace(/\n/g, "<br>")}</p>
        </div>
      ` : ""}
      
      <div class="detail-section">
        <h3><i class="fas fa-clock"></i> Informasi Waktu</h3>
        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">Dibuat:</span>
            <span class="info-value">${formatDateTime(property.created_at)}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Diupdate:</span>
            <span class="info-value">${formatDateTime(property.updated_at)}</span>
          </div>
        </div>
      </div>
    </div>
  `;
}

function changeDetailImage(imageSrc) {
  const mainImage = document.getElementById("mainDetailImage");
  const thumbnails = document.querySelectorAll(".thumbnail-images img");

  mainImage.src = imageSrc;

  thumbnails.forEach((thumb) => {
    thumb.classList.remove("active");
    if (thumb.src === imageSrc) {
      thumb.classList.add("active");
    }
  });
}

function closeDetailModal() {
  const modal = document.getElementById("detailModal");
  modal.classList.remove("active");
}

// ============================================
// NOTIFICATION SYSTEM
// ============================================
function showNotification(message, type = "info") {
  // Remove existing notification
  const existingNotif = document.querySelector(".notification");
  if (existingNotif) {
    existingNotif.remove();
  }

  // Create notification
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;

  let icon = "fa-info-circle";
  if (type === "success") icon = "fa-check-circle";
  if (type === "error") icon = "fa-exclamation-circle";
  if (type === "warning") icon = "fa-exclamation-triangle";

  notification.innerHTML = `
    <i class="fas ${icon}"></i>
    <span>${message}</span>
    <button onclick="this.parentElement.remove()" class="close-notif">&times;</button>
  `;

  document.body.appendChild(notification);

  // Auto remove after 5 seconds
  setTimeout(() => {
    if (notification.parentElement) {
      notification.classList.add("fade-out");
      setTimeout(() => notification.remove(), 300);
    }
  }, 5000);
}

// ============================================
// UTILITY FUNCTIONS
// ============================================
function formatDateTime(dateTimeString) {
  const date = new Date(dateTimeString);
  const options = {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  };
  return date.toLocaleDateString("id-ID", options);
}

// ============================================
// ADD STYLES DYNAMICALLY
// ============================================
const styles = document.createElement("style");
styles.textContent = `
  .pagination-container {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    margin-top: 40px;
    padding: 30px 0;
    flex-wrap: wrap;
  }
  
  .pagination-numbers {
    display: flex;
    gap: 8px;
    align-items: center;
  }
  
  .pagination-btn, .pagination-num {
    padding: 10px 16px;
    border: 2px solid #e5e7eb;
    background: white;
    color: #4b5563;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  
  .pagination-btn:hover, .pagination-num:hover {
    border-color: #10b981;
    color: #10b981;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
  }
  
  .pagination-num.active {
    background: #10b981;
    border-color: #10b981;
    color: white;
  }
  
  .pagination-dots {
    color: #9ca3af;
    padding: 0 5px;
  }
  
  .pagination-info {
    width: 100%;
    text-align: center;
    color: #6b7280;
    font-size: 14px;
    margin-top: 15px;
  }
  
  .loading-state, .error-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
  }
  
  .loading-state i, .error-state i {
    font-size: 48px;
    margin-bottom: 20px;
    display: block;
  }
  
  .loading-state i {
    color: #10b981;
  }
  
  .error-state i {
    color: #ef4444;
  }
  
  @media (max-width: 768px) {
    .pagination-container {
      gap: 10px;
    }
    
    .pagination-btn, .pagination-num {
      padding: 8px 12px;
      font-size: 13px;
    }
    
<<<<<<< HEAD
    .pagination-info {
      font-size: 13px;
=======
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
        
    
    .notification i {
        font-size: 24px;
    }
    
    .notification-success {
        border-left: 4px solid #22c55e;
    }
    
    .notification-success i {
        color: #22c55e;
    }
    
    .notification-error {
        border-left: 4px solid #ef4444;
    }
    
    .notification-error i {
        color: #ef4444;
    }
    
    .notification-warning {
        border-left: 4px solid #f59e0b;
    }
    
    .notification-warning i {
        color: #f59e0b;
    }
    
    .notification-info {
        border-left: 4px solid #3b82f6;
    }
    
    .notification-info i {
        color: #3b82f6;
    }
    
    .notification span {
        flex: 1;
        color: #1f2937;
        font-size: 14px;
    }
    
    .close-notif {
        background: none;
        border: none;
        font-size: 24px;
        color: #6b7280;
        cursor: pointer;
        padding: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.3s ease;
    }
    
    .close-notif:hover {
        color: #1f2937;
    }
    
    /* Detail Modal Additional Styles */
    .detail-images {
        margin-bottom: 30px;
    }
    
    .main-image {
        width: 100%;
        height: 400px;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 15px;
    }
    
    .main-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .thumbnail-images {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 10px;
    }
    
    .thumbnail-images img {
        width: 100%;
        height: 80px;
        object-fit: cover;
        border-radius: 8px;
        cursor: pointer;
        border: 3px solid transparent;
        transition: all 0.3s ease;
    }
    
    .thumbnail-images img:hover {
        border-color: #10b981;
        transform: scale(1.05);
    }
    
    .thumbnail-images img.active {
        border-color: #10b981;
    }
    
    .detail-section {
        margin-bottom: 30px;
        padding-bottom: 30px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .detail-section:last-child {
        border-bottom: none;
    }
    
    .detail-section h3 {
        font-size: 18px;
        color: #1f2937;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .detail-section h3 i {
        color: #10b981;
    }
    
    .detail-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        gap: 20px;
    }
    
    .detail-header h2 {
        font-size: 28px;
        color: #1f2937;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .info-item.full-width {
        grid-column: 1 / -1;
    }
    
    .info-label {
        font-size: 13px;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .info-value {
        font-size: 15px;
        color: #1f2937;
    }
    
    .description-text, .rules-text {
        color: #4b5563;
        line-height: 1.8;
        font-size: 15px;
    }
    
    .facility-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .facility-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        background: #f0fdf4;
        border-radius: 8px;
        border-left: 3px solid #10b981;
    }
    
    .facility-item i {
        color: #10b981;
        font-size: 16px;
    }
    
    .facility-item span {
        color: #1f2937;
        font-size: 14px;
    }

      /* Google Maps Button */
    .btn-maps {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 24px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        text-decoration: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
    }
    
    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .facility-grid {
            grid-template-columns: 1fr;
        }
        
        .main-image {
            height: 250px;
        }
        
        .thumbnail-images {
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        }
        
        .thumbnail-images img {
            height: 60px;
        }
>>>>>>> 99e59ed2a7a58e7bbee6bf1838809084885d5afc
    }
  }
`;
document.head.appendChild(styles);