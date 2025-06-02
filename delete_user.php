<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != '') {
    header("Location: login_admin.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Cek apakah user memiliki peminjaman aktif
    $check_query = "SELECT COUNT(*) as count FROM peminjaman WHERE id_user = '$id' AND status NOT IN ('dikembalikan', 'dibatalkan')";
    $check_result = mysqli_query($conn, $check_query);
    $check_data = mysqli_fetch_assoc($check_result);
    
    if ($check_data['count'] > 0) {
        $_SESSION['error_message'] = "User tidak dapat dihapus karena memiliki peminjaman aktif!";
    } else {
        // Hapus user jika tidak memiliki peminjaman aktif
        $sql = "DELETE FROM users WHERE id = '$id'";
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success_message'] = "User berhasil dihapus!";
        } else {
            $_SESSION['error_message'] = "Error: " . mysqli_error($conn);
        }
    }
}

header("Location: admin_dashboard.php#users");
exit(); 