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
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $penulis = mysqli_real_escape_string($conn, $_POST['penulis']);
    $rekomendasi_usia = mysqli_real_escape_string($conn, $_POST['rekomendasi_usia']);
    $cover = mysqli_real_escape_string($conn, $_POST['cover']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $stok = mysqli_real_escape_string($conn, $_POST['stok']);

    $sql = "INSERT INTO buku (judul, penulis, rekomendasi_usia, cover, deskripsi, stok) 
            VALUES ('$judul', '$penulis', '$rekomendasi_usia', '$cover', '$deskripsi', '$stok')";

    if (mysqli_query($conn, $sql)) {
        $success_message = "Buku berhasil ditambahkan!";
        header("Location: admin_dashboard.php#books");
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
    <title>Tambah Buku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Tambah Buku Baru</h5>
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
                                <label class="form-label">Judul</label>
                                <input type="text" class="form-control" name="judul" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Penulis</label>
                                <input type="text" class="form-control" name="penulis" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Rekomendasi Usia</label>
                                <input type="number" class="form-control" name="rekomendasi_usia">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">URL Cover</label>
                                <input type="text" class="form-control" name="cover">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Deskripsi</label>
                                <textarea class="form-control" name="deskripsi" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Stok</label>
                                <input type="number" class="form-control" name="stok" required>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="admin_dashboard.php#books" class="btn btn-secondary">Kembali</a>
                                <button type="submit" class="btn btn-primary">Tambah Buku</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 