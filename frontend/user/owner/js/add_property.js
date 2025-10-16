/**
 * ============================================
 * ADD PROPERTY FORM HANDLER
 * File: frontend/user/owner/js/add_property.js
 * ============================================
 * Handle fasilitas, aturan, validasi, dan form submission
 */

// Arrays untuk menyimpan data terpilih
let selectedFacilities = [];
let selectedRules = [];

/**
 * ============================================
 * VALIDATION HELPERS
 * ============================================
 */

/**
 * Tampilkan pesan error di bawah field
 */
function showError(inputElement, message) {
  // Hapus error sebelumnya jika ada
  clearError(inputElement);

  // Tambahkan class error ke input
  inputElement.style.borderColor = "#dc3545";

  // Buat elemen error message
  const errorDiv = document.createElement("div");
  errorDiv.className = "error-message";
  errorDiv.style.color = "#dc3545";
  errorDiv.style.fontSize = "0.875rem";
  errorDiv.style.marginTop = "0.25rem";
  errorDiv.innerHTML = '<i class="bi bi-exclamation-circle"></i> ' + message;

  // Sisipkan setelah input element
  inputElement.parentNode.appendChild(errorDiv);
}

/**
 * Hapus pesan error dari field
 */
function clearError(inputElement) {
  inputElement.style.borderColor = "";

  // Hapus error message jika ada
  const errorDiv = inputElement.parentNode.querySelector(".error-message");
  if (errorDiv) {
    errorDiv.remove();
  }
}

/**
 * Validasi nama property
 */
function validateName(input) {
  const value = input.value.trim();

  if (value === "") {
    showError(input, "Nama property harus diisi");
    return false;
  }

  if (value.length < 3) {
    showError(input, "Nama property minimal 3 karakter");
    return false;
  }

  if (value.length > 100) {
    showError(input, "Nama property maksimal 100 karakter");
    return false;
  }

  clearError(input);
  return true;
}

/**
 * Validasi kode pos
 */
function validatePostalCode(input) {
  const value = input.value.trim();

  if (value === "") {
    clearError(input); // Kode pos opsional
    return true;
  }

  // Validasi format kode pos Indonesia (5 digit)
  if (!/^\d{5}$/.test(value)) {
    showError(input, "Kode pos harus 5 digit angka");
    return false;
  }

  clearError(input);
  return true;
}

/**
 * Validasi alamat
 */
function validateAddress(input) {
  const value = input.value.trim();

  if (value === "") {
    showError(input, "Alamat harus diisi");
    return false;
  }

  if (value.length < 10) {
    showError(input, "Alamat terlalu pendek, minimal 10 karakter");
    return false;
  }

  clearError(input);
  return true;
}

/**
 * Validasi provinsi
 */
function validateProvince(input) {
  const value = input.value.trim();

  if (value === "") {
    showError(input, "Provinsi harus diisi");
    return false;
  }

  if (value.length < 3) {
    showError(input, "Nama provinsi minimal 3 karakter");
    return false;
  }

  clearError(input);
  return true;
}

/**
 * Validasi kota
 */
function validateCity(input) {
  const value = input.value.trim();

  if (value === "") {
    showError(input, "Kota harus diisi");
    return false;
  }

  if (value.length < 3) {
    showError(input, "Nama kota minimal 3 karakter");
    return false;
  }

  clearError(input);
  return true;
}

/**
 * Validasi total kamar
 */
function validateTotalRooms(input) {
  const value = input.value.trim();

  if (value === "") {
    showError(input, "Total kamar harus diisi");
    return false;
  }

  const num = parseInt(value);

  if (isNaN(num) || num < 1) {
    showError(input, "Total kamar minimal 1");
    return false;
  }

  if (num > 1000) {
    showError(input, "Total kamar maksimal 1000");
    return false;
  }

  clearError(input);
  return true;
}

/**
 * Validasi jenis kos
 */
