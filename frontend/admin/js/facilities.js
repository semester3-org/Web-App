/**
 * ============================================
 * FACILITIES MODULE - JAVASCRIPT
 * ============================================
 */

// ============================================
// ICON DATA - Font Awesome Icons
// ============================================
const iconData = {
  room: [
    'fa-bed', 'fa-couch', 'fa-chair', 'fa-tv', 'fa-fan', 'fa-lightbulb',
    'fa-door-open', 'fa-door-closed', 'fa-window-maximize', 'fa-lamp',
    'fa-blinds', 'fa-carpet', 'fa-drawer', 'fa-cabinet-filing'
  ],
  bathroom: [
    'fa-bath', 'fa-shower', 'fa-toilet', 'fa-sink', 'fa-soap',
    'fa-pump-soap', 'fa-hand-sparkles', 'fa-faucet', 'fa-toilet-paper',
    'fa-spray-can', 'fa-broom'
  ],
  common: [
    'fa-wifi', 'fa-coffee', 'fa-utensils', 'fa-blender', 'fa-kitchen-set',
    'fa-mug-hot', 'fa-plate-wheat', 'fa-glass-water', 'fa-washing-machine',
    'fa-temperature-low', 'fa-wind', 'fa-snowflake', 'fa-fire',
    'fa-bolt', 'fa-plug', 'fa-computer', 'fa-laptop', 'fa-phone',
    'fa-tv-retro', 'fa-gamepad', 'fa-book', 'fa-newspaper'
  ],
  parking: [
    'fa-car', 'fa-motorcycle', 'fa-bicycle', 'fa-truck', 'fa-van-shuttle',
    'fa-square-parking', 'fa-p', 'fa-garage', 'fa-warehouse'
  ],
  security: [
    'fa-shield', 'fa-shield-halved', 'fa-shield-alt', 'fa-lock',
    'fa-lock-open', 'fa-key', 'fa-camera', 'fa-video',
    'fa-eye', 'fa-user-shield', 'fa-bell', 'fa-siren-on',
    'fa-fingerprint', 'fa-id-card'
  ],
  all: []
};

// Combine all icons
iconData.all = [...new Set([
  ...iconData.room,
  ...iconData.bathroom,
  ...iconData.common,
  ...iconData.parking,
  ...iconData.security
])];

// ============================================
// DOM ELEMENTS
// ============================================
const modal = document.getElementById("modalFacility");
const closeModal = document.getElementById("closeModal");
const overviewSection = document.getElementById("overviewSection");
const categorySections = document.getElementById("categorySections");
const viewOverview = document.getElementById("viewOverview");
const viewCategories = document.getElementById("viewCategories");
const btnBackOverview = document.getElementById("btnBackOverview");
const formFacility = document.getElementById("formFacility");
const modalTitle = document.getElementById("modalTitle");

// Icon Picker Elements
const modalIconPicker = document.getElementById("modalIconPicker");
const closeIconPicker = document.getElementById("closeIconPicker");
const btnOpenIconPicker = document.getElementById("btnOpenIconPicker");
const iconGrid = document.getElementById("iconGrid");
const iconSearch = document.getElementById("iconSearch");
const selectedIconDisplay = document.getElementById("selectedIconDisplay");
const iconInput = document.getElementById("icon");

let selectedIcon = '';

// ============================================
// VIEW SWITCHER FUNCTIONS
// ============================================

/**
 * Switch to Overview View
 */
viewOverview.onclick = () => {
  viewOverview.classList.add('active');
  viewCategories.classList.remove('active');
  overviewSection.style.display = 'block';
  categorySections.style.display = 'none';
};

/**
 * Switch to Category View (Show All Categories)
 */
viewCategories.onclick = () => {
  viewCategories.classList.add('active');
  viewOverview.classList.remove('active');
  overviewSection.style.display = 'none';
  categorySections.style.display = 'block';
  
  // Show all categories
  document.querySelectorAll('.category-section').forEach(section => {
    section.classList.add('active');
  });
};

/**
 * Back to Overview Button
 */
btnBackOverview.onclick = () => {
  viewOverview.click();
};

// ============================================
// CATEGORY CARD CLICK HANDLER
// ============================================

/**
 * Handle click on category card
 * Shows only the selected category
 */
