<?php
/**
 * ============================================
 * ADD PROPERTY WITH PAYMENT
 * File: backend/user/owner/classes/add_property_process.php
 * ============================================
 */

session_start();
require_once '../../../config/db.php';
require_once '../../../config/midtrans.php';

// Check if owner is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    $_SESSION['error'] = 'Anda harus login sebagai owner';
    header('Location: ../../../../frontend/user/owner/pages/login.php');
    exit();
}

$owner_id = $_SESSION['user_id'];

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method';
    header('Location: ../../../../frontend/user/owner/pages/add_property.php');
    exit();
}

// Get form data
$name = trim($_POST['name'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$province = trim($_POST['province'] ?? '');
$postal_code = trim($_POST['postal_code'] ?? '');
$latitude = trim($_POST['latitude'] ?? '');
$longitude = trim($_POST['longitude'] ?? '');
$kos_type = trim($_POST['kos_type'] ?? '');
$total_rooms = intval($_POST['total_rooms'] ?? 0);
$price_monthly = intval($_POST['price_monthly'] ?? 0);
$price_daily = !empty($_POST['price_daily']) ? intval($_POST['price_daily']) : null;
$description = trim($_POST['description'] ?? '');
$facilities = $_POST['facilities'] ?? [];
$rules = $_POST['rules'] ?? [];

// Validation
$errors = [];
if (empty($name)) $errors[] = 'Nama property wajib diisi';
if (empty($address)) $errors[] = 'Alamat wajib diisi';
if (empty($city)) $errors[] = 'Kota wajib diisi';
if (empty($province)) $errors[] = 'Provinsi wajib diisi';
if (empty($latitude) || empty($longitude)) $errors[] = 'Lokasi di peta wajib dipilih';
if (!in_array($kos_type, ['putra', 'putri', 'campur'])) $errors[] = 'Jenis kos tidak valid';
if ($total_rooms < 1) $errors[] = 'Total kamar minimal 1';
if ($price_monthly < 1) $errors[] = 'Harga perbulan wajib diisi';
if (empty($_FILES['images']['name'][0])) $errors[] = 'Minimal upload 1 gambar';

if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    header('Location: ../../../../frontend/user/owner/pages/add_property.php');
    exit();
}

// Setup upload path
$root_path = realpath(dirname(__FILE__) . '/../../../../');
$upload_dir = $root_path . '/uploads/kos/';

if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        $_SESSION['error'] = 'Gagal membuat folder upload';
        header('Location: ../../../../frontend/user/owner/pages/add_property.php');
        exit();
    }
}

if (!is_writable($upload_dir)) {
    $_SESSION['error'] = 'Folder upload tidak writable';
    header('Location: ../../../../frontend/user/owner/pages/add_property.php');
    exit();
}

/**
 * ============================================
 * BEGIN TRANSACTION
 * ============================================
 */
