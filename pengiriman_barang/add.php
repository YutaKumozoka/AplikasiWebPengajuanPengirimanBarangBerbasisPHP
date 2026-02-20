<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pengiriman');

require_once '../config/database.php';

define('UPLOAD_DIR_PENGIRIMAN', '../uploads/pengiriman/');

$error = $success = '';
$foto_filename = 'default.jpg';

// Data gudang hardcode (karena tabel gudang tidak ada)
$gudang = [
    'nama' => 'GUDANG Wahidin',
    'alamat' => 'Jl. Wahidin Putra Petir, No. 17, Cirebon',
    'telepon' => '(021) 1234-5678'
];

// Ambil data barang dari database untuk combobox (TANPA stok)
$barang_query = mysqli_query($connection, "SELECT id, nama_barang, harga FROM barang ORDER BY nama_barang ASC");

// Array untuk menyimpan barang yang dipilih
$barang_dipilih = [];
$total_qty_diajukan = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_pengajuan = trim($_POST['no_pengajuan'] ?? '');
    $nama_pengirim = trim($_POST['nama_pengirim'] ?? '');
    $alamat_pengirim = trim($_POST['alamat_pengirim'] ?? '');
    $telepon_pengirim = trim($_POST['telepon_pengirim'] ?? '');
    $nama_penerima = trim($_POST['nama_penerima'] ?? '');
    $alamat_tujuan = trim($_POST['alamat_tujuan'] ?? '');
    $telepon_penerima = trim($_POST['telepon_penerima'] ?? '');
    $jenis_barang = trim($_POST['jenis_barang'] ?? 'Barang Umum'); // TAMBAH INI
    $keterangan = trim($_POST['keterangan'] ?? '');
    $status = 'Menunggu'; // Default status untuk pengajuan baru

    // Validasi wajib - TAMBAH $jenis_barang
    if (!$no_pengajuan || !$nama_pengirim || !$nama_penerima || !$alamat_tujuan || !$jenis_barang) {
        $error = "No. Pengajuan, Nama Pengirim, Nama Penerima, Alamat Tujuan, dan Jenis Barang wajib diisi.";
    }

    // Ambil barang yang dipilih dari form
    $barang_ids = $_POST['barang_id'] ?? [];
    $qty_diajukans = $_POST['qty_diajukan'] ?? [];
    
    // Validasi minimal satu barang dipilih
    if (!$error && (empty($barang_ids) || !is_array($barang_ids))) {
        $error = "Minimal satu barang harus dipilih.";
    }
    
    // Validasi qty untuk setiap barang
    if (!$error) {
        $total_qty_diajukan = 0;
        foreach ($barang_ids as $index => $barang_id) {
            $barang_id = (int)$barang_id;
            $qty_diajukan = (int)($qty_diajukans[$index] ?? 0);
            
            if ($barang_id > 0 && $qty_diajukan > 0) {
                $total_qty_diajukan += $qty_diajukan;
                
                // Cek stok barang (di backend, toko tidak melihat stok)
                $check_stok = mysqli_query($connection, 
                    "SELECT stok, nama_barang, harga FROM barang WHERE id = $barang_id");
                $barang_data = mysqli_fetch_assoc($check_stok);
                
                if ($barang_data && $qty_diajukan > $barang_data['stok']) {
                    $error = "Stok tidak cukup untuk barang: " . $barang_data['nama_barang'];
                    break;
                }
                
                // Simpan data barang yang dipilih
                $barang_dipilih[] = [
                    'barang_id' => $barang_id,
                    'qty_diajukan' => $qty_diajukan,
                    'nama_barang' => $barang_data['nama_barang'] ?? '',
                    'harga' => $barang_data['harga'] ?? 0
                ];
            }
        }
        
        if ($total_qty_diajukan === 0) {
            $error = "Minimal satu barang harus memiliki qty lebih dari 0.";
        }
    }

    // Cek no pengajuan unik
    if (!$error) {
        $stmt = mysqli_prepare($connection,
            "SELECT id FROM pengiriman_barang WHERE no_pengajuan = ?"
        );
        mysqli_stmt_bind_param($stmt, "s", $no_pengajuan);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "No. Pengajuan sudah terdaftar.";
        }
        mysqli_stmt_close($stmt);
    }

    // Upload foto (optional)
    if (!$error && isset($_FILES['foto_barang']) && $_FILES['foto_barang']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = handle_file_upload_pengiriman($_FILES['foto_barang']);
        if ($upload === false) {
            $error = "Upload foto gagal. Pastikan format gambar valid dan ukuran < 2MB.";
        } elseif ($upload !== '') {
            $foto_filename = $upload;
        }
    }

    if (!$error) {
        // Mulai transaksi
        mysqli_begin_transaction($connection);
        
        try {
            // PERHATIAN: Jika tabel pengiriman_barang TIDAK punya kolom jenis_barang,
            // hapus jenis_barang dari query INSERT ini
            // Insert ke tabel pengiriman_barang - JIKA ADA KOLOM jenis_barang
            $stmt = mysqli_prepare($connection, "
                INSERT INTO pengiriman_barang (
                    no_pengajuan, nama_pengirim, alamat_pengirim, telepon_pengirim,
                    nama_penerima, alamat_tujuan, telepon_penerima,
                    jenis_barang, total_qty, keterangan, status, foto_barang
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            mysqli_stmt_bind_param($stmt, "ssssssssiiss",
                $no_pengajuan, $nama_pengirim, $alamat_pengirim, $telepon_pengirim,
                $nama_penerima, $alamat_tujuan, $telepon_penerima,
                $jenis_barang, $total_qty_diajukan, $keterangan, $status, $foto_filename
            );

            // JIKA TABEL TIDAK PUNYA KOLOM jenis_barang, gunakan query ini:
            /*
            $stmt = mysqli_prepare($connection, "
                INSERT INTO pengiriman_barang (
                    no_pengajuan, nama_pengirim, alamat_pengirim, telepon_pengirim,
                    nama_penerima, alamat_tujuan, telepon_penerima,
                    total_qty, keterangan, status, foto_barang
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            mysqli_stmt_bind_param($stmt, "sssssssiiss",
                $no_pengajuan, $nama_pengirim, $alamat_pengirim, $telepon_pengirim,
                $nama_penerima, $alamat_tujuan, $telepon_penerima,
                $total_qty_diajukan, $keterangan, $status, $foto_filename
            );
            */

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Gagal menyimpan data pengiriman: " . mysqli_error($connection));
            }
            
            $pengiriman_id = mysqli_insert_id($connection);
            mysqli_stmt_close($stmt);
            
            // Insert barang ke tabel pengiriman_detail
            foreach ($barang_dipilih as $barang) {
                $subtotal = $barang['harga'] * $barang['qty_diajukan'];
                
                // JIKA tabel pengiriman_detail TIDAK punya kolom jenis_barang
                $stmt_detail = mysqli_prepare($connection, 
                    "INSERT INTO pengiriman_detail (pengiriman_id, barang_id, qty_diajukan, harga, subtotal, status) 
                     VALUES (?, ?, ?, ?, ?, 'menunggu')");
                
                mysqli_stmt_bind_param($stmt_detail, "iiidd", 
                    $pengiriman_id, $barang['barang_id'], $barang['qty_diajukan'], 
                    $barang['harga'], $subtotal);
                
                if (!mysqli_stmt_execute($stmt_detail)) {
                    throw new Exception("Gagal menyimpan detail barang: " . mysqli_error($connection));
                }
                
                mysqli_stmt_close($stmt_detail);
            }
            
            // Commit transaksi
            mysqli_commit($connection);
            
            $_SESSION['success_message'] = "Pengiriman barang berhasil diajukan. Menunggu verifikasi gudang.";
            redirect('pengiriman_barang/index.php');
            
        } catch (Exception $e) {
            // Rollback transaksi jika ada error
            mysqli_rollback($connection);
            $error = $e->getMessage();
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
            <i class="fas fa-plus-circle me-2"></i>Ajukan Pengiriman Barang
        </h4>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="form-pengajuan">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3">Informasi Pengiriman</h5>

                    <div class="mb-3">
                        <label class="form-label">No. Pengajuan *</label>
                        <input type="text" name="no_pengajuan" class="form-control" 
                               value="PJ-<?= date('YmdHis') ?>" required readonly>
                        <div class="form-text">No. pengajuan otomatis</div>
                    </div>

                    <h5 class="mt-4 border-bottom pb-2 mb-3">Data Pengirim</h5>

                    <div class="mb-3">
                        <label class="form-label">Nama Pengirim *</label>
                        <input type="text" name="nama_pengirim" class="form-control" 
                               value="<?= htmlspecialchars($gudang['nama']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alamat Pengirim</label>
                        <textarea name="alamat_pengirim" class="form-control" rows="2"><?= htmlspecialchars($gudang['alamat']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Telepon Pengirim</label>
                        <input type="text" name="telepon_pengirim" class="form-control" 
                               value="<?= htmlspecialchars($gudang['telepon']) ?>">
                    </div>
                </div>

                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3">Data Penerima</h5>

                    <div class="mb-3">
                        <label class="form-label">Nama Penerima *</label>
                        <input type="text" name="nama_penerima" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alamat Tujuan *</label>
                        <textarea name="alamat_tujuan" class="form-control" rows="2" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Telepon Penerima</label>
                        <input type="text" name="telepon_penerima" class="form-control">
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-boxes me-2"></i>Pilih Barang yang Diajukan
                    </h5>
                    
                    <!-- Tabel untuk menambahkan barang -->
                    <div class="table-responsive mb-3">
                        <table class="table" id="tabel-barang">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="50%">Barang</th>
                                    <th width="20%">Harga</th>
                                    <th width="15%">Qty Diajukan</th>
                                    <th width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="barang-body">
                                <!-- Baris pertama untuk menambah barang -->
                                <tr id="barang-row-template" class="barang-row">
                                    <td>1</td>
                                    <td>
                                        <select name="barang_id[]" class="form-select form-select-sm barang-select" 
                                                onchange="updateBarangInfo(this)" required>
                                            <option value="">-- Pilih Barang --</option>
                                            <?php if (mysqli_num_rows($barang_query) > 0): ?>
                                                <?php while ($barang = mysqli_fetch_assoc($barang_query)): ?>
                                                    <option value="<?= $barang['id'] ?>" 
                                                            data-harga="<?= $barang['harga'] ?>"
                                                            data-nama="<?= htmlspecialchars($barang['nama_barang']) ?>">
                                                        <?= htmlspecialchars($barang['nama_barang']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                                <?php mysqli_data_seek($barang_query, 0); ?>
                                            <?php else: ?>
                                                <option value="" disabled>Tidak ada data barang</option>
                                            <?php endif; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <span class="harga-info text-muted">-</span>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="qty_diajukan[]" 
                                                   class="form-control qty-input" 
                                                   min="1" value="1" required>
                                            <span class="input-group-text">unit</span>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="hapusBarang(this)" disabled>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
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
                    
                    <!-- Input Jenis Barang - TAMBAH INI -->
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h5 class="border-bottom pb-2 mb-3">Jenis Barang</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Jenis Barang *</label>
                                <select name="jenis_barang" class="form-select" required>
                                    <option value="">-- Pilih Jenis Barang --</option>
                                    <option value="Elektronik">Elektronik</option>
                                    <option value="Perangkat Kantor">Perangkat Kantor</option>
                                    <option value="Furniture">Furniture</option>
                                    <option value="Alat Tulis">Alat Tulis</option>
                                    <option value="Perlengkapan Komputer">Perlengkapan Komputer</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                                <div class="form-text">Pilih kategori jenis barang yang akan dikirim</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6 class="mb-1"><i class="fas fa-info-circle me-2"></i>Informasi:</h6>
                        <ul class="mb-0">
                            <li>Pilih barang yang akan diajukan untuk dikirim</li>
                            <li>Pilih jenis barang sesuai kategori</li>
                            <li>Pengajuan akan diverifikasi oleh gudang terlebih dahulu</li>
                            <li>Jika stok tidak mencukupi, pengajuan akan ditolak oleh gudang</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <h5 class="mt-4 border-bottom pb-2 mb-3">Informasi Tambahan</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3" 
                                  placeholder="Tambah keterangan lain jika diperlukan"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Foto Barang (Optional)</label>
                        <input type="file" name="foto_barang" id="fotoInput" class="form-control"
                               accept="image/*" onchange="previewImage(event)">

                        <div class="mt-2">
                            <img id="imagePreview" src="#" alt="Preview Foto"
                                 style="max-width:300px; max-height:200px; display:none;
                                 border:1px solid #ddd; border-radius:4px; padding:5px;">
                        </div>

                        <div class="form-text">
                            Format: JPG, PNG, GIF, WebP. Maksimal 2MB.
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-1"></i>Ajukan Pengiriman
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
let barangCounter = 1;

// Fungsi untuk menambah baris barang
function tambahBarang() {
    const tbody = document.getElementById('barang-body');
    const template = document.querySelector('.barang-row').cloneNode(true);
    
    barangCounter++;
    template.querySelector('td').textContent = barangCounter;
    
    // Reset select dan input
    const select = template.querySelector('.barang-select');
    select.selectedIndex = 0;
    select.onchange = function() { updateBarangInfo(this); };
    
    const hargaInfo = template.querySelector('.harga-info');
    hargaInfo.textContent = '-';
    
    const qtyInput = template.querySelector('.qty-input');
    qtyInput.value = 1;
    
    const deleteBtn = template.querySelector('.btn-danger');
    deleteBtn.disabled = false;
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

// Update info barang saat dipilih
function updateBarangInfo(select) {
    const row = select.closest('tr');
    const selectedOption = select.options[select.selectedIndex];
    
    const harga = parseInt(selectedOption.getAttribute('data-harga')) || 0;
    const hargaInfo = row.querySelector('.harga-info');
    
    // Update tampilan harga saja
    hargaInfo.textContent = 'Rp ' + harga.toLocaleString();
}

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

// Validasi form sebelum submit
document.getElementById('form-pengajuan').addEventListener('submit', function(e) {
    const barangRows = document.querySelectorAll('.barang-row');
    
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
            e.preventDefault();
            return;
        }
        
        const qty = parseInt(qtyInput.value) || 0;
        
        if (qty < 1) {
            alert('Qty minimal 1 unit');
            e.preventDefault();
            return;
        }
    });
    
    const confirmation = confirm('Apakah Anda yakin dengan pengajuan ini?\n\nPengajuan akan dikirim ke gudang untuk diverifikasi.');
    if (!confirmation) {
        e.preventDefault();
    }
});

// Inisialisasi saat halaman load
document.addEventListener('DOMContentLoaded', function() {
    // Update info untuk baris pertama
    const firstSelect = document.querySelector('.barang-select');
    if (firstSelect.value) {
        updateBarangInfo(firstSelect);
    }
});
</script>

<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>