function validateKosType(select) {
  if (select.value === "") {
    showError(select, "Pilih jenis kos");
    return false;
  }

  clearError(select);
  return true;
}

/**
 * Validasi harga
 */
function validatePrice(input, fieldName) {
  const value = input.value.trim();

  // Harga perbulan wajib, harga perhari opsional
  if (fieldName === "monthly" && (value === "" || value === "Rp 0")) {
    showError(input, "Harga perbulan harus diisi");
    return false;
  }

  if (value !== "" && value !== "Rp 0") {
    // Ekstrak angka dari format Rupiah
    const numericValue = value.replace(/[^0-9]/g, "");
    const num = parseInt(numericValue);

    if (isNaN(num) || num < 1) {
      showError(input, "Harga harus lebih dari 0");
      return false;
    }

    if (num > 100000000) {
      // 100 juta
      showError(input, "Harga terlalu besar (maksimal 100 juta)");
      return false;
    }

    // Validasi khusus untuk harga perhari
    if (fieldName === "daily") {
      const priceMonthlyInput = document.getElementById("price_monthly");
      if (priceMonthlyInput && priceMonthlyInput.value) {
        const monthlyValue = priceMonthlyInput.value.replace(/[^0-9]/g, "");
        const monthlyNum = parseInt(monthlyValue);

        if (!isNaN(monthlyNum) && num >= monthlyNum) {
          showError(
            input,
            "Harga perhari harus lebih kecil dari harga perbulan"
          );
          return false;
        }
      }
    }
  }

  clearError(input);
  return true;
}

/**
 * Validasi deskripsi
 */
/**
 * Validasi deskripsi
 */
function validateDescription(textarea) {
  const value = textarea.value.trim();

  if (value === "") {
    showError(textarea, "Deskripsi harus diisi");
    return false;
  }

  if (value.length < 20) {
    showError(
      textarea,
      "Deskripsi minimal 20 karakter untuk memberikan informasi yang cukup"
    );
    return false;
  }

  if (value.length > 1000) {
    showError(textarea, "Deskripsi maksimal 1000 karakter");
    return false;
  }

  clearError(textarea);
  return true;
}

/**
 * Validasi koordinat
 */
function validateCoordinates() {
  const latitude = document.getElementById("latitude");
  const longitude = document.getElementById("longitude");

  if (!latitude || !longitude) {
    return true; // Jika element tidak ada
  }

  if (!latitude.value || !longitude.value) {
    const coordDisplay = document.getElementById("coord-display");
    if (coordDisplay) {
      coordDisplay.innerHTML =
        '<span style="color: #dc3545;"><i class="bi bi-exclamation-circle"></i> Lokasi belum dipilih di peta!</span>';
    }
    return false;
  }

  return true;
}

/**
 * Validasi file upload
 */
function validateImages(input) {
  const files = input.files;

  if (files.length === 0) {
    clearError(input);
    return true; // Upload opsional
  }

  // Validasi jumlah file
  if (files.length > 10) {
    showError(input, "Maksimal 10 gambar");
    return false;
  }

  // Validasi setiap file
  for (let i = 0; i < files.length; i++) {
    const file = files[i];

    // Validasi tipe file
    if (!file.type.match("image.*")) {
      showError(input, 'File "' + file.name + '" bukan gambar');
      return false;
    }

    // Validasi ukuran file (10MB)
    if (file.size > 10 * 1024 * 1024) {
      showError(
        input,
        'File "' + file.name + '" terlalu besar (maksimal 10MB)'
      );
      return false;
    }
  }

  clearError(input);
  return true;
}

/**
 * ============================================
 * LOAD FACILITIES DARI DATABASE
 * ============================================
 */
document.addEventListener("DOMContentLoaded", function () {
  console.log("DOM Loaded - Initializing...");
  loadFacilities();
  setupFacilityHandlers();
  setupRuleHandlers();
  setupRealTimeValidation();
  setupFormSubmission();
  setupRupiahFormat();
});

