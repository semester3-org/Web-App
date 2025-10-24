<?php
// backend/admin/classes/AdminManager.php
require_once __DIR__ . '/../../config/db.php';

class AdminManager {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    // Get all admins (superadmin and admin only)
    public function getAllAdmins() {
        $query = "SELECT id, username, email, full_name, phone, profile_picture, user_type, created_at, updated_at 
                  FROM users 
                  WHERE user_type IN ('superadmin', 'admin') 
                  ORDER BY created_at DESC";
        
        $result = $this->conn->query($query);
        
        if ($result) {
            $admins = [];
            while ($row = $result->fetch_assoc()) {
                $admins[] = $row;
            }
            return $admins;
        }
        
        return [];
    }
    
    // Get admin by ID
    public function getAdminById($id) {
        $query = "SELECT id, username, email, full_name, phone, profile_picture, user_type, created_at, updated_at 
                  FROM users 
                  WHERE id = ? AND user_type IN ('superadmin', 'admin')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    // Check if username exists
    public function usernameExists($username, $excludeId = null) {
        if ($excludeId) {
            $query = "SELECT id FROM users WHERE username = ? AND id != ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('si', $username, $excludeId);
        } else {
            $query = "SELECT id FROM users WHERE username = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('s', $username);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
    
    // Check if email exists
    public function emailExists($email, $excludeId = null) {
        if ($excludeId) {
            $query = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('si', $email, $excludeId);
        } else {
            $query = "SELECT id FROM users WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('s', $email);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
    
    // Create new admin
    public function createAdmin($data) {
        // Validate username
        if ($this->usernameExists($data['username'])) {
            return ['success' => false, 'message' => 'Username sudah digunakan'];
        }
        
        // Validate email
        if ($this->emailExists($data['email'])) {
            return ['success' => false, 'message' => 'Email sudah digunakan'];
        }
        
        $query = "INSERT INTO users (username, email, password, full_name, phone, user_type) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt->bind_param('ssssss', 
            $data['username'],
            $data['email'],
            $hashedPassword,
            $data['full_name'],
            $data['phone'],
            $data['user_type']
        );
        
        if ($stmt->execute()) {
            $this->conn->commit();
            return ['success' => true, 'message' => 'Admin berhasil ditambahkan'];
        }
        
        $this->conn->rollback();
        return ['success' => false, 'message' => 'Gagal menambahkan admin'];
    }
    
    // Update admin
    public function updateAdmin($id, $data) {
        // Validate username
        if ($this->usernameExists($data['username'], $id)) {
            return ['success' => false, 'message' => 'Username sudah digunakan'];
        }
        
        // Validate email
        if ($this->emailExists($data['email'], $id)) {
            return ['success' => false, 'message' => 'Email sudah digunakan'];
        }
        
        // Update password only if provided
        if (!empty($data['password'])) {
            $query = "UPDATE users SET 
                      username = ?, 
                      email = ?, 
                      full_name = ?, 
                      phone = ?, 
                      user_type = ?,
                      password = ?
                      WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt->bind_param('ssssssi', 
                $data['username'],
                $data['email'],
                $data['full_name'],
                $data['phone'],
                $data['user_type'],
                $hashedPassword,
                $id
            );
        } else {
            $query = "UPDATE users SET 
                      username = ?, 
                      email = ?, 
                      full_name = ?, 
                      phone = ?, 
                      user_type = ?
                      WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bind_param('sssssi', 
                $data['username'],
                $data['email'],
                $data['full_name'],
                $data['phone'],
                $data['user_type'],
                $id
            );
        }
        
        if ($stmt->execute()) {
            $this->conn->commit();
            return ['success' => true, 'message' => 'Admin berhasil diupdate'];
        }
        
        $this->conn->rollback();
        return ['success' => false, 'message' => 'Gagal mengupdate admin'];
    }
    
    // Delete admin
    public function deleteAdmin($id) {
        // Prevent deleting superadmin
        $admin = $this->getAdminById($id);
        if ($admin && $admin['user_type'] === 'superadmin') {
            return ['success' => false, 'message' => 'Tidak dapat menghapus superadmin'];
        }
        
        $query = "DELETE FROM users WHERE id = ? AND user_type = 'admin'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $this->conn->commit();
            return ['success' => true, 'message' => 'Admin berhasil dihapus'];
        }
        
        $this->conn->rollback();
        return ['success' => false, 'message' => 'Gagal menghapus admin'];
    }
    
    // Get admin statistics
    public function getAdminStats() {
        $query = "SELECT 
                  COUNT(*) as total_admins,
                  SUM(CASE WHEN user_type = 'superadmin' THEN 1 ELSE 0 END) as total_superadmin,
                  SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as total_admin
                  FROM users 
                  WHERE user_type IN ('superadmin', 'admin')";
        
        $result = $this->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return [
            'total_admins' => 0,
            'total_superadmin' => 0,
            'total_admin' => 0
        ];
    }
}