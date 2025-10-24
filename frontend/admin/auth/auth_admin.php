<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['user_type'], ['admin', 'superadmin'])
) {
    // Jika request datang dari AJAX / API
    if (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
        header('Content-Type: application/json');
        echo json_encode([
            "success" => false,
            "message" => "Unauthorized access"
        ]);
        exit;
    }

    // Jika request datang dari browser (bukan AJAX)
    header("Location: auth/login.php?type=admin&error=Hanya admin atau superadmin yang boleh mengakses");
    exit;
}
