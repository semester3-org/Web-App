<?php

/**
 * Property Approval Process Backend
 * Path: backend/admin/classes/approved_process.php
 */

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Adjust path based on where the file is included from
if (!isset($conn)) {
    require_once __DIR__ . '/../../config/db.php';
}

class ApprovalProcess
{
    private $conn;
    private $admin_id;

    public function __construct($db_connection)
    {
        $this->conn = $db_connection;

        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit();
        }

        $this->admin_id = $_SESSION['user_id'];
    }

    /**
     * Get properties by status
     */
   public function getPropertiesByStatus($status = 'pending', $page = 1, $limit = 5, $filters = [])
{
    try {
        $offset = ($page - 1) * $limit;

        $query = "SELECT 
                    k.id,
                    k.name,
                    k.description,
                    k.address,
                    k.city,
                    k.province,
                    k.postal_code,
                    k.latitude,
                    k.longitude,
                    k.kos_type,
                    k.total_rooms,
                    k.available_rooms,
                    k.price_monthly,
                    k.price_daily,
                    k.rules,
                    k.status,
                    k.created_at,
                    k.updated_at,
                    k.verified_at,
                    u.full_name as owner_name,
                    u.email as owner_email,
                    u.phone as owner_phone,
                    admin.full_name as verified_by_name,
                    pr.reason as rejection_reason,
                    pr.created_at as rejected_at,
                    reject_admin.full_name as rejected_by
                FROM kos k
                INNER JOIN users u ON k.owner_id = u.id
                LEFT JOIN users admin ON k.verified_by = admin.id
                LEFT JOIN property_rejections pr ON k.id = pr.kos_id
                LEFT JOIN users reject_admin ON pr.admin_id = reject_admin.id";

        $where = [];
        $params = [];
        $types = "";

        // Filter by status
        if ($status !== 'all') {
            $where[] = "k.status = ?";
            $params[] = $status;
            $types .= "s";
        }

        // Advanced Filters
        if (!empty($filters['city'])) {
            $where[] = "k.city = ?";
            $params[] = $filters['city'];
            $types .= "s";
        }

        if (!empty($filters['kos_type'])) {
            $where[] = "k.kos_type = ?";
            $params[] = $filters['kos_type'];
            $types .= "s";
        }

        if (!empty($filters['price_min'])) {
            $where[] = "k.price_monthly >= ?";
            $params[] = (int)$filters['price_min'];
            $types .= "i";
        }

        if (!empty($filters['price_max'])) {
            $where[] = "k.price_monthly <= ?";
            $params[] = (int)$filters['price_max'];
            $types .= "i";
        }

        if (count($where) > 0) {
            $query .= " WHERE " . implode(" AND ", $where);
        }

        $query .= " ORDER BY k.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $properties = [];
        while ($row = $result->fetch_assoc()) {
            $row['images'] = $this->getPropertyImages($row['id']);
            $row['facilities'] = $this->getPropertyFacilities($row['id']);
            $properties[] = $row;
        }

        return $properties;
    } catch (Exception $e) {
        error_log("Error getting filtered properties: " . $e->getMessage());
        return [];
    }
}

