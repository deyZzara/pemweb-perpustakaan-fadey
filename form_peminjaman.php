<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login_admin.php");
    exit();
}

$success_message = $error_message = '';
$book_id = isset($_GET['book_id']) ? $_GET['book_id'] : '';

// Ambil data buku
if ($book_id) {
    $sql = "SELECT * FROM buku WHERE id = '$book_id'";
    $result = mysqli_query($conn, $sql);
    $book = mysqli_fetch_assoc($result);
}

// Proses form peminjaman
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($book) && $book['stok'] > 0) {
    $tanggal_pinjam = mysqli_real_escape_string($conn, $_POST['tanggal_pinjam']);
    $tanggal_kembali = mysqli_real_escape_string($conn, $_POST['tanggal_kembali']);
    $user_id = $_SESSION['user_id'];
    
    // Validasi tanggal
    $date_pinjam = new DateTime($tanggal_pinjam);
    $date_kembali = new DateTime($tanggal_kembali);
    $today = new DateTime();
    
    if ($date_pinjam < $today) {
        $error_message = "Tanggal peminjaman tidak boleh kurang dari hari ini!";
    } elseif ($date_kembali <= $date_pinjam) {
        $error_message = "Tanggal pengembalian harus lebih besar dari tanggal peminjaman!";
    } else {
        // Cek ketersediaan buku
        $sql_check = "SELECT stok FROM buku WHERE id = '$book_id' AND stok > 0";
        $result_check = mysqli_query($conn, $sql_check);
        
        if (mysqli_num_rows($result_check) > 0) {
            // Insert data peminjaman
            $sql_insert = "INSERT INTO peminjaman (id_user, id_buku, tanggal_pinjam, tanggal_kembali, status) 
                          VALUES ('$user_id', '$book_id', '$tanggal_pinjam', '$tanggal_kembali', 'menunggu')";
            
            if (mysqli_query($conn, $sql_insert)) {
                // Update stok buku
                $sql_update = "UPDATE buku SET stok = stok - 1 WHERE id = '$book_id'";
                mysqli_query($conn, $sql_update);
                
                $success_message = "Permintaan peminjaman berhasil diajukan!";
            } else {
                $error_message = "Terjadi kesalahan saat memproses peminjaman: " . mysqli_error($conn);
            }
        } else {
            $error_message = "Maaf, stok buku tidak tersedia.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Peminjaman Buku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tambahkan CSS untuk date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-container {
            max-width: 600px;
            margin: 2rem auto;
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
        .book-details {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .stock-warning {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="card">
                <div class="card-header text-center">
                    <h4 class="mb-0">Form Peminjaman Buku</h4>
                </div>
                <div class="card-body p-4">
                    <?php if($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($book)): ?>
                        <div class="book-details">
                            <h5>Detail Buku:</h5>
                            <p><strong>Judul:</strong> <?php echo htmlspecialchars($book['judul']); ?></p>
                            <p><strong>Penulis:</strong> <?php echo htmlspecialchars($book['penulis'] ?? 'Tidak ada data'); ?></p>
                            <p>
                                <strong>Stok Tersedia:</strong> 
                                <span class="<?php echo $book['stok'] == 0 ? 'stock-warning' : ''; ?>">
                                    <?php echo htmlspecialchars($book['stok']); ?>
                                </span>
                            </p>
                        </div>

                        <?php if($book['stok'] > 0): ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Peminjaman</label>
                                    <input type="text" class="form-control datepicker" name="tanggal_pinjam" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Tanggal Pengembalian</label>
                                    <input type="text" class="form-control datepicker" name="tanggal_kembali" required>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Ajukan Peminjaman</button>
                                    <a href="user_dashboard.php" class="btn btn-light">Kembali</a>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-danger text-center">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <strong>Maaf, stok buku ini sedang tidak tersedia.</strong>
                            </div>
                            <div class="d-grid">
                                <a href="user_dashboard.php" class="btn btn-primary">Kembali ke Daftar Buku</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            Buku tidak ditemukan.
                            <a href="user_dashboard.php" class="btn btn-light mt-3">Kembali</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi date picker
            flatpickr(".datepicker", {
                dateFormat: "Y-m-d",
                minDate: "today",
                locale: "id"
            });
        });
    </script>
</body>
</html> 