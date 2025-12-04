<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
  echo json_encode([
    'success' => false,
    'message' => 'Unauthorized access'
  ]);
  exit;
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/backend/config/db.php");

$user_id = $_SESSION['user_id'];
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;

if ($booking_id <= 0) {
  echo json_encode([
    'success' => false,
    'message' => 'Invalid booking ID'
  ]);
  exit;
}

try {
  // Verify booking belongs to user
  $sql = "SELECT id FROM bookings WHERE id = ? AND user_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $booking_id, $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows === 0) {
    echo json_encode([
      'success' => false,
      'message' => 'Booking not found or access denied'
    ]);
    exit;
  }
  $stmt->close();

  // Get payment logs
  $sql = "SELECT 
            id,
            booking_id,
            order_id,
            transaction_status,
            payment_type,
            gross_amount,
            payment_status,
            notification_data,
            created_at
          FROM payment_logs 
          WHERE booking_id = ? 
          ORDER BY created_at DESC";
  
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $booking_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $logs = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  // Format the data
  foreach ($logs as &$log) {
    // Parse notification_data if it's JSON
    if ($log['notification_data']) {
      $notification = json_decode($log['notification_data'], true);
      $log['notification_parsed'] = $notification;
    } else {
      $log['notification_parsed'] = null;
    }

    // Format timestamps
    $log['created_at_formatted'] = date('d M Y, H:i:s', strtotime($log['created_at']));
    
    // Format amount
    if ($log['gross_amount']) {
      $log['gross_amount_formatted'] = 'Rp ' . number_format($log['gross_amount'], 0, ',', '.');
    } else {
      $log['gross_amount_formatted'] = '-';
    }
  }

  echo json_encode([
    'success' => true,
    'data' => [
      'logs' => $logs,
      'total_logs' => count($logs)
    ]
  ]);

} catch (Exception $e) {
  echo json_encode([
    'success' => false,
    'message' => 'Error: ' . $e->getMessage()
  ]);
}
?>