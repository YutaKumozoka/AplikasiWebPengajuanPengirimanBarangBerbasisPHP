<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.use_strict_mode', 1);
session_start();
require_once '../lib/auth.php';
require_once '../lib/functions.php';

// Cek apakah user sudah login dan role-nya 'toko'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'toko') {
    header("Location: ../login.php");
    exit();
}

// Set judul halaman
$page_title = "Dashboard Toko";
?>
<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-store me-2"></i>Dashboard Toko
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="alert alert-success">
                                <h5 class="alert-heading">
                                    <i class="fas fa-user-circle me-2"></i>Informasi Login
                                </h5>
                                <p class="mb-1"><strong>Username:</strong> <?= htmlspecialchars($_SESSION['username']) ?></p>
                                <p class="mb-1"><strong>Role:</strong> <span class="badge bg-success"><?= htmlspecialchars($_SESSION['role']) ?></span></p>
                                <p class="mb-0"><strong>Waktu Login:</strong> <?= date('d-m-Y H:i:s') ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h5 class="alert-heading">
                                    <i class="fas fa-info-circle me-2"></i>Informasi Akses
                                </h5>
                                <p class="mb-2"><strong>Catatan Penting:</strong></p>
                                <ul class="mb-0">
                                    <li>Anda dapat melihat daftar pengiriman</li>
                                    <li><strong>Tidak dapat</strong> mengakses detail pengiriman</li>
                                    <li><strong>Tidak dapat</strong> menambah/hapus item pengiriman</li>
                                    <li>Hanya dapat melihat informasi umum</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-3">
                        <i class="fas fa-tachometer-alt me-2"></i>Menu Cepat
                    </h5>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="../pengiriman_barang/index.php" class="card text-decoration-none">
                                <div class="card-body text-center">
                                    <i class="fas fa-shipping-fast fa-3x text-primary mb-3"></i>
                                    <h6 class="card-title">Daftar Pengiriman</h6>
                                    <p class="card-text text-muted small">Lihat daftar pengiriman</p>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <a href="#" class="card text-decoration-none">
                                <div class="card-body text-center">
                                    <i class="fas fa-shopping-cart fa-3x text-warning mb-3"></i>
                                    <h6 class="card-title">Pesanan</h6>
                                    <p class="card-text text-muted small">Kelola pesanan toko</p>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <a href="#" class="card text-decoration-none">
                                <div class="card-body text-center">
                                    <i class="fas fa-chart-pie fa-3x text-info mb-3"></i>
                                    <h6 class="card-title">Statistik</h6>
                                    <p class="card-text text-muted small">Lihat statistik penjualan</p>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <a href="../logout.php" class="card text-decoration-none border-danger">
                                <div class="card-body text-center text-danger">
                                    <i class="fas fa-sign-out-alt fa-3x mb-3"></i>
                                    <h6 class="card-title">Logout</h6>
                                    <p class="card-text text-muted small">Keluar dari sistem</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>Pembatasan Akses
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h6 class="alert-heading">
                            <i class="fas fa-lock me-2"></i>Perhatian!
                        </h6>
                        <p class="mb-2">Sebagai <strong>Role "Toko"</strong>, Anda memiliki pembatasan akses:</p>
                        <ul class="mb-0">
                            <li><i class="fas fa-times text-danger me-2"></i> <strong>Tidak dapat</strong> melihat detail pengiriman</li>
                            <li><i class="fas fa-times text-danger me-2"></i> <strong>Tidak dapat</strong> menambah item ke pengiriman</li>
                            <li><i class="fas fa-times text-danger me-2"></i> <strong>Tidak dapat</strong> menghapus item dari pengiriman</li>
                            <li><i class="fas fa-check text-success me-2"></i> <strong>Dapat</strong> melihat daftar pengiriman</li>
                            <li><i class="fas fa-check text-success me-2"></i> <strong>Dapat</strong> melihat status pengiriman</li>
                        </ul>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>