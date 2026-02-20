<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pengiriman_detail');

require_once '../config/database.php';

$result = mysqli_query($connection, "SELECT * FROM `pengiriman_detail` ORDER BY id DESC");
?>

<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Daftar Pengiriman Detail</h2>
                <a href="add.php" class="btn btn-primary">+ Tambah Pengiriman Detail</a>
            </div>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pengiriman Id</th>
                                <th>Barang Id</th>
                                <th>Qty</th>
                                <th>Harga</th>
                                <th>Subtotal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['pengiriman_id']) ?></td>
                                    <td><?= htmlspecialchars($row['barang_id']) ?></td>
                                    <td><?= htmlspecialchars($row['qty']) ?></td>
                                    <td><?= htmlspecialchars($row['harga']) ?></td>
                                    <td><?= htmlspecialchars($row['subtotal']) ?></td>
                                    <td>
                                        <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus pengiriman detail ini?')">Hapus</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">Belum ada data pengiriman detail.</div>
            <?php endif; ?>


<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>