try {
    $conn->begin_transaction();

    // 1. INSERT KOS dengan status NULL (kosong) dan payment_status 'unpaid'
    // Status kos baru akan diisi 'pending' setelah payment selesai
    $sql_kos = "INSERT INTO kos (
        owner_id, name, description, address, latitude, longitude, 
        city, province, postal_code, kos_type, total_rooms, 
        available_rooms, price_monthly, price_daily, rules, 
        status, payment_status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, 'unpaid')";

    $stmt_kos = $conn->prepare($sql_kos);
    $rules_json = !empty($rules) ? json_encode($rules) : null;
    $available_rooms = $total_rooms;
    
    $stmt_kos->bind_param(
        'isssddssssiiiss',
        $owner_id, $name, $description, $address, $latitude, $longitude,
        $city, $province, $postal_code, $kos_type, $total_rooms,
        $available_rooms, $price_monthly, $price_daily, $rules_json
    );

    if (!$stmt_kos->execute()) {
        throw new Exception('Gagal menyimpan data property');
    }

    $kos_id = (int) $conn->insert_id;

    // 2. INSERT FACILITIES
    if (!empty($facilities) && is_array($facilities)) {
        $sql_facility = "INSERT INTO kos_facilities (kos_id, facility_id) VALUES (?, ?)";
        $stmt_facility = $conn->prepare($sql_facility);

        foreach ($facilities as $facility_id) {
            $facility_id = intval($facility_id);
            if ($facility_id > 0) {
                $stmt_facility->bind_param('ii', $kos_id, $facility_id);
                $stmt_facility->execute();
            }
        }
        $stmt_facility->close();
    }

    // 3. UPLOAD IMAGES
    $uploaded_images = [];
    $files = $_FILES['images'];
    $file_count = count($files['name']);

    if ($file_count > 10) {
        throw new Exception('Maksimal upload 10 gambar');
    }

    for ($i = 0; $i < $file_count; $i++) {
        if (empty($files['name'][$i])) continue;

        $file_name = $files['name'][$i];
        $file_tmp = $files['tmp_name'][$i];
        $file_size = $files['size'][$i];
        $file_error = $files['error'][$i];

        if ($file_error !== UPLOAD_ERR_OK) {
            throw new Exception("Error upload gambar: $file_name");
        }

        if ($file_size > 10 * 1024 * 1024) {
            throw new Exception("Ukuran file $file_name terlalu besar (max 10MB)");
        }

        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception("File $file_name bukan gambar yang valid");
        }

        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name = 'kos_' . $kos_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $new_file_name;

        if (!move_uploaded_file($file_tmp, $file_path)) {
            throw new Exception("Gagal upload file: $file_name");
        }

        $image_url = 'uploads/kos/' . $new_file_name;
        $uploaded_images[] = [
            'url' => $image_url,
            'path' => $file_path
        ];
    }

    if (empty($uploaded_images)) {
        throw new Exception('Gagal upload gambar. Minimal 1 gambar diperlukan');
    }

    // 4. INSERT IMAGES
    $sql_image = "INSERT INTO kos_images (kos_id, image_url) VALUES (?, ?)";
    $stmt_image = $conn->prepare($sql_image);

    foreach ($uploaded_images as $image_data) {
        $stmt_image->bind_param('is', $kos_id, $image_data['url']);
        if (!$stmt_image->execute()) {
            throw new Exception('Gagal menyimpan data gambar');
        }
    }
    $stmt_image->close();

    // 5. CREATE PAYMENT RECORD
    $order_id = generateOrderId('KOS');
    $tax_amount = calculateTax($price_monthly);
    $total_amount = $tax_amount;
    $expired_at = date('Y-m-d H:i:s', strtotime('+' . PAYMENT_EXPIRY_DURATION . ' hours'));

    $sql_payment = "INSERT INTO property_payments (
        kos_id, owner_id, order_id, price_monthly, 
        tax_percentage, tax_amount, total_amount, 
        payment_status, expired_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)";

    $stmt_payment = $conn->prepare($sql_payment);
    $tax_percentage = TAX_PERCENTAGE;
    $stmt_payment->bind_param(
        'iisiiiis',
        $kos_id, $owner_id, $order_id, $price_monthly,
        $tax_percentage, $tax_amount, $total_amount, $expired_at
    );

    if (!$stmt_payment->execute()) {
        throw new Exception('Gagal membuat record pembayaran');
    }

    $payment_id = (int) $conn->insert_id;

    // 6. UPDATE KOS dengan payment_id
    $update_kos = "UPDATE kos SET payment_id = ? WHERE id = ?";
    $stmt_update = $conn->prepare($update_kos);
    $stmt_update->bind_param('ii', $payment_id, $kos_id);
    $stmt_update->execute();

    // COMMIT TRANSACTION
    $conn->commit();

    // Store data untuk payment page
    $_SESSION['pending_payment'] = [
        'kos_id' => $kos_id,
        'payment_id' => $payment_id,
        'order_id' => $order_id,
        'kos_name' => $name,
        'price_monthly' => $price_monthly,
        'tax_amount' => $tax_amount,
        'total_amount' => $total_amount
    ];

    $_SESSION['success'] = 'Property berhasil ditambahkan! Silakan lakukan pembayaran.';
    
    // Redirect ke dashboard (payment modal akan muncul)
    header('Location: ../../../../frontend/user/owner/pages/dashboard.php');
    exit();

} catch (Exception $e) {
    // ROLLBACK
    $conn->rollback();

    // Cleanup uploaded files
    if (!empty($uploaded_images)) {
        foreach ($uploaded_images as $image_data) {
            if (file_exists($image_data['path'])) {
                unlink($image_data['path']);
            }
        }
    }

    $_SESSION['error'] = 'Gagal menambahkan property: ' . $e->getMessage();
    header('Location: ../../../../frontend/user/owner/pages/add_property.php');
    exit();
}

if (isset($stmt_kos)) $stmt_kos->close();
$conn->close();
?>