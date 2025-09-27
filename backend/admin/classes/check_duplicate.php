<?php
session_start();
require_once '../../config/db.php';

header("Content-Type: application/json");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    $field = $input['field'] ?? '';
    $value = trim($input['value'] ?? '');
    
    // Validate field name
    if (!in_array($field, ['username', 'email'])) {
        echo json_encode(["success" => false, "message" => "Invalid field"]);
        exit;
    }
    
    // Check if value already exists (excluding current user)
    $stmt = $conn->prepare("SELECT id FROM users WHERE $field = ? AND id != ?");
    $stmt->bind_param("si", $value, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $exists = $result->fetch_assoc() !== null;
    
    echo json_encode(["exists" => $exists]);
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
}