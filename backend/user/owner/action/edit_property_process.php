<?php

/**
 * ============================================
 * EDIT PROPERTY PROCESS
 * File: backend/user/owner/classes/edit_property_process.php
 * ============================================
 */

session_start();
// DEBUGGING - HAPUS SETELAH FIX
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "../../../config/db.php";

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    header("Location: ../../../../frontend/pages/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../../frontend/user/owner/pages/your_property.php");
    exit();
}

$owner_id = $_SESSION['user_id'];
$property_id = intval($_POST['property_id']);

try {
    // Verify ownership
    $check_sql = "SELECT id FROM kos WHERE id = ? AND owner_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $property_id, $owner_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows == 0) {
        throw new Exception("Property tidak ditemukan atau Anda tidak memiliki akses!");
    }

    // Start transaction
    $conn->begin_transaction();

    // Prepare data
    $name = $_POST['name'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $province = $_POST['province'];
    $postal_code = $_POST['postal_code'] ?? null;
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $kos_type = $_POST['kos_type'];
    $total_rooms = intval($_POST['total_rooms']);
    $price_monthly = intval(str_replace('.', '', $_POST['price_monthly']));
    $price_daily = !empty($_POST['price_daily']) ? intval(str_replace('.', '', $_POST['price_daily'])) : null;
    $description = $_POST['description'] ?? null;

    // Process rules
    $rules = null;
    if (isset($_POST['rules'])) {
        $rulesArray = json_decode($_POST['rules'], true);
        if (is_array($rulesArray) && count($rulesArray) > 0) {
            $rules = json_encode($rulesArray);
        }
    }

    // Update kos table - STATUS KEMBALI KE PENDING
    $sql = "UPDATE kos SET 
            name = ?,
            address = ?,
            city = ?,
            province = ?,
            postal_code = ?,
            latitude = ?,
            longitude = ?,
            kos_type = ?,
            total_rooms = ?,
            price_monthly = ?,
            price_daily = ?,
            description = ?,
            rules = ?,
            status = 'pending',
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND owner_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssddsisissii",
        $name,
        $address,
        $city,
        $province,
        $postal_code,
        $latitude,
        $longitude,
        $kos_type,
        $total_rooms,
        $price_monthly,
        $price_daily,
        $description,
        $rules,
        $property_id,
        $owner_id
    );

    if (!$stmt->execute()) {
        throw new Exception("Gagal update property: " . $stmt->error);
    }

    // Update facilities
    if (isset($_POST['facilities'])) {
        $facilitiesArray = json_decode($_POST['facilities'], true);

        if (is_array($facilitiesArray)) {
            // Hapus semua fasilitas lama dulu
            $delete_fac = $conn->prepare("DELETE FROM kos_facilities WHERE kos_id = ?");
            $delete_fac->bind_param("i", $property_id);
            $delete_fac->execute();
            $delete_fac->close();

            // Masukkan fasilitas baru jika ada
            if (count($facilitiesArray) > 0) {
                $insert_fac = $conn->prepare("INSERT INTO kos_facilities (kos_id, facility_id) VALUES (?, ?)");

                foreach ($facilitiesArray as $facility_id) {
                    $facility_id = intval($facility_id);
                    $insert_fac->bind_param("ii", $property_id, $facility_id);

                    if (!$insert_fac->execute()) {
                        throw new Exception("Gagal menambahkan fasilitas ID $facility_id: " . $insert_fac->error);
                    }
                }

                $insert_fac->close();
            }
        } else {
            throw new Exception("Format data fasilitas tidak valid (bukan array).");
        }
    }


    // Handle images
    // 1. Get existing images that user kept
    $existing_images = [];
    if (isset($_POST['existing_images'])) {
        $existing_images = json_decode($_POST['existing_images'], true);
        if (!is_array($existing_images)) {
            $existing_images = [];
        }
    }

    // 2. Get all current images in database
    $get_all_images = $conn->prepare("SELECT image_url FROM kos_images WHERE kos_id = ?");
    $get_all_images->bind_param("i", $property_id);
    $get_all_images->execute();
    $result_all = $get_all_images->get_result();
    $db_images = [];
    while ($row = $result_all->fetch_assoc()) {
        $db_images[] = $row['image_url'];
    }

    // 3. Delete images that were removed (not in existing_images anymore)
    foreach ($db_images as $db_image) {
        if (!in_array($db_image, $existing_images)) {
            // Delete from database
            $delete_img = $conn->prepare("DELETE FROM kos_images WHERE kos_id = ? AND image_url = ?");
            $delete_img->bind_param("is", $property_id, $db_image);
            $delete_img->execute();

            // Delete physical file
            $file_path = "../../../../" . $db_image;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }

    // 4. Handle new image uploads
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $upload_dir = "../../../../uploads/kos/";

        // Create directory if not exists
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $image_stmt = $conn->prepare("INSERT INTO kos_images (kos_id, image_url) VALUES (?, ?)");

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] == 0) {
                $file_ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                $file_name = "kos_" . $property_id . "_" . time() . "_" . uniqid() . "." . $file_ext;
                $target_file = $upload_dir . $file_name;

                if (move_uploaded_file($tmp_name, $target_file)) {
                    $image_url = "uploads/kos/" . $file_name;
                    $image_stmt->bind_param("is", $property_id, $image_url);
                    $image_stmt->execute();
                }
            }
        }
    }

    // Commit transaction
    $conn->commit();

    // Redirect with success message
    $success_msg = urlencode("Property berhasil diupdate! Status kembali ke Pending untuk ditinjau admin.");
    header("Location: ../../../../frontend/user/owner/pages/your_property.php?success=1&message=" . $success_msg);
    exit();
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: ../../../../frontend/user/owner/pages/edit_property.php?id=" . $property_id);
    exit();
}

$conn->close();
?>