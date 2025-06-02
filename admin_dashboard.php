<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login dan memiliki role admin (role kosong)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== '') {
    header("Location: login_admin.php");
    exit();
}

// Mengambil data peminjaman
$sql = "SELECT p.*, u.username, b.judul 
        FROM peminjaman p 
        JOIN users u ON p.id_user = u.id 
        JOIN buku b ON p.id_buku = b.id 
        ORDER BY p.tanggal_pinjam DESC";
$result = mysqli_query($conn, $sql);

// Query untuk buku yang sering dipinjam
$popular_books = "SELECT b.judul, COUNT(p.id) as total_peminjaman 
                 FROM buku b 
                 LEFT JOIN peminjaman p ON b.id = p.id_buku 
                 GROUP BY b.id 
                 ORDER BY total_peminjaman DESC 
                 LIMIT 5";
$popular_books_result = mysqli_query($conn, $popular_books);

// Query untuk user yang sering meminjam
$active_users = "SELECT u.username, COUNT(p.id) as total_peminjaman 
                FROM users u 
                LEFT JOIN peminjaman p ON u.id = p.id_user 
                WHERE u.role = 'user'
                GROUP BY u.id 
                ORDER BY total_peminjaman DESC 
                LIMIT 5";
$active_users_result = mysqli_query($conn, $active_users);

// Mengambil data buku
$books_query = "SELECT * FROM buku ORDER BY id DESC";
$books = mysqli_query($conn, $books_query);

// Mengambil data users
$users_query = "SELECT * FROM users WHERE role != '' ORDER BY id DESC";
$users = mysqli_query($conn, $users_query);

