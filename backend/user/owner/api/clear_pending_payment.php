
<?php
/**
 * ============================================
 * CLEAR PENDING PAYMENT SESSION
 * File: backend/user/owner/api/clear_pending_payment.php
 * ============================================
 */

session_start();
header('Content-Type: application/json');

if (isset($_SESSION['pending_payment'])) {
    unset($_SESSION['pending_payment']);
}

echo json_encode(['success' => true]);
?>