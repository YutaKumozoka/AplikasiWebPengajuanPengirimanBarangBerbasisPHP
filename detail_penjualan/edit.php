<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('detail_penjualan');

require_once '../config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) redirect('index.php');

$stmt = mysqli_prepare($connection, "SELECT id, penjualan_id, barang_id, qty, harga, subtotal, created_at FROM `detail_penjualan` WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$detail_penjualan = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$detail_penjualan) {
    redirect('index.php');
}

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
        $stmt = mysqli_prepare($connection, "UPDATE `detail_penjualan` SET `penjualan_id` = ?, `barang_id` = ?, `qty` = ?, `harga` = ?, `subtotal` = ?, `created_at` = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "iiiddsi", $penjualan_id_post, $barang_id_post, $qty_post, $harga_post, $subtotal_post, $created_at_post, $id);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Detail Penjualan berhasil diperbarui.";
            mysqli_stmt_close($stmt);
            $stmt = mysqli_prepare($connection, "SELECT id, penjualan_id, barang_id, qty, harga, subtotal, created_at FROM `detail_penjualan` WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $detail_penjualan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'index.php';
                }, 2000);
            </script>";
        } else {
            $error = "Gagal memperbarui: " . mysqli_error($connection);
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>

            <h2>Edit Detail Penjualan</h2>
            <?php if ($error): ?>
                <?= showAlert($error, 'danger') ?>
            <?php endif; ?>
            <?php if ($success): ?>
                <?= showAlert($success, 'success') ?>
            <?php endif; ?>
            <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Penjualan Id*</label>
                        <input type="number" name="penjualan_id" class="form-control" value="<?= $detail_penjualan['penjualan_id'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Barang Id*</label>
                        <input type="number" name="barang_id" class="form-control" value="<?= $detail_penjualan['barang_id'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Qty*</label>
                        <input type="number" name="qty" class="form-control" value="<?= $detail_penjualan['qty'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga*</label>
                        <input type="text" name="harga" class="form-control" value="<?= htmlspecialchars($detail_penjualan['harga']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subtotal*</label>
                        <input type="text" name="subtotal" class="form-control" value="<?= htmlspecialchars($detail_penjualan['subtotal']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Created At</label>
                        <input type="datetime-local" name="created_at" class="form-control" value="<?= $detail_penjualan['created_at'] ? date('Y-m-d\TH:i', strtotime($detail_penjualan['created_at'])) : '' ?>">
                    </div>
                <button type="submit" class="btn btn-primary">Perbarui</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>


<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>
