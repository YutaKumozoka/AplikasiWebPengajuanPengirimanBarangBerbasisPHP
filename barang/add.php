<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('barang');

require_once '../config/database.php';

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
        $stmt = mysqli_prepare($connection, "INSERT INTO `barang` (kode_barang, nama_barang, harga, stok, created_at) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssdis", $kode_barang_post, $nama_barang_post, $harga_post, $stok_post, $created_at_post);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Barang berhasil ditambahkan.";
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


            <h2>Tambah Barang</h2>
            <?php if ($error): ?>
                <?= showAlert($error, 'danger') ?>
            <?php endif; ?>
            <?php if ($success): ?>
                <?= showAlert($success, 'success') ?>
                <a href="index.php" class="btn btn-secondary">Kembali ke Daftar</a>
            <?php else: ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Kode Barang*</label>
                        <input type="text" name="kode_barang" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Barang*</label>
                        <input type="text" name="nama_barang" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga*</label>
                        <input type="number" step="any" name="harga" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stok" class="form-control">
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
