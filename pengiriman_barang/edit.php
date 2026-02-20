<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pengiriman');

require_once '../config/database.php';

define('UPLOAD_DIR_PENGIRIMAN', '../uploads/pengiriman/');

$id = (int) ($_GET['id'] ?? 0);
if (!$id) redirect('index.php');

// Ambil data pengiriman
$stmt = mysqli_prepare($connection, "SELECT * FROM `pengiriman_barang` WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pengiriman = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$pengiriman) {
    redirect('index.php');
}

// Cek apakah pengiriman bisa diedit
// Hanya bisa diedit jika status bukan SELESAI dan masih ada yang menunggu verifikasi
$check_verifikasi = mysqli_query($connection, 
    "SELECT COUNT(*) as total_menunggu FROM pengiriman_detail 
     WHERE pengiriman_id = $id AND status = 'menunggu'");
$verifikasi_data = mysqli_fetch_assoc($check_verifikasi);
$can_edit = ($pengiriman['status'] !== 'SELESAI' && $verifikasi_data['total_menunggu'] > 0);

if ($_SESSION['role'] === 'toko' && !$can_edit) {
    $_SESSION['error_message'] = "Pengiriman ini tidak dapat diedit karena sudah diverifikasi atau selesai.";
    redirect('index.php');
}

// Ambil data barang untuk combobox
$barang_query = mysqli_query($connection, "SELECT id, nama_barang, harga FROM barang ORDER BY nama_barang ASC");

// Ambil barang yang sudah diajukan
$barang_diajukan_query = mysqli_query($connection,
    "SELECT pd.*, b.nama_barang, b.harga 
     FROM pengiriman_detail pd
     JOIN barang b ON b.id = pd.barang_id
     WHERE pd.pengiriman_id = $id");

$error = $success = '';
$foto_filename = $pengiriman['foto_barang'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Hanya update data dasar jika bukan role toko
    if ($_SESSION['role'] !== 'toko') {
        $no_pengajuan_post = trim($_POST['no_pengajuan'] ?? '');
        $nama_pengirim_post = trim($_POST['nama_pengirim'] ?? '');
        $alamat_pengirim_post = trim($_POST['alamat_pengirim'] ?? '');
        $telepon_pengirim_post = trim($_POST['telepon_pengirim'] ?? '');
        $nama_penerima_post = trim($_POST['nama_penerima'] ?? '');
        $alamat_tujuan_post = trim($_POST['alamat_tujuan'] ?? '');
        $telepon_penerima_post = trim($_POST['telepon_penerima'] ?? '');
        $keterangan_post = trim($_POST['keterangan'] ?? '');
        $status_post = trim($_POST['status'] ?? 'Menunggu');
        $hapus_foto = isset($_POST['hapus_foto']) ? (int)$_POST['hapus_foto'] : 0;
        
        // Validasi dasar
        if (empty($no_pengajuan_post) || empty($nama_pengirim_post) || empty($nama_penerima_post) || empty($alamat_tujuan_post)) {
            $error = "No. Pengajuan, Nama Pengirim, Nama Penerima dan Alamat Tujuan wajib diisi.";
        }
        
        // Cek No. Pengajuan unik (kecuali untuk data ini sendiri)
        if (!$error) {
            $check_stmt = mysqli_prepare($connection, "SELECT id FROM `pengiriman_barang` WHERE no_pengajuan = ? AND id != ?");
            mysqli_stmt_bind_param($check_stmt, "si", $no_pengajuan_post, $id);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
            
            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $error = "No. Pengajuan sudah terdaftar oleh pengiriman lain.";
            }
            mysqli_stmt_close($check_stmt);
        }
        
        // Handle file upload
        if (isset($_FILES['foto_barang']) && $_FILES['foto_barang']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = handle_file_upload_pengiriman($_FILES['foto_barang']);
            
            if ($upload_result === false) {
                $error = "Gagal mengupload foto barang. Pastikan file adalah gambar (JPG, PNG, GIF, WebP) dan ukuran maksimal 2MB.";
            } elseif ($upload_result !== '') {
                if ($foto_filename && $foto_filename !== 'default.jpg' && file_exists(UPLOAD_DIR_PENGIRIMAN . $foto_filename)) {
                    unlink(UPLOAD_DIR_PENGIRIMAN . $foto_filename);
                }
                $foto_filename = $upload_result;
            }
        } elseif ($hapus_foto) {
            if ($foto_filename && $foto_filename !== 'default.jpg' && file_exists(UPLOAD_DIR_PENGIRIMAN . $foto_filename)) {
                unlink(UPLOAD_DIR_PENGIRIMAN . $foto_filename);
            }
            $foto_filename = 'default.jpg';
        }
        
        if (!$error) {
            $stmt = mysqli_prepare($connection, "UPDATE `pengiriman_barang` SET 
                `no_pengajuan` = ?, `nama_pengirim` = ?, `alamat_pengirim` = ?, `telepon_pengirim` = ?,
                `nama_penerima` = ?, `alamat_tujuan` = ?, `telepon_penerima` = ?,
                `keterangan` = ?, `status` = ?, `foto_barang` = ? WHERE id = ?");
            
            mysqli_stmt_bind_param($stmt, "ssssssssssi", 
                $no_pengajuan_post, $nama_pengirim_post, $alamat_pengirim_post, $telepon_pengirim_post,
                $nama_penerima_post, $alamat_tujuan_post, $telepon_penerima_post,
                $keterangan_post, $status_post, $foto_filename, $id);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Data pengiriman barang berhasil diperbarui.";
                mysqli_stmt_close($stmt);
                
                // Refresh data
                $stmt = mysqli_prepare($connection, "SELECT * FROM `pengiriman_barang` WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $pengiriman = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                
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
    } else {
        // Untuk role toko: update barang yang diajukan
        $barang_ids = $_POST['barang_id'] ?? [];
        $qty_diajukans = $_POST['qty_diajukan'] ?? [];
        $detail_ids = $_POST['detail_id'] ?? [];
        
        $total_qty_diajukan = 0;
        $barang_dipilih = [];
        
        // Validasi
        if (empty($barang_ids) || !is_array($barang_ids)) {
            $error = "Minimal satu barang harus dipilih.";
        }
        
        if (!$error) {
            // Mulai transaksi
            mysqli_begin_transaction($connection);
            
            try {
                // Hapus semua barang lama yang masih menunggu
                $delete_stmt = mysqli_prepare($connection,
                    "DELETE FROM pengiriman_detail WHERE pengiriman_id = ? AND status = 'menunggu'");
                mysqli_stmt_bind_param($delete_stmt, "i", $id);
                mysqli_stmt_execute($delete_stmt);
                mysqli_stmt_close($delete_stmt);
                
                // Insert barang baru
                foreach ($barang_ids as $index => $barang_id) {
                    $barang_id = (int)$barang_id;
                    $qty_diajukan = (int)($qty_diajukans[$index] ?? 0);
                    $detail_id = (int)($detail_ids[$index] ?? 0);
                    
                    if ($barang_id > 0 && $qty_diajukan > 0) {
                        // Cek stok
                        $check_stok = mysqli_query($connection, 
                            "SELECT harga, nama_barang FROM barang WHERE id = $barang_id");
                        $barang_data = mysqli_fetch_assoc($check_stok);
                        
                        if (!$barang_data) {
                            throw new Exception("Barang tidak ditemukan.");
                        }
                        
                        $subtotal = $barang_data['harga'] * $qty_diajukan;
                        $total_qty_diajukan += $qty_diajukan;
                        
                        // Insert atau update
                        if ($detail_id > 0) {
                            // Update existing
                            $stmt = mysqli_prepare($connection,
                                "UPDATE pengiriman_detail SET qty_diajukan = ?, harga = ?, subtotal = ? 
                                 WHERE id = ? AND status = 'menunggu'");
                            mysqli_stmt_bind_param($stmt, "iddi", $qty_diajukan, $barang_data['harga'], $subtotal, $detail_id);
                        } else {
                            // Insert new
                            $stmt = mysqli_prepare($connection,
                                "INSERT INTO pengiriman_detail (pengiriman_id, barang_id, qty_diajukan, harga, subtotal, status) 
                                 VALUES (?, ?, ?, ?, ?, 'menunggu')");
                            mysqli_stmt_bind_param($stmt, "iiidd", $id, $barang_id, $qty_diajukan, $barang_data['harga'], $subtotal);
                        }
                        
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Gagal menyimpan data barang.");
                        }
                        mysqli_stmt_close($stmt);
                    }
                }
                
                // Update total qty di pengiriman_barang
                $update_total = mysqli_prepare($connection,
                    "UPDATE pengiriman_barang SET total_qty = ? WHERE id = ?");
                mysqli_stmt_bind_param($update_total, "ii", $total_qty_diajukan, $id);
                mysqli_stmt_execute($update_total);
                mysqli_stmt_close($update_total);
                
                // Commit transaksi
                mysqli_commit($connection);
                
                $success = "Pengajuan barang berhasil diperbarui. Menunggu verifikasi ulang.";
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'detail.php?id=" . $id . "';
                    }, 2000);
                </script>";
                
            } catch (Exception $e) {
                mysqli_rollback($connection);
                $error = $e->getMessage();
            }
        }
    }
}
?>

<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>

<div class="card">
    <div class="card-header">
        <h4 class="mb-0">
            <i class="fas fa-edit me-2"></i>Edit Pengiriman Barang
        </h4>
        <?php if ($_SESSION['role'] === 'toko'): ?>
        <p class="mb-0 text-muted">
            <small>Anda hanya dapat mengedit barang yang belum diverifikasi</small>
        </p>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="form-edit">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3">Informasi Pengiriman</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">No. Pengajuan *</label>
                        <input type="text" name="no_pengajuan" class="form-control" 
                               value="<?= htmlspecialchars($pengiriman['no_pengajuan']) ?>" 
                               <?= $_SESSION['role'] === 'toko' ? 'readonly' : 'required' ?>>
                    </div>
                    
                    <?php if ($_SESSION['role'] !== 'toko'): ?>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Menunggu" <?= $pengiriman['status'] == 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                            <option value="Diproses" <?= $pengiriman['status'] == 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                            <option value="Dikirim" <?= $pengiriman['status'] == 'Dikirim' ? 'selected' : '' ?>>Dikirim</option>
                            <option value="SELESAI" <?= $pengiriman['status'] == 'SELESAI' ? 'selected' : '' ?>>Selesai</option>
                            <option value="Dibatalkan" <?= $pengiriman['status'] == 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>
                    </div>
                    <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <input type="text" class="form-control" 
                               value="<?= htmlspecialchars($pengiriman['status']) ?>" readonly>
                    </div>
                    <?php endif; ?>
                    
                    <h5 class="mt-4 border-bottom pb-2 mb-3">Data Pengirim</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Pengirim *</label>
                        <input type="text" name="nama_pengirim" class="form-control" 
                               value="<?= htmlspecialchars($pengiriman['nama_pengirim']) ?>" 
                               <?= $_SESSION['role'] === 'toko' ? 'readonly' : 'required' ?>>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Alamat Pengirim</label>
                        <textarea name="alamat_pengirim" class="form-control" rows="2"
                                  <?= $_SESSION['role'] === 'toko' ? 'readonly' : '' ?>><?= htmlspecialchars($pengiriman['alamat_pengirim']) ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Telepon Pengirim</label>
                        <input type="text" name="telepon_pengirim" class="form-control" 
                               value="<?= htmlspecialchars($pengiriman['telepon_pengirim']) ?>"
                               <?= $_SESSION['role'] === 'toko' ? 'readonly' : '' ?>>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3">Data Penerima</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Penerima *</label>
                        <input type="text" name="nama_penerima" class="form-control" 
                               value="<?= htmlspecialchars($pengiriman['nama_penerima']) ?>"
                               <?= $_SESSION['role'] === 'toko' ? 'readonly' : 'required' ?>>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Alamat Tujuan *</label>
                        <textarea name="alamat_tujuan" class="form-control" rows="2" 
                                  <?= $_SESSION['role'] === 'toko' ? 'readonly' : 'required' ?>><?= htmlspecialchars($pengiriman['alamat_tujuan']) ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Telepon Penerima</label>
                        <input type="text" name="telepon_penerima" class="form-control" 
                               value="<?= htmlspecialchars($pengiriman['telepon_penerima']) ?>"
                               <?= $_SESSION['role'] === 'toko' ? 'readonly' : '' ?>>
                    </div>
                </div>
            </div>
            
            <?php if ($_SESSION['role'] === 'toko'): ?>
            <!-- FORM EDIT BARANG UNTUK TOKO -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-boxes me-2"></i>Edit Barang yang Diajukan
                    </h5>
                    
                    <?php 
                    // Ambil barang yang masih menunggu verifikasi
                    $barang_menunggu_query = mysqli_query($connection,
                        "SELECT pd.*, b.nama_barang, b.harga 
                         FROM pengiriman_detail pd
                         JOIN barang b ON b.id = pd.barang_id
                         WHERE pd.pengiriman_id = $id AND pd.status = 'menunggu'");
                    
                    $has_barang_menunggu = mysqli_num_rows($barang_menunggu_query) > 0;
                    mysqli_data_seek($barang_menunggu_query, 0);
                    ?>
                    
                    <?php if ($has_barang_menunggu): ?>
                    <div class="table-responsive mb-3">
                        <table class="table" id="tabel-barang">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="45%">Barang</th>
                                    <th width="20%">Harga</th>
                                    <th width="20%">Qty Diajukan</th>
                                    <th width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="barang-body">
                                <?php 
                                $counter = 1;
                                while ($barang = mysqli_fetch_assoc($barang_menunggu_query)): 
                                ?>
                                <tr class="barang-row">
                                    <td><?= $counter++ ?></td>
                                    <td>
                                        <input type="hidden" name="detail_id[]" value="<?= $barang['id'] ?>">
                                        <select name="barang_id[]" class="form-select form-select-sm barang-select" 
                                                onchange="updateHargaInfo(this)" required>
                                            <option value="">-- Pilih Barang --</option>
                                            <?php if (mysqli_num_rows($barang_query) > 0): ?>
                                                <?php mysqli_data_seek($barang_query, 0); ?>
                                                <?php while ($b = mysqli_fetch_assoc($barang_query)): ?>
                                                    <option value="<?= $b['id'] ?>" 
                                                            data-harga="<?= $b['harga'] ?>"
                                                            data-nama="<?= htmlspecialchars($b['nama_barang']) ?>"
                                                            <?= $b['id'] == $barang['barang_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($b['nama_barang']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                                <?php mysqli_data_seek($barang_query, 0); ?>
                                            <?php else: ?>
                                                <option value="" disabled>Tidak ada data barang</option>
                                            <?php endif; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <span class="harga-info text-muted">
                                            Rp <?= number_format($barang['harga'], 0, ',', '.') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="qty_diajukan[]" 
                                                   class="form-control qty-input" 
                                                   min="1" value="<?= $barang['qty_diajukan'] ?>" required>
                                            <span class="input-group-text">unit</span>
                                        </div>
                                        <small class="text-danger qty-error" style="display:none;"></small>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="hapusBarang(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5">
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="tambahBarang()">
                                            <i class="fas fa-plus me-1"></i>Tambah Barang Lain
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="alert alert-warning">
                        <h6 class="mb-1"><i class="fas fa-exclamation-triangle me-2"></i>Perhatian:</h6>
                        <ul class="mb-0">
                            <li>Anda hanya dapat mengedit barang yang statusnya "menunggu"</li>
                            <li>Barang yang sudah "disetujui" atau "ditolak" tidak dapat diedit</li>
                            <li>Setelah diedit, verifikasi akan diulang dari awal</li>
                            <li>Ketersediaan stok akan dicek saat verifikasi oleh gudang</li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Tidak ada barang yang dapat diedit. Semua barang sudah diverifikasi.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-12">
                    <h5 class="mt-4 border-bottom pb-2 mb-3">Informasi Tambahan</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3"
                                  <?= $_SESSION['role'] === 'toko' ? 'readonly' : '' ?>><?= htmlspecialchars($pengiriman['keterangan']) ?></textarea>
                    </div>
                    
                    <?php if ($_SESSION['role'] !== 'toko'): ?>
                    <div class="mb-3">
                        <label class="form-label">Foto Barang</label>
                        <input type="file" name="foto_barang" id="fotoInput" class="form-control"
                               accept="image/*" onchange="previewImage(event)">
                        
                        <?php if ($foto_filename && $foto_filename !== 'default.jpg' && file_exists(UPLOAD_DIR_PENGIRIMAN . $foto_filename)): ?>
                            <div class="mt-2">
                                <p class="mb-1">Foto saat ini:</p>
                                <img src="<?= UPLOAD_DIR_PENGIRIMAN . $foto_filename ?>" alt="Foto Barang" 
                                     style="max-width: 300px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px; padding: 5px;">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="hapus_foto" id="hapus_foto" value="1">
                                    <label class="form-check-label" for="hapus_foto">
                                        Hapus foto saat ini dan gunakan default
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-2">
                            <img id="imagePreview" src="#" alt="Preview Foto"
                                 style="max-width:300px; max-height:200px; display:none;
                                 border:1px solid #ddd; border-radius:4px; padding:5px;">
                        </div>
                        
                        <div class="form-text">
                            Format: JPG, PNG, GIF, WebP. Maksimal 2MB.
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Perbarui
                </button>
                <a href="<?= $_SESSION['role'] === 'toko' ? 'detail.php?id='.$id : 'index.php' ?>" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php if ($_SESSION['role'] === 'toko'): ?>
<script>
let barangCounter = <?= mysqli_num_rows($barang_menunggu_query) ?>;

// Fungsi untuk menambah baris barang
function tambahBarang() {
    const tbody = document.getElementById('barang-body');
    const template = document.querySelector('.barang-row').cloneNode(true);
    
    barangCounter++;
    template.querySelector('td').textContent = barangCounter;
    
    // Reset select dan input
    const select = template.querySelector('.barang-select');
    select.selectedIndex = 0;
    select.onchange = function() { updateHargaInfo(this); };
    
    // Hapus hidden input detail_id untuk barang baru
    const hiddenInput = template.querySelector('input[name="detail_id[]"]');
    if (hiddenInput) hiddenInput.value = '';
    
    const hargaInfo = template.querySelector('.harga-info');
    hargaInfo.textContent = '-';
    
    const qtyInput = template.querySelector('.qty-input');
    qtyInput.value = 1;
    qtyInput.oninput = function() { validasiQty(this); };
    
    const qtyError = template.querySelector('.qty-error');
    qtyError.style.display = 'none';
    
    const deleteBtn = template.querySelector('.btn-danger');
    deleteBtn.onclick = function() { hapusBarang(this); };
    
    tbody.appendChild(template);
}

// Fungsi untuk menghapus baris barang
function hapusBarang(button) {
    const row = button.closest('tr');
    if (document.querySelectorAll('.barang-row').length > 1) {
        row.remove();
        // Update nomor urut
        document.querySelectorAll('.barang-row').forEach((row, index) => {
            row.querySelector('td').textContent = index + 1;
        });
        barangCounter = document.querySelectorAll('.barang-row').length;
    }
}

// Update info harga saat barang dipilih
function updateHargaInfo(select) {
    const row = select.closest('tr');
    const selectedOption = select.options[select.selectedIndex];
    
    const harga = parseInt(selectedOption.getAttribute('data-harga')) || 0;
    const hargaInfo = row.querySelector('.harga-info');
    
    // Update tampilan
    hargaInfo.textContent = 'Rp ' + harga.toLocaleString();
}

// Validasi qty
function validasiQty(input) {
    const row = input.closest('tr');
    const qtyError = row.querySelector('.qty-error');
    const qty = parseInt(input.value) || 0;
    
    if (qty < 1) {
        qtyError.textContent = 'Minimal 1 unit';
        qtyError.style.display = 'block';
        input.classList.add('is-invalid');
    } else {
        qtyError.style.display = 'none';
        input.classList.remove('is-invalid');
    }
}

// Validasi form sebelum submit
document.getElementById('form-edit').addEventListener('submit', function(e) {
    const barangRows = document.querySelectorAll('.barang-row');
    let isValid = true;
    
    // Cek minimal satu barang
    if (barangRows.length === 0) {
        alert('Minimal satu barang harus dipilih');
        e.preventDefault();
        return;
    }
    
    // Cek setiap barang
    barangRows.forEach(row => {
        const select = row.querySelector('.barang-select');
        const qtyInput = row.querySelector('.qty-input');
        
        if (!select.value) {
            alert('Semua barang harus dipilih');
            isValid = false;
            e.preventDefault();
            return;
        }
        
        const qty = parseInt(qtyInput.value) || 0;
        
        if (qty < 1) {
            alert('Qty minimal 1 unit');
            isValid = false;
            e.preventDefault();
            return;
        }
    });
    
    if (isValid) {
        const confirmation = confirm('Apakah Anda yakin dengan perubahan ini?\n\nVerifikasi akan diulang dari awal.');
        if (!confirmation) {
            e.preventDefault();
        }
    }
});
</script>
<?php endif; ?>

<script>
// Preview image
function previewImage(event) {
    const reader = new FileReader();
    const preview = document.getElementById('imagePreview');

    reader.onload = function () {
        preview.src = reader.result;
        preview.style.display = 'block';
    };

    if (event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    } else {
        preview.style.display = 'none';
    }
}

// Inisialisasi untuk role toko
<?php if ($_SESSION['role'] === 'toko'): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Update info untuk setiap barang
    document.querySelectorAll('.barang-select').forEach(select => {
        if (select.value) {
            updateHargaInfo(select);
        }
    });
});
<?php endif; ?>
</script>

<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>