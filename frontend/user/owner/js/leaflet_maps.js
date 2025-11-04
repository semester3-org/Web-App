/**
 * ============================================
 * LEAFLET MAPS CONFIGURATION (GRATIS - TANPA API KEY!)
 * File: frontend/user/owner/js/leaflet_maps.js
 * ============================================
 * Menggunakan OpenStreetMap + Nominatim untuk geocoding
 * 100% GRATIS, tidak perlu API Key atau kartu kredit!
 */

// Variable global
let map;
let marker;

// Default lokasi awal (Jember, Indonesia)
const DEFAULT_LOCATION = {
  lat: -8.1592084,
  lng: 113.7226594,
};

/**
 * ============================================
 * INISIALISASI LEAFLET MAP
 * ============================================
 */
document.addEventListener("DOMContentLoaded", function () {
  initMap();
  setupSearch();
  setupFormValidation();
});

/**
 * Inisialisasi peta
 */
function initMap() {
  // Check if edit mode with existing coordinates
  let initialLat = DEFAULT_LOCATION.lat;
  let initialLng = DEFAULT_LOCATION.lng;
  let initialZoom = 15;

  if (window.isEditMode && window.editCoordinates) {
    initialLat = window.editCoordinates.lat;
    initialLng = window.editCoordinates.lng;
    initialZoom = 17; // Zoom lebih dekat untuk edit mode
  }

  // Buat peta dengan Leaflet
  map = L.map("map").setView([initialLat, initialLng], initialZoom);

  // Tambahkan tile layer dari OpenStreetMap (GRATIS!)
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "© OpenStreetMap contributors",
    maxZoom: 19,
  }).addTo(map);

  // Buat marker yang bisa di-drag
  marker = L.marker([DEFAULT_LOCATION.lat, DEFAULT_LOCATION.lng], {
    draggable: true,
    title: "Drag saya untuk mengubah lokasi",
  }).addTo(map);

  // Custom icon untuk marker (opsional - lebih menarik)
  const customIcon = L.icon({
    iconUrl:
      "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png",
    shadowUrl:
      "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png",
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
  });
  marker.setIcon(customIcon);
  // If edit mode, set marker to existing coordinates and update fields
  if (window.isEditMode && window.editCoordinates) {
    marker.setLatLng([window.editCoordinates.lat, window.editCoordinates.lng]);
    updateCoordinates(window.editCoordinates.lat, window.editCoordinates.lng);
  }
  // Event ketika marker di-drag
  marker.on("dragend", function (e) {
    const position = marker.getLatLng();
    updateCoordinates(position.lat, position.lng);
    reverseGeocode(position.lat, position.lng); // Cari alamat dari koordinat
  });

  // Event ketika klik di peta
  map.on("click", function (e) {
    marker.setLatLng(e.latlng);
    updateCoordinates(e.latlng.lat, e.latlng.lng);
    reverseGeocode(e.latlng.lat, e.latlng.lng);
  });
}

/**
 * ============================================
 * SETUP SEARCH ALAMAT
 * ============================================
 * Menggunakan Nominatim API (OpenStreetMap) - GRATIS!
 */
function setupSearch() {
  const searchInput = document.getElementById("address-search");
  const searchBtn = document.getElementById("search-btn");
  const searchResults = document.getElementById("search-results");

  // Event klik tombol search
  searchBtn.addEventListener("click", function () {
    performSearch();
  });

  // Event tekan Enter di input search
  searchInput.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      performSearch();
    }
  });

  // Fungsi untuk melakukan pencarian
  function performSearch() {
    const query = searchInput.value.trim();

    if (query.length < 3) {
      alert("Masukkan minimal 3 karakter untuk mencari");
      return;
    }

    // Tampilkan loading
    searchResults.innerHTML =
      '<div class="search-result-item">Mencari...</div>';
    searchResults.style.display = "block";

    // Panggil Nominatim API (GRATIS!)
    // Tambahkan bias ke Indonesia untuk hasil lebih akurat
    const url = `http://localhost/Web-App/proxy_nominatim.php?endpoint=search&format=json&q=${encodeURIComponent(
      query
    )}&countrycodes=id&limit=5`;

    fetch(url, {
      headers: {
        "User-Agent": "KostHub App", // Required oleh Nominatim
      },
    })
      .then((response) => response.json())
      .then((data) => {
        displaySearchResults(data);
      })
      .catch((error) => {
        console.error("Error:", error);
        searchResults.innerHTML =
          '<div class="search-result-item text-danger">Gagal mencari lokasi. Coba lagi.</div>';
      });
  }

  // Tampilkan hasil pencarian
  function displaySearchResults(results) {
    if (results.length === 0) {
      searchResults.innerHTML =
        '<div class="search-result-item">Tidak ada hasil. Coba kata kunci lain.</div>';
      return;
    }

    searchResults.innerHTML = "";

    results.forEach((result) => {
      const item = document.createElement("div");
      item.className = "search-result-item";
      item.innerHTML = `
                <i class="bi bi-geo-alt text-danger"></i> 
                <strong>${result.display_name}</strong>
            `;

      // Event klik hasil pencarian
      item.addEventListener("click", function () {
        selectLocation(result);
      });

      searchResults.appendChild(item);
    });
  }

  // Pilih lokasi dari hasil pencarian
  function selectLocation(result) {
    const lat = parseFloat(result.lat);
    const lng = parseFloat(result.lon);

    // Update peta
    map.setView([lat, lng], 17);
    marker.setLatLng([lat, lng]);

    // Update koordinat
    updateCoordinates(lat, lng);

    // Isi field alamat
    document.getElementById("address").value = result.display_name;

    // Auto-fill provinsi dan kota
    parseAddress(result.display_name);

    // Sembunyikan hasil pencarian
    searchResults.style.display = "none";
    document.getElementById("address-search").value = "";
  }

  // Sembunyikan hasil jika klik di luar
  document.addEventListener("click", function (e) {
    if (!e.target.closest(".search-box")) {
      searchResults.style.display = "none";
    }
  });
}

