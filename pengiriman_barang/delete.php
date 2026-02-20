<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pengiriman');

require_once '../config/database.php';

define('UPLOAD_DIR_PENGIRIMAN', '../uploads/pengiriman/');

$id = (int) ($_GET['id'] ?? 0);
if ($id) {
    // Ambil data foto sebelum menghapus
    $stmt = mysqli_prepare($connection, "SELECT foto_barang FROM `pengiriman_barang` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $foto);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    
    // Hapus file foto jika bukan default
    if ($foto && $foto !== 'default.jpg' && file_exists(UPLOAD_DIR_PENGIRIMAN . $foto)) {
        unlink(UPLOAD_DIR_PENGIRIMAN . $foto);
    }
    
    // Hapus data dari database
    $stmt = mysqli_prepare($connection, "DELETE FROM `pengiriman_barang` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

redirect('pengiriman_barang/index.php');
?>