/**
 * Setup validasi real-time
 */
function setupRealTimeValidation() {
  console.log("Setting up real-time validation...");
  const form = document.getElementById("propertyForm");

  if (!form) {
    console.error("Form tidak ditemukan!");
    return;
  }

  // Validasi nama
  const nameInput = form.querySelector('input[name="name"]');
  if (nameInput) {
    nameInput.addEventListener("blur", function () {
      validateName(this);
    });
    nameInput.addEventListener("input", function () {
      if (this.style.borderColor === "rgb(220, 53, 69)") {
        validateName(this);
      }
    });
  }

  // Validasi kode pos
  const postalInput = form.querySelector('input[name="postal_code"]');
  if (postalInput) {
    postalInput.addEventListener("input", function () {
      // Hanya izinkan angka
      this.value = this.value.replace(/[^\d]/g, "");
    });
    postalInput.addEventListener("blur", function () {
      validatePostalCode(this);
    });
  }

  // Validasi alamat
  const addressInput = form.querySelector('input[name="address"]');
  if (addressInput) {
    addressInput.addEventListener("blur", function () {
      validateAddress(this);
    });
  }

  // Validasi provinsi
  const provinceInput = form.querySelector('input[name="province"]');
  if (provinceInput) {
    provinceInput.addEventListener("blur", function () {
      validateProvince(this);
    });
  }

  // Validasi kota
  const cityInput = form.querySelector('input[name="city"]');
  if (cityInput) {
    cityInput.addEventListener("blur", function () {
      validateCity(this);
    });
  }

  // Validasi total kamar
  const totalRoomsInput = form.querySelector('input[name="total_rooms"]');
  if (totalRoomsInput) {
    totalRoomsInput.addEventListener("blur", function () {
      validateTotalRooms(this);
    });
    totalRoomsInput.addEventListener("input", function () {
      if (this.style.borderColor === "rgb(220, 53, 69)") {
        validateTotalRooms(this);
      }
    });
  }

  // Validasi jenis kos
  const kosTypeSelect = form.querySelector('select[name="kos_type"]');
  if (kosTypeSelect) {
    kosTypeSelect.addEventListener("change", function () {
      validateKosType(this);
    });
  }

  // Validasi harga
  const priceMonthlyInput = document.getElementById("price_monthly");
  if (priceMonthlyInput) {
    priceMonthlyInput.addEventListener("blur", function () {
      validatePrice(this, "monthly");
      // Re-validasi harga perhari jika sudah diisi
      const priceDailyInput = document.getElementById("price_daily");
      if (
        priceDailyInput &&
        priceDailyInput.value &&
        priceDailyInput.value !== "Rp 0"
      ) {
        validatePrice(priceDailyInput, "daily");
      }
    });
    priceMonthlyInput.addEventListener("input", function () {
      if (this.style.borderColor === "rgb(220, 53, 69)") {
        validatePrice(this, "monthly");
      }
    });
  }

  const priceDailyInput = document.getElementById("price_daily");
  if (priceDailyInput) {
    priceDailyInput.addEventListener("blur", function () {
      validatePrice(this, "daily");
    });
    priceDailyInput.addEventListener("input", function () {
      if (this.style.borderColor === "rgb(220, 53, 69)") {
        validatePrice(this, "daily");
      }
    });
  }

  // Validasi deskripsi
  const descriptionTextarea = form.querySelector(
    'textarea[name="description"]'
  );
  if (descriptionTextarea) {
    descriptionTextarea.addEventListener("blur", function () {
      validateDescription(this);
    });
    descriptionTextarea.addEventListener("input", function () {
      if (this.style.borderColor === "rgb(220, 53, 69)") {
        validateDescription(this);
      }
      // Counter karakter
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

  // Validasi upload gambar
  const imageInput = form.querySelector('input[type="file"]');
  if (imageInput) {
    imageInput.addEventListener("change", function () {
      validateImages(this);
    });
  }

  console.log("Real-time validation setup complete!");
}

/**
 * Load fasilitas dari database via AJAX
 */
function loadFacilities() {
  // Fetch facilities dari backend
  fetch("../api/get_facilities.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        populateFacilities(data.facilities);
      } else {
        console.error("Gagal load fasilitas");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}

/**
 * Populate facility select options berdasarkan kategori
 */
function populateFacilities(facilities) {
  // Group facilities by category
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

  // Populate each select
  populateSelect("facility-room", grouped.room);
  populateSelect("facility-bathroom", grouped.bathroom);
  populateSelect("facility-common", grouped.common);
  populateSelect("facility-parking", grouped.parking);
  populateSelect("facility-security", grouped.security);
}

/**
 * Populate select element dengan options
 */
function populateSelect(selectId, facilities) {
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
 * SETUP FACILITY HANDLERS
 * ============================================
 */
function setupFacilityHandlers() {
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
          addFacility(this.value, this.options[this.selectedIndex].text);
          this.value = ""; // Reset select
        }
      });
    }
  });
}

