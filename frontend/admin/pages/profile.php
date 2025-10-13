<?php
session_start();
require_once __DIR__ . "/../auth/auth_admin.php";
require_once "../../../backend/config/db.php";

// Ambil data user dari DB
// Ambil data user dari DB
$user = null;
$profilePicPath = ''; 

if (isset($_SESSION['user_id'])) {
  $stmt = $conn->prepare("SELECT id, username, email, full_name, phone, user_type, profile_picture FROM users WHERE id = ?");
  $stmt->bind_param("i", $_SESSION['user_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();

  // Set profile picture path
  $profilePicPath = $user['profile_picture'] ?? '';

  // DEBUG - Uncomment untuk cek
  error_log("DEBUG: User ID = " . $_SESSION['user_id']);
  error_log("DEBUG: Profile Picture Path = " . $profilePicPath);

  $stmt->close();
}

// Debug output ke HTML (hapus setelah selesai)
echo "<!-- DEBUG Session User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . " -->";
echo "<!-- DEBUG Profile Pic Path: " . htmlspecialchars($profilePicPath) . " -->";
echo "<!-- DEBUG Profile Pic Empty: " . (empty($profilePicPath) ? 'YES' : 'NO') . " -->";


?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Profile - Dashboard Admin</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f6fa;
    }

    .profile-container {
      max-width: 500px;
      margin: 30px auto;
      background: #fff;
      border-radius: 8px;
      padding: 25px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
    }

    .profile-header {
      text-align: center;
      margin-bottom: 25px;
      position: relative;
    }

    .profile-avatar-container {
      position: relative;
      width: 120px;
      height: 120px;
      margin: 0 auto 15px;
    }

    .profile-avatar {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      border: 3px solid #333;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 48px;
      background: #f0f0f0;
      overflow: hidden;
      position: relative;
    }

    .profile-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .upload-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: rgba(0, 0, 0, 0.7);
      color: white;
      padding: 8px;
      text-align: center;
      cursor: pointer;
      opacity: 0;
      transition: opacity 0.3s;
      border-bottom-left-radius: 50%;
      border-bottom-right-radius: 50%;
    }

    .profile-avatar-container:hover .upload-overlay {
      opacity: 1;
    }

    .upload-overlay i {
      font-size: 20px;
    }

    #imageUpload {
      display: none;
    }

    .edit-btn {
      position: absolute;
      top: 0;
      right: 0;
      background: #4CAF50;
      border: none;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: white;
      transition: all 0.3s;
    }

    .edit-btn:hover {
      background: #45a049;
      transform: scale(1.1);
    }

    .edit-btn.editing {
      background: #ff5722;
    }

    .form-group {
      margin-bottom: 20px;
      position: relative;
    }

    .form-label {
      font-weight: 600;
      font-size: 14px;
      margin-bottom: 5px;
      display: block;
    }

    .form-input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
      background: #f9f9f9;
      transition: all 0.3s;
    }

    .form-input:focus {
      background: #fff;
      border-color: #4CAF50;
      outline: none;
    }

    .form-input[readonly] {
      background: #eee;
      cursor: not-allowed;
      color: #666;
    }

    .form-input.error {
      border-color: #f44336;
    }

    .error-text {
      color: #f44336;
      font-size: 12px;
      margin-top: 5px;
      display: none;
    }

    .error-text.show {
      display: block;
    }

    .button-group {
      display: flex;
      gap: 10px;
      margin-top: 20px;
    }

    .save-btn,
    .cancel-btn {
      flex: 1;
      padding: 12px;
      border: none;
      border-radius: 6px;
      font-size: 15px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s;
    }

    .save-btn {
      background: #4CAF50;
      color: #fff;
    }

    .save-btn:hover:not(:disabled) {
      background: #45a049;
    }

    .cancel-btn {
      background: #757575;
      color: #fff;
    }

    .cancel-btn:hover {
      background: #616161;
    }

    .save-btn:disabled,
    .cancel-btn:disabled {
      background: #ccc;
      cursor: not-allowed;
    }

    .message {
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 15px;
      display: none;
      font-size: 14px;
      animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .info {
      background: #d1ecf1;
      color: #0c5460;
      border: 1px solid #bee5eb;
    }

    .hidden {
      display: none !important;
    }

    /* Image preview modal */
    .image-preview-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .image-preview-modal.show {
      display: flex;
    }

    .preview-container {
      background: white;
      padding: 20px;
      border-radius: 8px;
      max-width: 400px;
      width: 90%;
      text-align: center;
    }

    .preview-image {
      width: 200px;
      height: 200px;
      border-radius: 50%;
      object-fit: cover;
      margin: 0 auto 20px;
      border: 3px solid #4CAF50;
    }

    .preview-buttons {
      display: flex;
      gap: 10px;
    }

    .preview-buttons button {
      flex: 1;
      padding: 10px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
      transition: all 0.3s;
    }

    .upload-btn {
      background: #4CAF50;
      color: white;
    }

    .upload-btn:hover {
      background: #45a049;
    }

    .change-btn {
      background: #2196F3;
      color: white;
    }

    .change-btn:hover {
      background: #1976D2;
    }

    .cancel-upload-btn {
      background: #f44336;
      color: white;
    }

    .cancel-upload-btn:hover {
      background: #d32f2f;
    }

    /* Loading spinner */
    .spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid #f3f3f3;
      border-top: 2px solid #4CAF50;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-left: 10px;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }
  </style>
</head>

<body>
  <?php include __DIR__ . "/../includes/sidebar.php"; ?>

  <main class="content">

    <div class="profile-container" style="position: relative;">
  <!-- Judul Profile -->
  <h1 style="text-align: center; margin-bottom: 30px;">Profile</h1>

  <!-- Tombol Edit di pojok kanan atas -->
  <button class="edit-btn" id="editToggle" title="Edit Profile"
          style="position: absolute; top: 10px; right: 10px;">
    <i class="fas fa-pencil-alt"></i>
  </button>
  
  <!-- Pesan -->
  <div id="msgBox" class="message"></div>
  
  <!-- Header Profil -->
  <div class="profile-header">

    <!-- isi konten lain di sini -->
        <div class="profile-avatar-container">
          <div class="profile-avatar" id="avatarContainer">
            <?php if (!empty($profilePicPath)): ?>
              <img src="<?php echo htmlspecialchars($profilePicPath); ?>"
                alt="Profile Picture"
                id="currentAvatar"
                onerror="console.error('Image failed to load:', this.src); this.style.display='none'; this.nextElementSibling.style.display='flex';">
              <i class="fas fa-user" id="defaultAvatar" style="display: none; font-size: 60px; color: #999;"></i>
            <?php else: ?>
              <i class="fas fa-user" id="defaultAvatar" style="font-size: 60px; color: #999;"></i>
            <?php endif; ?>
          </div>
          <div class="upload-overlay" onclick="document.getElementById('imageUpload').click();">
            <i class="fas fa-camera"></i>
            <div style="font-size: 10px;">Change Photo</div>
          </div>
        </div>

        <input type="file" id="imageUpload" accept="image/jpeg,image/jpg,image/png,image/gif">

        <h2 id="profileUsername"><?= htmlspecialchars($user['username'] ?? '') ?></h2>
        <small id="profileEmail"><?= htmlspecialchars($user['email'] ?? '') ?></small>
      </div>

      <form id="profileForm">
        <div class="form-group">
          <label class="form-label">Nama Lengkap</label>
          <input type="text"
            class="form-input"
            id="full_name"
            value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
            readonly>
          <span class="error-text" id="full_name_error"></span>
        </div>

        <div class="form-group">
          <label class="form-label">Username</label>
          <input type="text"
            class="form-input"
            id="username"
            value="<?= htmlspecialchars($user['username'] ?? '') ?>"
            data-original="<?= htmlspecialchars($user['username'] ?? '') ?>"
            readonly>
          <span class="error-text" id="username_error"></span>
        </div>

        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email"
            class="form-input"
            id="email"
            value="<?= htmlspecialchars($user['email'] ?? '') ?>"
            data-original="<?= htmlspecialchars($user['email'] ?? '') ?>"
            readonly>
          <span class="error-text" id="email_error"></span>
        </div>

        <div class="form-group">
          <label class="form-label">Nomor HP</label>
          <input type="text"
            class="form-input"
            id="phone"
            value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
            readonly>
          <span class="error-text" id="phone_error"></span>
        </div>

        <div class="form-group">
          <label class="form-label">Role</label>
          <input type="text"
            class="form-input"
            value="<?= ucfirst($user['user_type'] ?? '') ?>"
            readonly
            disabled>
        </div>

        <div class="button-group hidden" id="actionButtons">
          <button type="submit" class="save-btn" id="saveBtn">
            <i class="fas fa-save"></i> Simpan
          </button>
          <button type="button" class="cancel-btn" id="cancelBtn">
            <i class="fas fa-times"></i> Batal
          </button>
        </div>
      </form>
    </div>
  </main>

  <!-- Image Preview Modal -->
  <div class="image-preview-modal" id="previewModal">
    <div class="preview-container">
      <h3>Preview Foto Profile</h3>
      <img id="previewImage" class="preview-image" src="" alt="Preview">
      <div class="preview-buttons">
        <button class="upload-btn" onclick="uploadProfilePicture()">
          <i class="fas fa-upload"></i> Upload
        </button>
        <button class="change-btn" onclick="document.getElementById('imageUpload').click();">
          <i class="fas fa-redo"></i> Ganti
        </button>
        <button class="cancel-upload-btn" onclick="cancelImageUpload()">
          <i class="fas fa-times"></i> Batal
        </button>
      </div>
    </div>
  </div>

  <script>
    let isEditing = false;
    let originalData = {};
    let selectedFile = null;

    // Image upload handling
    document.getElementById('imageUpload').addEventListener('change', function(e) {
      const file = e.target.files[0];

      if (file) {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
          showMessage('error', 'Hanya file gambar yang diizinkan (JPG, PNG, GIF)');
          return;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
          showMessage('error', 'Ukuran file maksimal 5MB');
          return;
        }

        selectedFile = file;

        // Preview image
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('previewImage').src = e.target.result;
          document.getElementById('previewModal').classList.add('show');
        }
        reader.readAsDataURL(file);
      }
    });

    function uploadProfilePicture() {
      if (!selectedFile) {
        showMessage('error', 'Pilih file terlebih dahulu');
        return;
      }

      const formData = new FormData();
      formData.append('profile_picture', selectedFile);

      // Show loading
      const uploadBtn = document.querySelector('.upload-btn');
      const originalText = uploadBtn.innerHTML;
      uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
      uploadBtn.disabled = true;

      fetch('../../../backend/admin/classes/upload_profile-pictures.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            // Update avatar display
            const avatarContainer = document.getElementById('avatarContainer');
            avatarContainer.innerHTML = `<img src="${data.filepath}" alt="Profile Picture" id="currentAvatar">`;

            showMessage('success', 'Foto profile berhasil diupload');

            // Close modal
            document.getElementById('previewModal').classList.remove('show');

            // Reset file input
            document.getElementById('imageUpload').value = '';
            selectedFile = null;
          } else {
            showMessage('error', data.message || 'Gagal upload foto');
          }

          uploadBtn.innerHTML = originalText;
          uploadBtn.disabled = false;
        })
        .catch(error => {
          showMessage('error', 'Terjadi kesalahan saat upload');
          console.error('Upload error:', error);

          uploadBtn.innerHTML = originalText;
          uploadBtn.disabled = false;
        });
    }

    function cancelImageUpload() {
      document.getElementById('previewModal').classList.remove('show');
      document.getElementById('imageUpload').value = '';
      selectedFile = null;
    }

    // Toggle Edit Mode
    document.getElementById('editToggle').addEventListener('click', function() {
      const editBtn = this;
      const inputs = document.querySelectorAll('.form-input:not([disabled])');
      const actionButtons = document.getElementById('actionButtons');

      if (!isEditing) {
        // Enter edit mode
        isEditing = true;
        editBtn.classList.add('editing');
        editBtn.innerHTML = '<i class="fas fa-times"></i>';
        editBtn.title = 'Cancel Edit';

        // Store original values
        inputs.forEach(input => {
          originalData[input.id] = input.value;
          input.removeAttribute('readonly');
        });

        actionButtons.classList.remove('hidden');

        // Show info message
        showMessage('info', 'Mode edit aktif. Silakan ubah data yang diperlukan.');
      } else {
        // Exit edit mode
        cancelEdit();
      }
    });

    // Cancel Edit
    document.getElementById('cancelBtn').addEventListener('click', cancelEdit);

    function cancelEdit() {
      isEditing = false;
      const editBtn = document.getElementById('editToggle');
      const inputs = document.querySelectorAll('.form-input:not([disabled])');
      const actionButtons = document.getElementById('actionButtons');

      editBtn.classList.remove('editing');
      editBtn.innerHTML = '<i class="fas fa-pencil-alt"></i>';
      editBtn.title = 'Edit Profile';

      // Restore original values
      inputs.forEach(input => {
        input.value = originalData[input.id] || '';
        input.setAttribute('readonly', true);
        input.classList.remove('error');
      });

      // Clear all error messages
      document.querySelectorAll('.error-text').forEach(error => {
        error.classList.remove('show');
        error.textContent = '';
      });

      actionButtons.classList.add('hidden');
      hideMessage();
    }

    // Real-time validation
    document.getElementById('username').addEventListener('input', function() {
      validateUsername(this.value);
    });

    document.getElementById('email').addEventListener('input', function() {
      validateEmail(this.value);
    });

    document.getElementById('phone').addEventListener('input', function() {
      validatePhone(this.value);
    });

    document.getElementById('full_name').addEventListener('input', function() {
      validateFullName(this.value);
    });

    // Validation functions
    function validateUsername(value) {
      const errorEl = document.getElementById('username_error');
      const inputEl = document.getElementById('username');

      if (value.length < 3) {
        showError(inputEl, errorEl, 'Username minimal 3 karakter');
        return false;
      } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
        showError(inputEl, errorEl, 'Username hanya boleh huruf, angka, dan underscore');
        return false;
      } else if (value !== originalData.username) {
        // Check for duplicate username via AJAX
        checkDuplicate('username', value);
      } else {
        clearError(inputEl, errorEl);
      }
      return true;
    }

    function validateEmail(value) {
      const errorEl = document.getElementById('email_error');
      const inputEl = document.getElementById('email');
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (!emailRegex.test(value)) {
        showError(inputEl, errorEl, 'Format email tidak valid (contoh: user@gmail.com)');
        return false;
      } else if (value !== originalData.email) {
        // Check for duplicate email via AJAX
        checkDuplicate('email', value);
      } else {
        clearError(inputEl, errorEl);
      }
      return true;
    }

    function validatePhone(value) {
      const errorEl = document.getElementById('phone_error');
      const inputEl = document.getElementById('phone');

      if (value === '') {
        clearError(inputEl, errorEl);
        return true;
      }

      const phoneClean = value.replace(/[^0-9]/g, '');

      if (!/^[0-9]+$/.test(value)) {
        showError(inputEl, errorEl, 'Nomor telepon hanya boleh angka');
        return false;
      } else if (phoneClean.length < 10) {
        showError(inputEl, errorEl, 'Nomor telepon minimal 10 digit');
        return false;
      } else if (phoneClean.length > 15) {
        showError(inputEl, errorEl, 'Nomor telepon maksimal 15 digit');
        return false;
      } else {
        clearError(inputEl, errorEl);
      }
      return true;
    } 

    function validateFullName(value) {
      const errorEl = document.getElementById('full_name_error');
      const inputEl = document.getElementById('full_name');

      if (value.length < 2) {
        showError(inputEl, errorEl, 'Nama minimal 2 karakter');
        return false;
      } else {
        clearError(inputEl, errorEl);
      }
      return true;
    }

    function showError(input, errorEl, message) {
      input.classList.add('error');
      errorEl.textContent = message;
      errorEl.classList.add('show');
    }

    function clearError(input, errorEl) {
      input.classList.remove('error');
      errorEl.textContent = '';
      errorEl.classList.remove('show');
    }

    // Check for duplicate username/email
    let checkTimeout;

    function checkDuplicate(field, value) {
      clearTimeout(checkTimeout);

      checkTimeout = setTimeout(() => {
        fetch('../../../backend/admin/classes/check_duplicate.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              field: field,
              value: value
            })
          })
          .then(res => res.json())
          .then(data => {
            const errorEl = document.getElementById(field + '_error');
            const inputEl = document.getElementById(field);

            if (data.exists) {
              showError(inputEl, errorEl, `${field === 'username' ? 'Username' : 'Email'} sudah digunakan`);
            } else {
              clearError(inputEl, errorEl);
            }
          })
          .catch(err => console.error('Error checking duplicate:', err));
      }, 500); // Debounce 500ms
    }

    // Submit form
    document.getElementById('profileForm').addEventListener('submit', function(e) {
      e.preventDefault();

      // Validate all fields
      const isUsernameValid = validateUsername(document.getElementById('username').value);
      const isEmailValid = validateEmail(document.getElementById('email').value);
      const isPhoneValid = validatePhone(document.getElementById('phone').value);
      const isFullNameValid = validateFullName(document.getElementById('full_name').value);

      // Check if there are any visible errors
      const hasErrors = document.querySelectorAll('.error-text.show').length > 0;

      if (hasErrors || !isUsernameValid || !isEmailValid || !isPhoneValid || !isFullNameValid) {
        showMessage('error', 'Mohon perbaiki kesalahan sebelum menyimpan');
        return;
      }

      const data = {
        full_name: document.getElementById('full_name').value.trim(),
        username: document.getElementById('username').value.trim(),
        email: document.getElementById('email').value.trim(),
        phone: document.getElementById('phone').value.trim(),
      };

      const btn = document.getElementById('saveBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

      fetch("../../../backend/admin/classes/profile_process.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            showMessage('success', res.message);

            // Update UI with new data
            document.getElementById('profileUsername').textContent = data.username;
            document.getElementById('profileEmail').textContent = data.email;

            // Update original data for future edits
            originalData = {
              ...data
            };

            // Update data-original attributes
            document.getElementById('username').setAttribute('data-original', data.username);
            document.getElementById('email').setAttribute('data-original', data.email);

            // Exit edit mode after successful save
            setTimeout(() => {
              cancelEdit();
            }, 1500);
          } else {
            showMessage('error', res.message);
          }

          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-save"></i> Simpan';
        })
        .catch((error) => {
          showMessage('error', 'Terjadi kesalahan server.');
          console.error('Error:', error);

          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-save"></i> Simpan';
        });
    });

    function showMessage(type, message) {
      const msg = document.getElementById('msgBox');
      msg.style.display = 'block';
      msg.className = 'message ' + type;
      msg.textContent = message;

      // Auto hide after 5 seconds
      setTimeout(() => {
        hideMessage();
      }, 5000);
    }

    function hideMessage() {
      const msg = document.getElementById('msgBox');
      msg.style.display = 'none';
    }

    // Close modal when clicking outside
    document.getElementById('previewModal').addEventListener('click', function(e) {
      if (e.target === this) {
        cancelImageUpload();
      }
    });


    
  </script>

  <script>
    function toggleDropdown() {
      const menu = document.getElementById("dropdownMenu");
      menu.style.display = menu.style.display === "block" ? "none" : "block";
    }
    window.addEventListener("click", function(e) {
      if (!e.target.closest(".user-menu")) {
        document.getElementById("dropdownMenu").style.display = "none";
      }
    });
  </script>



</body>

</html>