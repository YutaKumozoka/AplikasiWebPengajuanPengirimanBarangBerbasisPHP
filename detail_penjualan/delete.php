<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('detail_penjualan');

require_once '../config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id) {
    $stmt = mysqli_prepare($connection, "DELETE FROM `detail_penjualan` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

redirect('detail_penjualan/index.php');
?>
