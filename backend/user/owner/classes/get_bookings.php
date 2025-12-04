<?php
/**
 * ============================================
 * GET BOOKINGS FOR OWNER
 * File: backend/user/owner/classes/get_bookings.php
 * ============================================
 * Fetch semua booking untuk properties milik owner
 */

session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . "/backend/config/db.php");

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$owner_id = $_SESSION['user_id'];

try {
    // Get all bookings for owner's properties
    $sql = "SELECT 
                b.id,
                b.kos_id,
                b.user_id,
                b.check_in_date,
                b.check_out_date,
                b.booking_type,
                b.duration_months,
                b.total_price,
                b.status,
                b.notes,
                b.created_at,
                b.updated_at,
                k.name as property_name,
                k.address,
                k.monthly_price,
                k.daily_price,
                u.full_name as customer_name,
                u.email as customer_email,
                u.phone as customer_phone,
                u.profile_picture as customer_picture
            FROM bookings b
            JOIN kos k ON b.kos_id = k.id
            JOIN users u ON b.user_id = u.id
            WHERE k.owner_id = ?
            ORDER BY 
                CASE b.status
                    WHEN 'pending' THEN 1
                    WHEN 'confirmed' THEN 2
                    WHEN 'rejected' THEN 3
                    WHEN 'cancelled' THEN 4
                    ELSE 5
                END,
                b.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    $stmt->close();
    
    // Get statistics
    $sql_stats = "SELECT 
        COUNT(CASE WHEN b.status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN b.status = 'confirmed' THEN 1 END) as confirmed_count,
        COUNT(CASE WHEN b.status = 'rejected' THEN 1 END) as rejected_count,
        COUNT(CASE WHEN b.status = 'cancelled' THEN 1 END) as cancelled_count
        FROM bookings b
        JOIN kos k ON b.kos_id = k.id
        WHERE k.owner_id = ?";
    
    $stmt_stats = $conn->prepare($sql_stats);
    $stmt_stats->bind_param("i", $owner_id);
    $stmt_stats->execute();
    $statistics = $stmt_stats->get_result()->fetch_assoc();
    $stmt_stats->close();
    
    echo json_encode([
        'success' => true,
        'bookings' => $bookings,
        'statistics' => $statistics
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>