/**
 * ============================================
 * REVERSE GEOCODING
 * ============================================
 * Mendapatkan alamat dari koordinat
 */
function reverseGeocode(lat, lng) {
  const url = `http://localhost/Web-App/proxy_nominatim.php?endpoint=reverse&format=json&lat=${lat}&lon=${lng}`;

  fetch(url, {
    headers: {
      "User-Agent": "KostHub App",
    },
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.display_name) {
        document.getElementById("address").value = data.display_name;
        parseAddress(data.display_name);
      }
    })
    .catch((error) => {
      console.error("Reverse geocoding error:", error);
    });
}

/**
 * ============================================
 * UPDATE KOORDINAT
 * ============================================
 */
function updateCoordinates(lat, lng) {
  document.getElementById("latitude").value = lat;
  document.getElementById("longitude").value = lng;
  document.getElementById("coord-display").textContent = `${lat.toFixed(
    7
  )}, ${lng.toFixed(7)}`;

  console.log("Koordinat diupdate:", { lat, lng });
}

/**
 * ============================================
 * PARSE ALAMAT
 * ============================================
 * Ekstrak provinsi dan kota dari alamat lengkap
 */
function parseAddress(fullAddress) {
  // Split alamat berdasarkan koma
  const parts = fullAddress.split(",").map((part) => part.trim());

  // Logika sederhana untuk Indonesia
  // Format umum: Jalan, Kelurahan, Kecamatan, Kota, Provinsi, Kode Pos, Indonesia

  let city = "";
  let province = "";

  // Cari kata kunci provinsi
  const provinceKeywords = [
    "Jawa Timur",
    "Jawa Tengah",
    "Jawa Barat",
    "DKI Jakarta",
    "Bali",
    "Sumatera",
    "Kalimantan",
    "Sulawesi",
    "Papua",
  ];

  parts.forEach((part) => {
    // Cek provinsi
    provinceKeywords.forEach((keyword) => {
      if (part.includes(keyword)) {
        province = part;
      }
    });

    // Cek kota (biasanya ada kata "Kota" atau "Kabupaten")
    if (part.includes("Kota") || part.includes("Kabupaten")) {
      city = part.replace("Kota ", "").replace("Kabupaten ", "");
    }
  });

  // Auto-fill jika ditemukan
  if (city) {
    document.querySelector('[name="city"]').value = city;
  }
  if (province) {
    document.querySelector('[name="province"]').value = province;
  }
}

/**
 * ============================================
 * VALIDASI FORM
 * ============================================
 */
function setupFormValidation() {
  const form = document.getElementById("propertyForm");

  form.addEventListener("submit", function (e) {
    const lat = document.getElementById("latitude").value;
    const lng = document.getElementById("longitude").value;

    if (!lat || !lng) {
      e.preventDefault();

      alert(
        "⚠️ Mohon pilih lokasi di peta terlebih dahulu!\n\nCara memilih lokasi:\n1. Gunakan kotak pencarian untuk mencari lokasi\n2. Atau klik langsung di peta\n3. Atau drag marker merah ke lokasi yang tepat"
      );

      document.getElementById("map").scrollIntoView({
        behavior: "smooth",
        block: "center",
      });

      return false;
    }

    console.log("Form validation passed");
    console.log("Latitude:", lat);
    console.log("Longitude:", lng);
  });
}

/**
 * ============================================
 * HELPER FUNCTIONS
 * ============================================
 */

// Fungsi untuk mencari lokasi berdasarkan kota (bisa dipanggil dari luar)
function searchByCity(cityName) {
  document.getElementById("address-search").value = cityName;
  document.getElementById("search-btn").click();
}

// Fungsi untuk set lokasi manual (jika diperlukan)
function setLocation(lat, lng) {
  map.setView([lat, lng], 17);
  marker.setLatLng([lat, lng]);
  updateCoordinates(lat, lng);
  reverseGeocode(lat, lng);
}

// Export fungsi untuk digunakan di file lain (opsional)
window.leafletMaps = {
  searchByCity,
  setLocation,
};
