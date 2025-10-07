/**
 * ============================================
 * GOOGLE MAPS CONFIGURATION
 * File: google_maps.js
 * ============================================
 * Fungsi untuk menangani Google Maps di form Add Property
 */

// Variable global untuk menyimpan instance map dan marker
let map;
let marker;
let autocomplete;

// Default lokasi awal (Jember, Indonesia)
const DEFAULT_LOCATION = {
    lat: -8.1592084,
    lng: 113.7226594
};

/**
 * ============================================
 * INISIALISASI GOOGLE MAPS
 * ============================================
 * Fungsi ini dipanggil otomatis oleh Google Maps API
 * setelah script selesai dimuat (callback=initMap)
 */
function initMap() {
    // Buat peta di element dengan id "map"
    map = new google.maps.Map(document.getElementById('map'), {
        center: DEFAULT_LOCATION,
        zoom: 15,
        mapTypeControl: true,      // Tombol untuk ganti tipe peta (roadmap, satellite)
        streetViewControl: true,   // Tombol untuk street view
        fullscreenControl: true    // Tombol fullscreen
    });

    // Buat marker (pin merah) yang bisa di-drag
    marker = new google.maps.Marker({
        position: DEFAULT_LOCATION,
        map: map,
        draggable: true,                    // Marker bisa di-drag
        animation: google.maps.Animation.DROP, // Animasi jatuh saat pertama muncul
        title: 'Drag saya untuk mengubah lokasi'
    });

    // Setup event listeners
    setupMarkerEvents();
    setupMapEvents();
    setupAddressAutocomplete();
    setupFormValidation();
}

/**
 * ============================================
 * EVENT LISTENERS UNTUK MARKER
 * ============================================
 */
function setupMarkerEvents() {
    // Event ketika marker selesai di-drag
    marker.addListener('dragend', function(event) {
        const lat = event.latLng.lat();
        const lng = event.latLng.lng();
        updateCoordinates(lat, lng);
    });
}

/**
 * ============================================
 * EVENT LISTENERS UNTUK MAP
 * ============================================
 */
function setupMapEvents() {
    // Event ketika user klik di peta
    map.addListener('click', function(event) {
        // Pindahkan marker ke lokasi yang diklik
        marker.setPosition(event.latLng);
        
        // Update koordinat
        const lat = event.latLng.lat();
        const lng = event.latLng.lng();
        updateCoordinates(lat, lng);
    });
}

/**
 * ============================================
 * SETUP AUTOCOMPLETE UNTUK ALAMAT
 * ============================================
 * Fitur untuk suggestion alamat saat user mengetik
 */
function setupAddressAutocomplete() {
    const addressInput = document.getElementById('address');
    
    // Inisialisasi autocomplete
    autocomplete = new google.maps.places.Autocomplete(addressInput, {
        componentRestrictions: { country: 'id' }, // Batasi hanya alamat di Indonesia
        fields: ['geometry', 'formatted_address', 'address_components']
    });

    // Event ketika user memilih alamat dari suggestion
    autocomplete.addListener('place_changed', function() {
        const place = autocomplete.getPlace();
        
        // Validasi: pastikan alamat memiliki koordinat
        if (!place.geometry) {
            alert('Tidak dapat menemukan lokasi untuk alamat tersebut. Silakan pilih dari daftar yang muncul.');
            return;
        }

        // Update peta: zoom in dan center ke lokasi
        map.setCenter(place.geometry.location);
        map.setZoom(17);
        
        // Pindahkan marker ke lokasi baru
        marker.setPosition(place.geometry.location);
        
        // Update koordinat latitude & longitude
        const lat = place.geometry.location.lat();
        const lng = place.geometry.location.lng();
        updateCoordinates(lat, lng);

        // Auto-fill field provinsi, kota, dan kode pos
        if (place.address_components) {
            fillAddressComponents(place.address_components);
        }
    });
}

