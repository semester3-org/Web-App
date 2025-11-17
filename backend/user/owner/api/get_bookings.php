<?php
/**
 * ============================================
 * GET BOOKINGS API
 * File: backend/user/owner/classes/get_bookings.php
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

try {
    // Build base query
    $sql = "SELECT 
                b.*,
                k.name as kos_name,
                k.address,
                k.city,
                k.province,
                u.full_name,
                u.email,
                u.phone,
                u.profile_picture
            FROM bookings b
            JOIN kos k ON b.kos_id = k.id
            JOIN users u ON b.user_id = u.id
            WHERE k.owner_id = ?";
    
    $params = [$owner_id];
    $types = "i";
    
    // Apply filters
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $sql .= " AND b.status = ?";
        $params[] = $_GET['status'];
        $types .= "s";
    }
    
    if (isset($_GET['kos_id']) && !empty($_GET['kos_id'])) {
        $sql .= " AND b.kos_id = ?";
        $params[] = intval($_GET['kos_id']);
        $types .= "i";
    }
    
    if (isset($_GET['booking_type']) && !empty($_GET['booking_type'])) {
        $sql .= " AND b.booking_type = ?";
        $params[] = $_GET['booking_type'];
        $types .= "s";
    }
    
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR k.name LIKE ?)";
        $searchTerm = "%" . $_GET['search'] . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
    }
    
    $sql .= " ORDER BY 
                CASE b.status 
                    WHEN 'pending' THEN 1
                    WHEN 'confirmed' THEN 2
                    WHEN 'rejected' THEN 3
                    WHEN 'cancelled' THEN 4
                END,
                b.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'bookings' => $bookings,
        'count' => count($bookings)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
