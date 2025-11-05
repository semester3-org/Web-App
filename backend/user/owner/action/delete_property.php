<?php
/**
 * ============================================
 * DELETE PROPERTY API
 * File: backend/user/owner/action/delete_property.php
 * ============================================
 */

session_start();
require_once "../../../config/db.php";

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$property_id = isset($input['id']) ? intval($input['id']) : 0;
$owner_id = $_SESSION['user_id'];

if ($property_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid property ID']);
    exit();
}

try {
    // Verify ownership
    $check_sql = "SELECT id FROM kos WHERE id = ? AND owner_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $property_id, $owner_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Property tidak ditemukan atau Anda tidak memiliki akses']);
        exit();
    }

    // Start transaction
    $conn->begin_transaction();

    // Get all images to delete files
    $get_images = $conn->prepare("SELECT image_url FROM kos_images WHERE kos_id = ?");
    $get_images->bind_param("i", $property_id);
    $get_images->execute();
    $images_result = $get_images->get_result();
    
    $image_files = [];
    while ($img = $images_result->fetch_assoc()) {
        $image_files[] = $img['image_url'];
    }

    // Delete from kos_images
    $delete_images = $conn->prepare("DELETE FROM kos_images WHERE kos_id = ?");
    $delete_images->bind_param("i", $property_id);
    $delete_images->execute();

    // Delete from kos_facilities
    $delete_facilities = $conn->prepare("DELETE FROM kos_facilities WHERE kos_id = ?");
    $delete_facilities->bind_param("i", $property_id);
    $delete_facilities->execute();

    // Delete from kos table
    $delete_kos = $conn->prepare("DELETE FROM kos WHERE id = ? AND owner_id = ?");
    $delete_kos->bind_param("ii", $property_id, $owner_id);
    $delete_kos->execute();

    // Commit transaction
    $conn->commit();

    // Delete physical image files
    foreach ($image_files as $image_path) {
        $full_path = "../../../../" . $image_path;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Property berhasil dihapus'
    ]);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menghapus property: ' . $e->getMessage()
    ]);
}

$conn->close();
?>