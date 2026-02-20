<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.use_strict_mode', 1);
session_start();
require_once '../lib/auth.php';
require_once '../lib/functions.php';

// Cek apakah user sudah login dan role-nya 'gudang'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gudang') {
    header("Location: ../login.php");
    exit();
}

// Set judul halaman
$page_title = "Dashboard Gudang";
?>
<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-warehouse me-2"></i>Dashboard Gudang
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
                                <p class="mb-1"><strong>Role:</strong> <span class="badge bg-primary"><?= htmlspecialchars($_SESSION['role']) ?></span></p>
                                <p class="mb-0"><strong>Waktu Login:</strong> <?= date('d-m-Y H:i:s') ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h5 class="alert-heading">
                                    <i class="fas fa-info-circle me-2"></i>Akses yang Dimiliki
                                </h5>
                                <ul class="mb-0">
                                    <li>Manajemen Stok Barang</li>
                                    <li>Pengelolaan Pengiriman</li>
                                    <li>Monitoring Barang Masuk/Keluar</li>
                                    <li>Laporan Inventaris</li>
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
                                    <i class="fas fa-truck fa-3x text-primary mb-3"></i>
                                    <h6 class="card-title">Pengiriman Barang</h6>
                                    <p class="card-text text-muted small">Kelola pengiriman barang</p>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <a href="../barang/index.php" class="card text-decoration-none">
                                <div class="card-body text-center">
                                    <i class="fas fa-boxes fa-3x text-success mb-3"></i>
                                    <h6 class="card-title">Data Barang</h6>
                                    <p class="card-text text-muted small">Kelola stok barang</p>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <a href="#" class="card text-decoration-none">
                                <div class="card-body text-center">
                                    <i class="fas fa-chart-line fa-3x text-warning mb-3"></i>
                                    <h6 class="card-title">Laporan</h6>
                                    <p class="card-text text-muted small">Lihat laporan gudang</p>
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
                        <i class="fas fa-bell me-2"></i>Aktivitas Terbaru
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Pengiriman baru dibuat</h6>
                                <small>5 menit yang lalu</small>
                            </div>
                            <p class="mb-1">Pengiriman #PBL-2024-001 telah dibuat</p>
                        </div>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Stok barang diperbarui</h6>
                                <small>1 jam yang lalu</small>
                            </div>
                            <p class="mb-1">Stok barang "Kardus Besar" telah diperbarui</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>