<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.use_strict_mode', 1);
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';
requireAuth();

// Cek khusus untuk halaman detail.php - role 'toko' tidak bisa akses TAMBAH/HAPUS item
// Tapi BISA menginput qty jika ada form khusus
requireModuleAccess('pengiriman_barang');
require_once '../config/database.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header("Location: index.php");
    exit();
}

// Ambil data pengiriman terlebih dahulu
$stmt = mysqli_prepare($connection, "SELECT * FROM `pengiriman_barang` WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$pengiriman_barang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$pengiriman_barang) {
    header("Location: index.php");
    exit();
}

// PROSES UNTUK UPDATE QTY (Bisa diakses oleh semua role termasuk toko)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
    
    // 1. Proses Update Status (hanya untuk non-toko)
    if (isset($_POST['update_status']) && $_SESSION['role'] !== 'toko') {
        $new_status = trim($_POST['status'] ?? '');
        $allowed_statuses = ['Menunggu', 'Diproses', 'Dikirim', 'SELESAI', 'Dibatalkan'];
        
        if (in_array($new_status, $allowed_statuses) && $pengiriman_barang['status'] !== $new_status) {
            $updateStmt = mysqli_prepare($connection, "UPDATE `pengiriman_barang` SET `status` = ? WHERE `id` = ?");
            mysqli_stmt_bind_param($updateStmt, "si", $new_status, $id);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
            
            // Update variabel lokal
            $pengiriman_barang['status'] = $new_status;
            
            $_SESSION['success_message'] = 'Status pengiriman #' . $id . ' berhasil diubah menjadi ' . $new_status . '.';
            header("Location: detail.php?id=" . $id);
            exit();
        }
    }
    
    // 2. Proses Update Total Qty (bisa diakses semua role termasuk toko)
    if (isset($_POST['update_total_qty'])) {
        $total_qty = (int)($_POST['total_qty'] ?? 0);
        
        if ($total_qty >= 0) {
            $updateQtyStmt = mysqli_prepare($connection, "UPDATE `pengiriman_barang` SET `total_qty` = ? WHERE `id` = ?");
            mysqli_stmt_bind_param($updateQtyStmt, "ii", $total_qty, $id);
            mysqli_stmt_execute($updateQtyStmt);
            mysqli_stmt_close($updateQtyStmt);
            
            // Update variabel lokal
            $pengiriman_barang['total_qty'] = $total_qty;
            
            $_SESSION['success_message'] = 'Total Qty berhasil diupdate menjadi ' . $total_qty . ' unit.';
            header("Location: detail.php?id=" . $id);
            exit();
        } else {
            $_SESSION['error_message'] = 'Qty tidak valid. Harus angka positif.';
        }
    }
    
    // 3. PROSES BARU: Verifikasi Otomatis Semua Item (Hanya untuk gudang)
    if (isset($_POST['verifikasi_otomatis']) && $_SESSION['role'] === 'gudang' && $pengiriman_barang['status'] !== 'SELESAI') {
        // Ambil semua barang yang diajukan
        $barang_query = mysqli_query($connection,
            "SELECT pd.barang_id, pd.qty_diajukan, b.stok as stok_gudang, b.nama_barang
             FROM pengiriman_detail pd 
             JOIN barang b ON b.id = pd.barang_id 
             WHERE pd.pengiriman_id = $id AND pd.status = 'menunggu'");
        
        $total_disetujui = 0;
        $total_ditolak = 0;
        $total_diajukan = 0;
        $log_detail = [];
        
        // Loop melalui semua barang
        while ($barang = mysqli_fetch_assoc($barang_query)) {
            $barang_id = $barang['barang_id'];
            $nama_barang = $barang['nama_barang'];
            $qty_diajukan = $barang['qty_diajukan'];
            $stok_gudang = $barang['stok_gudang'];
            
            $total_diajukan += $qty_diajukan;
            
            // Tentukan qty yang disetujui berdasarkan stok
            if ($stok_gudang >= $qty_diajukan) {
                // Jika stok cukup, setujui semua
                $qty_disetujui = $qty_diajukan;
                $status = 'disetujui';
                $total_disetujui += $qty_disetujui;
                $log_detail[] = "✓ $nama_barang: $qty_disetujui unit (Stok cukup)";
                
                // Kurangi stok
                $update_stok = mysqli_prepare($connection,
                    "UPDATE barang SET stok = stok - ? WHERE id = ?");
                mysqli_stmt_bind_param($update_stok, "ii", $qty_disetujui, $barang_id);
                mysqli_stmt_execute($update_stok);
            } else {
                // Jika stok tidak cukup, setujui maksimal yang tersedia
                $qty_disetujui = $stok_gudang;
                $status = 'ditolak';
                $total_ditolak += $qty_diajukan;
                $log_detail[] = "✗ $nama_barang: $qty_diajukan unit diajukan, stok hanya $stok_gudang unit";
            }
            
            // Update status dan qty disetujui di pengiriman_detail
            $update_stmt = mysqli_prepare($connection,
                "UPDATE pengiriman_detail 
                 SET qty_disetujui = ?, status = ? 
                 WHERE pengiriman_id = ? AND barang_id = ?");
            mysqli_stmt_bind_param($update_stmt, "isii", $qty_disetujui, $status, $id, $barang_id);
            mysqli_stmt_execute($update_stmt);
        }
        
        // Update total qty di pengiriman_barang
        $update_total = mysqli_prepare($connection,
            "UPDATE pengiriman_barang SET total_qty = ? WHERE id = ?");
        mysqli_stmt_bind_param($update_total, "ii", $total_disetujui, $id);
        mysqli_stmt_execute($update_total);
        
        $_SESSION['success_message'] = $log_message;
        header("Location: detail.php?id=" . $id);
        exit();
    }
}

// Ambil data barang yang diajukan untuk pengiriman ini
$barang_diajukan_query = mysqli_query(
    $connection,
    "SELECT 
        pd.*,
        b.nama_barang,
        b.stok as stok_gudang,
        b.kode_barang,
        b.harga
     FROM pengiriman_detail pd
     JOIN barang b ON b.id = pd.barang_id
     WHERE pd.pengiriman_id = $id
     ORDER BY pd.id"
);

$total_qty_diajukan = 0;
$total_qty_disetujui = 0;
$total_subtotal = 0;

// Hitung total
while ($row = mysqli_fetch_assoc($barang_diajukan_query)) {
    $total_qty_diajukan += $row['qty_diajukan'] ?? 0;
    $total_qty_disetujui += $row['qty_disetujui'] ?? 0;
    $total_subtotal += $row['subtotal'] ?? 0;
}

// Reset pointer untuk tampilan
mysqli_data_seek($barang_diajukan_query, 0);

// INISIALISASI VARIABEL DI SINI SEBELUM DIPAKAI
$no = 1;

?>
<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= nl2br(htmlspecialchars($_SESSION['success_message'])) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="index.php" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Kembali ke Daftar
        </a>
        <h2 class="mb-0">Detail Pengiriman Barang #<?= $pengiriman_barang['id'] ?></h2>
        <p class="text-muted mb-0">No Pengajuan: <?= htmlspecialchars($pengiriman_barang['no_pengajuan']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($pengiriman_barang['status'] !== 'SELESAI' && $_SESSION['role'] !== 'toko'): ?>
        <!-- FORM UPDATE STATUS -->
        <form method="POST" class="d-flex gap-2" id="form-update-status">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
            <input type="hidden" name="update_status" value="1">
            
            <select name="status" class="form-select" style="width: auto;">
                <option value="Menunggu" <?= $pengiriman_barang['status'] === 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                <option value="Diproses" <?= $pengiriman_barang['status'] === 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                <option value="Dikirim" <?= $pengiriman_barang['status'] === 'Dikirim' ? 'selected' : '' ?>>Dikirim</option>
                <option value="SELESAI" <?= $pengiriman_barang['status'] === 'SELESAI' ? 'selected' : '' ?>>Selesai</option>
                <option value="Dibatalkan" <?= $pengiriman_barang['status'] === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
            </select>
            
            <button type="submit" class="btn btn-warning" id="btn-update-status">
                <i class="fas fa-sync-alt me-1"></i>Update Status
            </button>
        </form>
        <?php endif; ?>
        
        <?php if ($pengiriman_barang['status'] !== 'SELESAI' && $_SESSION['role'] === 'gudang'): ?>
        <!-- TOMBOL VERIFIKASI OTOMATIS -->
        <form method="POST" style="display:inline;" onsubmit="return confirmVerifikasiOtomatis();">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
            <input type="hidden" name="verifikasi_otomatis" value="1">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-robot me-1"></i>Proses Stok
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- PROGRESS BAR STATUS -->
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-road me-2"></i>Progress Pengiriman
        </h5>
    </div>
    <div class="card-body">
        <div class="progress-steps">
            <div class="step <?= $pengiriman_barang['status'] === 'Menunggu' ? 'active' : ($pengiriman_barang['status'] === 'Diproses' || $pengiriman_barang['status'] === 'Dikirim' || $pengiriman_barang['status'] === 'SELESAI' ? 'completed' : '') ?>">
                <div class="step-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="step-label">Menunggu</div>
            </div>
            <div class="step-line"></div>
            <div class="step <?= $pengiriman_barang['status'] === 'Diproses' ? 'active' : ($pengiriman_barang['status'] === 'Dikirim' || $pengiriman_barang['status'] === 'SELESAI' ? 'completed' : '') ?>">
                <div class="step-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="step-label">Diproses</div>
            </div>
            <div class="step-line"></div>
            <div class="step <?= $pengiriman_barang['status'] === 'Dikirim' ? 'active' : ($pengiriman_barang['status'] === 'SELESAI' ? 'completed' : '') ?>">
                <div class="step-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="step-label">Dikirim</div>
            </div>
            <div class="step-line"></div>
            <div class="step <?= $pengiriman_barang['status'] === 'SELESAI' ? 'active' : '' ?>">
                <div class="step-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="step-label">Selesai</div>
            </div>
        </div>
        
        <style>
        .progress-steps {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 0;
        }
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            flex: 1;
        }
        .step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            font-size: 20px;
            color: #6c757d;
            border: 3px solid #e9ecef;
            transition: all 0.3s;
        }
        .step.active .step-icon {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
            transform: scale(1.1);
        }
        .step.completed .step-icon {
            background-color: #198754;
            color: white;
            border-color: #198754;
        }
        .step-label {
            font-size: 14px;
            font-weight: 500;
            color: #6c757d;
            text-align: center;
        }
        .step.active .step-label {
            color: #0d6efd;
            font-weight: 600;
        }
        .step.completed .step-label {
            color: #198754;
        }
        .step-line {
            flex: 1;
            height: 3px;
            background-color: #e9ecef;
            margin: 0 10px;
        }
        .step.completed + .step-line {
            background-color: #198754;
        }
        </style>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <p><strong>Nama Pengirim:</strong> <?= htmlspecialchars($pengiriman_barang['nama_pengirim']) ?></p>
                <p><strong>Alamat Pengirim:</strong> <?= htmlspecialchars($pengiriman_barang['alamat_pengirim']) ?></p>
                <p><strong>Telepon Pengirim:</strong> <?= htmlspecialchars($pengiriman_barang['telepon_pengirim']) ?></p>
            </div>
            <div class="col-md-4">
                <p><strong>Nama Penerima:</strong> <?= htmlspecialchars($pengiriman_barang['nama_penerima']) ?></p>
                <p><strong>Alamat Tujuan:</strong> <?= htmlspecialchars($pengiriman_barang['alamat_tujuan']) ?></p>
                <p><strong>Telepon Penerima:</strong> <?= htmlspecialchars($pengiriman_barang['telepon_penerima']) ?></p>
            </div>
            <div class="col-md-4">
                <p><strong>Status:</strong> 
                    <span class="badge bg-<?= $pengiriman_barang['status'] === 'SELESAI' ? 'success' : ($pengiriman_barang['status'] === 'Menunggu' ? 'warning' : ($pengiriman_barang['status'] === 'Diproses' ? 'info' : ($pengiriman_barang['status'] === 'Dikirim' ? 'primary' : 'danger'))) ?>">
                        <?= htmlspecialchars($pengiriman_barang['status']) ?>
                    </span>
                    <?php if ($pengiriman_barang['status'] === 'SELESAI'): ?>
                    <small class="text-muted ms-2"><i class="fas fa-lock me-1"></i>Terkunci</small>
                    <?php endif; ?>
                </p>
                <p><strong>Total Diajukan:</strong> 
                    <span class="badge bg-primary">
                        <?= number_format($total_qty_diajukan, 0, ',', '.') ?> unit
                    </span>
                </p>
                <p><strong>Total Disetujui:</strong> 
                    <span class="badge bg-<?= $total_qty_disetujui > 0 ? 'success' : 'secondary' ?>">
                        <?= number_format($total_qty_disetujui, 0, ',', '.') ?> unit
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- TAMPILAN UNTUK SEMUA ROLE (termasuk toko) -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="fas fa-boxes me-2"></i>Daftar Item Barang
    </h3>
    <?php if ($pengiriman_barang['status'] !== 'SELESAI' && $_SESSION['role'] === 'toko'): ?>
    <a href="edit.php?id=<?= $id ?>" class="btn btn-primary">
        <i class="fas fa-edit me-1"></i>Edit Pengajuan Barang
    </a>
    <?php elseif ($pengiriman_barang['status'] === 'SELESAI'): ?>
    <span class="badge bg-secondary">
        <i class="fas fa-lock me-1"></i>Pengiriman Selesai - Tidak dapat diedit
    </span>
    <?php endif; ?>
