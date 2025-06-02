<?php
session_start();
require_once 'config.php';

// Tambahkan error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != '') {
    header("Location: login_admin.php");
    exit();
}

// Proses perubahan status peminjaman
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['loan_id'])) {
    $loan_id = mysqli_real_escape_string($conn, $_POST['loan_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    $id_buku = mysqli_real_escape_string($conn, $_POST['id_buku']);
    
    mysqli_begin_transaction($conn);
    try {
        // Update status peminjaman
        $sql = "UPDATE peminjaman SET status = '$new_status'";
        
        // Jika status dikembalikan
        if ($new_status == 'dikembalikan') {
            // Set tanggal kembali
            $sql .= ", tanggal_kembali = CURRENT_DATE";
            
            // Tambah stok buku
            if (!mysqli_query($conn, "UPDATE buku SET stok = stok + 1 WHERE id = '$id_buku'")) {
                throw new Exception("Gagal mengupdate stok buku");
            }
            
            // Hitung hari telat
            $loan_query = "SELECT p.*, DATEDIFF(CURRENT_DATE, p.batas_waktu) as hari_telat 
                          FROM peminjaman p 
                          WHERE p.id = '$loan_id'";
            $loan_result = mysqli_query($conn, $loan_query);
            
            if (!$loan_result) {
                throw new Exception("Gagal mengambil data peminjaman");
            }
            
            $loan_data = mysqli_fetch_assoc($loan_result);
            
            // Debug - tambahkan ini untuk melihat nilai
            error_log("Hari telat: " . $loan_data['hari_telat']);
            
            // Jika telat
            if ($loan_data['hari_telat'] > 0) {
                // Ambil pengaturan denda
                $denda_query = "SELECT denda_per_hari FROM pengaturan_denda LIMIT 1";
                $denda_result = mysqli_query($conn, $denda_query);
                
                if (!$denda_result) {
                    throw new Exception("Gagal mengambil pengaturan denda");
                }
                
                $denda_setting = mysqli_fetch_assoc($denda_result);
                
                if ($denda_setting) {
                    // Hitung total denda
                    $total_denda = $loan_data['hari_telat'] * $denda_setting['denda_per_hari'];
                    
                    // Debug - tambahkan ini untuk melihat nilai
                    error_log("Total denda: " . $total_denda);
                    
                    // Cek apakah sudah ada denda untuk peminjaman ini
                    $check_denda = mysqli_query($conn, "SELECT id FROM denda WHERE id_peminjaman = '$loan_id'");
                    if (mysqli_num_rows($check_denda) == 0) {
                        // Simpan data denda
                        $insert_denda = "INSERT INTO denda 
                                       (id_peminjaman, jumlah_hari_telat, jumlah_denda, status_pembayaran) 
                                       VALUES 
                                       ('$loan_id', '{$loan_data['hari_telat']}', '$total_denda', 'belum_dibayar')";
                        
                        if (!mysqli_query($conn, $insert_denda)) {
                            throw new Exception("Gagal menyimpan denda: " . mysqli_error($conn));
                        }
                    }
                }
            }
        }
        // Jika status dibatalkan
        else if ($new_status == 'dibatalkan') {
            $check_status = mysqli_query($conn, "SELECT status FROM peminjaman WHERE id = '$loan_id'");
            $current_status = mysqli_fetch_assoc($check_status)['status'];
            if ($current_status == 'dipinjam') {
                mysqli_query($conn, "UPDATE buku SET stok = stok + 1 WHERE id = '$id_buku'");
            }
        }
        
        // Update status peminjaman
        if (!mysqli_query($conn, $sql . " WHERE id = '$loan_id'")) {
            throw new Exception("Gagal mengupdate status peminjaman");
        }
        
        mysqli_commit($conn);
        $_SESSION['success_message'] = "Status peminjaman berhasil diperbarui!";
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        error_log("Error in manage_loan.php: " . $e->getMessage());
    }
    
    header("Location: admin_dashboard.php#loans");
    exit();
}

// Ambil data peminjaman
$loans_query = "SELECT p.*, u.username, b.judul, b.id as id_buku 
                FROM peminjaman p 
                JOIN users u ON p.id_user = u.id 
                JOIN buku b ON p.id_buku = b.id 
                ORDER BY p.tanggal_pinjam DESC";
$loans = mysqli_query($conn, $loans_query);

// Debug - tambahkan ini untuk melihat jumlah data
$num_rows = mysqli_num_rows($loans);
error_log("Jumlah data peminjaman: " . $num_rows);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Peminjaman</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .badge {
            font-size: 0.9em;
            padding: 0.5em 0.8em;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Kelola Peminjaman</h5>
            </div>
            <div class="card-body">
                <?php if(isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo $_SESSION['error_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Peminjam</th>
                                <th>Buku</th>
                                <th>Tanggal Pinjam</th>
                                <th>Batas Waktu</th>
                                <th>Tanggal Kembali</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($loan = mysqli_fetch_assoc($loans)): ?>
                                <tr>
                                    <td><?php echo $loan['id']; ?></td>
                                    <td><?php echo htmlspecialchars($loan['username']); ?></td>
                                    <td><?php echo htmlspecialchars($loan['judul']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($loan['tanggal_pinjam'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($loan['batas_waktu'])); ?></td>
                                    <td><?php echo $loan['tanggal_kembali'] ? date('d/m/Y', strtotime($loan['tanggal_kembali'])) : '-'; ?></td>
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
                                            <?php echo ucfirst($loan['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($loan['status'] != 'dikembalikan' && $loan['status'] != 'dibatalkan'): ?>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                                <input type="hidden" name="id_buku" value="<?php echo $loan['id_buku']; ?>">
                                                
                                                <?php if($loan['status'] == 'menunggu'): ?>
                                                    <button type="submit" name="new_status" value="bisa_diambil" 
                                                            class="btn btn-sm btn-info">
                                                        <i class="bi bi-check2"></i> Setujui
                                                    </button>
                                                    <button type="submit" name="new_status" value="dibatalkan" 
                                                            class="btn btn-sm btn-danger">
                                                        <i class="bi bi-x"></i> Tolak
                                                    </button>
                                                <?php elseif($loan['status'] == 'bisa_diambil'): ?>
                                                    <button type="submit" name="new_status" value="dipinjam" 
                                                            class="btn btn-sm btn-primary">
                                                        <i class="bi bi-box-arrow-in-right"></i> Konfirmasi Pengambilan
                                                    </button>
                                                <?php elseif($loan['status'] == 'dipinjam'): ?>
                                                    <button type="submit" name="new_status" value="dikembalikan" 
                                                            class="btn btn-sm btn-success">
                                                        <i class="bi bi-box-arrow-in-left"></i> Kembalikan
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        <?php endif; ?>
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
    <script>
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