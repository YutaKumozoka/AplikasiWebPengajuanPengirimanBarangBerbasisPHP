<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pengiriman_barang');

require_once '../config/database.php';

// Definisikan konstanta untuk direktori upload
define('UPLOAD_DIR_PENGIRIMAN', '../uploads/pengiriman/');

// Query untuk mendapatkan data pengiriman dengan info verifikasi
$query = "SELECT 
    pb.*,
    (SELECT COUNT(*) FROM pengiriman_detail WHERE pengiriman_id = pb.id AND status = 'menunggu') as menunggu_verifikasi,
    (SELECT COUNT(*) FROM pengiriman_detail WHERE pengiriman_id = pb.id AND status = 'disetujui') as disetujui,
    (SELECT COUNT(*) FROM pengiriman_detail WHERE pengiriman_id = pb.id AND status = 'ditolak') as ditolak,
    (SELECT COUNT(*) FROM pengiriman_detail WHERE pengiriman_id = pb.id) as total_barang
FROM pengiriman_barang pb
ORDER BY pb.created_at DESC";

$result = mysqli_query($connection, $query);
?>

<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Daftar Pengiriman Barang</h2>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Tambah Pengiriman
    </a>
</div>

<?php if (mysqli_num_rows($result) > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>No. Pengajuan</th>
                    <th>Pengirim</th>
                    <th>Penerima</th>
                    <th>Tujuan</th>
                    <th>Jenis Barang</th>
                    <th>Status Pengiriman</th>
                    <?php if ($_SESSION['role'] === 'gudang' || $_SESSION['role'] === 'admin'): ?>
                    <th class="text-center">Status Verifikasi</th>
                    <?php endif; ?>
                    <th class="text-center">Foto Barang</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    // Hitung persentase verifikasi
                    $total_barang = $row['total_barang'] ?? 0;
                    $disetujui = $row['disetujui'] ?? 0;
                    $menunggu = $row['menunggu_verifikasi'] ?? 0;
                    $ditolak = $row['ditolak'] ?? 0;
                    
                    $persentase = $total_barang > 0 ? round(($disetujui / $total_barang) * 100) : 0;
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['no_pengajuan']) ?></td>
                        <td><?= htmlspecialchars($row['nama_pengirim']) ?></td>
                        <td><?= htmlspecialchars($row['nama_penerima']) ?></td>
                        <td>
                            <?= strlen($row['alamat_tujuan']) > 30 
                                ? substr(htmlspecialchars($row['alamat_tujuan']), 0, 30) . '...' 
                                : htmlspecialchars($row['alamat_tujuan']) ?>
                        </td>
                        <td>
                            <?php if (empty($row['jenis_barang']) || $row['jenis_barang'] == '0'): ?>
                                <span class="text-muted fst-italic">Belum ada item</span>
                            <?php else: ?>
                                <?= htmlspecialchars($row['jenis_barang']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $status_class = '';
                            switch($row['status']) {
                                case 'Menunggu': $status_class = 'badge bg-warning'; break;
                                case 'Diproses': $status_class = 'badge bg-info'; break;
                                case 'Dikirim': $status_class = 'badge bg-primary'; break;
                                case 'SELESAI': $status_class = 'badge bg-success'; break;
                                case 'Dibatalkan': $status_class = 'badge bg-danger'; break;
                                default: $status_class = 'badge bg-secondary';
                            }
                            ?>
                            <span class="<?= $status_class ?>"><?= $row['status'] ?></span>
                        </td>
                        
                        <?php if ($_SESSION['role'] === 'gudang' || $_SESSION['role'] === 'admin'): ?>
                        <td class="text-center">
                            <?php if ($total_barang > 0): ?>
                                <div class="d-flex flex-column align-items-center">
                                    <!-- Progress Bar -->
                                    <div class="progress w-100 mb-1" style="height: 10px;">
                                        <div class="progress-bar bg-success" 
                                             role="progressbar" 
                                             style="width: <?= $persentase ?>%;"
                                             aria-valuenow="<?= $persentase ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                        <div class="progress-bar bg-warning" 
                                             role="progressbar" 
                                             style="width: <?= $menunggu > 0 ? round(($menunggu / $total_barang) * 100) : 0 ?>%;"
                                             aria-valuenow="<?= $menunggu ?>">
                                        </div>
                                        <div class="progress-bar bg-danger" 
                                             role="progressbar" 
                                             style="width: <?= $ditolak > 0 ? round(($ditolak / $total_barang) * 100) : 0 ?>%;"
                                             aria-valuenow="<?= $ditolak ?>">
                                        </div>
                                    </div>
                                    
                                    <!-- Badge Status -->
                                    <div class="d-flex gap-1">
                                        <span class="badge bg-success" title="Disetujui">
                                            <i class="fas fa-check"></i> <?= $disetujui ?>
                                        </span>
                                        <span class="badge bg-warning" title="Menunggu">
                                            <i class="fas fa-clock"></i> <?= $menunggu ?>
                                        </span>
                                        <span class="badge bg-danger" title="Ditolak">
                                            <i class="fas fa-times"></i> <?= $ditolak ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Text Status -->
                                    <small class="text-muted">
                                        <?php if ($menunggu > 0): ?>
                                            <span class="text-warning">Perlu verifikasi</span>
                                        <?php elseif ($disetujui == $total_barang): ?>
                                            <span class="text-success">Semua disetujui</span>
                                        <?php elseif ($ditolak > 0): ?>
                                            <span class="text-danger">Ada yang ditolak</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-secondary">Belum ada barang</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        
                        <td class="text-center">
                            <?php if ($row['foto_barang'] && file_exists(UPLOAD_DIR_PENGIRIMAN . $row['foto_barang'])): ?>
                                <a href="<?= UPLOAD_DIR_PENGIRIMAN . $row['foto_barang'] ?>" target="_blank" 
                                   title="Lihat Foto">
                                    <img src="<?= UPLOAD_DIR_PENGIRIMAN . $row['foto_barang'] ?>" alt="Foto Barang" 
                                         class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <!-- TOMBOL DETAIL -->
                                <a href="detail.php?id=<?= $row['id'] ?>" 
                                   class="btn btn-info" 
                                   title="Detail Pengiriman">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <!-- TOMBOL EDIT - Hanya untuk toko jika belum diverifikasi -->
                                <?php if ($_SESSION['role'] === 'toko' && $row['status'] !== 'SELESAI' && $menunggu > 0): ?>
                                <a href="edit.php?id=<?= $row['id'] ?>" 
                                   class="btn btn-warning" 
                                   title="Edit Data">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php elseif ($_SESSION['role'] !== 'toko' && $row['status'] !== 'SELESAI'): ?>
                                <a href="edit.php?id=<?= $row['id'] ?>" 
                                   class="btn btn-warning" 
                                   title="Edit Data">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php else: ?>
                                <button class="btn btn-secondary" 
                                        title="Tidak dapat diedit"
                                        disabled>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
                                
                                <!-- TOMBOL HAPUS -->
                                <?php if ($row['status'] !== 'SELESAI'): ?>
                                <a href="delete.php?id=<?= $row['id'] ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Yakin hapus data pengiriman ini?')"
                                   title="Hapus Data">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php else: ?>
                                <button class="btn btn-secondary" 
                                        title="Pengiriman selesai tidak dapat dihapus"
                                        disabled>
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Belum ada data pengiriman barang.
    </div>
<?php endif; ?>

<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>