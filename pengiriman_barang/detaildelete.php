<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.use_strict_mode', 1);
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';
requireAuth();

// Cek khusus untuk halaman detaildelete.php - role 'toko' tidak bisa akses
requirePengirimanPageAccess('detaildelete.php');

requireModuleAccess('pengiriman_barang');
require_once '../config/database.php';

// ... kode selanjutnya tetap sama ...

// Validasi parameter
if (!isset($_GET['id']) || !isset($_GET['master_id'])) {
    $_SESSION['error_message'] = "Parameter tidak valid.";
    redirect('index.php');
}

$id = (int)$_GET['id'];
$master_id = (int)$_GET['master_id'];

if ($id <= 0 || $master_id <= 0) {
    $_SESSION['error_message'] = "ID tidak valid.";
    redirect('index.php');
}

// Cek apakah item detail ada
$check_query = "SELECT d.*, b.nama_barang, b.stok 
                FROM pengiriman_detail d
                JOIN barang b ON d.barang_id = b.id
                WHERE d.id = $id AND d.pengiriman_id = $master_id";
$check_result = mysqli_query($connection, $check_query);

if (!$check_result) {
    $_SESSION['error_message'] = "Error: " . mysqli_error($connection);
    redirect("detail.php?id=$master_id");
}

if (mysqli_num_rows($check_result) == 0) {
    $_SESSION['error_message'] = "Item tidak ditemukan.";
    redirect("detail.php?id=$master_id");
}

$item_data = mysqli_fetch_assoc($check_result);

// Cek apakah pengiriman master sudah selesai
$master_check = mysqli_query($connection, 
    "SELECT status FROM pengiriman_barang WHERE id = $master_id");
if (!$master_check) {
    $_SESSION['error_message'] = "Error: " . mysqli_error($connection);
    redirect("detail.php?id=$master_id");
}

$master_data = mysqli_fetch_assoc($master_check);

if ($master_data['status'] === 'SELESAI') {
    $_SESSION['error_message'] = "Pengiriman sudah selesai, tidak dapat menghapus item.";
    redirect("detail.php?id=$master_id");
}

// PROSES FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Token CSRF tidak valid.";
        redirect("detail.php?id=$master_id");
    }
    
    // Mulai transaksi
    mysqli_begin_transaction($connection);
    
    try {
        // Kembalikan stok barang
        $qty_dikembalikan = (float)$item_data['qty'];
        $barang_id = (int)$item_data['barang_id'];
        $nama_barang = $item_data['nama_barang'];
        
        // Update stok barang (tambah kembali qty yang dihapus)
        $update_stok_stmt = mysqli_prepare($connection,
            "UPDATE barang SET stok = stok + ? WHERE id = ?");
        mysqli_stmt_bind_param($update_stok_stmt, "di", $qty_dikembalikan, $barang_id);
        
        if (!mysqli_stmt_execute($update_stok_stmt)) {
            throw new Exception("Gagal mengembalikan stok barang: " . mysqli_error($connection));
        }
        mysqli_stmt_close($update_stok_stmt);
        
        // Hapus item detail dari pengiriman
        $delete_query = "DELETE FROM pengiriman_detail WHERE id = ?";
        $stmt = mysqli_prepare($connection, $delete_query);
        mysqli_stmt_bind_param($stmt, 'i', $id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal menghapus item dari pengiriman: " . mysqli_error($connection));
        }
        mysqli_stmt_close($stmt);
        
        // Commit transaksi jika semua berhasil
        mysqli_commit($connection);
        
        $_SESSION['success_message'] = "Item '$nama_barang' berhasil dihapus. Stok dikembalikan $qty_dikembalikan unit.";
        redirect("/pengiriman/pengiriman_barang/detail.php?id=$master_id");
        
    } catch (Exception $e) {
        // Rollback jika ada error
        mysqli_rollback($connection);
        $_SESSION['error_message'] = $e->getMessage();
        redirect("/pengiriman/pengiriman_barang/detail.php?id=$master_id");
    }
    exit();
}

$csrfToken = generateCSRFToken();
?>
<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>

<div class="card">
    <div class="card-header bg-danger text-white">
        <h4 class="mb-0">
            <i class="fas fa-trash-alt me-2"></i>Konfirmasi Hapus Item
        </h4>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>PERINGATAN:</strong> Tindakan ini tidak dapat dibatalkan!
        </div>
        
        <div class="mb-4">
            <h5>Informasi Item:</h5>
            <table class="table table-bordered">
                <tr>
                    <th width="30%">Nama Barang</th>
                    <td><?= htmlspecialchars($item_data['nama_barang'] ?? 'Tidak ditemukan') ?></td>
                </tr>
                <tr>
                    <th>Quantity</th>
                    <td>
                        <?= number_format($item_data['qty'] ?? 0, 2) ?> unit
                        <span class="badge bg-info ms-2">
                            Stok akan dikembalikan: <?= number_format($item_data['qty'] ?? 0, 2) ?> unit
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Stok Saat Ini</th>
                    <td><?= number_format($item_data['stok'] ?? 0, 2) ?> unit</td>
                </tr>
                <tr>
                    <th>Stok Setelah Dikembalikan</th>
                    <td class="text-success fw-bold">
                        <?= number_format(($item_data['stok'] ?? 0) + ($item_data['qty'] ?? 0), 2) ?> unit
                    </td>
                </tr>
                <tr>
                    <th>Harga Satuan</th>
                    <td>Rp <?= number_format($item_data['harga'] ?? 0, 2, ',', '.') ?></td>
                </tr>
                <tr>
                    <th>Subtotal</th>
                    <td class="text-danger fw-bold">Rp <?= number_format($item_data['subtotal'] ?? 0, 2, ',', '.') ?></td>
                </tr>
            </table>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Perhatian:</strong> Saat menghapus item ini, stok barang akan dikembalikan ke sistem.
        </div>
        
        <form method="POST" action="detaildelete.php?id=<?= $id ?>&master_id=<?= $master_id ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div class="d-flex gap-2">
                <a href="detail.php?id=<?= $master_id ?>" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>Batal
                </a>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash-alt me-1"></i>Ya, Hapus Item & Kembalikan Stok
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>