public function getTotalPropertiesByStatus($status = 'pending', $filters = [])
{
    try {
        $query = "SELECT COUNT(*) as total FROM kos k";
        $where = [];
        $params = [];
        $types = "";

        if ($status !== 'all') {
            $where[] = "k.status = ?";
            $params[] = $status;
            $types .= "s";
        }

        // Advanced Filters
        if (!empty($filters['city'])) {
            $where[] = "k.city = ?";
            $params[] = $filters['city'];
            $types .= "s";
        }

        if (!empty($filters['kos_type'])) {
            $where[] = "k.kos_type = ?";
            $params[] = $filters['kos_type'];
            $types .= "s";
        }

        if (!empty($filters['price_min'])) {
            $where[] = "k.price_monthly >= ?";
            $params[] = (int)$filters['price_min'];
            $types .= "i";
        }

        if (!empty($filters['price_max'])) {
            $where[] = "k.price_monthly <= ?";
            $params[] = (int)$filters['price_max'];
            $types .= "i";
        }

        if (count($where) > 0) {
            $query .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row['total'] ?? 0;
    } catch (Exception $e) {
        error_log("Error getting total filtered properties: " . $e->getMessage());
        return 0;
    }
}


public function getDistinctCities()
{
    try {
        $sql = "SELECT DISTINCT city FROM kos WHERE city IS NOT NULL AND city <> '' ORDER BY city ASC";
        $result = $this->conn->query($sql);

        $cities = [];
        while ($row = $result->fetch_assoc()) {
            $cities[] = $row['city'];
        }

        return $cities;
    } catch (Exception $e) {
        error_log("Error getting distinct cities: " . $e->getMessage());
        return [];
    }
}


    /**
     * Get property images
     */
    private function getPropertyImages($kos_id)
    {
        try {
            $query = "SELECT image_url FROM kos_images WHERE kos_id = ? ORDER BY id ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $kos_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $images = [];
            while ($row = $result->fetch_assoc()) {
                $images[] = $row['image_url'];
            }

            return $images;
        } catch (Exception $e) {
            error_log("Error getting images: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get property facilities
     */
    private function getPropertyFacilities($kos_id)
    {
        try {
            $query = "SELECT f.name, f.icon
                  FROM kos_facilities kf
                  INNER JOIN facilities f ON kf.facility_id = f.id
                  WHERE kf.kos_id = ?
                  ORDER BY f.name ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $kos_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $facilities = [];
            while ($row = $result->fetch_assoc()) {
                $facilities[] = [
                    'name' => $row['name'],
                    'icon' => $row['icon'] ?? 'fa-check' // fallback jika NULL
                ];
            }

            return $facilities;
        } catch (Exception $e) {
            error_log("Error getting facilities: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Get approval statistics
     */
    public function getApprovalStats()
    {
        try {
            $stats = [
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'total' => 0
            ];

            $query = "SELECT status, COUNT(*) as count FROM kos GROUP BY status";
            $result = $this->conn->query($query);

            while ($row = $result->fetch_assoc()) {
                $stats[$row['status']] = $row['count'];
                $stats['total'] += $row['count'];
            }

            return $stats;
        } catch (Exception $e) {
            error_log("Error getting stats: " . $e->getMessage());
            return [
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'total' => 0
            ];
        }
    }

    /**
     * Approve property
     */
    public function approveProperty($property_id)
    {
        try {
            $this->conn->begin_transaction();

            // Check if property exists and is pending
            $check_query = "SELECT id, status, owner_id FROM kos WHERE id = ? AND status = 'pending'";
            $stmt = $this->conn->prepare($check_query);
            $stmt->bind_param("i", $property_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Property tidak ditemukan atau sudah diproses");
            }

            $property = $result->fetch_assoc();

            // Update property status
            $update_query = "UPDATE kos 
                           SET status = 'approved', 
                               verified_by = ?, 
                               verified_at = NOW() 
                           WHERE id = ?";

            $stmt = $this->conn->prepare($update_query);
            $stmt->bind_param("ii", $this->admin_id, $property_id);

            if (!$stmt->execute()) {
                throw new Exception("Gagal mengupdate status property");
            }

            // TODO: Send notification to owner (implement your notification system)
            // $this->sendApprovalNotification($property['owner_id'], $property_id);

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Property berhasil disetujui'
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error approving property: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Reject property
     */
    public function rejectProperty($property_id, $reason)
    {
        try {
            $this->conn->begin_transaction();

            // Check if property exists and is pending
            $check_query = "SELECT id, status, owner_id FROM kos WHERE id = ? AND status = 'pending'";
            $stmt = $this->conn->prepare($check_query);
            $stmt->bind_param("i", $property_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Property tidak ditemukan atau sudah diproses");
            }

            $property = $result->fetch_assoc();

            // Update property status
            $update_query = "UPDATE kos SET status = 'rejected' WHERE id = ?";
            $stmt = $this->conn->prepare($update_query);
            $stmt->bind_param("i", $property_id);

            if (!$stmt->execute()) {
                throw new Exception("Gagal mengupdate status property");
            }

            // Insert rejection reason
            $insert_query = "INSERT INTO property_rejections (kos_id, admin_id, reason, created_at) 
                           VALUES (?, ?, ?, NOW())";

            $stmt = $this->conn->prepare($insert_query);
            $stmt->bind_param("iis", $property_id, $this->admin_id, $reason);

            if (!$stmt->execute()) {
                throw new Exception("Gagal menyimpan alasan penolakan");
            }

            // TODO: Send notification to owner (implement your notification system)
            // $this->sendRejectionNotification($property['owner_id'], $property_id, $reason);

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Property berhasil ditolak'
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error rejecting property: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get property detail
     */
    public function getPropertyDetail($property_id)
    {
        try {
            $query = "SELECT 
                        k.*,
                        u.full_name as owner_name,
                        u.email as owner_email,
                        u.phone as owner_phone,
                        admin.full_name as verified_by_name,
                        pr.reason as rejection_reason,
                        pr.created_at as rejected_at,
                        reject_admin.full_name as rejected_by
                    FROM kos k
                    INNER JOIN users u ON k.owner_id = u.id
                    LEFT JOIN users admin ON k.verified_by = admin.id
                    LEFT JOIN property_rejections pr ON k.id = pr.kos_id
                    LEFT JOIN users reject_admin ON pr.admin_id = reject_admin.id
                    WHERE k.id = ?";

            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $property_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Property tidak ditemukan");
            }

            $property = $result->fetch_assoc();

            // Get images
            $property['images'] = $this->getPropertyImages($property_id);

            // Get facilities
            $property['facilities'] = $this->getPropertyFacilities($property_id);

            return [
                'success' => true,
                'property' => $property
            ];
        } catch (Exception $e) {
            error_log("Error getting property detail: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}

// ==============================================
// AJAX Handler - Only execute when called directly via AJAX
// ==============================================

// Check if this file is being called directly (not included)
$is_ajax_request = (
    ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET')
    &&
    (
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    )
    ||
    (
        strpos($_SERVER['REQUEST_URI'], 'approved_process.php') !== false
    )
);

// Only handle AJAX if this file is called directly
if ($is_ajax_request && basename($_SERVER['PHP_SELF']) === 'approved_process.php') {

    // Handle AJAX requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');

        // Get JSON input
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // If JSON decode fails, try $_POST
        if ($data === null) {
            $data = $_POST;
        }

        // Debug: Log received data
        error_log("Received data: " . print_r($data, true));

        $approvalProcess = new ApprovalProcess($conn);

        $action = isset($data['action']) ? $data['action'] : '';

        error_log("Action received: " . $action);

        if (empty($action)) {
            echo json_encode([
                'success' => false,
                'message' => 'Action tidak ditemukan',
                'debug' => [
                    'data' => $data,
                    'post' => $_POST,
                    'method' => $_SERVER['REQUEST_METHOD'],
                    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set'
                ]
            ]);
            exit();
        }

        switch ($action) {
            case 'approve':
                $property_id = isset($data['property_id']) ? intval($data['property_id']) : 0;

                if ($property_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Property ID tidak valid']);
                    exit();
                }

                $result = $approvalProcess->approveProperty($property_id);
                echo json_encode($result);
                break;

            case 'reject':
                $property_id = isset($data['property_id']) ? intval($data['property_id']) : 0;
                $reason = isset($data['reason']) ? trim($data['reason']) : '';

                if ($property_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Property ID tidak valid']);
                    exit();
                }

                if (empty($reason)) {
                    echo json_encode(['success' => false, 'message' => 'Alasan penolakan harus diisi']);
                    exit();
                }

                $result = $approvalProcess->rejectProperty($property_id, $reason);
                echo json_encode($result);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
                break;
        }

        exit();
    }

    // End of AJAX handler
} // End of is_ajax_request check

// Handle GET requests (for detail)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');

    $approvalProcess = new ApprovalProcess($conn);

    $action = isset($_GET['action']) ? $_GET['action'] : '';

    switch ($action) {
        case 'detail':
            $property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;

            if ($property_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Property ID tidak valid']);
                exit();
            }

            $result = $approvalProcess->getPropertyDetail($property_id);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
            break;
    }

    exit();
}
