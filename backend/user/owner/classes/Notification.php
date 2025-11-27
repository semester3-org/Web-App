<?php
class Notification {
    private $conn;
    private $table = 'notifications';

    public function __construct($db) {
        $this->conn = $db;
    }

    /* =============== CREATE NOTIFICATIONS =============== */
    
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

    // ❌ METHOD LAMA - Deprecated, diganti dengan summary
    public function createNewReviewNotification($kos_id, $owner_id, $review_id, $rating, $comment = '') {
        // Deprecated - gunakan updateOrCreateReviewSummaryNotification()
    }

    public function createPaymentSuccessNotification($kos_id, $owner_id, $booking_id, $amount) {
        $amount_fmt = number_format($amount, 0, ',', '.');
        $this->createNewBookingNotification($kos_id, $owner_id, $booking_id, "Pembayaran Berhasil!", "Pembayaran Rp {$amount_fmt} diterima.");
    }

    /**
     * 🆕 Notifikasi ketika properti disetujui oleh admin
     * Dipanggil dari approved_process.php setelah approve berhasil
     */
    public function createPropertyApprovedNotification($kos_id, $owner_id) {
        $kos_name = $this->getKosName($kos_id);
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} 
            (user_id, kos_id, type, title, message, is_read, is_archived, created_at) 
            VALUES (?, ?, 'property_approved', 'Properti Disetujui', ?, 0, 0, NOW())");
        
