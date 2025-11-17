<?php
/**
 * ============================================
 * DELETE REVIEW API
 * File: backend/user/owner/classes/delete_review.php
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
$review_id = isset($input['review_id']) ? intval($input['review_id']) : 0;
$owner_id = $_SESSION['user_id'];

if ($review_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid review ID']);
    exit();
}

try {
    // Verify that the review belongs to owner's property
    $check_sql = "SELECT r.id 
                  FROM reviews r 
                  JOIN kos k ON r.kos_id = k.id 
                  WHERE r.id = ? AND k.owner_id = ?";
    
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $review_id, $owner_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Review tidak ditemukan atau Anda tidak memiliki akses']);
        exit();
    }

    // Delete review
    $delete_sql = "DELETE FROM reviews WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $review_id);

     
    
    if ($delete_stmt->execute()) {
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Review berhasil dihapus'
        ]);
    } else {
        throw new Exception('Gagal menghapus review');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>