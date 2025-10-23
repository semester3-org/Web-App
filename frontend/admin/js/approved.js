// ============================================
// APPROVAL PROPERTY JAVASCRIPT
// ============================================

// Image Slider Functionality
document.addEventListener("DOMContentLoaded", function () {
  initializeSliders();
});

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

  // Send AJAX request with JSON
  const data = {
    action: "approve",
    property_id: propertyId,
  };

  console.log("Sending approve request:", data);

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

      console.log("Sending reject request:", data);

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
// MODERN PAGINATION ENHANCEMENT
// ============================================

document.addEventListener("DOMContentLoaded", function () {
  // Smooth scroll to top when clicking pagination
  const pageLinks = document.querySelectorAll(".page-link");

  pageLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      // Add loading state
      const paginationContainer = document.querySelector(".pagination-container");
      if (paginationContainer) {
        paginationContainer.classList.add("loading");
      }
      
      // Scroll to top of properties container with smooth animation
      const mainContent = document.querySelector(".main-content");
      if (mainContent) {
        mainContent.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    });
  });

  // Highlight current page in URL
  const urlParams = new URLSearchParams(window.location.search);
  const currentPage = urlParams.get("page") || "1";
  const currentStatus = urlParams.get("status") || "pending";

  // Update page title with current info
  updatePageInfo(currentPage, currentStatus);
});

// Update page information
function updatePageInfo(page, status) {
  const headerTitle = document.querySelector(".approval-header h1");
  if (headerTitle && page > 1) {
    const pageInfo = document.createElement("span");
    pageInfo.className = "page-info-badge";
    pageInfo.textContent = `Halaman ${page}`;
    pageInfo.style.cssText = `
            font-size: 14px;
            margin-left: 12px;
            padding: 6px 12px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 6px;
            font-weight: 600;
        `;
    headerTitle.appendChild(pageInfo);
  }
}

// Keyboard navigation for pagination
document.addEventListener("keydown", function (e) {
  const urlParams = new URLSearchParams(window.location.search);
  const currentPage = parseInt(urlParams.get("page") || "1");
  const status = urlParams.get("status") || "pending";

  // Get total pages from pagination
  const paginationLinks = document.querySelectorAll(".page-link:not(.active)");
  const lastPageLink = Array.from(paginationLinks)
    .filter((link) => !link.querySelector("i"))
    .pop();
  const totalPages = lastPageLink
    ? parseInt(lastPageLink.textContent)
    : currentPage;

  // Left arrow key - previous page
  if (e.key === "ArrowLeft" && currentPage > 1) {
    window.location.href = `?status=${status}&page=${currentPage - 1}`;
  }

  // Right arrow key - next page
  if (e.key === "ArrowRight" && currentPage < totalPages) {
    window.location.href = `?status=${status}&page=${currentPage + 1}`;
  }
});

// Add loading indicator when changing pages
document.querySelectorAll(".page-link").forEach((link) => {
  link.addEventListener("click", function () {
    const propertiesContainer = document.querySelector(".properties-container");
    if (propertiesContainer) {
      propertiesContainer.style.opacity = "0.5";
      propertiesContainer.style.pointerEvents = "none";

      // Add loading spinner
      const loadingDiv = document.createElement("div");
      loadingDiv.className = "page-loading";
      loadingDiv.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Memuat halaman...';
      loadingDiv.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 20px 40px;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                z-index: 9999;
                font-size: 16px;
                color: #10b981;
                font-weight: 600;
            `;
      document.body.appendChild(loadingDiv);
    }
  });
});

// ============================================
// ADD NOTIFICATION STYLES DYNAMICALLY
// ============================================
const notificationStyles = document.createElement("style");
notificationStyles.textContent = `
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        padding: 16px 20px;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 10000;
        animation: slideInRight 0.3s ease;
        min-width: 300px;
        max-width: 500px;
    }
    
    .notification.fade-out {
        animation: slideOutRight 0.3s ease;
    }
    
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
    }
`;
document.head.appendChild(notificationStyles);
