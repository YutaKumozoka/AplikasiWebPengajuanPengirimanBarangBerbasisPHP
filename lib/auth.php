<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
define('BASE_URL', $BASE_PATH);

/**
 * Authenticates user and sets session data.
 * Assumes session_start() was called BEFORE this function.
 */
function login($username, $password) {
    global $connection;
    
    // Sanitize input
    $username = mysqli_real_escape_string($connection, sanitize($username));
    
    // SEMUA role bisa login (tidak ada filter role)
    $sql = "SELECT id, username, password, role FROM users WHERE username=?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    if ($user && password_verify($password, $user['password'])) {
        // DO NOT call session_start() here!
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        mysqli_stmt_close($stmt);
        return $user['role'];
    }
    
    mysqli_stmt_close($stmt);
    return false;
}

function registerUser($username, $password, $role) {
    global $connection;
    
    // Get allowed roles from menu.json
    $menuConfig = loadMenuConfig();
    $allowedRoles = array_keys($menuConfig['roles'] ?? []);
    
    $username = mysqli_real_escape_string($connection, sanitize($username));
    $role = in_array($role, $allowedRoles) ? $role : 'mahasiswa'; // Use roles from config
    
    $hashedPass = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $username, $hashedPass, $role);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function redirectBasedOnRole($role) {
    switch ($role) {
        case 'admin':
            header("Location: /pengiriman/admin/index.php");
            break;
        case 'gudang':
            header("Location: /pengiriman/gudang/index.php");
            break;
        case 'toko':
            header("Location: /pengiriman/toko/index.php");
            break;
        default:
            // Jika role tidak dikenali, redirect ke login
            header("Location: /pengiriman/login.php");
    }
    exit();
}

/**
 * Check if user has access to a module
 */
function hasModuleAccess($module) {
    // First check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // All roles have access to all modules by default
    // Except for specific restrictions
    if (isset($_SESSION['role'])) {
        // Role 'toko' tidak bisa akses detail pengiriman
        if ($module === 'pengiriman_barang') {
            $current_page = basename($_SERVER['PHP_SELF']);
            $restricted_pages = ['detail.php', 'detailadd.php', 'detaildelete.php'];
            
            if ($_SESSION['role'] === 'toko' && in_array($current_page, $restricted_pages)) {
                return false;
            }
        }
    }
    
    return true;
}

/**
 * Require module access - SIMPLIFIED VERSION
 */
function requireModuleAccess($module) {
    // First require authentication
    requireAuth();
    
    // Then check module access
    if (!hasModuleAccess($module)) {
        $_SESSION['error_message'] = 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.';
        
        // Redirect berdasarkan role
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'toko') {
            // Role 'toko' redirect ke index.php di folder pengiriman_barang
            if (basename($_SERVER['PHP_SELF']) !== 'index.php') {
                header('Location: index.php');
                exit();
            }
        } else {
            // Role lain redirect ke dashboard sesuai role
            $menuConfig = loadMenuConfig();
            $dashboard = $menuConfig['roles'][$_SESSION['role']]['dashboard'] ?? '../dashboard.php';
            header('Location: ' . $dashboard);
            exit();
        }
    }
}

/**
 * Check if user can access a specific page in pengiriman_barang module
 * Role 'toko' cannot access detail.php, detailadd.php, detaildelete.php
 */
function canAccessPengirimanPage($page) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    // Role 'toko' tidak bisa akses halaman tertentu
    if ($_SESSION['role'] === 'toko') {
        $restrictedPages = ['detail.php', 'detailadd.php', 'detaildelete.php'];
        return !in_array($page, $restrictedPages);
    }
    
    // Role lain bisa akses semua
    return true;
}

/**
 * Require access for specific pengiriman page
 */
function requirePengirimanPageAccess($page) {
    // First require authentication
    requireAuth();
    
    // Then check page access
    if (!canAccessPengirimanPage($page)) {
        $_SESSION['error_message'] = 'Akses ditolak. Role "toko" tidak dapat mengakses halaman ini.';
        header('Location: index.php');
        exit();
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>