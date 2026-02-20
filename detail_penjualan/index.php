<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('detail_penjualan');

require_once '../config/database.php';

$result = mysqli_query($connection, "SELECT * FROM `detail_penjualan` ORDER BY id DESC");
?>

<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Daftar Detail Penjualan</h2>
                <a href="add.php" class="btn btn-primary">+ Tambah Detail Penjualan</a>
            </div>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Penjualan Id</th>
                                <th>Barang Id</th>
                                <th>Qty</th>
                                <th>Harga</th>
                                <th>Subtotal</th>
                                <th>Created At</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['penjualan_id']) ?></td>
                                    <td><?= htmlspecialchars($row['barang_id']) ?></td>
                                    <td><?= htmlspecialchars($row['qty']) ?></td>
                                    <td><?= htmlspecialchars($row['harga']) ?></td>
                                    <td><?= htmlspecialchars($row['subtotal']) ?></td>
                                    <td><?= $row['created_at'] ? date('d-m-Y', strtotime($row['created_at'])) : '-' ?></td>
                                    <td>
                                        <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus detail penjualan ini?')">Hapus</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">Belum ada data detail penjualan.</div>
            <?php endif; ?>


<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>
