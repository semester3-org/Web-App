<?php
// backend/admin/classes/TransactionManager.php
require_once __DIR__ . '/../../config/db.php';

class TransactionManager
{
    private $conn;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    // Get all transactions with property and owner details
    public function getAllTransactions()
    {
        $query = "SELECT 
                    pp.id,
                    pp.order_id,
                    pp.transaction_id,
                    pp.price_monthly,
                    pp.tax_percentage,
                    pp.tax_amount,
                    pp.total_amount,
                    pp.payment_type,
                    pp.payment_method,
                    pp.payment_status,
                    pp.paid_at,
                    pp.expired_at,
                    pp.created_at,
                    pp.updated_at,
                    k.name as property_name,
                    k.address as property_address,
                    k.city,
                    u.full_name as owner_name,
                    u.email as owner_email,
                    u.phone as owner_phone
                  FROM property_payments pp
                  INNER JOIN kos k ON pp.kos_id = k.id
                  INNER JOIN users u ON pp.owner_id = u.id
                  ORDER BY pp.created_at DESC";

        $result = $this->conn->query($query);

        if ($result) {
            $transactions = [];
            while ($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }
            return $transactions;
        }

        return [];
    }

    // Get transaction by ID
    public function getTransactionById($id)
    {
        $query = "SELECT 
                    pp.*,
                    k.name as property_name,
                    k.address as property_address,
                    k.city,
                    k.province,
                    k.kos_type,
                    u.full_name as owner_name,
                    u.email as owner_email,
                    u.phone as owner_phone
                  FROM property_payments pp
                  INNER JOIN kos k ON pp.kos_id = k.id
                  INNER JOIN users u ON pp.owner_id = u.id
                  WHERE pp.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    // Get transactions by status
    public function getTransactionsByStatus($status)
    {
        $query = "SELECT 
                    pp.id,
                    pp.order_id,
                    pp.transaction_id,
                    pp.price_monthly,
                    pp.tax_percentage,
                    pp.tax_amount,
                    pp.total_amount,
                    pp.payment_type,
                    pp.payment_method,
                    pp.payment_status,
                    pp.paid_at,
                    pp.created_at,
                    k.name as property_name,
                    u.full_name as owner_name
                  FROM property_payments pp
                  INNER JOIN kos k ON pp.kos_id = k.id
                  INNER JOIN users u ON pp.owner_id = u.id
                  WHERE pp.payment_status = ?
                  ORDER BY pp.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $transactions = [];
            while ($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }
            return $transactions;
        }

        return [];
    }

    // Get transaction statistics
    public function getTransactionStats()
    {
        $query = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(CASE WHEN payment_status = 'settlement' THEN 1 ELSE 0 END) as total_success,
                    SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as total_pending,
                    SUM(CASE WHEN payment_status IN ('deny', 'cancel', 'expire') THEN 1 ELSE 0 END) as total_failed,
                    SUM(CASE WHEN payment_status = 'settlement' THEN total_amount ELSE 0 END) as total_revenue
                  FROM property_payments";

        $result = $this->conn->query($query);

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return [
            'total_transactions' => 0,
            'total_success' => 0,
            'total_pending' => 0,
            'total_failed' => 0,
            'total_revenue' => 0
        ];
    }

    // Search transactions
    public function searchTransactions($keyword)
    {
        $query = "SELECT 
                    pp.id,
                    pp.order_id,
                    pp.transaction_id,
                    pp.price_monthly,
                    pp.tax_amount,
                    pp.total_amount,
                    pp.payment_status,
                    pp.created_at,
                    k.name as property_name,
                    u.full_name as owner_name
                  FROM property_payments pp
                  INNER JOIN kos k ON pp.kos_id = k.id
                  INNER JOIN users u ON pp.owner_id = u.id
                  WHERE pp.order_id LIKE ? 
                     OR pp.transaction_id LIKE ?
                     OR k.name LIKE ?
                     OR u.full_name LIKE ?
                  ORDER BY pp.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $searchTerm = "%$keyword%";
        $stmt->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $transactions = [];
            while ($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }
            return $transactions;
        }

        return [];
    }

    // Get all properties that have been paid (payment_status NOT NULL)
    public function getAllProperties()
    {
        $query = "SELECT 
                    k.id,
                    k.name,
                    k.address,
                    k.city,
                    k.price_monthly,
                    k.owner_id,
                    k.status,
                    k.payment_status,
                    k.created_at,
                    u.full_name as owner_name,
                    u.email as owner_email,
                    u.phone as owner_phone,
                    COALESCE(pp.tax_percentage, 10) as current_tax_percentage,
                    pp.total_amount,
                    pp.paid_at,
                    pp.created_at as payment_created_at
                  FROM kos k
                  INNER JOIN users u ON k.owner_id = u.id
                  LEFT JOIN property_payments pp ON k.id = pp.kos_id 
                  WHERE k.status IS NOT NULL 
                    AND k.status IN ('pending', 'approved', 'rejected')
                    AND k.payment_status != 'unpaid'
                  ORDER BY k.created_at DESC";

        $result = $this->conn->query($query);

        if ($result) {
            $properties = [];
            while ($row = $result->fetch_assoc()) {
                $properties[] = $row;
            }
            return $properties;
        }

        return [];
    }

    // Get property detail by ID
    public function getPropertyDetail($id)
    {
        $query = "SELECT 
                    k.*,
                    u.full_name as owner_name,
                    u.email as owner_email,
                    u.phone as owner_phone,
                    pp.order_id,
                    pp.transaction_id,
                    pp.tax_percentage,
                    pp.tax_amount,
                    pp.total_amount,
                    pp.payment_type,
                    pp.payment_method,
                    pp.payment_status as payment_transaction_status,
                    pp.paid_at,
                    pp.created_at as payment_date
                  FROM kos k
                  INNER JOIN users u ON k.owner_id = u.id
                  LEFT JOIN property_payments pp ON k.id = pp.kos_id
                  WHERE k.id = ?
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    // Get property statistics for tax page
    public function getPropertyTaxStats()
    {
        $query = "SELECT 
                COUNT(DISTINCT k.id) AS total_properties_paid,
                SUM(pp.tax_amount) AS total_tax_profit
              FROM kos k
              LEFT JOIN property_payments pp 
                ON k.id = pp.kos_id 
                AND pp.payment_status = 'settlement'
              WHERE k.status IS NOT NULL 
                AND k.status IN ('pending', 'approved', 'rejected')
                AND k.payment_status != 'unpaid'";

        $result = $this->conn->query($query);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return [
                'total_properties_paid' => $row['total_properties_paid'] ?? 0,
                'total_tax_profit' => $row['total_tax_profit'] ?? 0
            ];
        }

        return [
            'total_properties_paid' => 0,
            'total_tax_profit' => 0
        ];
    }
}
