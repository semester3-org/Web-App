/**
 * ============================================
 * FACILITIES MODULE - JAVASCRIPT FINAL
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

viewOverview.onclick = () => {
  viewOverview.classList.add('active');
  viewCategories.classList.remove('active');
  overviewSection.style.display = 'block';
  categorySections.style.display = 'none';
};

viewCategories.onclick = () => {
  viewCategories.classList.add('active');
  viewOverview.classList.remove('active');
  overviewSection.style.display = 'none';
  categorySections.style.display = 'block';
  
  document.querySelectorAll('.category-section').forEach(section => {
    section.classList.add('active');
  });
};

btnBackOverview.onclick = () => {
  viewOverview.click();
};

// ============================================
// CATEGORY CARD CLICK HANDLER
// ============================================

document.querySelectorAll('.category-card').forEach(card => {
  card.addEventListener('click', function() {
    const category = this.dataset.category;
    
    viewCategories.classList.add('active');
    viewOverview.classList.remove('active');
    overviewSection.style.display = 'none';
    categorySections.style.display = 'block';
    
    document.querySelectorAll('.category-section').forEach(section => {
      section.classList.remove('active');
    });
    
    document.getElementById('section-' + category).classList.add('active');
  });
});

// ============================================
// ICON PICKER FUNCTIONS
// ============================================

btnOpenIconPicker.onclick = () => {
  modalIconPicker.style.display = 'flex';
  renderIcons('all');
};

function closeIconPickerModal() {
  modalIconPicker.style.display = 'none';
}

closeIconPicker.onclick = closeIconPickerModal;

function renderIcons(category = 'all', searchTerm = '') {
  iconGrid.innerHTML = '';
  let icons = iconData[category] || iconData.all;
  
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

function selectIcon(iconClass) {
  selectedIcon = iconClass;
  iconInput.value = iconClass;
  
  selectedIconDisplay.innerHTML = `<i class="fas ${iconClass}"></i><span>${iconClass.replace('fa-', '')}</span>`;
  selectedIconDisplay.classList.add('has-icon');
  
  document.querySelectorAll('.icon-item').forEach(item => {
    item.classList.remove('selected');
  });
  event.target.closest('.icon-item').classList.add('selected');
  
  setTimeout(() => {
    closeIconPickerModal();
  }, 300);
}

document.querySelectorAll('.icon-tab').forEach(tab => {
  tab.addEventListener('click', function() {
    document.querySelectorAll('.icon-tab').forEach(t => t.classList.remove('active'));
    this.classList.add('active');
    
    const category = this.getAttribute('data-category');
    renderIcons(category, iconSearch.value);
  });
});

iconSearch.addEventListener('input', function() {
  const activeTab = document.querySelector('.icon-tab.active');
  const category = activeTab ? activeTab.getAttribute('data-category') : 'all';
  renderIcons(category, this.value);
});

// ============================================
// MODAL FUNCTIONS
// ============================================

function openModalWithCategory(category) {
  resetForm();
  document.getElementById('category').value = category;
  
  // DISABLE CATEGORY SELECT
  const categorySelect = document.getElementById('category');
  categorySelect.setAttribute('readonly', 'readonly');
  categorySelect.style.pointerEvents = 'none';
  categorySelect.style.backgroundColor = '#f0f0f0';
  
  formFacility.action = '../../../backend/admin/classes/add_facilities_process.php';
  modalTitle.innerHTML = '<i class="fas fa-plus-circle"></i> Tambah Fasilitas Baru';
  modal.style.display = "flex";
}

function closeModalFacility() {
  modal.style.display = "none";
  resetForm();
}

function resetForm() {
  formFacility.reset();
  document.getElementById('facilityId').value = '';
  selectedIcon = '';
  iconInput.value = '';
  selectedIconDisplay.innerHTML = '<i class="fas fa-question-circle"></i><span>Pilih Icon</span>';
  selectedIconDisplay.classList.remove('has-icon');
  
  // ENABLE CATEGORY SELECT
  const categorySelect = document.getElementById('category');
  categorySelect.removeAttribute('readonly');
  categorySelect.style.pointerEvents = 'auto';
  categorySelect.style.backgroundColor = '';
}

// ============================================
// MODAL CLOSE HANDLERS
// ============================================

closeModal.onclick = closeModalFacility;

window.onclick = (e) => {
  if (e.target == modal) {
    closeModalFacility();
  }
  if (e.target == modalIconPicker) {
    closeIconPickerModal();
  }
};

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

function editFacility(id, name, icon, category) {
  // Reset form first
  resetForm();
  
  // Set form values
  document.getElementById('facilityId').value = id;
  document.getElementById('name').value = name;
  document.getElementById('category').value = category;
  
  // DISABLE CATEGORY SELECT DURING EDIT
  const categorySelect = document.getElementById('category');
  categorySelect.setAttribute('readonly', 'readonly');
  categorySelect.style.pointerEvents = 'none';
  categorySelect.style.backgroundColor = '#f0f0f0';
  
  // Set icon if exists
  if (icon && icon.trim() !== '') {
    selectedIcon = icon;
    iconInput.value = icon;
    
    // Update display with icon
    const iconName = icon.replace('fa-', '');
    selectedIconDisplay.innerHTML = `<i class="fas ${icon}"></i><span>${iconName}</span>`;
    selectedIconDisplay.classList.add('has-icon');
  }
  
  formFacility.action = '../../../backend/admin/classes/edit_facilities_process.php';
  modalTitle.innerHTML = '<i class="fas fa-edit"></i> Edit Fasilitas';
  modal.style.display = "flex";
}

function deleteFacility(id, name) {
  if (confirm(`Apakah Anda yakin ingin menghapus fasilitas "${name}"?\n\nTindakan ini tidak dapat dibatalkan.`)) {
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

formFacility.addEventListener('submit', function(e) {
  const name = document.getElementById('name').value.trim();
  const category = document.getElementById('category').value;
  const icon = iconInput.value;
  
  if (!name) {
    e.preventDefault();
    alert('Nama fasilitas harus diisi!');
    document.getElementById('name').focus();
    return false;
  }
  
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
  
  if (!category) {
    e.preventDefault();
    alert('Kategori harus dipilih!');
    document.getElementById('category').focus();
    return false;
  }
  
  if (!icon) {
    e.preventDefault();
    alert('Icon harus dipilih!');
    btnOpenIconPicker.focus();
    return false;
  }
  
  return true;
});

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
  if (!modal || !formFacility || !viewOverview || !viewCategories) {
    console.error('❌ Required DOM elements not found');
    return;
  }
  
  console.info('✅ Facilities module initialized');
  
  // Initialize icon picker with all icons
  renderIcons('all');
});