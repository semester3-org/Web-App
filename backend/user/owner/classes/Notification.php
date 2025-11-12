<?php
// backend/user/owner/classes/Notification.php

class Notification {
    private $conn;
    private $table = 'notifications';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get all notifications for owner
     */
    public function getOwnerNotifications($owner_id, $limit = null, $unread_only = false) {
        $query = "SELECT 
                    n.*,
                    k.name as kos_name,
                    k.city,
                    u.full_name as user_name,
                    u.profile_picture as user_picture
                FROM " . $this->table . " n
                LEFT JOIN kos k ON n.kos_id = k.id
                LEFT JOIN users u ON u.id = CASE 
                    WHEN n.type = 'new_review' THEN (SELECT user_id FROM reviews WHERE id = n.related_id)
                    WHEN n.type = 'new_wishlist' THEN (SELECT user_id FROM saved_kos WHERE id = n.related_id)
                    WHEN n.type = 'new_booking' THEN (SELECT user_id FROM bookings WHERE id = n.related_id)
                    ELSE NULL
                END
                WHERE n.user_id = ? AND n.is_archived = 0";
        
        if ($unread_only) {
            $query .= " AND n.is_read = 0";
        }
        
        $query .= " ORDER BY n.created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT " . intval($limit);
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    /**
     * Get unread notification count
     */
    public function getUnreadCount($owner_id) {
        $query = "SELECT COUNT(*) as count 
                FROM " . $this->table . " 
                WHERE user_id = ? AND is_read = 0 AND is_archived = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id, $owner_id) {
        $query = "UPDATE " . $this->table . " 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $notification_id, $owner_id);
        return $stmt->execute();
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead($owner_id) {
        $query = "UPDATE " . $this->table . " 
                SET is_read = 1 
                WHERE user_id = ? AND is_archived = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $owner_id);
        return $stmt->execute();
    }
    
    /**
     * Archive notification
     */
    public function archiveNotification($notification_id, $owner_id) {
        $query = "UPDATE " . $this->table . " 
                SET is_archived = 1 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $notification_id, $owner_id);
        return $stmt->execute();
    }
    
    /**
     * Delete notification
     */
    public function deleteNotification($notification_id, $owner_id) {
        $query = "DELETE FROM " . $this->table . " 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $notification_id, $owner_id);
        return $stmt->execute();
    }
    
    /**
     * Create notification for property approval
     */
    public function createPropertyApprovedNotification($kos_id, $owner_id) {
        $query = "INSERT INTO " . $this->table . " 
                (user_id, kos_id, type, title, message, created_at) 
                VALUES (?, ?, 'property_approved', 'Properti Disetujui', 
                'Selamat! Properti Anda telah disetujui dan sekarang dapat dilihat oleh pencari kos.', NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $owner_id, $kos_id);
        return $stmt->execute();
    }
    
    /**
     * Create notification for property rejection
     */
    public function createPropertyRejectedNotification($kos_id, $owner_id, $rejection_id, $reason) {
        $title = "Properti Ditolak";
        $message = "Properti Anda ditolak. Alasan: " . substr($reason, 0, 100) . (strlen($reason) > 100 ? '...' : '');
        
        $query = "INSERT INTO " . $this->table . " 
                (user_id, kos_id, type, title, message, related_id, created_at) 
                VALUES (?, ?, 'property_rejected', ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iissi", $owner_id, $kos_id, $title, $message, $rejection_id);
        return $stmt->execute();
    }
    
    /**
     * Create notification for new review
     */
    public function createNewReviewNotification($kos_id, $owner_id, $review_id, $rating) {
        $stars = str_repeat('⭐', $rating);
        $title = "Review Baru";
        $message = "Properti Anda mendapat review baru dengan rating " . $stars;
        
        $query = "INSERT INTO " . $this->table . " 
                (user_id, kos_id, type, title, message, related_id, created_at) 
                VALUES (?, ?, 'new_review', ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iissi", $owner_id, $kos_id, $title, $message, $review_id);
        return $stmt->execute();
    }
    
    /**
     * Create notification for new wishlist
     */
    public function createNewWishlistNotification($kos_id, $owner_id, $saved_id) {
        $title = "Ditambahkan ke Wishlist";
        $message = "Seseorang menambahkan properti Anda ke wishlist mereka.";
        
        $query = "INSERT INTO " . $this->table . " 
                (user_id, kos_id, type, title, message, related_id, created_at) 
                VALUES (?, ?, 'new_wishlist', ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iissi", $owner_id, $kos_id, $title, $message, $saved_id);
        return $stmt->execute();
    }
    
    /**
     * Create notification for new booking
     */
    public function createNewBookingNotification($kos_id, $owner_id, $booking_id) {
        $title = "Booking Baru";
        $message = "Anda memiliki booking baru untuk properti Anda. Silakan cek detail booking.";
        
        $query = "INSERT INTO " . $this->table . " 
                (user_id, kos_id, type, title, message, related_id, created_at) 
                VALUES (?, ?, 'new_booking', ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iissi", $owner_id, $kos_id, $title, $message, $booking_id);
        return $stmt->execute();
    }
    
    /**
     * Get notification details
     */
    public function getNotificationDetail($notification_id, $owner_id) {
        $query = "SELECT 
                    n.*,
                    k.name as kos_name,
                    k.address,
                    k.city,
                    pr.reason as rejection_reason,
                    r.rating,
                    r.comment as review_comment,
                    u.full_name as reviewer_name
                FROM " . $this->table . " n
                LEFT JOIN kos k ON n.kos_id = k.id
                LEFT JOIN property_rejections pr ON n.related_id = pr.id AND n.type = 'property_rejected'
                LEFT JOIN reviews r ON n.related_id = r.id AND n.type = 'new_review'
                LEFT JOIN users u ON r.user_id = u.id
                WHERE n.id = ? AND n.user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $notification_id, $owner_id);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    /**
     * Get notification statistics
     */
    public function getNotificationStats($owner_id) {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                    SUM(CASE WHEN type = 'property_approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN type = 'property_rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN type = 'new_review' THEN 1 ELSE 0 END) as reviews,
                    SUM(CASE WHEN type = 'new_wishlist' THEN 1 ELSE 0 END) as wishlists,
                    SUM(CASE WHEN type = 'new_booking' THEN 1 ELSE 0 END) as bookings
                FROM " . $this->table . "
                WHERE user_id = ? AND is_archived = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}