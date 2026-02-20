<?php
// Hapus baris require_once di global, pindahkan ke dalam fungsi

function backupDatabaseSimple($backupName, $backupDir = 'backups') {
    // Load autoload hanya jika fungsi dipanggil
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        return [
            'success' => false,
            'error' => 'Vendor autoload not found. Please run composer install.'
        ];
    }
    require_once $autoloadPath;
    
    // Load .env dari root proyek
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
    
    // Get DB config
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbUser = $_ENV['DB_USER'] ?? 'root';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';
    $dbName = $_ENV['DB_NAME'];
    $dbPort = $_ENV['DB_PORT'] ?? '3306';
    
    // Cek apakah exec tersedia
    if (!function_exists('exec')) {
        return [
            'success' => false,
            'error' => 'Fungsi exec() tidak diizinkan oleh server.'
        ];
    }
    
    // Cek folder backup
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    if (!is_writable($backupDir)) {
        return [
            'success' => false,
            'error' => 'Folder backup tidak dapat ditulis.'
        ];
    }
    
    // Cek apakah mysqldump bisa dijalankan via PATH
    exec('mysqldump --version 2>&1', $verOutput, $verCode);
    if ($verCode !== 0) {
        // Coba dengan path umum di XAMPP (sesuaikan dengan instalasi)
        $possiblePaths = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'D:\\xampp\\mysql\\bin\\mysqldump.exe', // jika di D:
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe',
        ];
        $mysqldumpPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $mysqldumpPath = $path;
                break;
            }
        }
        if (!$mysqldumpPath) {
            return [
                'success' => false,
                'error' => 'mysqldump tidak ditemukan. Pastikan MySQL terinstal dan path-nya benar.'
            ];
        }
    } else {
        $mysqldumpPath = 'mysqldump'; // bisa langsung panggil
    }
    
    // Create filename
    $filename = $backupName . '_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backupDir . '/' . $filename;
    
    // Build and execute command
    $command = sprintf(
        '"%s" --host=%s --port=%s --user=%s --password=%s %s > %s 2>&1',
        $mysqldumpPath,
        escapeshellarg($dbHost),
        escapeshellarg($dbPort),
        escapeshellarg($dbUser),
        escapeshellarg($dbPass),
        escapeshellarg($dbName),
        escapeshellarg($filepath)
    );
    
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($filepath)) {
        return [
            'success' => true,
            'file' => $filepath,
            'size' => filesize($filepath)
        ];
    }
    
    return [
        'success' => false,
        'error' => 'mysqldump error: ' . implode("\n", $output)
    ];
}
// ... lanjutkan dengan fungsi-fungsi lain seperti sanitize, redirect, dll ...
// Cara menggunakan
// $backup = backupDatabaseSimple('daily_backup');

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    // If URL is relative, prepend BASE_URL
    if (!preg_match('~^(https?://|//)~', $url) && !str_starts_with($url, '/')) {
        $url = BASE_URL . '/' . ltrim($url, '/');
    }
    header("Location: " . $url);
    exit();
}

function showAlert($message, $type = 'danger') {
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
        $safeMessage
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>";
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function validatePassword($password, $enabled = true) {
    // If validation is disabled, always return valid
    if (!$enabled) {
        return []; // Always valid when disabled
    }
    
    $errors = [];
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number.";
    }
    return $errors; // empty array = valid
}

function userCanAccess($allowedRoles = ['admin']) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    $userRole = $_SESSION['role'] ?? '';
    return in_array($userRole, $allowedRoles);
}

/**
* Show access denied page with error message
* @param array $allowedRoles List of roles allowed to access the module
*/
function showAccessDenied($allowedRoles = ['admin']) {
    $roleLabels = getRoleLabels(); // Now loaded from menu.json via config functions
    $allowedLabels = array_map(fn($r) => $roleLabels[$r] ?? $r, $allowedRoles);
    $allowedText = implode(' atau ', $allowedLabels);
    
    include __DIR__ . '/../views/header.php';
    include __DIR__ . '/../views/topnav.php';
    ?>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../views/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="alert alert-danger">
                    <h4>🚫 Akses Ditolak</h4>
                    <p>
                        Halaman ini hanya dapat diakses oleh: <strong><?= htmlspecialchars($allowedText) ?></strong>.
                    </p>
                    <p>
                        Anda login sebagai <strong><?= htmlspecialchars(getRoleLabel($_SESSION['role'] ?? 'user')) ?></strong>.
                    </p>
                    <a href="../<?= htmlspecialchars($_SESSION['role'] ?? 'login') ?>/index.php"
                       class="btn btn-primary">
                        Kembali ke Dashboard
                    </a>
                </div>
            </main>
        </div>
    </div>
    <?php
    include __DIR__ . '/../views/footer.php';
    exit();
}

function requireRoleAccess($allowedRoles = ['admin', 'dosen'], $redirectUrl = null) {
    if (!userCanAccess($allowedRoles)) {
        if ($redirectUrl) {
            redirect($redirectUrl);
        } else {
            showAccessDenied($allowedRoles);
        }
    }
}