</div>

<?php 
// Reset pointer query untuk tampilan
mysqli_data_seek($barang_diajukan_query, 0);
if (mysqli_num_rows($barang_diajukan_query) > 0): 
?>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Nama Barang</th>
                <th class="text-center">Stok Gudang</th>
                <th class="text-center">Qty Diajukan</th>
                <th class="text-center">Qty Disetujui</th>
                <th class="text-center">Status</th>
                <th class="text-end">Harga Satuan</th>
                <th class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $total_diajukan = 0;
            $total_disetujui = 0;
            $total_subtotal = 0;
            
            while ($barang = mysqli_fetch_assoc($barang_diajukan_query)): 
                $qty_diajukan = $barang['qty_diajukan'] ?? 0;
                $qty_disetujui = $barang['qty_disetujui'] ?? 0;
                $stok_gudang = $barang['stok_gudang'] ?? 0;
                $status = $barang['status'] ?? 'menunggu';
                $subtotal = $barang['subtotal'] ?? 0;
                
                $total_diajukan += $qty_diajukan;
                $total_disetujui += $qty_disetujui;
                $total_subtotal += $subtotal;
                
                $status_class = [
                    'menunggu' => 'warning',
                    'disetujui' => 'success',
                    'ditolak' => 'danger'
                ][$status] ?? 'secondary';
                
                $status_text = [
                    'menunggu' => 'Menunggu',
                    'disetujui' => 'Disetujui',
                    'ditolak' => 'Ditolak (Stok: ' . $stok_gudang . ')'
                ][$status] ?? ucfirst($status);
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td>
                    <strong><?= htmlspecialchars($barang['nama_barang']) ?></strong>
                    <?php if (!empty($barang['kode_barang'])): ?>
                    <br><small class="text-muted">Kode: <?= htmlspecialchars($barang['kode_barang']) ?></small>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <span class="badge bg-<?= $stok_gudang >= $qty_diajukan ? 'info' : 'danger' ?>">
                        <?= number_format($stok_gudang, 0, ',', '.') ?> unit
                    </span>
                </td>
                <td class="text-center">
                    <span class="badge bg-primary">
                        <?= number_format($qty_diajukan, 0, ',', '.') ?> unit
                    </span>
                </td>
                <td class="text-center">
                    <span class="badge bg-<?= $qty_disetujui > 0 ? 'success' : ($status === 'ditolak' ? 'danger' : 'secondary') ?>">
                        <?= number_format($qty_disetujui, 0, ',', '.') ?> unit
                    </span>
                </td>
                <td class="text-center">
                    <span class="badge bg-<?= $status_class ?>">
                        <?= $status_text ?>
                    </span>
                </td>
                <td class="text-end">Rp <?= number_format((float)($barang['harga'] ?? 0), 0, ',', '.') ?></td>
                <td class="text-end fw-semibold">Rp <?= number_format((float)$subtotal, 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
            <!-- Total Row -->
            <tr class="table-active">
                <td colspan="3" class="text-end fw-bold">Total:</td>
                <td class="text-center fw-bold">
                    <span class="badge bg-primary fs-6">
                        <?= number_format($total_diajukan, 0, ',', '.') ?> unit
                    </span>
                </td>
                <td class="text-center fw-bold">
                    <span class="badge bg-success fs-6">
                        <?= number_format($total_disetujui, 0, ',', '.') ?> unit
                    </span>
                </td>
                <td class="text-center fw-bold">-</td>
                <td class="text-end fw-bold">Total Nilai:</td>
                <td class="text-end fw-bold">Rp <?= number_format($total_subtotal, 0, ',', '.') ?></td>
            </tr>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>Belum ada data detail barang. 
    <?php if ($pengiriman_barang['status'] !== 'SELESAI' && $_SESSION['role'] === 'toko'): ?>
    Klik <a href="edit.php?id=<?= $id ?>" class="alert-link">Edit Pengajuan</a> untuk menambahkan barang.
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="row mt-4">
    <div class="col-md-6">
        <!-- SUMMARY BOX -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Ringkasan Pengiriman
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="text-center p-3 border rounded bg-light">
                            <h6 class="text-muted">Total Diajukan</h6>
                            <h3 class="text-primary mb-0"><?= number_format($total_qty_diajukan, 0, ',', '.') ?></h3>
                            <small class="text-muted">unit</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-3 border rounded bg-light">
                            <h6 class="text-muted">Total Disetujui</h6>
                            <h3 class="text-success mb-0"><?= number_format($total_qty_disetujui, 0, ',', '.') ?></h3>
                            <small class="text-muted">unit</small>
                        </div>
                    </div>
                </div>
                
                <?php 
                $selisih = $total_qty_diajukan - $total_qty_disetujui;
                $persentase = $total_qty_diajukan > 0 ? round(($total_qty_disetujui / $total_qty_diajukan) * 100, 1) : 0;
                $persentase_class = $persentase >= 80 ? 'success' : ($persentase >= 50 ? 'warning' : 'danger');
                ?>
                
                <div class="mt-3 text-center">
                    <p class="mb-1">
                        <strong>Persetujuan:</strong> 
                        <span class="text-<?= $persentase_class ?>">
                            <?= $persentase ?>%
                        </span>
                    </p>
                    <p class="mb-1">
                        <strong>Belum/Tidak Disetujui:</strong> 
                        <span class="text-warning">
                            <?= number_format($selisih, 0, ',', '.') ?> unit
                        </span>
                    </p>
                    <?php if ($selisih > 0): ?>
                    <small class="text-muted">
                        <?= number_format($selisih, 0, ',', '.') ?> unit barang belum/tidak disetujui
                    </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-secondary flex-fill">
                <i class="fas fa-arrow-left me-1"></i>Kembali ke Daftar
            </a>
            
            <?php if ($pengiriman_barang['status'] !== 'SELESAI' && $_SESSION['role'] === 'toko'): ?>
            <a href="edit.php?id=<?= $id ?>" class="btn btn-primary flex-fill">
                <i class="fas fa-edit me-1"></i>Edit Pengajuan
            </a>
            <?php endif; ?>
        </div>
        
        <div class="mt-3">
            <div class="alert alert-light border">
                <h6 class="alert-heading">
                    <i class="fas fa-user-tag me-2"></i>Hak Akses Anda
                </h6>
                <p class="mb-1">
                    <strong>Role:</strong> 
                    <span class="badge bg-<?= $_SESSION['role'] === 'admin' ? 'danger' : ($_SESSION['role'] === 'gudang' ? 'primary' : 'success') ?>">
                        <?= htmlspecialchars($_SESSION['role']) ?>
                    </span>
                </p>
                <ul class="mb-0">
                    <?php if ($_SESSION['role'] === 'toko'): ?>
                    <li>✓ Dapat melihat status pengajuan</li>
                    <li>✓ Dapat mengedit pengajuan (jika belum selesai)</li>
                    <li>✗ Tidak dapat verifikasi barang</li>
                    <li>✗ Tidak dapat mengubah status</li>
                    <?php elseif ($_SESSION['role'] === 'gudang'): ?>
                    <li>✓ Dapat melihat semua data pengajuan</li>
                    <li>✓ Dapat klik "Verifikasi Otomatis" untuk check stok</li>
                    <li>✓ Dapat mengubah status pengiriman</li>
                    <li>✓ Stok gudang akan otomatis berkurang</li>
                    <?php else: ?>
                    <li>✓ Dapat melihat semua data</li>
                    <li>✓ Dapat mengubah status pengiriman</li>
                    <li>✓ Dapat mengedit data pengiriman</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Konfirmasi perubahan status HANYA saat tombol update diklik
function confirmStatusChange(newStatus) {
    const currentStatus = "<?= $pengiriman_barang['status'] ?>";
    
    if (newStatus === currentStatus) {
        return true;
    }
    
    let confirmationMessage = `Apakah Anda yakin ingin mengubah status dari "${currentStatus}" ke "${newStatus}"?`;
    
    // Tambahkan pesan khusus berdasarkan status tujuan
    switch(newStatus) {
        case 'Diproses':
            confirmationMessage += '\n\n📋 Pengiriman akan diproses oleh gudang.';
            break;
        case 'Dikirim':
            confirmationMessage += '\n\n🚚 Pengiriman akan dikirim ke tujuan.';
            break;
        case 'SELESAI':
            confirmationMessage += '\n\n✅ Pengiriman akan diselesaikan dan tidak dapat diubah lagi.';
            break;
        case 'Dibatalkan':
            confirmationMessage += '\n\n❌ Pengiriman akan dibatalkan.';
            break;
    }
    
    if (newStatus === 'SELESAI') {
        confirmationMessage += '\n\n⚠️ PERINGATAN: Setelah diubah menjadi SELESAI, status tidak dapat diubah kembali!';
    }
    
    return confirm(confirmationMessage);
}

// Konfirmasi verifikasi otomatis
function confirmVerifikasiOtomatis() {
    const confirmationMessage = `Apakah Anda yakin ingin melakukan VERIFIKASI OTOMATIS?\n\nSistem akan:\n✅ Menyetujui barang yang stoknya CUKUP\n❌ Menolak barang yang stoknya TIDAK CUKUP\n📉 Stok gudang akan otomatis dikurangi\n📊 Hasil akan ditampilkan setelah proses selesai`;
    
    return confirm(confirmationMessage);
}

// Event listener untuk form status - HANYA trigger saat tombol diklik
document.getElementById('form-update-status')?.addEventListener('submit', function(e) {
    const statusSelect = this.querySelector('select[name="status"]');
    const newStatus = statusSelect.value;
    const currentStatus = "<?= $pengiriman_barang['status'] ?>";
    
    // Cegah submit jika status tidak berubah
    if (newStatus === currentStatus) {
        e.preventDefault();
        alert('Status tidak berubah. Pilih status yang berbeda untuk melakukan update.');
        return false;
    }
    
    // Tampilkan konfirmasi hanya saat form akan disubmit (tombol diklik)
    if (!confirmStatusChange(newStatus)) {
        e.preventDefault();
        // Reset ke status sebelumnya jika dibatalkan
        statusSelect.value = currentStatus;
        return false;
    }
    
    return true;
});
</script>

<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>