// Ambil pesan sukses/error dari session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background-color: var(--primary-color) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
        }

        .nav-link {
            font-weight: 500;
            padding: 0.8rem 1rem !important;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background-color: var(--secondary-color);
            border-radius: 4px;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 1rem 1.5rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: var(--light-bg);
            border-bottom: 2px solid var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
        }

        .btn {
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
        }

        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
            color: white;
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .badge {
            padding: 0.5em 0.8em;
            font-weight: 500;
        }

        .table-responsive {
            border-radius: 0 0 10px 10px;
            overflow: hidden;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,0.02);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 0.5rem 0;
        }

        .section-header h2 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--secondary-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }

        /* Animasi untuk cards */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            animation: fadeIn 0.5s ease-out forwards;
        }

        /* Responsivitas */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .card {
                margin-bottom: 1rem;
            }

            .section-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Perpustakaan - Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="#books">Kelola Buku</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#users">Kelola Pengguna</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#loans">Kelola Peminjaman</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_atur_denda.php">Kelola Denda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports">Laporan</a>
                    </li>
                </ul>
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Dashboard Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Buku</h5>
                        <h2 class="mb-0">
                            <?php 
                            $total_books = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM buku"))['total'];
                            echo $total_books;
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total User</h5>
                        <h2 class="mb-0">
                            <?php 
                            $total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'user'"))['total'];
                            echo $total_users;
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Peminjaman Aktif</h5>
                        <h2 class="mb-0">
                            <?php 
                            $active_loans = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'dipinjam'"))['total'];
                            echo $active_loans;
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Denda</h5>
                        <h2 class="mb-0">
                            <?php 
                            $total_denda = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM denda WHERE status_pembayaran = 'belum_dibayar'"))['total'];
                            echo $total_denda;
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Utama -->
        <div class="row mb-4">
            <!-- Menu Kelola Buku -->
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-book display-4 text-primary mb-3"></i>
                        <h5 class="card-title">Kelola Buku</h5>
                        <p class="card-text">Tambah, edit, atau hapus buku perpustakaan</p>
                        <a href="#books" class="btn btn-primary">Kelola Buku</a>
                    </div>
                </div>
            </div>

            <!-- Menu Kelola User -->
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-people display-4 text-primary mb-3"></i>
                        <h5 class="card-title">Kelola User</h5>
                        <p class="card-text">Kelola user dan staff perpustakaan</p>
                        <a href="#users" class="btn btn-primary">Kelola User</a>
                    </div>
                </div>
            </div>

            <!-- Menu Kelola Peminjaman -->
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-journal-text display-4 text-primary mb-3"></i>
                        <h5 class="card-title">Kelola Peminjaman</h5>
                        <p class="card-text">Kelola peminjaman dan pengembalian buku</p>
                        <a href="#loans" class="btn btn-primary">Kelola Peminjaman</a>
                    </div>
                </div>
            </div>

            <!-- Menu Pengaturan Denda -->
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-cash-coin display-4 text-primary mb-3"></i>
                        <h5 class="card-title">Pengaturan Denda</h5>
                        <p class="card-text">Atur denda keterlambatan dan lihat riwayat</p>
                        <a href="admin_atur_denda.php" class="btn btn-primary">Kelola Denda</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seksi Denda -->
        <section id="denda" class="mb-5">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Pengaturan Denda</h5>
                        <a href="admin_atur_denda.php" class="btn btn-primary">
                            <i class="bi bi-gear"></i> Atur Denda
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php
                    // Ambil pengaturan denda saat ini
                    $denda_query = "SELECT * FROM pengaturan_denda LIMIT 1";
                    $denda_result = mysqli_query($conn, $denda_query);
                    $denda_setting = mysqli_fetch_assoc($denda_result);

                    // Ambil total denda yang belum dibayar
                    $unpaid_query = "SELECT COUNT(*) as total, SUM(jumlah_denda) as total_denda 
                                   FROM denda WHERE status_pembayaran = 'belum_dibayar'";
                    $unpaid_result = mysqli_query($conn, $unpaid_query);
                    $unpaid_data = mysqli_fetch_assoc($unpaid_result);
                    ?>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Denda per Hari</h6>
                                    <h3 class="card-title">Rp <?php echo number_format($denda_setting['denda_per_hari'], 0, ',', '.'); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Denda Belum Dibayar</h6>
                                    <h3 class="card-title"><?php echo $unpaid_data['total']; ?> Denda</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Total Denda Belum Dibayar</h6>
                                    <h3 class="card-title">Rp <?php echo number_format($unpaid_data['total_denda'], 0, ',', '.'); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Kelola Buku -->
        <section id="books" class="mb-5">
            <div class="section-header">
                <h2>Kelola Buku</h2>
                <a href="add_book.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Buku
                </a>
            </div>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar Buku</h5>
                    <div class="input-group" style="width: 300px;">
                        <input type="text" class="form-control" id="searchBooks" placeholder="Cari buku...">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Judul</th>
                                    <th>Penulis</th>
                                    <th>Stok</th>
                                    <th>Rekomendasi Usia</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($book = mysqli_fetch_assoc($books)): ?>
                                <tr>
                                    <td><?php echo $book['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if($book['cover']): ?>
                                                <img src="<?php echo $book['cover']; ?>" alt="Cover" class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                            <?php endif; ?>
                                            <div>
                                                <strong><?php echo $book['judul']; ?></strong>
                                                <?php if($book['deskripsi']): ?>
                                                    <small class="d-block text-muted"><?php echo substr($book['deskripsi'], 0, 50); ?>...</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $book['penulis']; ?></td>
                                    <td>
                                        <span class="badge <?php echo $book['stok'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $book['stok']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $book['rekomendasi_usia']; ?>+</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="edit_book.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="delete_book.php?id=<?php echo $book['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus buku ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Kelola Pengguna -->
        <section id="users" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Kelola User</h2>
                <a href="add_user.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Tambah User
                </a>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = mysqli_fetch_assoc($users)): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo $user['username']; ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo match($user['role']) {
                                                'user' => 'bg-info',
                                                'staff' => 'bg-success',
                                                '' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        ?>">
                                            <?php echo $user['role'] ?: 'Admin'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($user['is_banned']): ?>
                                            <span class="badge bg-danger">Banned</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="delete_user.php?id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Kelola Peminjaman -->
        <section id="loans" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Kelola Peminjaman</h2>
                <a href="manage_loan.php" class="btn btn-primary">
                    <i class="bi bi-list-check"></i> Kelola Detail Peminjaman
                </a>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Peminjam</th>
                                    <th>Buku</th>
                                    <th>Tanggal Pinjam</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Ambil 10 peminjaman terbaru
                                $recent_loans = mysqli_query($conn, "SELECT p.*, u.username, b.judul 
                                                                   FROM peminjaman p 
                                                                   JOIN users u ON p.id_user = u.id 
                                                                   JOIN buku b ON p.id_buku = b.id 
                                                                   ORDER BY p.tanggal_pinjam DESC 
                                                                   LIMIT 10");
                                while($loan = mysqli_fetch_assoc($recent_loans)): 
                                ?>
                                    <tr>
                                        <td><?php echo $loan['id']; ?></td>
                                        <td><?php echo $loan['username']; ?></td>
                                        <td><?php echo $loan['judul']; ?></td>
                                        <td><?php echo $loan['tanggal_pinjam']; ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                echo match($loan['status']) {
                                                    'menunggu' => 'bg-warning',
                                                    'bisa_diambil' => 'bg-info',
                                                    'dipinjam' => 'bg-primary',
                                                    'dikembalikan' => 'bg-success',
                                                    'dibatalkan' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                };
                                            ?>">
                                                <?php echo $loan['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="manage_loan.php" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Laporan -->
        <section id="reports" class="mb-5">
            <h2 class="mb-3">Laporan</h2>
            <div class="row">
                <!-- Buku Terpopuler -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Buku Terpopuler</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Judul Buku</th>
                                            <th>Total Peminjaman</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($book = mysqli_fetch_assoc($popular_books_result)): ?>
                                        <tr>
                                            <td><?php echo $book['judul']; ?></td>
                                            <td><?php echo $book['total_peminjaman']; ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pengguna Teraktif -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Pengguna Teraktif</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Total Peminjaman</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($user = mysqli_fetch_assoc($active_users_result)): ?>
                                        <tr>
                                            <td><?php echo $user['username']; ?></td>
                                            <td><?php echo $user['total_peminjaman']; ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    <!-- Modal dan Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchBooks').addEventListener('keyup', function() {
            let searchText = this.value.toLowerCase();
            let tableRows = document.querySelectorAll('#books tbody tr');
            
            tableRows.forEach(row => {
                let title = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                let author = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                
                if (title.includes(searchText) || author.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });


        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                let bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    </script>
</body>
</html> 