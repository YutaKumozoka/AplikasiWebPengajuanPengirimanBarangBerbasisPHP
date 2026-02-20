<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('barang');

require_once '../config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) redirect('index.php');

$stmt = mysqli_prepare($connection, "SELECT id, kode_barang, nama_barang, harga, stok, created_at FROM `barang` WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$barang = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$barang) {
    redirect('index.php');
}

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_barang_post = trim($_POST['kode_barang'] ?? '');
    $nama_barang_post = trim($_POST['nama_barang'] ?? '');
    $harga_post = trim($_POST['harga'] ?? '');
    $stok_post = trim($_POST['stok'] ?? '');
    $created_at_post = trim($_POST['created_at'] ?? '');
    if (empty($kode_barang_post) || empty($nama_barang_post) || empty($harga_post)) {
        $error = "Kode Barang dan Nama Barang dan Harga wajib diisi.";
    }
    if (!$error) {
        $stmt = mysqli_prepare($connection, "UPDATE `barang` SET `kode_barang` = ?, `nama_barang` = ?, `harga` = ?, `stok` = ?, `created_at` = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssdisi", $kode_barang_post, $nama_barang_post, $harga_post, $stok_post, $created_at_post, $id);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Barang berhasil diperbarui.";
            mysqli_stmt_close($stmt);
            $stmt = mysqli_prepare($connection, "SELECT id, kode_barang, nama_barang, harga, stok, created_at FROM `barang` WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $barang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
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

            <h2>Edit Barang</h2>
            <?php if ($error): ?>
                <?= showAlert($error, 'danger') ?>
            <?php endif; ?>
            <?php if ($success): ?>
                <?= showAlert($success, 'success') ?>
            <?php endif; ?>
            <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Kode Barang*</label>
                        <input type="text" name="kode_barang" class="form-control" value="<?= htmlspecialchars($barang['kode_barang']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Barang*</label>
                        <input type="text" name="nama_barang" class="form-control" value="<?= htmlspecialchars($barang['nama_barang']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga*</label>
                        <input type="text" name="harga" class="form-control" value="<?= htmlspecialchars($barang['harga']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stok" class="form-control" value="<?= $barang['stok'] ?>">
                    </div>
                <button type="submit" class="btn btn-primary">Perbarui</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>


<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>
