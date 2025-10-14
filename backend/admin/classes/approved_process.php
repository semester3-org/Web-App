<?php
/**
 * ============================================
 * PROPERTY APPROVAL PROCESS
 * File: backend/admin/classes/approved_process.php
 * ============================================
 * Backend untuk approve/reject property
 */

session_start();
require_once '../../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: ../../../frontend/admin/pages/login.php');
    exit();
}

$admin_id = $_SESSION['user_id'];

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method';
    header('Location: ../../../frontend/admin/pages/approved.php');
    exit();
}

// Get action and property_id
$action = $_POST['action'] ?? '';
$property_id = intval($_POST['property_id'] ?? 0);

// Validate inputs
if (!in_array($action, ['approve', 'reject'])) {
    $_SESSION['error'] = 'Invalid action';
    header('Location: ../../../frontend/admin/pages/approved.php');
    exit();
}

if ($property_id <= 0) {
    $_SESSION['error'] = 'Invalid property ID';
    header('Location: ../../../frontend/admin/pages/approved.php');
    exit();
}

try {
    $conn->begin_transaction();
    
    /**
     * ============================================
     * APPROVE PROPERTY
     * ============================================
     */
    if ($action === 'approve') {
        // Check if property exists and is pending
        $check_sql = "SELECT id, name, owner_id, status FROM kos WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('i', $property_id);
        $check_stmt->execute();
        $property = $check_stmt->get_result()->fetch_assoc();
        
        if (!$property) {
            throw new Exception('Property tidak ditemukan');
        }
        
        if ($property['status'] !== 'pending') {
            throw new Exception('Property sudah diproses sebelumnya');
        }
        
        // Update property status to approved
        $update_sql = "UPDATE kos 
                      SET status = 'approved', 
                          verified_by = ?, 
                          verified_at = NOW() 
                      WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('ii', $admin_id, $property_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception('Gagal menyetujui property');
        }
        
        // TODO: Send notification to owner (email/in-app)
        // sendApprovalNotification($property['owner_id'], $property['name']);
        
        $conn->commit();
        $_SESSION['success'] = 'Property "' . $property['name'] . '" berhasil disetujui!';
        
    }
    
    /**
     * ============================================
     * REJECT PROPERTY
     * ============================================
     */
    else if ($action === 'reject') {
        $rejection_reason = trim($_POST['rejection_reason'] ?? '');
        
        if (empty($rejection_reason)) {
            throw new Exception('Alasan penolakan wajib diisi');
        }
        
        // Check if property exists and is pending
        $check_sql = "SELECT id, name, owner_id, status FROM kos WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('i', $property_id);
        $check_stmt->execute();
        $property = $check_stmt->get_result()->fetch_assoc();
        
        if (!$property) {
            throw new Exception('Property tidak ditemukan');
        }
        
        if ($property['status'] !== 'pending') {
            throw new Exception('Property sudah diproses sebelumnya');
        }
        
        // Update property status to rejected
        $update_sql = "UPDATE kos 
                      SET status = 'rejected', 
                          verified_by = ?, 
                          verified_at = NOW() 
                      WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('ii', $admin_id, $property_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception('Gagal menolak property');
        }
        
        // Insert rejection reason into property_rejections table
        $rejection_sql = "INSERT INTO property_rejections (kos_id, admin_id, reason, created_at) 
                         VALUES (?, ?, ?, NOW())";
        $rejection_stmt = $conn->prepare($rejection_sql);
        $rejection_stmt->bind_param('iis', $property_id, $admin_id, $rejection_reason);
        
        if (!$rejection_stmt->execute()) {
            throw new Exception('Gagal menyimpan alasan penolakan');
        }
        
        // TODO: Send notification to owner with reason
        // sendRejectionNotification($property['owner_id'], $property['name'], $rejection_reason);
        
        $conn->commit();
        $_SESSION['success'] = 'Property "' . $property['name'] . '" telah ditolak.';
    }
    
    // Redirect back to approval page
    header('Location: ../../../frontend/admin/pages/approved.php?status=pending');
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../../../frontend/admin/pages/approved.php');
    exit();
}

$conn->close();
?>