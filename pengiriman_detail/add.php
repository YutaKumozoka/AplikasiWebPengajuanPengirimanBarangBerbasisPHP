<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pengiriman_detail');

require_once '../config/database.php';

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pengiriman_id_post = trim($_POST['pengiriman_id'] ?? '');
    $barang_id_post = trim($_POST['barang_id'] ?? '');
    $qty_post = trim($_POST['qty'] ?? '');
    $harga_post = trim($_POST['harga'] ?? '');
    $subtotal_post = trim($_POST['subtotal'] ?? '');
    if (empty($pengiriman_id_post) || empty($barang_id_post) || empty($harga_post) || empty($subtotal_post)) {
        $error = "Pengiriman Id dan Barang Id dan Harga dan Subtotal wajib diisi.";
    }
    if (!$error) {
        $stmt = mysqli_prepare($connection, "INSERT INTO `pengiriman_detail` (pengiriman_id, barang_id, qty, harga, subtotal) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiidd", $pengiriman_id_post, $barang_id_post, $qty_post, $harga_post, $subtotal_post);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Pengiriman Detail berhasil ditambahkan.";
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


            <h2>Tambah Pengiriman Detail</h2>
            <?php if ($error): ?>
                <?= showAlert($error, 'danger') ?>
            <?php endif; ?>
            <?php if ($success): ?>
                <?= showAlert($success, 'success') ?>
                <a href="index.php" class="btn btn-secondary">Kembali ke Daftar</a>
            <?php else: ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Pengiriman Id*</label>
                        <input type="number" name="pengiriman_id" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Barang Id*</label>
                        <input type="number" name="barang_id" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Qty</label>
                        <input type="number" name="qty" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga*</label>
                        <input type="number" step="any" name="harga" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subtotal*</label>
                        <input type="number" step="any" name="subtotal" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                </form>
            <?php endif; ?>

<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>
