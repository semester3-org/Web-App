<?php
session_start();
require_once("../../config/db.php"); // koneksi database

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        header("Location: ../../../frontend/auth/login.php?error=Username dan password wajib diisi");
        exit;
    }

    // Query cek user berdasarkan username
    $stmt = $conn->prepare("SELECT id, username, password, full_name, user_type 
                            FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verifikasi password
        if (password_verify($password, $user['password'])) {
            // Simpan ke session
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['full_name']  = $user['full_name'];
            $_SESSION['user_type']  = $user['user_type'];

            // Tutup statement dan connection
            $stmt->close();
            $conn->close();

            // Redirect sesuai role
            switch ($user['user_type']) {
                case 'superadmin':
                case 'admin':
                    header("Location: ../../../frontend/admin/pages/dashboard.php");
                    break;
                case 'owner':
                    header("Location: ../../../frontend/user/owner/pages/dashboard.php");
                    break;
                case 'user':
                case 'customer':
                default:
                    header("Location: ../../../frontend/user/customer/home.php");
                    break;
            }
            exit;
        } else {
            // Password salah
            $stmt->close();
            $conn->close();
            header("Location: ../../../frontend/auth/login.php?error=Password salah");
            exit;
        }
    } else {
        // Username tidak ditemukan
        $stmt->close();
        $conn->close();
        header("Location: ../../../frontend/auth/login.php?error=Username tidak ditemukan");
        exit;
    }
} else {
    // Bukan POST request
    header("Location: ../../../frontend/auth/login.php");
    exit;
}