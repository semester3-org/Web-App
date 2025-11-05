<?php
/**
 * ============================================
 * GET PROPERTIES API
 * File: backend/user/owner/classes/get_properties.php
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

$owner_id = $_SESSION['user_id'];
$status_filter = isset($_GET['status']) && !empty($_GET['status']) ? $_GET['status'] : null;

try {
    // Build query dengan filter
    $sql = "SELECT 
                k.id,
                k.name,
                k.city,
                k.province,
                k.kos_type,
                k.total_rooms,
                k.available_rooms,
                k.price_monthly,
                k.price_daily,
                k.status,
                k.payment_status,
                (SELECT image_url FROM kos_images WHERE kos_id = k.id LIMIT 1) as image_url
            FROM kos k
            WHERE k.owner_id = ? 
            AND k.payment_status = 'paid'
            AND k.status IN ('pending', 'approved', 'rejected')";
    
    $params = [$owner_id];
    $types = "i";
    
    // Add status filter if specified
    if ($status_filter) {
        $sql .= " AND k.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
    
    $sql .= " ORDER BY k.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $properties = [];
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'properties' => $properties,
        'count' => count($properties)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>