function loadMenuConfig() {
    $configFile = __DIR__ . '/../config/menu.json';
    if (file_exists($configFile)) {
        $jsonContent = file_get_contents($configFile);
        return json_decode($jsonContent, true) ?: [];
    }
    return [];
}

function getRoleLabel($role) {
    $menuConfig = loadMenuConfig();
    return $menuConfig['roles'][$role]['label'] ?? $role;
}

/**
* Get all role labels
*/
function getRoleLabels() {
    $menuConfig = loadMenuConfig();
    $labels = [];
    foreach ($menuConfig['roles'] as $role => $config) {
        $labels[$role] = $config['label'];
    }
    return $labels;
}

function getAllowedRolesForModule($moduleName) {
    $menuConfig = loadMenuConfig();
    return $menuConfig['modules'][$moduleName]['allowed_roles'] ?? ['admin']; // default to admin if not found
}

/**
* Check if current user can access a specific module
* TINGGALKAN INI karena masih digunakan oleh fungsi lain
*/
function userCanAccessModule($moduleName) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    $userRole = $_SESSION['role'] ?? '';
    $allowedRoles = getAllowedRolesForModule($moduleName);
    return in_array($userRole, $allowedRoles);
}

/**
* Require role access for a specific module
* HAPUS FUNGSI INI - sudah ada di lib/auth.php
* function requireModuleAccess($moduleName, $redirectUrl = null) {
*     $allowedRoles = getAllowedRolesForModule($moduleName);
*     if (!userCanAccessModule($moduleName)) {
*         if ($redirectUrl) {
*             redirect($redirectUrl);
*         } else {
*             showAccessDenied($allowedRoles);
*         }
*     }
* }
*/

function base_url($path = '') {
    $url = BASE_URL . '/' . $path;
    return $url;
}

// helpers/form_helper.php
function dropdownFromTable($table, $value_field = 'id', $label_field = 'name',
                          $selected = '', $name = '', $placeholder = '-- Pilih --',
                          $order_by = '', $where = '') {
    // Use global connection from config/database.php
    global $connection;
    
    // Validate/sanitize identifiers (basic protection)
    // In real apps, whitelist allowed tables/columns!
    $value_field = str_replace('`', '', $value_field);
    $label_field = str_replace('`', '', $label_field);
    $table       = str_replace('`', '', $table);
    if ($order_by) {
        $order_by = str_replace('`', '', $order_by);
    }
    
    // Build query
    $sql = "SELECT `$value_field`, `$label_field` FROM `$table`";
    if ($where) {
        // ⚠️ WARNING: $where must be trusted or pre-sanitized!
        $sql .= " WHERE $where";
    }
    $sql .= $order_by
        ? " ORDER BY `$order_by`"
        : " ORDER BY `$label_field` ASC";
    
    $result = mysqli_query($connection, $sql);
    
    $html = '<select name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" class="form-control">';
    $html .= '<option value="">' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '</option>';
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $value = htmlspecialchars($row[$value_field], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($row[$label_field], ENT_QUOTES, 'UTF-8');
            $selected_attr = ($row[$value_field] == $selected) ? 'selected' : '';
            $html .= "<option value=\"$value\" $selected_attr>$label</option>";
        }
    } else {
        $html .= '<option value="">-- Tidak ada data --</option>';
    }
    $html .= '</select>';
    return $html;
}

// helpers/db_helper.php (or add to existing helper file)
function getFieldValue($table, $field, $where_field, $where_value) {
    global $connection;
    
    // Basic sanitization: remove backticks to avoid injection in identifiers
    $table = str_replace('`', '', $table);
    $field = str_replace('`', '', $field);
    $where_field = str_replace('`', '', $where_field);
    
    // Use prepared statement via mysqli to prevent SQL injection
    $sql = "SELECT `$field` FROM `$table` WHERE `$where_field` = ? LIMIT 1";
    $stmt = mysqli_prepare($connection, $sql);
    if (!$stmt) {
        error_log("SQL prepare error: " . mysqli_error($connection));
        return null;
    }
    
    // Determine the type for bind_param (assume string unless numeric)
    $type = is_int($where_value) || is_float($where_value) ? 'd' : 's';
    mysqli_stmt_bind_param($stmt, $type, $where_value);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_row($result);
    mysqli_stmt_close($stmt);
    
    return $row ? $row[0] : null;
}

