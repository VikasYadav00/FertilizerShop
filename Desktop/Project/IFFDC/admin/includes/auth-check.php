<?php
// ============================================================
// admin/includes/auth-check.php — Admin auth guard
// Include at the top of every admin page
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? 'login.php' : 'admin/login.php'));
    exit;
}

// ============================================================
// admin/includes/sidebar.php — Shared admin sidebar
// ============================================================
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$adminName   = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');

function navItem($href, $icon, $label, $page, $current) {
    $isActive = $page === $current;
    return "<a href='{$href}' class='nav-item " . ($isActive ? 'active' : '') . "'>
        <i class='fas fa-{$icon}'></i><span>{$label}</span></a>";
}
?>
