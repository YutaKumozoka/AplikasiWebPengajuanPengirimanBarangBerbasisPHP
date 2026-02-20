<aside class="sidebar" id="sidebar">
    <!-- ... header ... -->
    <nav class="sidebar-menu">
        <!-- menu lainnya -->
        <div class="sidebar-item">
            <a href="<?= base_url('index.php') ?>" class="sidebar-link active">
                <i class="bi bi-speedometer2"></i><span>Dashboard</span>
            </a>
        </div>
        <!-- ... Users, Reports, Settings ... -->
        
        <!-- ... menu lain ... -->
        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'gudang'): ?>
        <div class="sidebar-item">
            <a href="<?= base_url('backup.php') ?>" class="sidebar-link">
                <i class="bi bi-database"></i><span>Backup Database</span>
            </a>
        </div>
        <?php endif; ?>
        <!-- ... logout ... -->
        <div class="sidebar-item">
            <a href="<?= base_url('logout.php') ?>" class="sidebar-link">
                <i class="bi bi-box-arrow-right"></i><span>Logout</span>
            </a>
        </div>
    </nav>
</aside>