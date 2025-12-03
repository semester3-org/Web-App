<?php
header("Content-Type: application/json");

include "../../config/db.php";

try {
    $sql = "SELECT id, name, icon FROM facilities ORDER BY name ASC";
    $res = $conn->query($sql);

    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = [
            "id"   => (int)$row["id"],
            "name" => $row["name"],
            "icon" => $row["icon"], // bisa null
        ];
    }

    echo json_encode([
        "status" => "success",
        "data"   => $items
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}
