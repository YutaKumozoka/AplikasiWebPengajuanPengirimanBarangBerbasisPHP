<?php
// pengiriman_barang/access_check.php
session_start();
require_once '../lib/auth.php';

// Cek apakah user adalah 'toko'
if (isset($_SESSION['role']) && $_SESSION['role'] === 'toko') {
    // Tentukan halaman mana saja yang tidak bisa diakses oleh toko
    $current_page = basename($_SERVER['PHP_SELF']);
    $restricted_pages = ['detail.php', 'detailadd.php', 'detaildelete.php'];
    
    if (in_array($current_page, $restricted_pages)) {
        $_SESSION['error_message'] = 'Role "toko" tidak dapat mengakses halaman ini.';
        header("Location: index.php");
        exit();
    }
}
?>