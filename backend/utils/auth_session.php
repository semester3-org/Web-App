<?php
session_start();

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../frontend/pages/login.php');
        exit;
    }
}

function check_role($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header('HTTP/1.1 403 Forbidden');
        echo "Access denied.";
        exit;
    }
}