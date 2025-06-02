<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != '') {
    header("Location: login_admin.php");
    exit();
}

$success_message = $error_message = '';
$user = null;

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id = '$id'");
    $user = mysqli_fetch_assoc($result);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $is_banned = isset($_POST['is_banned']) ? 1 : 0;
    
    // Jika password diisi, update password juga
    $password_query = "";
    if (!empty($_POST['password'])) {
        $password = md5($_POST['password']);
        $password_query = ", password = '$password'";
    }

    $sql = "UPDATE users SET 
            username = '$username',
            role = '$role',
            is_banned = '$is_banned'
            $password_query
            WHERE id = '$id'";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['success_message'] = "User berhasil diperbarui!";
        header("Location: admin_dashboard.php#users");
        exit();
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Edit User</h5>
                    </div>
                    <div class="card-body">
                        <?php if($success_message): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <?php if($user): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" value="<?php echo $user['username']; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Password Baru (kosongkan jika tidak ingin mengubah)</label>
                                    <input type="password" class="form-control" name="password">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <select class="form-select" name="role" required>
                                        <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="staff" <?php echo $user['role'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                        <option value="" <?php echo $user['role'] == '' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" name="is_banned" id="is_banned" <?php echo $user['is_banned'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_banned">Banned</label>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="admin_dashboard.php#users" class="btn btn-secondary">Kembali</a>
                                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-danger">User tidak ditemukan.</div>
                            <a href="admin_dashboard.php#users" class="btn btn-secondary">Kembali</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 