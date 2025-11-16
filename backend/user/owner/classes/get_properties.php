<?php
/**
 * ============================================
 * GET PROPERTIES API
 * File: backend/user/owner/classes/get_properties.php
 * ============================================
 * Fetch properties untuk filter di booking management
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

try {
    // Ambil semua property milik owner yang sudah approved dan paid
    // Untuk dropdown filter di booking management
    $sql = "SELECT 
                k.id,
                k.name,
                k.city,
                k.status
            FROM kos k
            WHERE k.owner_id = ? 
            AND k.payment_status = 'paid'
            AND k.status = 'approved'
            ORDER BY k.name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $properties = [];
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
    
    $stmt->close();
    
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