document.querySelectorAll('.category-card').forEach(card => {
  card.addEventListener('click', function() {
    const category = this.dataset.category;
    
    // Switch to category view
    viewCategories.classList.add('active');
    viewOverview.classList.remove('active');
    overviewSection.style.display = 'none';
    categorySections.style.display = 'block';
    
    // Hide all sections first
    document.querySelectorAll('.category-section').forEach(section => {
      section.classList.remove('active');
    });
    
    // Show only selected category
    document.getElementById('section-' + category).classList.add('active');
  });
});

// ============================================
// ICON PICKER FUNCTIONS
// ============================================

/**
 * Open Icon Picker Modal
 */
btnOpenIconPicker.onclick = () => {
  modalIconPicker.style.display = 'flex';
  renderIcons('all');
};

/**
 * Close Icon Picker Modal
 */
function closeIconPickerModal() {
  modalIconPicker.style.display = 'none';
}

closeIconPicker.onclick = closeIconPickerModal;

/**
 * Render icons in grid
 */
function renderIcons(category = 'all', searchTerm = '') {
  iconGrid.innerHTML = '';
  let icons = iconData[category] || iconData.all;
  
  // Filter by search term
  if (searchTerm) {
    icons = icons.filter(icon => icon.toLowerCase().includes(searchTerm.toLowerCase()));
  }
  
  icons.forEach(iconClass => {
    const iconItem = document.createElement('div');
    iconItem.className = 'icon-item';
    if (selectedIcon === iconClass) {
      iconItem.classList.add('selected');
    }
    iconItem.setAttribute('data-icon-name', iconClass.replace('fa-', ''));
    iconItem.innerHTML = `<i class="fas ${iconClass}"></i>`;
    
    iconItem.onclick = () => selectIcon(iconClass);
    iconGrid.appendChild(iconItem);
  });
  
  if (icons.length === 0) {
    iconGrid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:40px; color:#999;">Tidak ada icon yang ditemukan</div>';
  }
}

/**
 * Select an icon
 */
function selectIcon(iconClass) {
  selectedIcon = iconClass;
  iconInput.value = iconClass;
  
  // Update display
  selectedIconDisplay.innerHTML = `<i class="fas ${iconClass}"></i><span>${iconClass.replace('fa-', '')}</span>`;
  selectedIconDisplay.classList.add('has-icon');
  
  // Update grid selection
  document.querySelectorAll('.icon-item').forEach(item => {
    item.classList.remove('selected');
  });
  event.target.closest('.icon-item').classList.add('selected');
  
  // Close modal after selection
  setTimeout(() => {
    closeIconPickerModal();
  }, 300);
}

/**
 * Icon category tabs
 */
document.querySelectorAll('.icon-tab').forEach(tab => {
  tab.addEventListener('click', function() {
    document.querySelectorAll('.icon-tab').forEach(t => t.classList.remove('active'));
    this.classList.add('active');
    
    const category = this.getAttribute('data-category');
    renderIcons(category, iconSearch.value);
  });
});

/**
 * Icon search
 */
iconSearch.addEventListener('input', function() {
  const activeTab = document.querySelector('.icon-tab.active');
  const category = activeTab ? activeTab.getAttribute('data-category') : 'all';
  renderIcons(category, this.value);
});

// ============================================
// MODAL FUNCTIONS
// ============================================

/**
 * Open modal with pre-selected category (for Add)
 * @param {string} category - Category key (room, bathroom, etc)
 */
function openModalWithCategory(category) {
  resetForm();
  document.getElementById('category').value = category;
  formFacility.action = '../../../backend/admin/classes/add_facilities_process.php';
  modalTitle.innerHTML = '<i class="fas fa-plus-circle"></i> Tambah Fasilitas Baru';
  modal.style.display = "flex";
}

/**
 * Close modal and reset form
 */
function closeModalFacility() {
  modal.style.display = "none";
  resetForm();
}

/**
 * Reset form to default state
 */
function resetForm() {
  formFacility.reset();
  document.getElementById('facilityId').value = '';
  selectedIcon = '';
  iconInput.value = '';
  selectedIconDisplay.innerHTML = '<i class="fas fa-question-circle"></i><span>Pilih Icon</span>';
  selectedIconDisplay.classList.remove('has-icon');
}

// ============================================
// MODAL CLOSE HANDLERS
// ============================================

// Close button click
closeModal.onclick = closeModalFacility;