/**
 * Tambah fasilitas ke list
 */
function addFacility(id, name) {
  // Cek jika sudah ada
  if (selectedFacilities.includes(id)) {
    return;
  }

  selectedFacilities.push(id);

  // Buat tag badge
  const tagsContainer = document.getElementById("facility-tags");

  // Hapus placeholder text jika ada
  if (tagsContainer.querySelector("small")) {
    tagsContainer.innerHTML = "";
  }

  const badge = document.createElement("span");
  badge.className = "badge bg-success me-2 mb-2";
  badge.innerHTML = `
        ${name}
        <i class="bi bi-x-circle ms-1" style="cursor: pointer;" onclick="removeFacility('${id}', this)"></i>
    `;

  tagsContainer.appendChild(badge);
}

/**
 * Hapus fasilitas dari list
 */
function removeFacility(id, element) {
  const index = selectedFacilities.indexOf(id);
  if (index > -1) {
    selectedFacilities.splice(index, 1);
  }

  // Hapus badge
  element.closest(".badge").remove();

  // Tampilkan placeholder jika kosong
  const tagsContainer = document.getElementById("facility-tags");
  if (tagsContainer.children.length === 0) {
    tagsContainer.innerHTML =
      '<small class="text-muted">Fasilitas yang dipilih akan muncul di sini</small>';
  }
}

/**
 * ============================================
 * SETUP RULE HANDLERS
 * ============================================
 */
function setupRuleHandlers() {
  const ruleSelect = document.getElementById("aturan-select");

  if (ruleSelect) {
    ruleSelect.addEventListener("change", function () {
      if (this.value) {
        addRule(this.value, this.options[this.selectedIndex].text);
        this.value = ""; // Reset select
      }
    });
  }
}

/**
 * Tambah aturan ke list
 */
function addRule(value, text) {
  // Cek jika sudah ada
  if (selectedRules.includes(value)) {
    return;
  }

  selectedRules.push(value);

  // Buat tag badge
  const tagsContainer = document.getElementById("aturan-tags");

  const badge = document.createElement("span");
  badge.className = "badge bg-info me-2 mb-2";
  badge.innerHTML = `
        ${text}
        <i class="bi bi-x-circle ms-1" style="cursor: pointer;" onclick="removeRule('${value}', this)"></i>
    `;

  tagsContainer.appendChild(badge);
}

/**
 * Hapus aturan dari list
 */
function removeRule(value, element) {
  const index = selectedRules.indexOf(value);
  if (index > -1) {
    selectedRules.splice(index, 1);
  }

  // Hapus badge
  element.closest(".badge").remove();
}

/**
 * ============================================
 * SETUP FORM SUBMISSION
 * ============================================
 */
