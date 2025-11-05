/**
 * ============================================
 * EDIT PROPERTY FORM HANDLER
 * File: frontend/user/owner/js/edit_property.js
 * ============================================
 * Handle edit property dengan pre-populated data
 */

// Arrays untuk menyimpan data terpilih
let editSelectedFacilities = [];
let editSelectedRules = [];
let editExistingImages = []; // Gambar yang sudah ada
let editNewFiles = []; // File baru yang akan diupload

/**
 * ============================================
 * VALIDATION HELPERS
 * ============================================
 */

function showEditError(inputElement, message) {
  clearEditError(inputElement);
  inputElement.style.borderColor = "#dc3545";

  const errorDiv = document.createElement("div");
  errorDiv.className = "error-message";
  errorDiv.style.color = "#dc3545";
  errorDiv.style.fontSize = "0.875rem";
  errorDiv.style.marginTop = "0.25rem";
  errorDiv.innerHTML = '<i class="bi bi-exclamation-circle"></i> ' + message;

  inputElement.parentNode.appendChild(errorDiv);
}

function clearEditError(inputElement) {
  inputElement.style.borderColor = "";
  const errorDiv = inputElement.parentNode.querySelector(".error-message");
  if (errorDiv) {
    errorDiv.remove();
  }
}

function validateEditName(input) {
  const value = input.value.trim();
  if (value === "") {
    showEditError(input, "Nama property harus diisi");
    return false;
  }
  if (value.length < 3) {
    showEditError(input, "Nama property minimal 3 karakter");
    return false;
  }
  if (value.length > 100) {
    showEditError(input, "Nama property maksimal 100 karakter");
    return false;
  }
  clearEditError(input);
  return true;
}

function validateEditPostalCode(input) {
  const value = input.value.trim();
  if (value === "") {
    clearEditError(input);
    return true;
  }
  if (!/^\d{5}$/.test(value)) {
    showEditError(input, "Kode pos harus 5 digit angka");
    return false;
  }
  clearEditError(input);
  return true;
}

function validateEditAddress(input) {
  const value = input.value.trim();
  if (value === "") {
    showEditError(input, "Alamat harus diisi");
    return false;
  }
  if (value.length < 10) {
    showEditError(input, "Alamat terlalu pendek, minimal 10 karakter");
    return false;
  }
  clearEditError(input);
  return true;
}

function validateEditProvince(input) {
  const value = input.value.trim();
  if (value === "") {
    showEditError(input, "Provinsi harus diisi");
    return false;
  }
  if (value.length < 3) {
    showEditError(input, "Nama provinsi minimal 3 karakter");
    return false;
  }
  clearEditError(input);
  return true;
}

function validateEditCity(input) {
  const value = input.value.trim();
  if (value === "") {
    showEditError(input, "Kota harus diisi");
    return false;
  }
  if (value.length < 3) {
    showEditError(input, "Nama kota minimal 3 karakter");
    return false;
  }
  clearEditError(input);
  return true;
}

function validateEditTotalRooms(input) {
  const value = input.value.trim();
  if (value === "") {
    showEditError(input, "Total kamar harus diisi");
    return false;
  }
  const num = parseInt(value);
  if (isNaN(num) || num < 1) {
    showEditError(input, "Total kamar minimal 1");
    return false;
  }
  if (num > 1000) {
    showEditError(input, "Total kamar maksimal 1000");
    return false;
  }
  clearEditError(input);
  return true;
}

function validateEditKosType(select) {
  if (select.value === "") {
    showEditError(select, "Pilih jenis kos");
    return false;
  }
  clearEditError(select);
  return true;
}

