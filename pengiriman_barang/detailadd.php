<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.use_strict_mode', 1);
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';
requireAuth();

// Cek khusus untuk halaman detailadd.php - role 'toko' tidak bisa akses
requirePengirimanPageAccess('detailadd.php');

requireModuleAccess('pengiriman_barang');
require_once '../config/database.php';

// Parameter GET 'pengiriman_id'
$master_id = (int)($_GET['pengiriman_id'] ?? 0);
if (!$master_id) redirect('index.php');

$error = '';

// Validasi apakah pengiriman master ada dan belum selesai
$master_check = mysqli_query($connection, "SELECT * FROM `pengiriman_barang` WHERE id = $master_id");
if (mysqli_num_rows($master_check) == 0) {
    redirect('index.php');
}
$master_data = mysqli_fetch_assoc($master_check);

// Cek status master, jika sudah SELESAI tidak boleh tambah item
if ($master_data['status'] === 'SELESAI') {
    $error = "Pengiriman ini sudah selesai, tidak dapat menambahkan item.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) die('Invalid CSRF token.');
    
    // Ambil data dari POST
    $barang_id = (int)($_POST['barang_id'] ?? 0);
    $qty = (float)($_POST['qty'] ?? 0);
    
    // Validasi input
    if ($barang_id <= 0) {
        $error = "Barang Id wajib dipilih.";
    } elseif ($qty <= 0) {
        $error = "Qty harus lebih dari 0.";
    }
    
    if (!$error) {
        // Mulai transaksi
        mysqli_begin_transaction($connection);
        
        try {
            // Ambil data barang dengan stok
            $barang_query = mysqli_query($connection, 
                "SELECT harga, nama_barang, stok FROM barang WHERE id = $barang_id FOR UPDATE");
            
            if (mysqli_num_rows($barang_query) == 0) {
                throw new Exception("Barang tidak ditemukan.");
            }
            
            $barang_data = mysqli_fetch_assoc($barang_query);
            $harga = (float)$barang_data['harga'];
            $stok_tersedia = (float)$barang_data['stok'];
            $nama_barang = $barang_data['nama_barang'];
            
            // Cek stok tersedia
            if ($stok_tersedia < $qty) {
                throw new Exception("Stok '$nama_barang' tidak mencukupi. Stok tersedia: $stok_tersedia, Qty diminta: $qty");
            }
            
            $subtotal = $qty * $harga;
            
            // Check for duplicate item in this pengiriman
            $check_stmt = mysqli_prepare($connection, 
                "SELECT id FROM `pengiriman_detail` WHERE `pengiriman_id` = ? AND `barang_id` = ?");
            mysqli_stmt_bind_param($check_stmt, "ii", $master_id, $barang_id);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
            
            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                throw new Exception("Barang '$nama_barang' sudah ditambahkan ke pengiriman ini.");
            }
            mysqli_stmt_close($check_stmt);
            
            // Kurangi stok barang
            $new_stok = $stok_tersedia - $qty;
            $update_stok_stmt = mysqli_prepare($connection,
                "UPDATE barang SET stok = ? WHERE id = ?");
            mysqli_stmt_bind_param($update_stok_stmt, "di", $new_stok, $barang_id);
            
            if (!mysqli_stmt_execute($update_stok_stmt)) {
                throw new Exception("Gagal mengurangi stok barang.");
            }
            mysqli_stmt_close($update_stok_stmt);
            
            // Insert ke pengiriman_detail
            $stmt = mysqli_prepare($connection, 
                "INSERT INTO `pengiriman_detail` (`pengiriman_id`, `barang_id`, `qty`, `harga`, `subtotal`) 
                 VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iiddd", $master_id, $barang_id, $qty, $harga, $subtotal);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Gagal menyimpan item: " . mysqli_error($connection));
            }
            mysqli_stmt_close($stmt);
            
            // Commit transaksi jika semua berhasil
            mysqli_commit($connection);
            
            $_SESSION['success_message'] = "Item '$nama_barang' berhasil ditambahkan. Stok berkurang $qty unit.";
            redirect("/pengiriman/pengiriman_barang/detail.php?id=$master_id");
            exit();
            
        } catch (Exception $e) {
            // Rollback jika ada error
            mysqli_rollback($connection);
            $error = $e->getMessage();
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>

<div class="card">
    <div class="card-header">
        <h4 class="mb-0">
            <i class="fas fa-cube me-2"></i>Tambah Item ke Pengiriman Barang #<?= $master_id ?>
        </h4>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success_message']) ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if ($master_data['status'] === 'SELESAI'): ?>
            <div class="alert alert-warning">
                <i class="fas fa-info-circle me-2"></i>Pengiriman ini sudah selesai, tidak dapat menambahkan item.
                <div class="mt-2">
                    <a href="detail.php?id=<?= $master_id ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Kembali ke Detail
                    </a>
                </div>
            </div>
        <?php else: ?>
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Barang*</label>
                        <?php
                        // Ambil barang yang stoknya > 0
                        echo dropdownFromQuery(
                            "SELECT id, CONCAT(nama_barang, ' (Stok: ', stok, ')') as label 
                             FROM barang 
                             WHERE stok > 0 
                             ORDER BY nama_barang",
                            '',                     // selected
                            'barang_id',            // HTML name
                            '-- Pilih Barang --'    // placeholder
                        ); 
                        ?>
                        <div class="form-text">Hanya barang dengan stok tersedia yang ditampilkan</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Quantity (Qty)*</label>
                        <div class="input-group">
                            <input type="number" name="qty" class="form-control" min="1" step="1" value="1" required>
                            <span class="input-group-text">unit</span>
                        </div>
                        <div class="invalid-feedback">Harap isi quantity dengan angka positif</div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="alert alert-info">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fas fa-info-circle fa-2x"></i>
                            </div>
                            <div>
                                <strong>Informasi:</strong>
                                <ul class="mb-0 mt-1">
                                    <li>Harga akan otomatis diambil dari data barang yang dipilih</li>
                                    <li>Subtotal = Harga × Quantity</li>
                                    <li>Stok barang akan otomatis berkurang sesuai jumlah yang dimasukkan</li>
                                    <li>Hanya barang dengan stok tersedia yang dapat dipilih</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i>Tambah Item
                    </button>
                    <a href="detail.php?id=<?= $master_id ?>" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i>Batal
                    </a>
                </div>
            </form>
            
            <!-- Script untuk validasi form -->
            <script>
            (function() {
                'use strict';
                var forms = document.querySelectorAll('.needs-validation');
                Array.prototype.slice.call(forms).forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            })();
            
            // Menampilkan info stok ketika barang dipilih
            document.addEventListener('DOMContentLoaded', function() {
                const barangSelect = document.querySelector('select[name="barang_id"]');
                const qtyInput = document.querySelector('input[name="qty"]');
                
                if (barangSelect) {
                    barangSelect.addEventListener('change', function() {
                        const selectedOption = this.options[this.selectedIndex];
                        if (selectedOption.value) {
                            // Ekstrak info stok dari teks option
                            const optionText = selectedOption.text;
                            const stokMatch = optionText.match(/Stok:\s*(\d+(\.\d+)?)/);
                            if (stokMatch) {
                                const stok = parseFloat(stokMatch[1]);
                                // Set max value pada qty input
                                qtyInput.max = stok;
                                qtyInput.title = `Stok tersedia: ${stok} unit`;
                            }
                        }
                    });
                }
            });
            </script>
        <?php endif; ?>
    </div>
</div>

<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>