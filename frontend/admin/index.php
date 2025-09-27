<?php
session_start();

// Panggil middleware admin
require_once __DIR__ . "/auth/auth_admin.php";

// Jika lolos middleware, arahkan ke dashboard
header("Location: pages/dashboard.php");
exit();
