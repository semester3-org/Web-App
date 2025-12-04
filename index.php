<?php
session_start();
require_once(__DIR__ . "/backend/config/db.php");

// Kalau sudah login → redirect sesuai role
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    switch ($_SESSION['user_type']) {
        case 'superadmin':
        case 'admin':
            header("Location: /frontend/admin/pages/dashboard.php");
            exit;

        case 'owner':
            header("Location: /frontend/user/owner/pages/dashboard.php");
            exit;

        case 'user':
        case 'customer':
            header("Location: /frontend/user/customer/home.php");
            exit;
    }
}

// Guest / belum login → langsung ke home customer
header("Location: /frontend/user/customer/home.php");
exit;
?>