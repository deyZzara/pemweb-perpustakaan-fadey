<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login dan memiliki role staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: login_admin.php");
    exit();
}

// Proses perubahan status peminjaman
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_loan'])) {
    $loan_id = mysqli_real_escape_string($conn, $_POST['loan_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    $id_buku = mysqli_real_escape_string($conn, $_POST['id_buku']);
    
    mysqli_begin_transaction($conn);
    try {
        // Update status peminjaman
        mysqli_query($conn, "UPDATE peminjaman SET status = '$new_status' WHERE id = '$loan_id'");
        
        // Update stok buku dan hitung denda jika dikembalikan
        if ($new_status == 'dikembalikan') {
            // Update stok buku
            mysqli_query($conn, "UPDATE buku SET stok = stok + 1 WHERE id = '$id_buku'");
            
            // Ambil data peminjaman
            $loan_query = "SELECT *, DATEDIFF(CURRENT_DATE, batas_waktu) as hari_telat 
                          FROM peminjaman WHERE id = '$loan_id'";
            $loan_result = mysqli_query($conn, $loan_query);
            $loan_data = mysqli_fetch_assoc($loan_result);
            
            // Jika telat, hitung dan simpan denda
            if ($loan_data['hari_telat'] > 0) {
                // Ambil pengaturan denda per hari
                $denda_query = "SELECT denda_per_hari FROM pengaturan_denda LIMIT 1";
                $denda_result = mysqli_query($conn, $denda_query);
                $denda_setting = mysqli_fetch_assoc($denda_result);
                
                $jumlah_denda = $loan_data['hari_telat'] * $denda_setting['denda_per_hari'];
                
                // Simpan data denda
                $save_denda = "INSERT INTO denda (id_peminjaman, jumlah_hari_telat, jumlah_denda) 
                              VALUES ('$loan_id', '{$loan_data['hari_telat']}', '$jumlah_denda')";
                mysqli_query($conn, $save_denda);
            }
            
            // Update tanggal kembali
            mysqli_query($conn, "UPDATE peminjaman SET tanggal_kembali = CURRENT_DATE WHERE id = '$loan_id'");
        }
        
        mysqli_commit($conn);
        $success_message = "Status peminjaman berhasil diperbarui!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_message = "Error: " . $e->getMessage();
    }
}

// Proses persetujuan/penolakan registrasi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process_registration'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $action = $_POST['process_registration']; // Mengambil nilai dari tombol submit
    
    if ($action == 'approve') {
        mysqli_query($conn, "UPDATE users SET is_banned = 0 WHERE id = '$user_id'");
        $success_message = "Registrasi user berhasil disetujui!";
    } else if ($action == 'reject') {
        mysqli_query($conn, "DELETE FROM users WHERE id = '$user_id'");
        $success_message = "Registrasi user berhasil ditolak!";
    }
}

// Ambil daftar peminjaman aktif
$loans_query = "SELECT p.*, u.username, b.judul, b.id as id_buku 
               FROM peminjaman p 
               JOIN users u ON p.id_user = u.id 
               JOIN buku b ON p.id_buku = b.id 
               WHERE p.status != 'dikembalikan' AND p.status != 'dibatalkan'
               ORDER BY p.tanggal_pinjam DESC";
$loans = mysqli_query($conn, $loans_query);

// Ambil daftar permintaan registrasi
$registrations_query = "SELECT * FROM users WHERE role = 'user' AND is_banned = 1";
$registrations = mysqli_query($conn, $registrations_query);

// Ambil data untuk dashboard
$sql_users = "SELECT COUNT(*) as total_users FROM users WHERE role = 'user'";
$sql_books = "SELECT COUNT(*) as total_books FROM buku";
$sql_loans = "SELECT COUNT(*) as total_loans FROM peminjaman";

$result_users = mysqli_query($conn, $sql_users);
$result_books = mysqli_query($conn, $sql_books);
$result_loans = mysqli_query($conn, $sql_loans);

$total_users = mysqli_fetch_assoc($result_users)['total_users'];
$total_books = mysqli_fetch_assoc($result_books)['total_books'];
$total_loans = mysqli_fetch_assoc($result_loans)['total_loans'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .navbar-custom {
            background-color: #2c3e50;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            background: linear-gradient(45deg, #2c3e50, #34495e);
            color: white;
        }
        .action-card {
            background-color: white;
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="#">Staff Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
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
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Total User</h6>
                                <h2 class="card-title mb-0"><?php echo $total_users; ?></h2>
                            </div>
                            <i class="bi bi-people stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Total Buku</h6>
                                <h2 class="card-title mb-0"><?php echo $total_books; ?></h2>
                            </div>
                            <i class="bi bi-book stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Total Peminjaman</h6>
                                <h2 class="card-title mb-0"><?php echo $total_loans; ?></h2>
                            </div>
                            <i class="bi bi-journal-text stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Aksi -->
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card action-card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-person-plus display-4 text-primary mb-3"></i>
                        <h5 class="card-title">Tambah User</h5>
                        <p class="card-text">Tambahkan user baru ke sistem perpustakaan</p>
                        <a href="staff_add_user.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Tambah User
                        </a>
                    </div>
                </div>
            </div>
            <!-- Tambahkan card menu lainnya di sini jika diperlukan -->
        </div>

        <div class="row mt-4">
            <?php if(isset($success_message)): ?>
                <div class="col-md-12">
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
                <div class="col-md-12">
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                </div>
            <?php endif; ?>

            <!-- Kelola Peminjaman -->
            <div class="col-md-6 mb-3">
                <h2 class="mb-3">Kelola Peminjaman Aktif</h2>
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
                                        <th>Batas Waktu</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($loan = mysqli_fetch_assoc($loans)): ?>
                                    <tr>
                                        <td><?php echo $loan['id']; ?></td>
                                        <td><?php echo $loan['username']; ?></td>
                                        <td><?php echo $loan['judul']; ?></td>
                                        <td><?php echo $loan['tanggal_pinjam']; ?></td>
                                        <td><?php echo $loan['batas_waktu']; ?></td>
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
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                                <input type="hidden" name="id_buku" value="<?php echo $loan['id_buku']; ?>">
                                                <select name="new_status" class="form-select form-select-sm d-inline-block w-auto">
                                                    <option value="menunggu">Menunggu</option>
                                                    <option value="bisa_diambil">Bisa Diambil</option>
                                                    <option value="dipinjam">Dipinjam</option>
                                                    <option value="dikembalikan">Dikembalikan</option>
                                                    <option value="dibatalkan">Dibatalkan</option>
                                                </select>
                                                <button type="submit" name="update_loan" class="btn btn-sm btn-primary">
                                                    Update
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permintaan Registrasi -->
            <div class="col-md-6 mb-3">
                <h2 class="mb-3">Permintaan Registrasi</h2>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($registration = mysqli_fetch_assoc($registrations)): ?>
                                    <tr>
                                        <td><?php echo $registration['id']; ?></td>
                                        <td><?php echo $registration['username']; ?></td>
                                        <td>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $registration['id']; ?>">
                                                <button type="submit" name="process_registration" value="approve" class="btn btn-sm btn-success">
                                                    <i class="bi bi-check-circle"></i> Setujui
                                                </button>
                                                <button type="submit" name="process_registration" value="reject" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-x-circle"></i> Tolak
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 