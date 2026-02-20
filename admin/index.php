<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // atur nilai menjadi 1 jika di publish ke real public server
ini_set('session.use_strict_mode', 1);
session_start();
require_once '../lib/auth.php';
require_once '../lib/functions.php';
requireAuth();
if (getUserRole() !== 'admin') {
redirect('../login.php');
}
?>
<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>
<?php include '../views/'.$THEME.'/admin_content.php'; ?>
<h2>Welcome, <?php echo ucfirst($_SESSION['role']); ?>!</h2>
<p>You are logged in as: <strong><?php echo $_SESSION['username']; ?></strong></p>
<a href="../logout.php" class="btn btn-outline-danger">Logout</a>
<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>