function validateEditPrice(input, fieldName) {
  const value = input.value.trim();

  if (fieldName === "monthly" && (value === "" || value === "Rp 0")) {
    showEditError(input, "Harga perbulan harus diisi");
    return false;
  }

  if (value !== "" && value !== "Rp 0") {
    const numericValue = value.replace(/[^0-9]/g, "");
    const num = parseInt(numericValue);

    if (isNaN(num) || num < 1) {
      showEditError(input, "Harga harus lebih dari 0");
      return false;
    }

    if (num > 100000000) {
      showEditError(input, "Harga terlalu besar (maksimal 100 juta)");
      return false;
    }

    if (fieldName === "daily") {
      const priceMonthlyInput = document.getElementById("price_monthly");
      if (priceMonthlyInput && priceMonthlyInput.value) {
        const monthlyValue = priceMonthlyInput.value.replace(/[^0-9]/g, "");
        const monthlyNum = parseInt(monthlyValue);
        if (!isNaN(monthlyNum) && num >= monthlyNum) {
          showEditError(
            input,
            "Harga perhari harus lebih kecil dari harga perbulan"
          );
          return false;
        }
      }
    }
  }

  clearEditError(input);
  return true;
}

function validateEditDescription(textarea) {
  const value = textarea.value.trim();
  if (value === "") {
    showEditError(textarea, "Deskripsi harus diisi");
    return false;
  }
  if (value.length < 20) {
    showEditError(textarea, "Deskripsi minimal 20 karakter");
    return false;
  }
  if (value.length > 1000) {
    showEditError(textarea, "Deskripsi maksimal 1000 karakter");
    return false;
  }
  clearEditError(textarea);
  return true;
}

function validateEditCoordinates() {
  const latitude = document.getElementById("latitude");
  const longitude = document.getElementById("longitude");

  if (!latitude || !longitude) {
    return true;
  }

  if (!latitude.value || !longitude.value) {
    const coordDisplay = document.getElementById("coord-display");
    if (coordDisplay) {
      coordDisplay.innerHTML =
        '<span style="color: #dc3545;"><i class="bi bi-exclamation-circle"></i> Lokasi belum dipilih!</span>';
    }
    return false;
  }

  return true;
}

function validateEditImages(input) {
  const files = input.files;

  if (files.length === 0) {
    clearEditError(input);
    return true;
  }

  // Total gambar = existing + new
  const totalImages = editExistingImages.length + files.length;
  if (totalImages > 10) {
    showEditError(input, "Maksimal 10 gambar (termasuk gambar lama)");
    return false;
  }

  for (let i = 0; i < files.length; i++) {
    const file = files[i];
    if (!file.type.match("image.*")) {
      showEditError(input, 'File "' + file.name + '" bukan gambar');
      return false;
    }
    if (file.size > 10 * 1024 * 1024) {
      showEditError(
        input,
        'File "' + file.name + '" terlalu besar (maksimal 10MB)'
      );
      return false;
    }
  }

  clearEditError(input);
  return true;
}

/**
 * ============================================
 * INITIALIZE ON PAGE LOAD
 * ============================================
 */
document.addEventListener("DOMContentLoaded", function () {
  console.log("Edit Property - Initializing...");

  loadEditFacilities();
  setupEditFacilityHandlers();
  setupEditRuleHandlers();
  setupEditRealTimeValidation();
  setupEditFormSubmission();
  setupEditRupiahFormat();
  setupEditImagePreview();

  // Load existing data
  loadExistingFacilities();
  loadExistingRules();
  loadExistingImages();
});

/**
 * ============================================
 * LOAD FACILITIES
 * ============================================
 */