/**
 * ============================================
 * UPDATE KOORDINAT
 * ============================================
 * Fungsi untuk mengupdate nilai latitude dan longitude
 * ke hidden input fields dan menampilkan di UI
 * 
 * @param {number} lat - Latitude
 * @param {number} lng - Longitude
 */
function updateCoordinates(lat, lng) {
    // Update hidden input fields
    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;
    
    // Update tampilan koordinat untuk user (7 digit desimal)
    document.getElementById('coord-display').textContent = 
        `${lat.toFixed(7)}, ${lng.toFixed(7)}`;
    
    console.log('Koordinat diupdate:', { lat, lng });
}

/**
 * ============================================
 * AUTO-FILL PROVINSI, KOTA, KODE POS
 * ============================================
 * Fungsi untuk otomatis mengisi field provinsi, kota, dan kode pos
 * berdasarkan hasil Google Places API
 * 
 * @param {Array} components - Array dari address_components Google Places
 */
function fillAddressComponents(components) {
    let city = '';
    let province = '';
    let postalCode = '';

    // Loop melalui semua komponen alamat
    components.forEach(component => {
        const types = component.types;
        
        // Cari komponen yang sesuai dengan tipe yang kita butuhkan
        if (types.includes('administrative_area_level_2')) {
            // Level 2 = Kota/Kabupaten
            city = component.long_name;
        }
        if (types.includes('administrative_area_level_1')) {
            // Level 1 = Provinsi
            province = component.long_name;
        }
        if (types.includes('postal_code')) {
            // Kode Pos
            postalCode = component.long_name;
        }
    });

    // Isi field jika data ditemukan
    if (city) {
        document.querySelector('[name="city"]').value = city;
    }
    if (province) {
        document.querySelector('[name="province"]').value = province;
    }
    if (postalCode) {
        document.querySelector('[name="postal_code"]').value = postalCode;
    }

    console.log('Address components filled:', { city, province, postalCode });
}

/**
 * ============================================
 * VALIDASI FORM SEBELUM SUBMIT
 * ============================================
 * Pastikan user sudah memilih lokasi di peta
 * sebelum form bisa di-submit
 */
function setupFormValidation() {
    const form = document.getElementById('propertyForm');
    
    form.addEventListener('submit', function(e) {
        const lat = document.getElementById('latitude').value;
        const lng = document.getElementById('longitude').value;

        // Validasi: koordinat harus terisi
        if (!lat || !lng) {
            e.preventDefault(); // Batalkan submit
            
            alert('⚠️ Mohon pilih lokasi di peta terlebih dahulu!\n\nCara memilih lokasi:\n1. Ketik alamat di field "Alamat"\n2. Atau klik langsung di peta\n3. Atau drag marker merah ke lokasi yang tepat');
            
            // Scroll ke peta
            document.getElementById('map').scrollIntoView({ 
                behavior: 'smooth',
                block: 'center'
            });
            
            return false;
        }

        // Koordinat sudah valid, form bisa disubmit
        console.log('Form validation passed');
        console.log('Latitude:', lat);
        console.log('Longitude:', lng);
        
        // Jika Anda ingin mencegah submit default dan handle dengan AJAX,
        // uncomment baris berikut:
        // e.preventDefault();
        // submitFormWithAjax(lat, lng);
    });
}

/**
 * ============================================
 * OPTIONAL: SUBMIT DENGAN AJAX
 * ============================================
 * Contoh fungsi jika ingin submit form dengan AJAX
 * (Uncomment dan sesuaikan jika diperlukan)
 */
/*
function submitFormWithAjax(lat, lng) {
    const formData = new FormData(document.getElementById('propertyForm'));
    
    fetch('process_add_property.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Property berhasil ditambahkan!');
            window.location.href = 'dashboard.php';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat submit form');
    });
}
*/

// Pastikan initMap tersedia di global scope untuk callback Google Maps API
window.initMap = initMap;