// helpers/db_helper.php
/**
* Sum a field from a detail table and update the master total automatically.
*
* @param mysqli $connection       Database connection
* @param string $detail_table     Detail table name (e.g., 'penjualan_detail')
* @param string $sum_field        Field to sum in detail table (e.g., 'subtotal')
* @param string $detail_fk_field  Foreign key in detail table (e.g., 'penjualan_id')
* @param string $master_table     Master table name (e.g., 'penjualan')
* @param string $master_pk_field  Primary key in master table (e.g., 'id')
* @param string $master_total_field Field in master to update (e.g., 'total_bayar')
* @param mixed  $master_id        The master record ID
* @return bool                    True on success, false on failure
*/
function updateMasterTotalFromDetail(
    $connection,
    $detail_table,
    $sum_field,
    $detail_fk_field,
    $master_table,
    $master_pk_field,
    $master_total_field,
    $master_id
) {
    // Sanitize identifiers (remove backticks to prevent injection in names)
    $detail_table      = str_replace('`', '', $detail_table);
    $sum_field         = str_replace('`', '', $sum_field);
    $detail_fk_field   = str_replace('`', '', $detail_fk_field);
    $master_table      = str_replace('`', '', $master_table);
    $master_pk_field   = str_replace('`', '', $master_pk_field);
    $master_total_field = str_replace('`', '', $master_total_field);
    
    // Step 1: Calculate the sum from detail table
    $sql_sum = "SELECT COALESCE(SUM(`$sum_field`), 0) AS total
                FROM `$detail_table`
                WHERE `$detail_fk_field` = ?";
    $stmt_sum = mysqli_prepare($connection, $sql_sum);
    if (!$stmt_sum) {
        error_log("updateMasterTotalFromDetail (SUM) prepare error: " . mysqli_error($connection));
        return false;
    }
    $type = is_int($master_id) || is_float($master_id) ? 'd' : 's';
    mysqli_stmt_bind_param($stmt_sum, $type, $master_id);
    mysqli_stmt_execute($stmt_sum);
    $result = mysqli_stmt_get_result($stmt_sum);
    $row = mysqli_fetch_assoc($result);
    $total = (float)($row['total'] ?? 0.0);
    mysqli_stmt_close($stmt_sum);
    
    // Step 2: Update master table
    $sql_update = "UPDATE `$master_table`
                   SET `$master_total_field` = ?
                   WHERE `$master_pk_field` = ?";
    $stmt_update = mysqli_prepare($connection, $sql_update);
    if (!$stmt_update) {
        error_log("updateMasterTotalFromDetail (UPDATE) prepare error: " . mysqli_error($connection));
        return false;
    }
    mysqli_stmt_bind_param($stmt_update, "d" . $type, $total, $master_id);
    $success = mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);
    
    return $success;
}

/**
 * FUNGSI KHUSUS UNTUK PENGIRIMAN - FIXED VERSION
 * Update total pengiriman secara khusus
 */
function updateTotalPengiriman($connection, $master_id) {
    // Hitung total dari tabel pengiriman_detail
    $total_query = mysqli_query($connection, 
        "SELECT COALESCE(SUM(`subtotal`), 0) as total 
         FROM `pengiriman_detail` 
         WHERE `pengiriman_id` = $master_id");
    
    if (!$total_query) {
        error_log("Error calculating total: " . mysqli_error($connection));
        return false;
    }
    
    $total_row = mysqli_fetch_assoc($total_query);
    $total = (float)($total_row['total'] ?? 0);
    
    // Update ke tabel pengiriman_barang
    $result = mysqli_query($connection, 
        "UPDATE `pengiriman_barang` 
         SET `jenis_barang` = '$total' 
         WHERE `id` = $master_id");
    
    if (!$result) {
        error_log("Error updating pengiriman_barang: " . mysqli_error($connection));
        return false;
    }
    
    return $result;
}

// ============ FUNGSI UPLOAD FILE ============

function handle_file_upload_pengiriman($file) {
    // Check if file was uploaded
    if (!isset($file['name']) || empty($file['name'])) {
        return ''; // No file uploaded
    }

    $target_dir = UPLOAD_DIR_PENGIRIMAN;

    // Create upload directory if not exists
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Generate unique filename
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'pengiriman_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $file_extension;
    $target_file = $target_dir . $filename;

    // Check file size (max 2MB)
    if ($file['size'] > 2097152) {
        return false; // File too large
    }

    // Allow certain file formats
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($file_extension, $allowed_types)) {
        return false; // Invalid file type
    }

    // Check if file is actually an image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return false; // Not an image
    }

    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $filename;
    }

    return false;
}

// Tambahkan fungsi ini di lib/functions.php
function dropdownFromQuery($query, $selected = '', $name = '', $placeholder = '-- Pilih --') {
    global $connection;
    
    $html = "<select name='$name' class='form-select' required>";
    $html .= "<option value=''>$placeholder</option>";
    
    $result = mysqli_query($connection, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $value = $row['id'];
            $label = $row['label'];
            $is_selected = ($selected == $value) ? 'selected' : '';
            $html .= "<option value='$value' $is_selected>$label</option>";
        }
    }
    
    $html .= "</select>";
    return $html;
}

// Jika membutuhkan fungsi upload untuk modul lain, tambahkan di bawah ini:
// Contoh: function handle_file_upload_mobil($file) { ... }

?>