<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != '') {
    header("Location: login_admin.php");
    exit();
}

$success_message = $error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']); // Enkripsi password
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $is_banned = isset($_POST['is_banned']) ? 1 : 0;

    // Cek apakah username sudah ada
    $check_query = "SELECT id FROM users WHERE username = '$username'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error_message = "Username sudah digunakan!";
    } else {
        $sql = "INSERT INTO users (username, password, role, is_banned) 
                VALUES ('$username', '$password', '$role', '$is_banned')";

        if (mysqli_query($conn, $sql)) {
            $_SESSION['success_message'] = "User berhasil ditambahkan!";
            header("Location: admin_dashboard.php#users");
            exit();
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Tambah User Baru</h5>
                    </div>
                    <div class="card-body">
                        <?php if($success_message): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="role" required>
                                    <option value="user">User</option>
                                    <option value="staff">Staff</option>
                                    <option value="">Admin</option>
                                </select>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="is_banned" id="is_banned">
                                <label class="form-check-label" for="is_banned">Banned</label>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="admin_dashboard.php#users" class="btn btn-secondary">Kembali</a>
                                <button type="submit" class="btn btn-primary">Tambah User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 