// Click outside modal
window.onclick = (e) => {
  if (e.target == modal) {
    closeModalFacility();
  }
  if (e.target == modalIconPicker) {
    closeIconPickerModal();
  }
};

// ESC key to close modal
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    if (modal.style.display === 'flex') {
      closeModalFacility();
    }
    if (modalIconPicker.style.display === 'flex') {
      closeIconPickerModal();
    }
  }
});

// ============================================
// CRUD FUNCTIONS
// ============================================

/**
 * Edit facility - Open modal with existing data
 * @param {number} id - Facility ID
 * @param {string} name - Facility name
 * @param {string} icon - Facility icon
 * @param {string} category - Facility category
 */
function editFacility(id, name, icon, category) {
  document.getElementById('facilityId').value = id;
  document.getElementById('name').value = name;
  document.getElementById('category').value = category;
  
  // Set icon
  if (icon) {
    selectedIcon = icon;
    iconInput.value = icon;
    selectedIconDisplay.innerHTML = `<i class="fas ${icon}"></i><span>${icon.replace('fa-', '')}</span>`;
    selectedIconDisplay.classList.add('has-icon');
  }
  
  formFacility.action = '../../../backend/admin/classes/edit_facilities_process.php';
  modalTitle.innerHTML = '<i class="fas fa-edit"></i> Edit Fasilitas';
  modal.style.display = "flex";
}

/**
 * Delete facility with confirmation
 * @param {number} id - Facility ID
 * @param {string} name - Facility name (for confirmation message)
 */
function deleteFacility(id, name) {
  // Confirm deletion
  if (confirm(`Apakah Anda yakin ingin menghapus fasilitas "${name}"?\n\nTindakan ini tidak dapat dibatalkan.`)) {
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../../../backend/admin/classes/delete_facilities_process.php';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'id';
    input.value = id;
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
  }
}

// ============================================
// FORM VALIDATION
// ============================================

/**
 * Handle form submission with validation
 */
formFacility.addEventListener('submit', function(e) {
  const name = document.getElementById('name').value.trim();
  const category = document.getElementById('category').value;
  const icon = iconInput.value;
  
  // Validate name
  if (!name) {
    e.preventDefault();
    alert('Nama fasilitas harus diisi!');
    document.getElementById('name').focus();
    return false;
  }
  
  // Validate name length
  if (name.length < 2) {
    e.preventDefault();
    alert('Nama fasilitas minimal 2 karakter!');
    document.getElementById('name').focus();
    return false;
  }
  
  if (name.length > 100) {
    e.preventDefault();
    alert('Nama fasilitas maksimal 100 karakter!');
    document.getElementById('name').focus();
    return false;
  }
  
  // Validate category
  if (!category) {
    e.preventDefault();
    alert('Kategori harus dipilih!');
    document.getElementById('category').focus();
    return false;
  }
  
  // Validate icon
  if (!icon) {
    e.preventDefault();
    alert('Icon harus dipilih!');
    btnOpenIconPicker.focus();
    return false;
  }
  
  // Form is valid, will submit normally
  return true;
});

// ============================================
// INITIALIZATION
// ============================================

/**
 * Initialize page on load
 */
document.addEventListener('DOMContentLoaded', function() {
  // Ambil elemen-elemen yang dibutuhkan
  const modal = document.getElementById('facilityModal');
  const formFacility = document.getElementById('formFacility');
  const viewOverview = document.getElementById('viewOverview');
  const viewCategories = document.getElementById('viewCategories');
  const sections = document.querySelectorAll('.section');

  // Cek apakah semua elemen ada
  if (!modal || !formFacility || !viewOverview || !viewCategories) {
    console.error('❌ Required DOM elements not found');
    return;
  }
  
  console.info('✅ Facilities module initialized');
  
  // Inisialisasi ikon
  renderIcons('all');

  // Event listener untuk kategori
  viewCategories.addEventListener('click', () => {
    const category = 'something'; // ganti sesuai logika kamu
    sections.forEach(section => {
      section.classList.remove('active');
    });
    
    document.getElementById('section-' + category).classList.add('active');
  });
});


// ============================================
// MODAL FUNCTIONS
// ============================================

/**
 * Open modal with pre-selected category (for Add)
 * @param {string} category - Category key (room, bathroom, etc)
 */
