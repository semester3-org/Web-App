<?php
/**
 * ============================================
 * GET FACILITIES API
 * File: frontend/user/owner/api/get_facilities.php
 * ============================================
 * Endpoint untuk mengambil semua fasilitas
 */

header('Content-Type: application/json');
require_once '../../../../backend/config/db.php';

try {
    // Query untuk mengambil semua fasilitas
    $sql = "SELECT id, name, icon, category FROM facilities ORDER BY category, name";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception('Database query failed');
    }

    $facilities = [];
    while ($row = $result->fetch_assoc()) {
        $facilities[] = $row;
    }

    // Return JSON response
    echo json_encode([
        'success' => true,
        'facilities' => $facilities
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>