<?php
// backend/user/owner/api/notification_api.php

session_start();
require_once '../../../config/db.php';
require_once '../classes/Notification.php';

header('Content-Type: application/json');

// Check if user is logged in and is owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$notification = new Notification($conn);
$owner_id = $_SESSION['user_id'];

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_notifications':
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
            $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            
            $result = $notification->getOwnerNotifications($owner_id, $limit, $unread_only);
            $notifications = [];
            
            while ($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $notifications
            ]);
            break;
            
        case 'get_unread_count':
            $count = $notification->getUnreadCount($owner_id);
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
            break;
            
        case 'mark_as_read':
            $notification_id = $_POST['notification_id'] ?? 0;
            
            if ($notification->markAsRead($notification_id, $owner_id)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification marked as read'
                ]);
            } else {
                throw new Exception('Failed to mark notification as read');
            }
            break;
            
        case 'mark_all_read':
            if ($notification->markAllAsRead($owner_id)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'All notifications marked as read'
                ]);
            } else {
                throw new Exception('Failed to mark all notifications as read');
            }
            break;
            
        case 'archive':
            $notification_id = $_POST['notification_id'] ?? 0;
            
            if ($notification->archiveNotification($notification_id, $owner_id)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification archived'
                ]);
            } else {
                throw new Exception('Failed to archive notification');
            }
            break;
            
        case 'delete':
            $notification_id = $_POST['notification_id'] ?? 0;
            
            if ($notification->deleteNotification($notification_id, $owner_id)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification deleted'
                ]);
            } else {
                throw new Exception('Failed to delete notification');
            }
            break;
            
        case 'get_stats':
            $stats = $notification->getNotificationStats($owner_id);
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        case 'get_detail':
            $notification_id = $_GET['id'] ?? 0;
            $result = $notification->getNotificationDetail($notification_id, $owner_id);
            
            if ($row = $result->fetch_assoc()) {
                // Mark as read when viewing detail
                $notification->markAsRead($notification_id, $owner_id);
                
                echo json_encode([
                    'success' => true,
                    'data' => $row
                ]);
            } else {
                throw new Exception('Notification not found');
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}