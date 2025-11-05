// Your Property JavaScript
let deletePropertyId = null;

// Load properties on page load
document.addEventListener("DOMContentLoaded", function () {
  loadProperties();
  checkSuccessMessage();

  // Filter change event
  document
    .getElementById("filterStatus")
    .addEventListener("change", function () {
      loadProperties(this.value);
    });

  // Delete confirmation
  document
    .getElementById("confirmDeleteBtn")
    .addEventListener("click", function () {
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

  fetch(
    `../../../../backend/user/owner/classes/get_properties.php?status=${status}`
  )
    .then((response) => response.json())
    .then((data) => {
      loadingState.style.display = "none";

      if (data.success && data.properties.length > 0) {
        propertyGrid.style.display = "block";
        renderProperties(data.properties);
      } else {
        emptyState.style.display = "block";
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      loadingState.style.display = "none";
      emptyState.style.display = "block";
    });
}

// Render properties to grid
function renderProperties(properties) {
  const grid = document.getElementById("propertyGrid");
  grid.innerHTML = "";

  properties.forEach((property) => {
    const col = document.createElement("div");
    col.className = "col-xl-3 col-lg-4 col-md-6";

    const statusClass = `status-${property.status}`;
    const statusText =
      property.status.charAt(0).toUpperCase() + property.status.slice(1);

    col.innerHTML = `
      <div class="property-card">
          <img src="/Web-App/${property.image_url}" alt="${
      property.name
    }" class="property-image">
          <div class="property-body">
              <span class="status-badge ${statusClass}">${statusText}</span>
              <h5 class="property-title">${property.name}</h5>
              <div class="property-location">
                  <i class="bi bi-geo-alt-fill text-danger"></i>
                  ${property.city}, ${property.province}
              </div>
              <div class="property-price">
                  Rp ${formatNumber(
                    property.price_monthly
                  )} <small class="text-muted fs-6">/ bulan</small>
              </div>
              <div class="property-info">
                  <div class="info-item">
                      <i class="bi bi-door-closed-fill"></i>
                      <span>${property.available_rooms}/${
      property.total_rooms
    } Kamar</span>
                  </div>
                  <div class="info-item">
                      <i class="bi bi-gender-ambiguous"></i>
                      <span>${property.kos_type}</span>
                  </div>
              </div>
              <div class="property-actions">
                  <button class="btn btn-action btn-edit" onclick="editProperty(${
                    property.id
                  })">
                      <i class="bi bi-pencil-fill"></i> Edit
                  </button>
                  <button class="btn btn-action btn-detail" onclick="viewDetail(${
                    property.id
                  })">
                      <i class="bi bi-eye-fill"></i> Detail
                  </button>
                  <button class="btn btn-action btn-delete" onclick="confirmDelete(${
                    property.id
                  }, '${property.name}')">
                      <i class="bi bi-trash-fill"></i> Hapus
                  </button>
              </div>
          </div>
      </div>
    `;

    grid.appendChild(col);
  });

  // ✅ tampilkan grid setelah dirender
  grid.style.display = "flex";
  grid.style.flexWrap = "wrap";
}

// Format number to Indonesian currency
function formatNumber(num) {
  return new Intl.NumberFormat("id-ID").format(num);
}

// Edit property
function editProperty(id) {
  window.location.href = `edit_property.php?id=${id}`;
}

// View detail (placeholder for now)
function viewDetail(id) {
  const targetUrl = `../pages/detail_property.php?id=${id}`;
  console.log("Redirecting to:", targetUrl);
  window.location.href = targetUrl;
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
  confirmBtn.innerHTML =
    '<span class="spinner-border spinner-border-sm me-2"></span>Menghapus...';

  fetch("../../../../backend/user/owner/action/delete_property.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ id: id }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Close modal
        const modal = bootstrap.Modal.getInstance(
          document.getElementById("deleteModal")
        );
        modal.hide();

        // Show success message
        showNotification("Properti berhasil dihapus!", "success");

        // Reload properties
        setTimeout(() => {
          loadProperties();
        }, 1000);
      } else {
        showNotification(data.message || "Gagal menghapus properti", "danger");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("Terjadi kesalahan saat menghapus properti", "danger");
    })
    .finally(() => {
      confirmBtn.disabled = false;
      confirmBtn.innerHTML = '<i class="bi bi-trash"></i> Ya, Hapus';
    });
}

// Show notification
function showNotification(message, type) {
  const alertDiv = document.createElement("div");
  alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
  alertDiv.style.zIndex = "9999";
  alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

  document.body.appendChild(alertDiv);

  setTimeout(() => {
    alertDiv.remove();
  }, 3000);
}

// ADD THIS NEW FUNCTION: (taruh di akhir file sebelum function definitions)
function checkSuccessMessage() {
  // Get URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  const success = urlParams.get("success");
  const message = urlParams.get("message");

  if (success === "1" && message) {
    showSuccessNotification(decodeURIComponent(message));
    // Clean URL
    window.history.replaceState({}, document.title, window.location.pathname);
  }
}

function showSuccessNotification(message) {
  const notification = document.createElement("div");
  notification.className = "success-notification";
  notification.innerHTML = `
        <div class="success-content">
            <i class="bi bi-check-circle-fill"></i>
            <div class="success-text">
                <strong>Berhasil!</strong>
                <p>${message}</p>
            </div>
            <button class="success-close" onclick="this.parentElement.parentElement.remove()">
                <i class="bi bi-x"></i>
            </button>
        </div>
    `;

  document.body.appendChild(notification);

  // Auto remove after 5 seconds
  setTimeout(() => {
    notification.classList.add("fade-out");
    setTimeout(() => notification.remove(), 300);
  }, 5000);
}