function setupFormSubmission() {
  const form = document.getElementById("propertyForm");

  if (!form) {
    console.error("Form tidak ditemukan!");
    return;
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault(); // Cegah submit dulu untuk validasi

    console.log("Form submitted - validating...");

    // Validasi semua field
    let isValid = true;
    const errors = [];

    // Validasi nama
    const nameInput = form.querySelector('input[name="name"]');
    if (nameInput && !validateName(nameInput)) {
      isValid = false;
      errors.push("Nama property");
    }

    // Validasi kode pos
    const postalInput = form.querySelector('input[name="postal_code"]');
    if (postalInput && !validatePostalCode(postalInput)) {
      isValid = false;
      errors.push("Kode pos");
    }

    // Validasi alamat
    const addressInput = form.querySelector('input[name="address"]');
    if (addressInput && !validateAddress(addressInput)) {
      isValid = false;
      errors.push("Alamat");
    }

    // Validasi provinsi
    const provinceInput = form.querySelector('input[name="province"]');
    if (provinceInput && !validateProvince(provinceInput)) {
      isValid = false;
      errors.push("Provinsi");
    }

    // Validasi kota
    const cityInput = form.querySelector('input[name="city"]');
    if (cityInput && !validateCity(cityInput)) {
      isValid = false;
      errors.push("Kota");
    }

    // Validasi total kamar
    const totalRoomsInput = form.querySelector('input[name="total_rooms"]');
    if (totalRoomsInput && !validateTotalRooms(totalRoomsInput)) {
      isValid = false;
      errors.push("Total kamar");
    }

    // Validasi jenis kos
    const kosTypeSelect = form.querySelector('select[name="kos_type"]');
    if (kosTypeSelect && !validateKosType(kosTypeSelect)) {
      isValid = false;
      errors.push("Jenis kos");
    }

    // Validasi harga
    const priceMonthlyInput = document.getElementById("price_monthly");
    if (priceMonthlyInput && !validatePrice(priceMonthlyInput, "monthly")) {
      isValid = false;
      errors.push("Harga perbulan");
    }

    const priceDailyInput = document.getElementById("price_daily");
    if (priceDailyInput && !validatePrice(priceDailyInput, "daily")) {
      isValid = false;
      errors.push("Harga perhari");
    }

    // Validasi deskripsi
    const descriptionTextarea = form.querySelector(
      'textarea[name="description"]'
    );
    if (descriptionTextarea && !validateDescription(descriptionTextarea)) {
      isValid = false;
      errors.push("Deskripsi");
    }

    // Validasi koordinat
    if (!validateCoordinates()) {
      isValid = false;
      errors.push("Lokasi peta");
      alert("Mohon pilih lokasi di peta!");
    }

    // Validasi gambar
    const imageInput = form.querySelector('input[type="file"]');
    if (imageInput && !validateImages(imageInput)) {
      isValid = false;
      errors.push("Upload gambar");
    }

    // Jika ada error
    if (!isValid) {
      console.log("Validation failed:", errors);

      // Scroll ke field pertama yang error
      const firstErrorField = form.querySelector(
        '[style*="border-color: rgb(220, 53, 69)"]'
      );
      if (firstErrorField) {
        firstErrorField.scrollIntoView({ behavior: "smooth", block: "center" });
        setTimeout(() => firstErrorField.focus(), 300);
      }

      return false;
    }

    console.log("Validation passed - submitting form...");

    // Hapus input hidden facilities dan rules yang mungkin sudah ada sebelumnya
    const oldFacilitiesInput = form.querySelector('input[name="facilities"]');
    const oldRulesInput = form.querySelector('input[name="rules"]');
    if (oldFacilitiesInput) oldFacilitiesInput.remove();
    if (oldRulesInput) oldRulesInput.remove();

    // Tambahkan hidden input untuk facilities dalam format JSON
    const facilitiesInput = document.createElement("input");
    facilitiesInput.type = "hidden";
    facilitiesInput.name = "facilities";
    facilitiesInput.value = JSON.stringify(selectedFacilities);
    form.appendChild(facilitiesInput);

    // Tambahkan hidden input untuk rules dalam format JSON
    const rulesInput = document.createElement("input");
    rulesInput.type = "hidden";
    rulesInput.name = "rules";
    rulesInput.value = JSON.stringify(selectedRules);
    form.appendChild(rulesInput);

    // Konversi harga ke angka murni sebelum submit
    const priceInputs = [priceMonthlyInput, priceDailyInput];
    priceInputs.forEach((input) => {
      if (input && input.value) {
        const numeric = input.value.replace(/[^0-9]/g, "") || "0";
        input.value = numeric;
      }
    });

    // Submit form
    form.submit();
  });
}

