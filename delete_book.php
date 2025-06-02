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
    
    // Cek apakah buku sedang dipinjam
    $check_query = "SELECT COUNT(*) as count FROM peminjaman WHERE id_buku = '$id' AND status NOT IN ('dikembalikan', 'dibatalkan')";
    $check_result = mysqli_query($conn, $check_query);
    $check_data = mysqli_fetch_assoc($check_result);
    
    if ($check_data['count'] > 0) {
        $_SESSION['error_message'] = "Buku tidak dapat dihapus karena sedang dipinjam!";
    } else {
        // Hapus buku jika tidak sedang dipinjam
        $sql = "DELETE FROM buku WHERE id = '$id'";
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success_message'] = "Buku berhasil dihapus!";
        } else {
            $_SESSION['error_message'] = "Error: " . mysqli_error($conn);
        }
    }
}

header("Location: admin_dashboard.php#books");
exit(); 