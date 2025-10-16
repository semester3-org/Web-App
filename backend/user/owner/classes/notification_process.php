<?php
require_once __DIR__ . '/../../../../config/config.php';

class NotificationProcess {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    /**
     * Buat notifikasi baru
     */
    public function createNotification($user_id, $kos_id, $type, $title, $message, $related_id = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (user_id, kos_id, type, title, message, related_id) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisssi", $user_id, $kos_id, $type, $title, $message, $related_id);
        return $stmt->execute();
    }
    
    /**
     * Ambil semua notifikasi owner
     */
    public function getOwnerNotifications($owner_id, $show_archived = false) {
        $archived_condition = $show_archived ? "" : "AND n.is_archived = 0";
        
        $stmt = $this->conn->prepare("
            SELECT 
                n.*,
                k.name as kos_name,
                k.city as kos_city,
                CASE 
                    WHEN n.type = 'property_rejected' THEN pr.reason
                    ELSE NULL
                END as rejection_reason
            FROM notifications n
            LEFT JOIN kos k ON n.kos_id = k.id
            LEFT JOIN property_rejections pr ON n.related_id = pr.id AND n.type = 'property_rejected'
            WHERE n.user_id = ? $archived_condition
            ORDER BY n.created_at DESC
        ");
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Hitung notifikasi yang belum dibaca
     */
    public function getUnreadCount($owner_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as unread_count 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0 AND is_archived = 0
        ");
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['unread_count'];
    }
    
    /**
     * Tandai notifikasi sebagai dibaca
     */
    public function markAsRead($notification_id, $owner_id) {
        $stmt = $this->conn->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $notification_id, $owner_id);
        return $stmt->execute();
    }
    
    /**
     * Tandai semua notifikasi sebagai dibaca
     */
    public function markAllAsRead($owner_id) {
        $stmt = $this->conn->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE user_id = ? AND is_archived = 0
        ");
        $stmt->bind_param("i", $owner_id);
        return $stmt->execute();
    }
    
    /**
     * Arsipkan notifikasi
     */
    public function archiveNotification($notification_id, $owner_id) {
        $stmt = $this->conn->prepare("
            UPDATE notifications 
            SET is_archived = 1, is_read = 1 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $notification_id, $owner_id);
        return $stmt->execute();
    }
    
    /**
     * Hapus notifikasi (permanent)
     */
    public function deleteNotification($notification_id, $owner_id) {
        $stmt = $this->conn->prepare("
            DELETE FROM notifications 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $notification_id, $owner_id);
        return $stmt->execute();
    }
    
    /**
     * Ambil detail notifikasi
     */
    public function getNotificationById($notification_id, $owner_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                n.*,
                k.name as kos_name,
                k.city as kos_city,
                k.address as kos_address,
                CASE 
                    WHEN n.type = 'property_rejected' THEN pr.reason
                    WHEN n.type = 'new_booking' THEN b.check_in_date
                    WHEN n.type = 'new_review' THEN r.rating
                    ELSE NULL
                END as extra_data
            FROM notifications n
            LEFT JOIN kos k ON n.kos_id = k.id
            LEFT JOIN property_rejections pr ON n.related_id = pr.id AND n.type = 'property_rejected'
            LEFT JOIN bookings b ON n.related_id = b.id AND n.type = 'new_booking'
            LEFT JOIN reviews r ON n.related_id = r.id AND n.type = 'new_review'
            WHERE n.id = ? AND n.user_id = ?
        ");
        $stmt->bind_param("ii", $notification_id, $owner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}

// Handler untuk AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    session_start();
    
    // Validasi owner login
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $notification = new NotificationProcess();
    $owner_id = $_SESSION['user_id'];
    $action = $_POST['action'];
    
    switch ($action) {
        case 'get_unread_count':
            $count = $notification->getUnreadCount($owner_id);
            echo json_encode(['success' => true, 'count' => $count]);
            break;
            
        case 'mark_as_read':
            $notification_id = intval($_POST['notification_id']);
            $result = $notification->markAsRead($notification_id, $owner_id);
            echo json_encode(['success' => $result]);
            break;
            
        case 'mark_all_as_read':
            $result = $notification->markAllAsRead($owner_id);
            echo json_encode(['success' => $result]);
            break;
            
        case 'archive':
            $notification_id = intval($_POST['notification_id']);
            $result = $notification->archiveNotification($notification_id, $owner_id);
            echo json_encode(['success' => $result]);
            break;
            
        case 'delete':
            $notification_id = intval($_POST['notification_id']);
            $result = $notification->deleteNotification($notification_id, $owner_id);
            echo json_encode(['success' => $result]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}
?>