<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.use_strict_mode', 1);
session_start();
require_once 'lib/auth.php';
require_once 'lib/functions.php'; // berisi fungsi backup

// Hanya admin dan gudang yang boleh akses
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'gudang'])) {
    header("Location: login.php");
    exit();
}

$page_title = "Backup Database";
$message = '';
$backupInfo = null;

if (isset($_POST['backup'])) {
    $backupName = 'backup_' . $_SESSION['username'];
    $result = backupDatabaseSimple($backupName);
    
    if ($result['success']) {
        $message = '<div class="alert alert-success">Backup berhasil! File: ' . basename($result['file']) . ' (' . formatBytes($result['size']) . ')</div>';
        $backupInfo = $result;
    } else {
        $message = '<div class="alert alert-danger">Backup gagal: ' . htmlspecialchars($result['error']) . '</div>';
    }
}

// Fungsi format bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

include 'views/' . $THEME . '/header.php';
include 'views/' . $THEME . '/sidebar.php';
include 'views/' . $THEME . '/topnav.php';
include 'views/' . $THEME . '/upper_block.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-database me-2"></i>Backup Database</h4>
                </div>
                <div class="card-body">
                    <?= $message ?>
                    <p>Klik tombol di bawah untuk membuat cadangan database. File backup akan disimpan di folder <code>backups/</code>.</p>
                    <form method="post">
                        <button type="submit" name="backup" class="btn btn-success">
                            <i class="bi bi-download"></i> Backup Sekarang
                        </button>
                    </form>
                    
                    <?php if ($backupInfo): ?>
                    <hr>
                    <h5>Download Backup</h5>
                    <p>
                        <a href="download_backup.php?file=<?= urlencode(basename($backupInfo['file'])) ?>" class="btn btn-primary">
                            <i class="bi bi-cloud-download"></i> Download File Backup
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Daftar backup yang sudah ada -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-folder"></i> Backup Tersimpan</h5>
                </div>
                <div class="card-body">
                    <?php
                    $backupDir = 'backups';
                    if (is_dir($backupDir)) {
                        $files = glob($backupDir . '/*.sql');
                        if ($files) {
                            echo '<ul class="list-group">';
                            foreach ($files as $file) {
                                $filename = basename($file);
                                $size = filesize($file);
                                $date = date('d-m-Y H:i:s', filemtime($file));
                                echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                echo '<span><i class="bi bi-file-earmark"></i> ' . htmlspecialchars($filename) . ' (' . formatBytes($size) . ') - ' . $date . '</span>';
                                echo '<a href="download_backup.php?file=' . urlencode($filename) . '" class="btn btn-sm btn-outline-primary">Download</a>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p class="text-muted">Belum ada backup tersimpan.</p>';
                        }
                    } else {
                        echo '<p class="text-muted">Folder backup belum dibuat.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'views/' . $THEME . '/lower_block.php';
include 'views/' . $THEME . '/footer.php';
?>