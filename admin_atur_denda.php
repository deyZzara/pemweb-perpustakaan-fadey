<?php
session_start();
require_once 'config.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== '') {
    header("Location: login_admin.php");
    exit();
}

$success_message = $error_message = '';

// Proses update denda
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_denda'])) {
    $denda_per_hari = mysqli_real_escape_string($conn, $_POST['denda_per_hari']);
    
    // Update pengaturan denda
    $sql = "UPDATE pengaturan_denda SET denda_per_hari = '$denda_per_hari'";
    
    if (mysqli_query($conn, $sql)) {
        $success_message = "Pengaturan denda berhasil diperbarui!";
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}

// Ambil pengaturan denda saat ini
$sql = "SELECT * FROM pengaturan_denda LIMIT 1";
$result = mysqli_query($conn, $sql);
$pengaturan = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Denda - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .container {
            max-width: 800px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #2c3e50;
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Pengaturan Denda</h4>
            </div>
            <div class="card-body">
                <?php if($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Denda per Hari (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" name="denda_per_hari" 
                                   value="<?php echo $pengaturan['denda_per_hari']; ?>" 
                                   min="0" step="500" required>
                        </div>
                        <div class="form-text">Masukkan jumlah denda per hari dalam Rupiah</div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="admin_dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" name="update_denda" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Riwayat Denda -->
        <div class="card mt-4">
            <div class="card-header">
                <h4 class="mb-0">Riwayat Denda</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID Peminjaman</th>
                                <th>Peminjam</th>
                                <th>Buku</th>
                                <th>Hari Telat</th>
                                <th>Jumlah Denda</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT d.*, p.id_user, p.id_buku, u.username, b.judul 
                                   FROM denda d 
                                   JOIN peminjaman p ON d.id_peminjaman = p.id
                                   JOIN users u ON p.id_user = u.id
                                   JOIN buku b ON p.id_buku = b.id
                                   ORDER BY d.id DESC";
                            $result = mysqli_query($conn, $sql);
                            while($row = mysqli_fetch_assoc($result)):
                            ?>
                            <tr>
                                <td><?php echo $row['id_peminjaman']; ?></td>
                                <td><?php echo $row['username']; ?></td>
                                <td><?php echo $row['judul']; ?></td>
                                <td><?php echo $row['jumlah_hari_telat']; ?> hari</td>
                                <td>Rp <?php echo number_format($row['jumlah_denda'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge <?php echo $row['status_pembayaran'] == 'sudah_dibayar' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $row['status_pembayaran'] == 'sudah_dibayar' ? 'Lunas' : 'Belum Dibayar'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 