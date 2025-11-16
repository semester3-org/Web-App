<?php
/**
 * ============================================
 * CUSTOMER NOTIFICATIONS HANDLER
 * File: backend/user/customer/classes/notifications.php
 * ============================================
 * Handle notifikasi untuk customer/user
 */

session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . "/Web-App/backend/config/db.php");

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_notifications':
            getNotifications($conn, $user_id);
            break;
        
        case 'get_count':
            getUnreadCount($conn, $user_id);
            break;
        
        case 'mark_read':
            markAsRead($conn, $user_id);
            break;
        
        case 'mark_all_read':
            markAllAsRead($conn, $user_id);
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

/**
 * Get notifications for user
 */
function getNotifications($conn, $user_id) {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($page - 1) * $limit;
    
    $status = $_GET['status'] ?? '';
    $type = $_GET['type'] ?? '';
    
    // Build WHERE clause
    $where = "n.user_id = ? AND n.is_archived = 0";
    $params = [$user_id];
    $types = "i";
    
    if ($status === 'read') {
        $where .= " AND n.is_read = 1";
    } elseif ($status === 'unread') {
        $where .= " AND n.is_read = 0";
    }
    
    if ($type) {
        $where .= " AND n.type = ?";
        $params[] = $type;
        $types .= "s";
    }
    
    // Get total count
    $sql_count = "SELECT COUNT(*) as total FROM notifications n WHERE $where";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $total = $stmt_count->get_result()->fetch_assoc()['total'];
    $stmt_count->close();
    
    // Get notifications
    $sql = "SELECT 
                n.id,
                n.type,
                n.title,
                n.message,
                n.kos_id,
                n.related_id,
                n.is_read,
                n.created_at,
                k.name as property_name,
                CASE 
                    WHEN TIMESTAMPDIFF(MINUTE, n.created_at, NOW()) < 1 THEN 'Baru saja'
                    WHEN TIMESTAMPDIFF(MINUTE, n.created_at, NOW()) < 60 THEN CONCAT(TIMESTAMPDIFF(MINUTE, n.created_at, NOW()), ' menit yang lalu')
                    WHEN TIMESTAMPDIFF(HOUR, n.created_at, NOW()) < 24 THEN CONCAT(TIMESTAMPDIFF(HOUR, n.created_at, NOW()), ' jam yang lalu')
                    WHEN TIMESTAMPDIFF(DAY, n.created_at, NOW()) < 7 THEN CONCAT(TIMESTAMPDIFF(DAY, n.created_at, NOW()), ' hari yang lalu')
                    ELSE DATE_FORMAT(n.created_at, '%d %b %Y')
                END as time_ago
            FROM notifications n
            LEFT JOIN kos k ON n.kos_id = k.id
            WHERE $where
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'total' => $total,
        'current_page' => $page,
        'total_pages' => ceil($total / $limit)
    ]);
}

/**
 * Get unread count
 */
function getUnreadCount($conn, $user_id) {
    $sql = "SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0 AND is_archived = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'count' => $row['count']
    ]);
}

/**
 * Mark single notification as read
 */
function markAsRead($conn, $user_id) {
    $notification_id = $_POST['notification_id'] ?? 0;
    
    if (!$notification_id) {
        echo json_encode(['success' => false, 'message' => 'Notification ID required']);
        return;
    }
    
    $sql = "UPDATE notifications 
            SET is_read = 1 
            WHERE id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notification_id, $user_id);
    
    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode(['success' => true]);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to update']);
    }
    
    $stmt->close();
}

/**
 * Mark all notifications as read
 */
function markAllAsRead($conn, $user_id) {
    $sql = "UPDATE notifications 
            SET is_read = 1 
            WHERE user_id = ? AND is_read = 0 AND is_archived = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to update']);
    }
    
    $stmt->close();
}

/**
 * Create notification (helper function for internal use)
 */
function createNotification($conn, $user_id, $type, $title, $message, $kos_id = null, $related_id = null) {
    $sql = "INSERT INTO notifications (user_id, type, title, message, kos_id, related_id, is_read, is_archived, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 0, 0, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssii", $user_id, $type, $title, $message, $kos_id, $related_id);
    
    if ($stmt->execute()) {
        $notification_id = $conn->insert_id;
        $stmt->close();
        return $notification_id;
    } else {
        $stmt->close();
        return false;
    }
}
?>