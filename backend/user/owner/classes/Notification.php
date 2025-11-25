<?php
class Notification {
    private $conn;
    private $table = 'notifications';

    public function __construct($db) {
        $this->conn = $db;
    }

    /* =============== CREATE NOTIFICATIONS =============== */
    public function createNewWishlistNotification($kos_id, $owner_id, $saved_id = null) {
        $kos_name = $this->getKosName($kos_id);
        $title    = "Kos Anda Disukai!";
        $message  = "Kos \"{$kos_name}\" ditambahkan ke wishlist pengguna.";

        $stmt = $this->conn->prepare("INSERT INTO {$this->table} 
            (user_id, kos_id, type, title, message, related_id, is_read, is_archived, created_at) 
            VALUES (?, ?, 'new_wishlist', ?, ?, ?, 0, 0, NOW())");
        $stmt->bind_param("iissi", $owner_id, $kos_id, $title, $message, $saved_id);
        $stmt->execute();
        $stmt->close();
        $this->conn->commit(); // PAKSA COMMIT
    }

    public function createNewBookingNotification($kos_id, $owner_id, $booking_id, $custom_title = null, $custom_message = null) {
        $title   = $custom_title   ?: "Booking Baru";
        $message = $custom_message ?: "Ada booking baru untuk kos Anda.";

        $stmt = $this->conn->prepare("INSERT INTO {$this->table} 
            (user_id, kos_id, type, title, message, related_id, is_read, is_archived, created_at) 
            VALUES (?, ?, 'new_booking', ?, ?, ?, 0, 0, NOW())");
        $stmt->bind_param("iissi", $owner_id, $kos_id, $title, $message, $booking_id);
        $stmt->execute();
        $stmt->close();
        $this->conn->commit();
    }

    public function createNewReviewNotification($kos_id, $owner_id, $review_id, $rating, $comment = '') {
        $stars   = str_repeat('⭐', $rating);
        $short   = strlen($comment) > 80 ? substr($comment, 0, 80).'...' : $comment;
        $title   = "Review Baru";
        $message = $short ? "\"{$short}\" {$stars}" : "Rating {$stars}";

        $stmt = $this->conn->prepare("INSERT INTO {$this->table} 
            (user_id, kos_id, type, title, message, related_id, is_read, is_archived, created_at) 
            VALUES (?, ?, 'new_review', ?, ?, ?, 0, 0, NOW())");
        $stmt->bind_param("iissi", $owner_id, $kos_id, $title, $message, $review_id);
        $stmt->execute();
        $stmt->close();
        $this->conn->commit();
    }

    public function createPaymentSuccessNotification($kos_id, $owner_id, $booking_id, $amount) {
        $amount_fmt = number_format($amount, 0, ',', '.');
        $this->createNewBookingNotification($kos_id, $owner_id, $booking_id, "Pembayaran Berhasil!", "Pembayaran Rp {$amount_fmt} diterima.");
    }

    public function createPropertyApprovedNotification($kos_id, $owner_id) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} 
            (user_id, kos_id, type, title, message, is_read, is_archived, created_at) 
            VALUES (?, ?, 'property_approved', 'Properti Disetujui', 'Selamat! Kos Anda telah disetujui.', 0, 0, NOW())");
        $stmt->bind_param("ii", $owner_id, $kos_id);
        $stmt->execute();
        $stmt->close();
        $this->conn->commit();
    }

    public function createPropertyRejectedNotification($kos_id, $owner_id, $rejection_id, $reason) {
        $short = substr($reason, 0, 200);
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} 
            (user_id, kos_id, type, title, message, related_id, is_read, is_archived, created_at) 
            VALUES (?, ?, 'property_rejected', 'Properti Ditolak', ?, ?, 0, 0, NOW())");
        $stmt->bind_param("iisi", $owner_id, $kos_id, $short, $rejection_id);
        $stmt->execute();
        $stmt->close();
        $this->conn->commit();
    }

    /* =============== READ / FETCH =============== */
    public function getOwnerNotifications($owner_id, $limit = null, $unread_only = false) {
        $sql = "SELECT n.*, k.name as kos_name, k.city 
                FROM {$this->table} n 
                LEFT JOIN kos k ON n.kos_id = k.id 
                WHERE n.user_id = ? AND n.is_archived = 0";
        if ($unread_only) $sql .= " AND n.is_read = 0";
        $sql .= " ORDER BY n.created_at DESC";
        if ($limit) $sql .= " LIMIT " . (int)$limit;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getUnreadCount($owner_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ? AND is_read = 0 AND is_archived = 0");
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)$row['count'];
    }

    public function getNotificationDetail($notification_id, $owner_id) {
        $query = "SELECT n.*, k.name as kos_name, k.address, k.city, r.rating, r.comment as review_comment, u.full_name as reviewer_name
                  FROM {$this->table} n
                  LEFT JOIN kos k ON n.kos_id = k.id
                  LEFT JOIN reviews r ON n.related_id = r.id AND n.type = 'new_review'
                  LEFT JOIN users u ON r.user_id = u.id
                  WHERE n.id = ? AND n.user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $notification_id, $owner_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getNotificationStats($owner_id) {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_read = 0 AND is_archived = 0 THEN 1 ELSE 0 END) as unread,
                    SUM(CASE WHEN type = 'property_approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN type = 'property_rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN type = 'new_review' THEN 1 ELSE 0 END) as reviews,
                    SUM(CASE WHEN type = 'new_wishlist' THEN 1 ELSE 0 END) as wishlists,
                    SUM(CASE WHEN type = 'new_booking' THEN 1 ELSE 0 END) as bookings
                  FROM {$this->table} WHERE user_id = ? AND is_archived = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return array_map('intval', $result);
    }

    /* =============== UPDATE / DELETE (DENGAN COMMIT) =============== */
    public function markAsRead($notification_id, $owner_id) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $owner_id);
        $stmt->execute();
        $stmt->close();
        $this->conn->commit();
    }

    public function markAllAsRead($owner_id) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET is_read = 1 WHERE user_id = ? AND is_read = 0 AND is_archived = 0");
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        $this->conn->commit(); // INI YANG MENYELAMATKAN SEMUA!
        return $affected;
    }

    public function archiveNotification($notification_id, $owner_id) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET is_archived = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $owner_id);
        $stmt->execute();
        $stmt->close();
        $this->conn->commit();
    }

    public function deleteNotification($notification_id, $owner_id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $owner_id);
        $stmt->execute();
        $stmt->close();
        $this->conn->commit();
    }

    private function getKosName($kos_id) {
        $stmt = $this->conn->prepare("SELECT name FROM kos WHERE id = ?");
        $stmt->bind_param("i", $kos_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $name = $res->num_rows ? $res->fetch_assoc()['name'] : 'Kos Anda';
        $stmt->close();
        return $name;
    }
}
?>