/**
 * ============================================
 * SETUP RUPIAH FORMAT
 * ============================================
 */
function setupRupiahFormat() {
  // Fungsi format Rupiah
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

    // Tampilkan placeholder
    if (!input.getAttribute("placeholder")) {
      input.setAttribute("placeholder", "Rp 0");
    }

    // Saat fokus: kosongkan jika sebelumnya "Rp 0"
    input.addEventListener("focus", function () {
      if (this.value.trim() === "Rp 0") this.value = "";
    });

    // Saat mengetik: format realtime
    input.addEventListener("input", function () {
      const cleaned = this.value.replace(/[^0-9,]/g, "");
      if (cleaned === "") {
        this.value = "";
        return;
      }
      this.value = formatRupiah(cleaned, "Rp");
      this.setSelectionRange(this.value.length, this.value.length);
    });

    // Saat keluar (blur): jika kosong, kembalikan ke "Rp 0"
    input.addEventListener("blur", function () {
      if (this.value.trim() === "") {
        this.value = "Rp 0";
      }
    });
  });
}

/**
 * ============================================
 * SETUP UPLOAD GAMBAR MULTIPLE PREVIEW
 * ============================================
 */

document.addEventListener('DOMContentLoaded', () => {
  const imageInput = document.getElementById('imageInput');
  const previewContainer = document.getElementById('previewContainer');

  // Simpan semua file yang dipilih (karena FileList tidak bisa dimodifikasi langsung)
  let allFiles = [];

  imageInput.addEventListener('change', function() {
    const newFiles = Array.from(this.files);
    allFiles = [...allFiles, ...newFiles]; // tambahkan file baru ke daftar lama
    updatePreview();
  });

  function updatePreview() {
    previewContainer.innerHTML = ''; // bersihkan preview untuk render ulang

    allFiles.forEach(file => {
      if (!file.type.startsWith('image/')) return; // pastikan file adalah gambar

      const reader = new FileReader();
      reader.onload = function(e) {
        const imgBox = document.createElement('div');
        imgBox.classList.add('position-relative');
        imgBox.style.width = '120px';
        imgBox.style.height = '120px';

        imgBox.innerHTML = `
          <img 
            src="${e.target.result}" 
            class="img-thumbnail w-100 h-100 object-fit-cover rounded"
            alt="preview">
          <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 remove-btn">
            <i class="bi bi-x"></i>
          </button>
        `;

        // tombol hapus preview
        imgBox.querySelector('.remove-btn').addEventListener('click', () => {
          allFiles = allFiles.filter(f => f !== file);
          updateInputFiles();
          updatePreview();
        });

        previewContainer.appendChild(imgBox);
      };
      reader.readAsDataURL(file);
    });

    updateInputFiles();
  }

  // Update FileList pada input agar sinkron dengan daftar allFiles
  function updateInputFiles() {
    const dt = new DataTransfer();
    allFiles.forEach(f => dt.items.add(f));
    imageInput.files = dt.files;
  }
});


// Make functions globally accessible
window.removeFacility = removeFacility;
window.removeRule = removeRule;
