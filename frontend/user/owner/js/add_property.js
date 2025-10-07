/**
 * Add Property - JavaScript Handler
 * Handles facilities and rules selection for property creation
 */

document.addEventListener("DOMContentLoaded", function() {
  
  // ==================== ATURAN HANDLER ====================
  initAturanHandler();
  
  // ==================== FASILITAS HANDLER ====================
  initFacilitiesHandler();
  
  // ==================== LOAD FACILITIES DATA ====================
  loadFacilitiesData();
  
});

/**
 * Initialize Aturan (Rules) Selection Handler
 */
function initAturanHandler() {
  const aturanSelect = document.getElementById("aturan-select");
  const aturanContainer = document.getElementById("aturan-tags");

  if (!aturanSelect || !aturanContainer) return;

  aturanSelect.addEventListener("change", function() {
    const value = this.value;
    const text = this.options[this.selectedIndex].text;

    // Check if already selected
    if (value && !document.querySelector(`#aturan-tags [data-value="${value}"]`)) {
      createAturanTag(value, text, aturanContainer);
      this.value = ""; // Reset dropdown
    }
  });
}

/**
 * Create Aturan Tag/Badge
 */
function createAturanTag(value, text, container) {
  const tag = document.createElement("span");
  tag.className = "badge bg-success me-2 mb-2 p-2";
  tag.dataset.value = value;
  tag.innerHTML = `${text} <i class="bi bi-x-circle ms-1" style="cursor:pointer"></i>`;
  
  container.appendChild(tag);
  
  // Remove tag on click X
  tag.querySelector("i").addEventListener("click", function() {
    tag.remove();
  });
}

/**
 * Initialize Facilities Selection Handler
 */
function initFacilitiesHandler() {
  const facilityContainer = document.getElementById("facility-tags");
  const facilitySelects = [
    'facility-room', 
    'facility-bathroom', 
    'facility-common', 
    'facility-parking', 
    'facility-security'
  ];

  if (!facilityContainer) return;

  // Add event listener to each facility select
  facilitySelects.forEach(selectId => {
    const select = document.getElementById(selectId);
    if (select) {
      select.addEventListener("change", function() {
        handleFacilitySelection(this, facilityContainer);
      });
    }
  });
}

/**
 * Handle Facility Selection from Dropdown
 */
function handleFacilitySelection(selectElement, container) {
  const option = selectElement.options[selectElement.selectedIndex];
  
  if (selectElement.value) {
    const facilityData = {
      id: selectElement.value,
      name: option.text,
      icon: option.dataset.icon || '',
      category: option.dataset.category || ''
    };
    
    createFacilityTag(facilityData, container);
    selectElement.value = ""; // Reset dropdown
  }
}

/**
 * Create Facility Tag/Badge
 */
function createFacilityTag(data, container) {
  // Check if already exists
  if (document.querySelector(`#facility-tags [data-facility-id="${data.id}"]`)) {
    return;
  }

  // Remove placeholder if exists
  const placeholder = container.querySelector('small');
  if (placeholder) {
    placeholder.remove();
  }

  const tag = document.createElement("span");
  tag.className = "badge bg-success me-2 mb-2 p-2";
  tag.dataset.facilityId = data.id;
  tag.dataset.category = data.category;
  
  const iconHtml = data.icon ? `<i class="${data.icon} me-1"></i>` : '';
  tag.innerHTML = `${iconHtml}${data.name} <i class="bi bi-x-circle ms-1" style="cursor:pointer"></i>`;
  
  container.appendChild(tag);
  
  // Remove tag on click X
  tag.querySelector("i.bi-x-circle").addEventListener("click", function() {
    tag.remove();
    updateFacilityPlaceholder(container);
  });
}

/**
 * Update Facility Placeholder
 */
function updateFacilityPlaceholder(container) {
  const tags = container.querySelectorAll('.badge');
  if (tags.length === 0) {
    container.innerHTML = '<small class="text-muted">Fasilitas yang dipilih akan muncul di sini</small>';
  }
}

/**
 * Load Facilities Data from API
 */
function loadFacilitiesData() {
  fetch('../function/get_facilities.php')
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      console.log('Facilities data loaded:', data);
      populateFacilitiesDropdowns(data);
    })
    .catch(error => {
      console.error('Error loading facilities:', error);
      showErrorMessage('Gagal memuat data fasilitas. Silakan refresh halaman.');
    });
}

/**
 * Populate Facilities Dropdowns by Category
 */
function populateFacilitiesDropdowns(data) {
  // Group data by category
  const grouped = {
    room: [],
    bathroom: [],
    common: [],
    parking: [],
    security: []
  };

  data.forEach(facility => {
    if (grouped[facility.category]) {
      grouped[facility.category].push(facility);
    }
  });

  // Populate each dropdown
  Object.keys(grouped).forEach(category => {
    const select = document.getElementById(`facility-${category}`);
    
    if (!select) return;

    // Clear existing options except the first one
    select.innerHTML = `<option value="">Pilih Fasilitas ${getCategoryName(category)}...</option>`;

    if (grouped[category].length > 0) {
      grouped[category].forEach(facility => {
        const option = document.createElement('option');
        option.value = facility.id;
        option.text = facility.name;
        option.dataset.icon = facility.icon || '';
        option.dataset.category = facility.category;
        select.appendChild(option);
      });
    } else {
      // No data for this category
      const option = document.createElement('option');
      option.value = '';
      option.text = 'Tidak ada data';
      option.disabled = true;
      select.appendChild(option);
    }
  });
}

/**
 * Get Category Display Name
 */
function getCategoryName(category) {
  const names = {
    room: 'Kamar',
    bathroom: 'Kamar Mandi',
    common: 'Umum',
    parking: 'Parkir',
    security: 'Keamanan'
  };
  return names[category] || category;
}

/**
 * Show Error Message
 */
function showErrorMessage(message) {
  alert(message);
}

/**
 * Get Selected Facilities IDs (for form submission)
 */
function getSelectedFacilities() {
  const facilityTags = document.querySelectorAll('#facility-tags .badge');
  return Array.from(facilityTags).map(tag => tag.dataset.facilityId);
}

/**
 * Get Selected Rules (for form submission)
 */
function getSelectedRules() {
  const aturanTags = document.querySelectorAll('#aturan-tags .badge');
  return Array.from(aturanTags).map(tag => tag.dataset.value);
}

// Export functions for use in form submission (if needed)
window.PropertyForm = {
  getSelectedFacilities,
  getSelectedRules
};