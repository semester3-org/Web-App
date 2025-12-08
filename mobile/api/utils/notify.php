<?php
function pushNotification($conn, $user_id, $type, $title, $message, $kos_id = null, $related_id = null) {

    // cek preferensi notif user
    $q = $conn->prepare("SELECT notification_enabled FROM users WHERE id = ? LIMIT 1");
    $q->bind_param("i", $user_id);
    $q->execute();
    $res = $q->get_result();
    $row = $res->fetch_assoc();

    if (!$row || intval($row["notification_enabled"]) !== 1) {
        return false; // notif OFF, skip insert
    }

    // Insert notif
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, kos_id, type, title, message, related_id, is_read, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
    ");

    // i i s s s i
    $stmt->bind_param("iisssi", $user_id, $kos_id, $type, $title, $message, $related_id);

    return $stmt->execute();
}