function openModalWithCategory(category) {
  resetForm();
  document.getElementById('category').value = category;
  formFacility.action = '../../../backend/admin/classes/add_facilities_process.php';
  modalTitle.innerHTML = '<i class="fas fa-plus-circle"></i> Tambah Fasilitas Baru';
  modal.style.display = "flex";
}

/**
 * Close modal and reset form
 */
function closeModalFacility() {
  modal.style.display = "none";
  resetForm();
}

/**
 * Reset form to default state
 */
function resetForm() {
  formFacility.reset();
  document.getElementById('facilityId').value = '';
}

// ============================================
// MODAL CLOSE HANDLERS
// ============================================

// Close button click
closeModal.onclick = closeModalFacility;

// Click outside modal
window.onclick = (e) => {
  if (e.target == modal) {
    closeModalFacility();
  }
};

// ESC key to close modal
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && modal.style.display === 'flex') {
    closeModalFacility();
  }
});

// ============================================
// CRUD FUNCTIONS
// ============================================

/**
 * Edit facility - Open modal with existing data
 * @param {number} id - Facility ID
 * @param {string} name - Facility name
 * @param {string} icon - Facility icon
 * @param {string} category - Facility category
 */
function editFacility(id, name, icon, category) {
  document.getElementById('facilityId').value = id;
  document.getElementById('name').value = name;
  document.getElementById('icon').value = icon;
  document.getElementById('category').value = category;
  
  formFacility.action = '../../../backend/admin/classes/edit_facilities_process.php';
  modalTitle.innerHTML = '<i class="fas fa-edit"></i> Edit Fasilitas';
  modal.style.display = "flex";
}

/**
 * Delete facility with confirmation
 * @param {number} id - Facility ID
 * @param {string} name - Facility name (for confirmation message)
 */
function deleteFacility(id, name) {
  // Confirm deletion
  if (confirm(`Apakah Anda yakin ingin menghapus fasilitas "${name}"?\n\nTindakan ini tidak dapat dibatalkan.`)) {
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../../../backend/admin/classes/delete_facilities_process.php';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'id';
    input.value = id;
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
  }
}

// ============================================
// FORM VALIDATION
// ============================================

/**
 * Handle form submission with validation
 */
formFacility.addEventListener('submit', function(e) {
  const name = document.getElementById('name').value.trim();
  const category = document.getElementById('category').value;
  
  // Validate name
  if (!name) {
    e.preventDefault();
    alert('Nama fasilitas harus diisi!');
    document.getElementById('name').focus();
    return false;
  }
  
  // Validate name length
  if (name.length < 2) {
    e.preventDefault();
    alert('Nama fasilitas minimal 2 karakter!');
    document.getElementById('name').focus();
    return false;
  }
  
  if (name.length > 100) {
    e.preventDefault();
    alert('Nama fasilitas maksimal 100 karakter!');
    document.getElementById('name').focus();
    return false;
  }
  
  // Validate category
  if (!category) {
    e.preventDefault();
    alert('Kategori harus dipilih!');
    document.getElementById('category').focus();
    return false;
  }
  
  // Form is valid, will submit normally
  return true;
});

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Format date to Indonesian format
 * @param {string} dateString - Date string
 * @returns {string} Formatted date
 */
function formatDate(dateString) {
  const date = new Date(dateString);
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = date.getFullYear();
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  
  return `${day}/${month}/${year} ${hours}:${minutes}`;
}

/**
 * Show loading state on button
 * @param {HTMLElement} button - Button element
 */
function showButtonLoading(button) {
  button.disabled = true;
  button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
}

/**
 * Hide loading state on button
 * @param {HTMLElement} button - Button element
 * @param {string} originalText - Original button text
 */
function hideButtonLoading(button, originalText) {
  button.disabled = false;
  button.innerHTML = originalText;
}

// ============================================
// INITIALIZATION
// ============================================

/**
 * Initialize page on load
 */
document.addEventListener('DOMContentLoaded', function() {
  // Check if all required elements exist
  if (!modal || !formFacility || !viewOverview || !viewCategories) {
    console.error('❌ Required DOM elements not found');
    return;
  }
  
  console.log('✅ Facilities module initialized');
  
  // Auto-focus on name field when modal opens (only if modal supports transitionend)
  if (modal && typeof modal.addEventListener === 'function') {
    modal.addEventListener('transitionend', function() {
      if (modal.style.display === 'flex') {
        const nameField = document.getElementById('name');
        if (nameField) {
          nameField.focus();
        }
      }
    });
  }
});