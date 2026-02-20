<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('detail_penjualan');

require_once '../config/database.php';

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $penjualan_id_post = trim($_POST['penjualan_id'] ?? '');
    $barang_id_post = trim($_POST['barang_id'] ?? '');
    $qty_post = trim($_POST['qty'] ?? '');
    $harga_post = trim($_POST['harga'] ?? '');
    $subtotal_post = trim($_POST['subtotal'] ?? '');
    $created_at_post = trim($_POST['created_at'] ?? '');
    if (empty($penjualan_id_post) || empty($barang_id_post) || empty($qty_post) || empty($harga_post) || empty($subtotal_post)) {
        $error = "Penjualan Id dan Barang Id dan Qty dan Harga dan Subtotal wajib diisi.";
    }
    if (!$error) {
        $stmt = mysqli_prepare($connection, "INSERT INTO `detail_penjualan` (penjualan_id, barang_id, qty, harga, subtotal, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiidds", $penjualan_id_post, $barang_id_post, $qty_post, $harga_post, $subtotal_post, $created_at_post);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Detail Penjualan berhasil ditambahkan.";
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'index.php';
                }, 2000);
            </script>";
        } else {
            $error = "Gagal menyimpan: " . mysqli_error($connection);
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>


            <h2>Tambah Detail Penjualan</h2>
            <?php if ($error): ?>
                <?= showAlert($error, 'danger') ?>
            <?php endif; ?>
            <?php if ($success): ?>
                <?= showAlert($success, 'success') ?>
                <a href="index.php" class="btn btn-secondary">Kembali ke Daftar</a>
            <?php else: ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Penjualan Id*</label>
                        <input type="number" name="penjualan_id" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Barang Id*</label>
                        <input type="number" name="barang_id" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Qty*</label>
                        <input type="number" name="qty" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga*</label>
                        <input type="number" step="any" name="harga" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subtotal*</label>
                        <input type="number" step="any" name="subtotal" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Created At</label>
                        <input type="datetime-local" name="created_at" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                </form>
            <?php endif; ?>

<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>