        $message = "Selamat! Properti \"{$kos_name}\" telah disetujui dan sekarang dapat dilihat oleh pengguna.";
        $stmt->bind_param("iis", $owner_id, $kos_id, $message);
        $stmt->execute();
        $stmt->close();
        $this->conn->commit();
    }

    /**
     * 🆕 Notifikasi ketika properti ditolak oleh admin
     * Dipanggil dari approved_process.php setelah reject berhasil
     */
    public function createPropertyRejectedNotification($kos_id, $owner_id, $rejection_id, $reason) {
        $kos_name = $this->getKosName($kos_id);
        $short = substr($reason, 0, 200);
        
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} 
            (user_id, kos_id, type, title, message, related_id, is_read, is_archived, created_at) 
            VALUES (?, ?, 'property_rejected', 'Properti Ditolak', ?, ?, 0, 0, NOW())");
        
        $message = "Properti \"{$kos_name}\" ditolak. Alasan: {$short}";
        $stmt->bind_param("iisi", $owner_id, $kos_id, $message, $rejection_id);
        $stmt->execute();
        $stmt->close();
        $this->conn->commit();
    }

    /* =============== WISHLIST SUMMARY — 1 notifikasi per kos =============== */
    public function updateOrCreateWishlistSummaryNotification($kos_id, $owner_id) {
        // Hitung total yang menyimpan (kecuali owner sendiri)
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS total 
            FROM saved_kos 
            WHERE kos_id = ? AND user_id != ?
        ");
        $stmt->bind_param("ii", $kos_id, $owner_id);
        $stmt->execute();
        $total = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $kos_name = $this->getKosName($kos_id);
        $title    = "Kos Anda Disimpan";
        $message  = $total === 1 
            ? "Kos \"{$kos_name}\" disimpan oleh 1 orang" 
            : "Kos \"{$kos_name}\" disimpan oleh {$total} orang";

        // Cek apakah sudah ada summary
        $check = $this->conn->prepare("
            SELECT id FROM {$this->table} 
            WHERE user_id = ? AND kos_id = ? AND type = 'wishlist_summary'
        ");
        $check->bind_param("ii", $owner_id, $kos_id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $notif_id = $result->fetch_assoc()['id'];
            $check->close();

            if ($total === 0) {
                // Hapus notifikasi kalau sudah tidak ada yang wishlist
                $del = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
                $del->bind_param("i", $notif_id);
                $del->execute();
                $del->close();
            } else {
                // Update jumlah & timestamp, reset is_read agar muncul sebagai notifikasi baru
                $upd = $this->conn->prepare("
                    UPDATE {$this->table} 
                    SET message = ?, wishlist_count = ?, is_read = 0, created_at = NOW()
                    WHERE id = ?
                ");
                $upd->bind_param("sii", $message, $total, $notif_id);
                $upd->execute();
                $upd->close();
            }
        } else {
            $check->close();
            
            // Insert baru kalau ada yang wishlist
            if ($total > 0) {
                $ins = $this->conn->prepare("
                    INSERT INTO {$this->table} 
                    (user_id, kos_id, type, title, message, wishlist_count, is_read, is_archived, created_at)
                    VALUES (?, ?, 'wishlist_summary', ?, ?, ?, 0, 0, NOW())
                ");
                $ins->bind_param("iissi", $owner_id, $kos_id, $title, $message, $total);
                $ins->execute();
                $ins->close();
            }
        }

        $this->conn->commit();
    }

    /* =============== REVIEW SUMMARY — 1 notifikasi per kos =============== */
    public function updateOrCreateReviewSummaryNotification($kos_id, $owner_id) {
        // Hitung total review untuk kos ini
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS total, AVG(rating) AS avg_rating
            FROM reviews 
            WHERE kos_id = ?
        ");
        $stmt->bind_param("i", $kos_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $total = (int)$result['total'];
        $avg_rating = round((float)$result['avg_rating'], 1);
        $stmt->close();

        $kos_name = $this->getKosName($kos_id);
        $title    = "Review Baru";
        
        // Format pesan berdasarkan jumlah review
        if ($total === 0) {
            $message = "Belum ada review untuk kos \"{$kos_name}\"";
        } elseif ($total === 1) {
            $stars = str_repeat('⭐', round($avg_rating));
            $message = "Kos \"{$kos_name}\" mendapat 1 review {$stars}";
        } else {
            $stars = str_repeat('⭐', round($avg_rating));
            $message = "Kos \"{$kos_name}\" mendapat {$total} review dengan rating rata-rata {$avg_rating} {$stars}";
        }

        // Cek apakah sudah ada summary
        $check = $this->conn->prepare("
            SELECT id FROM {$this->table} 
            WHERE user_id = ? AND kos_id = ? AND type = 'review_summary'
        ");
        $check->bind_param("ii", $owner_id, $kos_id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $notif_id = $result->fetch_assoc()['id'];
            $check->close();

            if ($total === 0) {
                // Hapus notifikasi kalau sudah tidak ada review
                $del = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
                $del->bind_param("i", $notif_id);
                $del->execute();
                $del->close();
            } else {
                // Update jumlah & timestamp, reset is_read agar muncul sebagai notifikasi baru
                $upd = $this->conn->prepare("
                    UPDATE {$this->table} 
                    SET message = ?, review_count = ?, is_read = 0, created_at = NOW()
                    WHERE id = ?
                ");
                $upd->bind_param("sii", $message, $total, $notif_id);
                $upd->execute();
                $upd->close();
            }
        } else {
            $check->close();
            
            // Insert baru kalau ada review
            if ($total > 0) {
                $ins = $this->conn->prepare("
                    INSERT INTO {$this->table} 
                    (user_id, kos_id, type, title, message, review_count, is_read, is_archived, created_at)
                    VALUES (?, ?, 'review_summary', ?, ?, ?, 0, 0, NOW())
                ");
                $ins->bind_param("iissi", $owner_id, $kos_id, $title, $message, $total);
                $ins->execute();
                $ins->close();
            }
        }

        $this->conn->commit();
    }

    /* =============== READ / FETCH =============== */
    public function getOwnerNotifications($owner_id, $limit = null, $unread_only = false) {
        // Skip notifikasi individual lama (new_wishlist & new_review), hanya tampilkan summary
        $sql = "SELECT n.*, k.name as kos_name, k.city 
                FROM {$this->table} n 
                LEFT JOIN kos k ON n.kos_id = k.id 
                WHERE n.user_id = ? 
                AND n.is_archived = 0
                AND n.type NOT IN ('new_wishlist', 'new_review')";
        
        if ($unread_only) $sql .= " AND n.is_read = 0";
        $sql .= " ORDER BY n.created_at DESC";
        if ($limit) $sql .= " LIMIT " . (int)$limit;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getUnreadCount($owner_id) {
        // Hitung hanya notifikasi yang ditampilkan (skip new_wishlist & new_review)
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM {$this->table} 
            WHERE user_id = ? 
            AND is_read = 0 
            AND is_archived = 0
            AND type NOT IN ('new_wishlist', 'new_review')
        ");
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)$row['count'];
    }

    public function getNotificationDetail($notification_id, $owner_id) {
        $query = "SELECT n.*, k.name as kos_name, k.address, k.city
                  FROM {$this->table} n
                  LEFT JOIN kos k ON n.kos_id = k.id
                  WHERE n.id = ? AND n.user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $notification_id, $owner_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getNotificationStats($owner_id) {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_read = 0 AND is_archived = 0 AND type NOT IN ('new_wishlist', 'new_review') THEN 1 ELSE 0 END) as unread,
                    SUM(CASE WHEN type = 'property_approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN type = 'property_rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN type = 'review_summary' THEN 1 ELSE 0 END) as reviews,
                    SUM(CASE WHEN type = 'wishlist_summary' THEN 1 ELSE 0 END) as wishlists,
                    SUM(CASE WHEN type = 'new_booking' THEN 1 ELSE 0 END) as bookings
                  FROM {$this->table} 
                  WHERE user_id = ? 
                  AND is_archived = 0
                  AND type NOT IN ('new_wishlist', 'new_review')";
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
        $stmt = $this->conn->prepare("
            UPDATE {$this->table} 
            SET is_read = 1 
            WHERE user_id = ? 
            AND is_read = 0 
            AND is_archived = 0
            AND type NOT IN ('new_wishlist', 'new_review')
        ");
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        $this->conn->commit();
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