<?php
/**
 * Notification System Backend
 * Path: backend/user/notifications.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

class NotificationSystem
{
    private $conn;
    private $user_id;

    public function __construct($db_connection, $user_id)
    {
        $this->conn = $db_connection;
        $this->user_id = $user_id;
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount()
    {
        try {
            $query = "SELECT COUNT(*) as count FROM notifications 
                     WHERE user_id = ? AND is_read = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            return $row['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all notifications
     */
    public function getNotifications($limit = 10)
    {
        try {
            $query = "SELECT n.*, k.name as property_name 
                     FROM notifications n
                     LEFT JOIN kos k ON n.kos_id = k.id
                     WHERE n.user_id = ? 
                     ORDER BY n.created_at DESC 
                     LIMIT ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $this->user_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $notifications = [];
            while ($row = $result->fetch_assoc()) {
                $notifications[] = [
                    'id' => $row['id'],
                    'type' => $row['type'],
                    'title' => $row['title'],
                    'message' => $row['message'],
                    'kos_id' => $row['kos_id'],
                    'property_name' => $row['property_name'],
                    'is_read' => $row['is_read'],
                    'created_at' => $row['created_at'],
                    'time_ago' => $this->timeAgo($row['created_at'])
                ];
            }
            
            return $notifications;
        } catch (Exception $e) {
            error_log("Error getting notifications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id)
    {
        try {
            $query = "UPDATE notifications SET is_read = 1 
                     WHERE id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $notification_id, $this->user_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error marking as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        try {
            $query = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $this->user_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error marking all as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create notification
     */
    public static function createNotification($conn, $user_id, $type, $title, $message, $kos_id = null)
    {
        try {
            $query = "INSERT INTO notifications (user_id, type, title, message, kos_id, created_at) 
                     VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isssi", $user_id, $type, $title, $message, $kos_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper: Time ago format
     */
    private function timeAgo($datetime)
    {
        $timestamp = strtotime($datetime);
        $difference = time() - $timestamp;
        
        if ($difference < 60) {
            return 'Baru saja';
        } elseif ($difference < 3600) {
            $minutes = floor($difference / 60);
            return $minutes . ' menit lalu';
        } elseif ($difference < 86400) {
            $hours = floor($difference / 3600);
            return $hours . ' jam lalu';
        } elseif ($difference < 604800) {
            $days = floor($difference / 86400);
            return $days . ' hari lalu';
        } else {
            return date('d M Y', $timestamp);
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $notifSystem = new NotificationSystem($conn, $_SESSION['user_id']);
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_count':
            $count = $notifSystem->getUnreadCount();
            echo json_encode(['success' => true, 'count' => $count]);
            break;
            
        case 'get_notifications':
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $notifications = $notifSystem->getNotifications($limit);
            echo json_encode(['success' => true, 'notifications' => $notifications]);
            break;
            
        case 'mark_read':
            $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
            $result = $notifSystem->markAsRead($notification_id);
            echo json_encode(['success' => $result]);
            break;
            
        case 'mark_all_read':
            $result = $notifSystem->markAllAsRead();
            echo json_encode(['success' => $result]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
    exit();
}