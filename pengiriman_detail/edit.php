<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pengiriman_detail');

require_once '../config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) redirect('index.php');

$stmt = mysqli_prepare($connection, "SELECT id, pengiriman_id, barang_id, qty, harga, subtotal FROM `pengiriman_detail` WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pengiriman detail = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$pengiriman detail) {
    redirect('index.php');
}

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
        $stmt = mysqli_prepare($connection, "UPDATE `pengiriman_detail` SET `pengiriman_id` = ?, `barang_id` = ?, `qty` = ?, `harga` = ?, `subtotal` = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "iiiddi", $pengiriman_id_post, $barang_id_post, $qty_post, $harga_post, $subtotal_post, $id);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Pengiriman Detail berhasil diperbarui.";
            mysqli_stmt_close($stmt);
            $stmt = mysqli_prepare($connection, "SELECT id, pengiriman_id, barang_id, qty, harga, subtotal FROM `pengiriman_detail` WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $pengiriman detail = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
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

            <h2>Edit Pengiriman Detail</h2>
            <?php if ($error): ?>
                <?= showAlert($error, 'danger') ?>
            <?php endif; ?>
            <?php if ($success): ?>
                <?= showAlert($success, 'success') ?>
            <?php endif; ?>
            <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Pengiriman Id*</label>
                        <input type="number" name="pengiriman_id" class="form-control" value="<?= $pengiriman detail['pengiriman_id'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Barang Id*</label>
                        <input type="number" name="barang_id" class="form-control" value="<?= $pengiriman detail['barang_id'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Qty</label>
                        <input type="number" name="qty" class="form-control" value="<?= $pengiriman detail['qty'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga*</label>
                        <input type="text" name="harga" class="form-control" value="<?= htmlspecialchars($pengiriman detail['harga']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subtotal*</label>
                        <input type="text" name="subtotal" class="form-control" value="<?= htmlspecialchars($pengiriman detail['subtotal']) ?>">
                    </div>
                <button type="submit" class="btn btn-primary">Perbarui</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>


<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>