function loadEditFacilities() {
  fetch("../api/get_facilities.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        populateEditFacilities(data.facilities);
      } else {
        console.error("Gagal load fasilitas");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}

function populateEditFacilities(facilities) {
  const grouped = {
    room: [],
    bathroom: [],
    common: [],
    parking: [],
    security: [],
  };

  facilities.forEach((facility) => {
    if (grouped[facility.category]) {
      grouped[facility.category].push(facility);
    }
  });

  populateEditSelect("facility-room", grouped.room);
  populateEditSelect("facility-bathroom", grouped.bathroom);
  populateEditSelect("facility-common", grouped.common);
  populateEditSelect("facility-parking", grouped.parking);
  populateEditSelect("facility-security", grouped.security);
}

function populateEditSelect(selectId, facilities) {
  const select = document.getElementById(selectId);
  if (!select) return;

  facilities.forEach((facility) => {
    const option = document.createElement("option");
    option.value = facility.id;
    option.textContent = facility.name;
    option.dataset.icon = facility.icon || "";
    select.appendChild(option);
  });
}

/**
 * ============================================
 * LOAD EXISTING DATA
 * ============================================
 */
function loadExistingFacilities() {
  // Clear dulu untuk hindari duplikasi
  editSelectedFacilities = [];
  
  const tagsContainer = document.getElementById("facility-tags"); // ✅ SAMAKAN dengan addEditFacility
  if (tagsContainer) {
    tagsContainer.innerHTML = '<small class="text-muted">Fasilitas yang dipilih akan muncul di sini</small>';
  }
  
  // Cek apakah selectedFacilities ada dan valid
  if (typeof selectedFacilities !== 'undefined' && Array.isArray(selectedFacilities) && selectedFacilities.length > 0) {
    
    console.log('Loading facilities:', selectedFacilities); // Debug
    
    // Tunggu sebentar untuk memastikan DOM dan data facility siap
    setTimeout(() => {
      selectedFacilities.forEach(facilityId => {
        const facilityName = getFacilityName(facilityId);
        
        if (facilityName) {
          addEditFacility(facilityId.toString(), facilityName);
        } else {
          console.warn(`⚠️ Facility ID ${facilityId} tidak ditemukan`);
        }
      });
      
      console.log('✅ Loaded facilities:', editSelectedFacilities);
    }, 300); // Kurangi dari 500ms ke 300ms
  }
}

function getFacilityName(facilityId) {
  // Cari nama facility dari semua select options
  const selects = document.querySelectorAll('[id^="facility-"]');
  for (let select of selects) {
    const option = select.querySelector(`option[value="${facilityId}"]`);
    if (option) {
      return option.textContent;
    }
  }
  return null;
}

function loadExistingRules() {
  // Bersihkan dulu
  const rulesContainer = document.getElementById("rule-tags");
  if (rulesContainer) {
    rulesContainer.innerHTML = '<small class="text-muted">Aturan yang dipilih akan muncul di sini</small>';
  }

  if (typeof existingRules !== "undefined" && Array.isArray(existingRules) && existingRules.length > 0) {
    console.log("Loading existing rules:", existingRules);

    setTimeout(() => {
      existingRules.forEach((rule) => {
        const ruleSelect = document.getElementById("aturan-select");
        const option = ruleSelect?.querySelector(`option[value="${rule}"]`);
        if (option) {
          const group = option.parentElement.label;
          addEditRule(rule, option.textContent, group);
        } else {
          console.warn(`⚠️ Rule "${rule}" tidak ditemukan di dropdown`);
        }
      });

      console.log("✅ Loaded rules:", existingRules);
    }, 300);
  }
}


function loadExistingImages() {
  // Ambil gambar yang sudah ada dari preview container
  const existingPreviews = document.querySelectorAll(
    "#previewContainer .preview-item"
  );
  existingPreviews.forEach((item) => {
    const img = item.querySelector("img");
    if (img) {
      const imagePath = img.src.split("/").slice(-3).join("/"); // ambil path relatif
      editExistingImages.push(imagePath);
    }
  });

  updateEditImagePreview();
}

/**
 * ============================================
 * FACILITY HANDLERS
 * ============================================
 */
function setupEditFacilityHandlers() {
  const facilitySelects = [
    "facility-room",
    "facility-bathroom",
    "facility-common",
    "facility-parking",
    "facility-security",
  ];

  facilitySelects.forEach((selectId) => {
    const select = document.getElementById(selectId);
    if (select) {
      select.addEventListener("change", function () {
        if (this.value) {
          addEditFacility(this.value, this.options[this.selectedIndex].text);
          this.value = "";
        }
      });
    }
  });
}

function addEditFacility(id, name) {
  if (editSelectedFacilities.includes(id)) {
    return;
  }

  editSelectedFacilities.push(id);

  const tagsContainer = document.getElementById("facility-tags");
  if (tagsContainer.querySelector("small")) {
    tagsContainer.innerHTML = "";
  }

  const badge = document.createElement("span");
  badge.className = "badge bg-success me-2 mb-2";
  badge.innerHTML = `
    ${name}
    <i class="bi bi-x-circle ms-1" style="cursor: pointer;" onclick="removeEditFacility('${id}', this)"></i>
  `;

  tagsContainer.appendChild(badge);
}

function removeEditFacility(id, element) {
  const index = editSelectedFacilities.indexOf(id);
  if (index > -1) {
    editSelectedFacilities.splice(index, 1);
  }

  element.closest(".badge").remove();

  const tagsContainer = document.getElementById("facility-tags");
  if (tagsContainer.children.length === 0) {
    tagsContainer.innerHTML =
      '<small class="text-muted">Fasilitas yang dipilih akan muncul di sini</small>';
  }
}

/**
 * ============================================
 * RULE HANDLERS
 * ============================================
 */
function setupEditRuleHandlers() {
  const ruleSelect = document.getElementById("aturan-select");

  if (ruleSelect) {
    ruleSelect.addEventListener("change", function () {
      const value = this.value;
      const text = this.options[this.selectedIndex].text;
      const group = this.options[this.selectedIndex].parentElement.label;

      if (value) {
        addEditRule(value, text, group);
        this.value = "";
      }
    });
  }
}

function addEditRule(value, text, group) {
  if (editSelectedRules.includes(value)) return;

  editSelectedRules.push(value);

  const tagsContainer = document.getElementById("aturan-tags");
  const wrapper = document.createElement("span");
  wrapper.className =
    "badge bg-success p-2 d-flex align-items-center me-2 mb-2";
  wrapper.style.gap = "6px";
  wrapper.dataset.value = value;

  wrapper.innerHTML = `
    <div class="text-start">
      <strong>${group}</strong><br>${text}
    </div>
    <i class="bi bi-x-circle" style="cursor:pointer; font-size:1rem;" onclick="removeEditRule('${value}', this)"></i>
  `;

  tagsContainer.appendChild(wrapper);
}

function removeEditRule(value, element) {
  const index = editSelectedRules.indexOf(value);
  if (index > -1) editSelectedRules.splice(index, 1);

  element.closest(".badge").remove();
}

/**
 * ============================================
 * IMAGE PREVIEW HANDLER
 * ============================================
 */
function setupEditImagePreview() {
  const imageInput = document.getElementById("imageInput");
  const previewContainer = document.getElementById("previewContainer");

  if (!imageInput || !previewContainer) return;

  imageInput.addEventListener("change", function () {
    const newFiles = Array.from(this.files);
    editNewFiles = [...editNewFiles, ...newFiles];
    updateEditImagePreview();
  });
}

function updateEditImagePreview() {
  const previewContainer = document.getElementById("previewContainer");
  if (!previewContainer) return;

  previewContainer.innerHTML = "";

  // Tampilkan gambar existing
  editExistingImages.forEach((imagePath, index) => {
    const imgBox = document.createElement("div");
    imgBox.classList.add("position-relative");
    imgBox.style.width = "120px";
    imgBox.style.height = "120px";

    // FIX PATH: Pastikan path benar
    const fullPath = imagePath.startsWith("http")
      ? imagePath
      : `../../../../${imagePath}`;

    imgBox.innerHTML = `
      <img 
        src="${fullPath}" 
        class="img-thumbnail w-100 h-100 object-fit-cover rounded"
        alt="existing"
        style="object-fit: cover;">
      <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" 
              onclick="removeEditExistingImage(${index})">
        <i class="bi bi-x"></i>
      </button>
      <span class="badge bg-info position-absolute bottom-0 start-0 m-1" style="font-size: 0.65rem;">Existing</span>
    `;

    previewContainer.appendChild(imgBox);
  });

  // Tampilkan gambar baru
  editNewFiles.forEach((file, index) => {
    if (!file.type.startsWith("image/")) return;

    const reader = new FileReader();
    reader.onload = function (e) {
      const imgBox = document.createElement("div");
      imgBox.classList.add("position-relative");
      imgBox.style.width = "120px";
      imgBox.style.height = "120px";

      imgBox.innerHTML = `
        <img 
          src="${e.target.result}" 
          class="img-thumbnail w-100 h-100 object-fit-cover rounded"
          alt="new"
          style="object-fit: cover;">
        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" 
                onclick="removeEditNewImage(${index})">
          <i class="bi bi-x"></i>
        </button>
        <span class="badge bg-success position-absolute bottom-0 start-0 m-1" style="font-size: 0.65rem;">New</span>
      `;

      previewContainer.appendChild(imgBox);
    };
    reader.readAsDataURL(file);
  });

  updateEditInputFiles();
}

function removeEditExistingImage(index) {
  editExistingImages.splice(index, 1);
  updateEditImagePreview();
}

function removeEditNewImage(index) {
  editNewFiles.splice(index, 1);
  updateEditImagePreview();
}

function updateEditInputFiles() {
  const imageInput = document.getElementById("imageInput");
  if (!imageInput) return;

  const dt = new DataTransfer();
  editNewFiles.forEach((f) => dt.items.add(f));
  imageInput.files = dt.files;
}

/**
 * ============================================
 * REAL-TIME VALIDATION
 * ============================================
 */
function setupEditRealTimeValidation() {
  const form = document.getElementById("propertyForm");
  if (!form) return;

  // Nama
  const nameInput = form.querySelector('input[name="name"]');
  if (nameInput) {
    nameInput.addEventListener("blur", function () {
      validateEditName(this);
    });
    nameInput.addEventListener("input", function () {
      if (this.style.borderColor === "rgb(220, 53, 69)") validateEditName(this);
    });
  }

  // Kode pos
  const postalInput = form.querySelector('input[name="postal_code"]');
  if (postalInput) {
    postalInput.addEventListener("input", function () {
      this.value = this.value.replace(/[^\d]/g, "");
    });
    postalInput.addEventListener("blur", function () {
      validateEditPostalCode(this);
    });
  }

  // Alamat
  const addressInput = form.querySelector('input[name="address"]');
  if (addressInput) {
    addressInput.addEventListener("blur", function () {
      validateEditAddress(this);
    });
  }

  // Provinsi
  const provinceInput = form.querySelector('input[name="province"]');
  if (provinceInput) {
    provinceInput.addEventListener("blur", function () {
      validateEditProvince(this);
    });
  }

  // Kota
  const cityInput = form.querySelector('input[name="city"]');
  if (cityInput) {
    cityInput.addEventListener("blur", function () {
      validateEditCity(this);
    });
  }

  // Total kamar
  const totalRoomsInput = form.querySelector('input[name="total_rooms"]');
  if (totalRoomsInput) {
    totalRoomsInput.addEventListener("blur", function () {
      validateEditTotalRooms(this);
    });
  }

  // Jenis kos
  const kosTypeSelect = form.querySelector('select[name="kos_type"]');
  if (kosTypeSelect) {
    kosTypeSelect.addEventListener("change", function () {
      validateEditKosType(this);
    });
  }

  // Harga
  const priceMonthlyInput = document.getElementById("price_monthly");
  if (priceMonthlyInput) {
    priceMonthlyInput.addEventListener("blur", function () {
      validateEditPrice(this, "monthly");
    });
  }

  const priceDailyInput = document.getElementById("price_daily");
  if (priceDailyInput) {
    priceDailyInput.addEventListener("blur", function () {
      validateEditPrice(this, "daily");
    });
  }

  // Deskripsi
  const descriptionTextarea = form.querySelector(
    'textarea[name="description"]'
  );
  if (descriptionTextarea) {
    descriptionTextarea.addEventListener("blur", function () {
      validateEditDescription(this);
    });
    descriptionTextarea.addEventListener("input", function () {
      const currentLength = this.value.trim().length;
      const parent = this.parentNode;
      let counter = parent.querySelector(".char-counter");

      if (!counter) {
        counter = document.createElement("small");
        counter.className = "char-counter text-muted";
        counter.style.display = "block";
        counter.style.marginTop = "0.25rem";
        parent.appendChild(counter);
      }

      counter.textContent = currentLength + " / 1000 karakter";

      if (currentLength > 0 && currentLength < 20) {
        counter.style.color = "#dc3545";
      } else if (currentLength >= 1000) {
        counter.style.color = "#dc3545";
      } else {
        counter.style.color = "#6c757d";
      }
    });
  }

  // Upload gambar
  const imageInput = form.querySelector('input[type="file"]');
  if (imageInput) {
    imageInput.addEventListener("change", function () {
      validateEditImages(this);
    });
  }
}

/**
 * ============================================
 * RUPIAH FORMAT
 * ============================================
 */
function setupEditRupiahFormat() {
  function formatRupiah(angka, prefix) {
    let number_string = angka.replace(/[^,\d]/g, "").toString();
    let split = number_string.split(",");
    let sisa = split[0].length % 3;
    let rupiah = split[0].substr(0, sisa);
    let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

    if (ribuan) {
      let separator = sisa ? "." : "";
      rupiah += separator + ribuan.join(".");
    }

    rupiah = split[1] !== undefined ? rupiah + "," + split[1] : rupiah;
    return prefix === undefined ? rupiah : rupiah ? "Rp " + rupiah : "";
  }

  const priceMonthly = document.getElementById("price_monthly");
  const priceDaily = document.getElementById("price_daily");
  const inputs = [priceMonthly, priceDaily];

  inputs.forEach((input) => {
    if (!input) return;

    // Format nilai awal jika sudah ada
    if (input.value && input.value !== "" && input.value !== "0") {
      input.value = formatRupiah(input.value, "Rp");
    }

    input.addEventListener("focus", function () {
      if (this.value.trim() === "Rp 0") this.value = "";
    });

    input.addEventListener("input", function () {
      const cleaned = this.value.replace(/[^0-9,]/g, "");
      if (cleaned === "") {
        this.value = "";
        return;
      }
      this.value = formatRupiah(cleaned, "Rp");
    });

    input.addEventListener("blur", function () {
      if (this.value.trim() === "") {
        this.value = "Rp 0";
      }
    });
  });
}

/**
 * ============================================
 * FORM SUBMISSION
 * ============================================
 */
function setupEditFormSubmission() {
  const form = document.getElementById("propertyForm");
  if (!form) return;

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    console.log("Edit form submitted - validating...");

    let isValid = true;
    const errors = [];

    // Validasi semua field
    const nameInput = form.querySelector('input[name="name"]');
    if (nameInput && !validateEditName(nameInput)) {
      isValid = false;
      errors.push("Nama property");
    }

    const postalInput = form.querySelector('input[name="postal_code"]');
    if (postalInput && !validateEditPostalCode(postalInput)) {
      isValid = false;
      errors.push("Kode pos");
    }

    const addressInput = form.querySelector('input[name="address"]');
    if (addressInput && !validateEditAddress(addressInput)) {
      isValid = false;
      errors.push("Alamat");
    }

    const provinceInput = form.querySelector('input[name="province"]');
    if (provinceInput && !validateEditProvince(provinceInput)) {
      isValid = false;
      errors.push("Provinsi");
    }

    const cityInput = form.querySelector('input[name="city"]');
    if (cityInput && !validateEditCity(cityInput)) {
      isValid = false;
      errors.push("Kota");
    }

    const totalRoomsInput = form.querySelector('input[name="total_rooms"]');
    if (totalRoomsInput && !validateEditTotalRooms(totalRoomsInput)) {
      isValid = false;
      errors.push("Total kamar");
    }

    const kosTypeSelect = form.querySelector('select[name="kos_type"]');
    if (kosTypeSelect && !validateEditKosType(kosTypeSelect)) {
      isValid = false;
      errors.push("Jenis kos");
    }

    const priceMonthlyInput = document.getElementById("price_monthly");
    if (priceMonthlyInput && !validateEditPrice(priceMonthlyInput, "monthly")) {
      isValid = false;
      errors.push("Harga perbulan");
    }

    const priceDailyInput = document.getElementById("price_daily");
    if (
      priceDailyInput &&
      priceDailyInput.value &&
      priceDailyInput.value !== "Rp 0"
    ) {
      if (!validateEditPrice(priceDailyInput, "daily")) {
        isValid = false;
        errors.push("Harga perhari");
      }
    }

    const descriptionTextarea = form.querySelector(
      'textarea[name="description"]'
    );
    if (descriptionTextarea && !validateEditDescription(descriptionTextarea)) {
      isValid = false;
      errors.push("Deskripsi");
    }

    // Validasi koordinat - SIMPLIFIED
    const latInput = document.getElementById("latitude");
    const lngInput = document.getElementById("longitude");
    if (!latInput.value || !lngInput.value) {
      isValid = false;
      errors.push("Lokasi peta");
      alert("Mohon pilih lokasi di peta!");
    }

    // Jika ada error
    if (!isValid) {
      console.log("Validation failed:", errors);
      const firstErrorField = form.querySelector(
        '[style*="border-color: rgb(220, 53, 69)"]'
      );
      if (firstErrorField) {
        firstErrorField.scrollIntoView({ behavior: "smooth", block: "center" });
        setTimeout(() => firstErrorField.focus(), 300);
      }
      return false;
    }

    console.log("Validation passed - preparing data...");

    // Hapus input hidden lama
    const oldFacilitiesInput = form.querySelector('input[name="facilities"]');
    const oldRulesInput = form.querySelector('input[name="rules"]');
    const oldExistingImagesInput = form.querySelector(
      'input[name="existing_images"]'
    );

    if (oldFacilitiesInput) oldFacilitiesInput.remove();
    if (oldRulesInput) oldRulesInput.remove();
    if (oldExistingImagesInput) oldExistingImagesInput.remove();

    // Tambahkan facilities
    const facilitiesInput = document.createElement("input");
    facilitiesInput.type = "hidden";
    facilitiesInput.name = "facilities";
    facilitiesInput.value = JSON.stringify(editSelectedFacilities);
    form.appendChild(facilitiesInput);

    // Tambahkan rules
    const rulesInput = document.createElement("input");
    rulesInput.type = "hidden";
    rulesInput.name = "rules";
    rulesInput.value = JSON.stringify(editSelectedRules);
    form.appendChild(rulesInput);

    // Tambahkan existing images
    const existingImagesInput = document.createElement("input");
    existingImagesInput.type = "hidden";
    existingImagesInput.name = "existing_images";
    existingImagesInput.value = JSON.stringify(editExistingImages);
    form.appendChild(existingImagesInput);

    // Konversi harga ke angka murni
    const priceInputs = [priceMonthlyInput, priceDailyInput];
    priceInputs.forEach((input) => {
      if (input && input.value) {
        const numeric = input.value.replace(/[^0-9]/g, "") || "0";
        input.value = numeric;
      }
    });

    console.log("Submitting form...");
    console.log("Facilities:", editSelectedFacilities);
    console.log("Rules:", editSelectedRules);
    console.log("Existing images:", editExistingImages);

    // Submit form
    form.submit();
  });
}

// Make functions globally accessible
window.removeEditFacility = removeEditFacility;
window.removeEditRule = removeEditRule;
window.removeEditExistingImage = removeEditExistingImage;
window.removeEditNewImage = removeEditNewImage;
