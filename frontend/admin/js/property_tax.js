// frontend/admin/js/property-tax.js

// Search functionality
document.getElementById("searchInput").addEventListener("keyup", function () {
  filterProperties();
});

// Filter by city
document.getElementById("filterCity").addEventListener("change", function () {
  filterProperties();
});

// Filter by status
document.getElementById("filterStatus").addEventListener("change", function () {
  filterProperties();
});

function filterProperties() {
  const searchTerm = document.getElementById("searchInput").value.toLowerCase();
  const filterCity = document.getElementById("filterCity").value.toLowerCase();
  const filterStatus = document
    .getElementById("filterStatus")
    .value.toLowerCase();
  const propertyCards = document.querySelectorAll(".property-card");

  let visibleCount = 0;

  propertyCards.forEach((card) => {
    const text = card.textContent.toLowerCase();
    const city = card.getAttribute("data-city").toLowerCase();
    const status = card.getAttribute("data-status").toLowerCase();

    const matchSearch = text.includes(searchTerm);
    const matchCity = !filterCity || city === filterCity;
    const matchStatus = !filterStatus || status === filterStatus;

    if (matchSearch && matchCity && matchStatus) {
      card.style.display = "";
      visibleCount++;
    } else {
      card.style.display = "none";
    }
  });

  // Show/hide empty state
  const emptyState = document.querySelector(".empty-state");
  if (emptyState) {
    emptyState.style.display = visibleCount === 0 ? "block" : "none";
  }
}

// Format number with thousands separator
function formatNumber(num) {
  return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// View property detail
function viewPropertyDetail(propertyId) {
  fetch(
    `../../../backend/admin/actions/get_property_detail.php?id=${propertyId}`
  )
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        displayPropertyDetail(data.property);
        document.getElementById("propertyDetailModal").style.display = "block";
      } else {
        alert(data.message || "Gagal mengambil detail properti");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Terjadi kesalahan saat mengambil detail properti");
    });
}

function displayPropertyDetail(property) {
  const statusBadge = getStatusBadge(property.status);
  const paymentStatusBadge = getPaymentStatusBadge(
    property.payment_transaction_status
  );

  const taxAmount = property.tax_amount; // kalau kamu masih ingin menampilkan pajaknya
  const totalAmount = property.total_amount; // ini nilai total dari DB

  const content = `
        <div class="detail-section">
            <h3><i class="fas fa-home"></i> Informasi Properti</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Nama Properti</div>
                    <div class="detail-value">${property.name || "-"}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">${statusBadge}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Tipe Kos</div>
                    <div class="detail-value">${
                      property.kos_type
                        ? property.kos_type.charAt(0).toUpperCase() +
                          property.kos_type.slice(1)
                        : "-"
                    }</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Total Kamar</div>
                    <div class="detail-value">${
                      property.total_rooms || 0
                    } Kamar</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Kamar Tersedia</div>
                    <div class="detail-value">${
                      property.available_rooms || 0
                    } Kamar</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Kota</div>
                    <div class="detail-value">${property.city || "-"}</div>
                </div>
            </div>
            <div style="margin-top: 12px;">
                <div class="detail-label">Alamat Lengkap</div>
                <div class="detail-value">${property.address || "-"}</div>
            </div>
        </div>
        
        <div class="detail-section">
            <h3><i class="fas fa-user"></i> Informasi Owner</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Nama Owner</div>
                    <div class="detail-value">${
                      property.owner_name || "-"
                    }</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Email</div>
                    <div class="detail-value">${
                      property.owner_email || "-"
                    }</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">No. Telepon</div>
                    <div class="detail-value">${
                      property.owner_phone || "-"
                    }</div>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h3><i class="fas fa-dollar-sign"></i> Informasi Harga & Pajak</h3>
            <div class="payment-summary">
                <div class="payment-row">
                    <span>Harga Bulanan:</span>
                    <strong>Rp ${formatNumber(
                      property.price_monthly || 0
                    )}</strong>
                </div>
                <div class="payment-row">
                    <span>Pajak (10%):</span>
                    <strong>Rp ${formatNumber(Math.round(taxAmount))}</strong>
                </div>
                    <div class="payment-row total">
                    <span>Pajak yang Harus Dibayarkan:</span>
                    <span>Rp ${formatNumber(Math.round(totalAmount))}</span>
                    </div>
                </div>
        </div>
        
        ${
          property.order_id
            ? `
        <div class="detail-section">
            <h3><i class="fas fa-receipt"></i> Informasi Pembayaran</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Order ID</div>
                    <div class="detail-value" style="font-family: monospace; font-size: 12px;">${
                      property.order_id || "-"
                    }</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Transaction ID</div>
                    <div class="detail-value" style="font-family: monospace; font-size: 12px;">${
                      property.transaction_id || "-"
                    }</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Payment Status</div>
                    <div class="detail-value">${paymentStatusBadge}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Payment Method</div>
                    <div class="detail-value">${
                      property.payment_method || property.payment_type || "-"
                    }</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Tanggal Bayar</div>
                    <div class="detail-value">${
                      property.paid_at ? formatDate(property.paid_at) : "-"
                    }</div>
                </div>
            </div>
        </div>
        `
            : '<div class="detail-section"><p style="text-align: center; color: var(--text-gray);">Belum ada informasi pembayaran</p></div>'
        }
    `;

  document.getElementById("propertyDetailContent").innerHTML = content;
}

function getStatusBadge(status) {
  let badge = "";
  switch (status) {
    case "approved":
      badge =
        '<span class="badge" style="background: #d1fae5; color: #065f46;"><i class="fas fa-check-circle"></i> Approved</span>';
      break;
    case "pending":
      badge =
        '<span class="badge" style="background: #fef3c7; color: #92400e;"><i class="fas fa-clock"></i> Pending</span>';
      break;
    case "rejected":
      badge =
        '<span class="badge" style="background: #fee2e2; color: #991b1b;"><i class="fas fa-times-circle"></i> Rejected</span>';
      break;
    default:
      badge =
        '<span class="badge" style="background: #f3f4f6; color: #6b7280;">Unknown</span>';
  }
  return badge;
}

function getPaymentStatusBadge(status) {
  if (!status) return "-";

  let badge = "";
  switch (status) {
    case "settlement":
      badge =
        '<span class="badge" style="background: #d1fae5; color: #065f46;">Settlement</span>';
      break;
    case "pending":
      badge =
        '<span class="badge" style="background: #fef3c7; color: #92400e;">Pending</span>';
      break;
    case "capture":
      badge =
        '<span class="badge" style="background: #dbeafe; color: #1e40af;">Capture</span>';
      break;
    default:
      badge = `<span class="badge" style="background: #f3f4f6; color: #6b7280;">${status}</span>`;
  }
  return badge;
}

function formatDate(dateString) {
  const options = {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  };
  return new Date(dateString).toLocaleDateString("id-ID", options);
}

function closeDetailModal() {
  document.getElementById("propertyDetailModal").style.display = "none";
}

// Close modal when clicking outside
window.onclick = function (event) {
  const modal = document.getElementById("propertyDetailModal");
  if (event.target === modal) {
    closeDetailModal();
  }
};
