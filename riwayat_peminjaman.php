<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login_admin.php");
    exit();
}

// Proses pengembalian buku
if(isset($_POST['kembalikan']) && isset($_POST['peminjaman_id'])) {
    $peminjaman_id = mysqli_real_escape_string($conn, $_POST['peminjaman_id']);
    $id_buku = mysqli_real_escape_string($conn, $_POST['id_buku']);
    
    // Update status peminjaman
    $sql_update = "UPDATE peminjaman SET status = 'dikembalikan' WHERE id = '$peminjaman_id' AND id_user = '{$_SESSION['user_id']}'";
    if(mysqli_query($conn, $sql_update)) {
        // Update stok buku
        $sql_stok = "UPDATE buku SET stok = stok + 1 WHERE id = '$id_buku'";
        mysqli_query($conn, $sql_stok);
        $success_message = "Buku berhasil dikembalikan!";
    } else {
        $error_message = "Gagal mengembalikan buku: " . mysqli_error($conn);
    }
}

// Ambil riwayat peminjaman user
$id = $_SESSION['user_id'];
$sql = "SELECT p.*, b.judul, b.penulis 
        FROM peminjaman p 
        JOIN buku b ON p.id_buku = b.id 
        WHERE p.id_user = '$id' 
        ORDER BY p.tanggal_pinjam DESC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .navbar-custom {
            background-color: #2c3e50;
        }
        .table-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 20px;
        }
        .status-badge {
            font-size: 0.85rem;
            padding: 5px 10px;
        }
        .btn-return {
            font-size: 0.85rem;
            padding: 5px 10px;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="user_dashboard.php">Perpustakaan</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="riwayat_peminjaman.php">
                            <i class="bi bi-clock-history"></i> Riwayat Peminjaman
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Riwayat Peminjaman Buku</h2>
            <a href="user_dashboard.php" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>

        <?php if(isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <?php if(mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul Buku</th>
                                <th>Penulis</th>
                                <th>Tanggal Pinjam</th>
                                <th>Tanggal Kembali</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while($row = mysqli_fetch_assoc($result)): 
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                    <td><?php echo htmlspecialchars($row['penulis'] ?? 'Tidak ada data'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal_pinjam'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal_kembali'])); ?></td>
                                    <td>
                                        <span class="badge status-badge <?php 
                                            echo match($row['status']) {
                                                'menunggu' => 'bg-warning',
                                                'dipinjam' => 'bg-primary',
                                                'dikembalikan' => 'bg-success',
                                                'ditolak' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($row['status'] === 'dipinjam'): ?>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="peminjaman_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="id_buku" value="<?php echo $row['id_buku']; ?>">
                                                <button type="submit" name="kembalikan" class="btn btn-success btn-sm btn-return" onclick="return confirm('Apakah Anda yakin ingin mengembalikan buku ini?')">
                                                    <i class="bi bi-arrow-return-left"></i> Kembalikan
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle"></i> Belum ada riwayat peminjaman buku.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 