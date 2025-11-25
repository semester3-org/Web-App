<?php
// Path: backend/user/owner/classes/notification_handler.php

session_start();
require_once '../../../../config/database.php';
require_once 'Notification.php';

header('Content-Type: application/json');

// Cek apakah user sudah login dan merupakan owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$notification = new Notification($db);
$owner_id = $_SESSION['user_id'];

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_notifications':
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
        $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
        
        $notifications = $notification->getOwnerNotifications($owner_id, $limit, $unread_only);
        
        // Format data untuk frontend
        $formatted = array_map(function($notif) {
            return [
                'id' => $notif['id'],
                'type' => $notif['type'],
                'title' => $notif['title'],
                'message' => $notif['message'],
                'kos_name' => $notif['kos_name'],
                'city' => $notif['city'],
                'related_user_name' => $notif['related_user_name'],
                'related_user_picture' => $notif['related_user_picture'],
                'is_read' => (bool)$notif['is_read'],
                'created_at' => $notif['created_at'],
                'time_ago' => Notification::timeAgo($notif['created_at']),
                'icon' => Notification::getNotificationIcon($notif['type']),
                'badge' => Notification::getNotificationBadge($notif['type'])
            ];
        }, $notifications);
        
        echo json_encode([
            'success' => true,
            'data' => $formatted
        ]);
        break;
    
    case 'get_unread_count':
        $count = $notification->countUnreadNotifications($owner_id);
        echo json_encode([
            'success' => true,
            'count' => (int)$count
        ]);
        break;
    
    case 'mark_as_read':
        $notification_id = $_POST['notification_id'] ?? 0;
        
        if ($notification_id) {
            $result = $notification->markAsRead($notification_id, $owner_id);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Notifikasi ditandai sebagai dibaca' : 'Gagal menandai notifikasi'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID notifikasi tidak valid']);
        }
        break;
    
    case 'mark_all_as_read':
        $result = $notification->markAllAsRead($owner_id);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Semua notifikasi ditandai sebagai dibaca' : 'Gagal menandai notifikasi'
        ]);
        break;
    
    case 'archive':
        $notification_id = $_POST['notification_id'] ?? 0;
        
        if ($notification_id) {
            $result = $notification->archiveNotification($notification_id, $owner_id);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Notifikasi diarsipkan' : 'Gagal mengarsipkan notifikasi'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID notifikasi tidak valid']);
        }
        break;
    
    case 'delete':
        $notification_id = $_POST['notification_id'] ?? 0;
        
        if ($notification_id) {
            $result = $notification->deleteNotification($notification_id, $owner_id);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Notifikasi dihapus' : 'Gagal menghapus notifikasi'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID notifikasi tidak valid']);
        }
        break;
    
    case 'get_stats':
        $stats = $notification->getNotificationStats($owner_id);
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
        break;
    
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Action tidak valid'
        ]);
        break;
}
?>