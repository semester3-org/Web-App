<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../../config/db.php';
require_once '../classes/Notification.php';

$notif    = new Notification($conn);
$owner_id = $_SESSION['user_id'];
$action   = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_unread_count':
            $count = $notif->getUnreadCount($owner_id);
            echo json_encode(['success' => true, 'count' => (int)$count]);
            break;

        case 'get_notifications':
            $limit = max(1, (int)($_GET['limit'] ?? 10));
            $result = $notif->getOwnerNotifications($owner_id, $limit);
            $list = [];
            while ($row = $result->fetch_assoc()) $list[] = $row;
            echo json_encode(['success' => true, 'data' => $list]);
            break;

        case 'mark_as_read':
            $id = (int)($_POST['notification_id'] ?? $_GET['id'] ?? 0);
            if (!$id) throw new Exception('ID diperlukan');
            $notif->markAsRead($id, $owner_id);
            echo json_encode(['success' => true]);
            break;

        case 'mark_all_read':
            $affected = $notif->markAllAsRead($owner_id);
            echo json_encode(['success' => true, 'affected' => $affected]);
            break;

        case 'get_detail':
            $id = (int)$_GET['id'];
            $detail = $notif->getNotificationDetail($id, $owner_id);
            if ($detail->num_rows === 0) throw new Exception('Notifikasi tidak ditemukan');
            $data = $detail->fetch_assoc();
            $notif->markAsRead($id, $owner_id);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'get_stats':
            $stats = $notif->getNotificationStats($owner_id);
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'delete':
            $id = (int)$_POST['notification_id'];
            $notif->deleteNotification($id, $owner_id);
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception('Aksi tidak dikenali');
    }

    // PAKSA COMMIT SEMUA PERUBAHAN (karena autocommit = false)
    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>