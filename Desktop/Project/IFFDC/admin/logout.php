<?php
// admin/logout.php — Admin logout
if (session_status() === PHP_SESSION_NONE) session_start();
unset($_SESSION['admin_id'], $_SESSION['admin_name']);
session_destroy();
header('Location: login.php');
exit;
?>
