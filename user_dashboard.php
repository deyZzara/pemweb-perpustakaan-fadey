<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login_admin.php");
    exit();
}

// Ambil daftar buku
$sql = "SELECT id, judul, penulis, stok, cover FROM buku ORDER BY judul ASC";
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
    <title>Dashboard User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .book-card {
            transition: transform 0.2s;
        }
        .book-card:hover {
            transform: translateY(-5px);
        }
        .navbar-custom {
            background-color: #2c3e50;
        }
        .search-box {
            max-width: 500px;
            margin: 2rem auto;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="#">Perpustakaan</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="riwayat_peminjaman.php">
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
        <!-- Search Box -->
        <div class="search-box">
            <input type="text" id="searchInput" class="form-control" placeholder="Cari buku...">
        </div>

        <!-- Daftar Buku -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4" id="bookList">
            <?php while($row = mysqli_fetch_assoc($result)): ?>
                <div class="col">
                    <div class="card h-100 book-card">
                        <?php if(!empty($row['cover'])): ?>
                            <img src="<?php echo htmlspecialchars($row['cover']); ?>" class="card-img-top" alt="Cover buku" style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-secondary text-white d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="bi bi-book" style="font-size: 2rem;"></i>
                                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                    <small class="text-white-50 position-absolute bottom-0 start-0 p-2">
                                        Debug: <?php echo htmlspecialchars(print_r($row, true)); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($row['judul']); ?></h5>
                            <p class="card-text">
                                <strong>Penulis:</strong> <?php echo htmlspecialchars($row['penulis'] ?? 'Tidak ada data'); ?><br>
                                <strong>Stok:</strong> <?php echo htmlspecialchars($row['stok']); ?>
                            </p>
                            <?php if ($row['stok'] > 0): ?>
                                <a href="form_peminjaman.php?book_id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-journal-plus"></i> Pinjam Buku
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled>
                                    <i class="bi bi-x-circle"></i> Stok Habis
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fungsi pencarian real-time
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let cards = document.querySelectorAll('.book-card');

            cards.forEach(card => {
                let title = card.querySelector('.card-title').textContent.toLowerCase();
                let author = card.querySelector('.card-text').textContent.toLowerCase();
                
                if (title.includes(filter) || author.includes(filter)) {
                    card.parentElement.style.display = '';
                } else {
                    card.parentElement.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 