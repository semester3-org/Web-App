<?php
// backend/admin/classes/UserManager.php
require_once __DIR__ . '/../../config/db.php';

class UserManager {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    // Get all users (user and owner only)
    public function getAllUsers() {
        $query = "SELECT u.id, u.username, u.email, u.full_name, u.phone, u.profile_picture, 
                  u.user_type, u.created_at, u.updated_at,
                  COUNT(k.id) as total_properties
                  FROM users u
                  LEFT JOIN kos k ON u.id = k.owner_id
                  WHERE u.user_type IN ('user', 'owner')
                  GROUP BY u.id
                  ORDER BY u.created_at DESC";
        
        $result = $this->conn->query($query);
        
        if ($result) {
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            return $users;
        }
        
        return [];
    }
    
    // Get user by ID with property details
    public function getUserById($id) {
        $query = "SELECT u.id, u.username, u.email, u.full_name, u.phone, u.profile_picture, 
                  u.user_type, u.created_at, u.updated_at,
                  COUNT(k.id) as total_properties
                  FROM users u
                  LEFT JOIN kos k ON u.id = k.owner_id
                  WHERE u.id = ? AND u.user_type IN ('user', 'owner')
                  GROUP BY u.id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    // Get properties owned by a user
    public function getUserProperties($userId) {
        $query = "SELECT id, name, address, status, created_at 
                  FROM kos 
                  WHERE owner_id = ? 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            $properties = [];
            while ($row = $result->fetch_assoc()) {
                $properties[] = $row;
            }
            return $properties;
        }
        
        return [];
    }
    
    // Update user status
    public function updateUserStatus($id, $status) {
        $query = "UPDATE users SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('si', $status, $id);
        
        if ($stmt->execute()) {
            $this->conn->commit();
            return ['success' => true, 'message' => 'Status user berhasil diupdate'];
        }
        
        $this->conn->rollback();
        return ['success' => false, 'message' => 'Gagal mengupdate status user'];
    }
    
    // Delete user
    public function deleteUser($id) {
        // Check if user has properties
        $properties = $this->getUserProperties($id);
        if (count($properties) > 0) {
            return ['success' => false, 'message' => 'Tidak dapat menghapus user yang memiliki properti'];
        }
        
        $query = "DELETE FROM users WHERE id = ? AND user_type IN ('user', 'owner')";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $this->conn->commit();
            return ['success' => true, 'message' => 'User berhasil dihapus'];
        }
        
        $this->conn->rollback();
        return ['success' => false, 'message' => 'Gagal menghapus user'];
    }
    
    // Get user statistics
    public function getUserStats() {
        $query = "SELECT 
                  COUNT(*) as total_users,
                  SUM(CASE WHEN user_type = 'user' THEN 1 ELSE 0 END) as total_regular_users,
                  SUM(CASE WHEN user_type = 'owner' THEN 1 ELSE 0 END) as total_owners
                  FROM users 
                  WHERE user_type IN ('user', 'owner')";
        
        $result = $this->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return [
            'total_users' => 0,
            'total_regular_users' => 0,
            'total_owners' => 0
        ];
    }
    
    // Search users
    public function searchUsers($keyword) {
        $query = "SELECT u.id, u.username, u.email, u.full_name, u.phone, u.profile_picture, 
                  u.user_type, u.created_at, u.updated_at,
                  COUNT(k.id) as total_properties
                  FROM users u
                  LEFT JOIN kos k ON u.id = k.owner_id
                  WHERE u.user_type IN ('user', 'owner')
                  AND (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)
                  GROUP BY u.id
                  ORDER BY u.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%$keyword%";
        $stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            return $users;
        }
        
        return [];
    }
}