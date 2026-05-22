<?php
// ============================================================
// includes/header.php — Shared Navbar for Customer Pages
// ============================================================
require_once __DIR__ . '/../config/db.php';
checkMaintenanceMode($conn);

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn   = isset($_SESSION['user_id']);
$userName     = $isLoggedIn ? htmlspecialchars($_SESSION['user_name']) : '';
$shopPhone    = getSiteSetting($conn, 'shop_whatsapp') ?? '910000000000';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'IFFDC Maharajpur | Krishak Seva Kendra'; ?></title>
    <meta name="description" content="<?php echo $pageDesc ?? 'Official IFFDC fertilizer shop in Maharajpur. Buy urea, DAP, nano fertilizers and seeds online.'; ?>">
    <link rel="stylesheet" href="<?php echo $cssPath ?? ''; ?>style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <a href="<?php echo $rootPath ?? ''; ?>index.php" class="logo" style="text-decoration:none;color:inherit;">
            <div>
                <h1>IFFDC Maharajpur</h1>
                <span>KRISHAK SEVA KENDRA</span>
            </div>
        </a>
        <div class="nav-actions">
            <?php if ($isLoggedIn): ?>
                <div class="user-menu" style="position:relative;">
                    <button class="user-btn" onclick="toggleUserMenu()" style="background:none;border:none;color:white;cursor:pointer;display:flex;align-items:center;gap:8px;font-size:0.9rem;font-weight:600;">
                        <i class="fas fa-user-circle" style="font-size:1.3rem;"></i>
                        <span><?php echo $userName; ?></span>
                        <i class="fas fa-chevron-down" style="font-size:0.7rem;"></i>
                    </button>
                    <div id="userDropdown" style="display:none;position:absolute;top:100%;right:0;background:white;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.15);min-width:180px;z-index:999;margin-top:8px;overflow:hidden;">
                        <a href="<?php echo $rootPath ?? ''; ?>customer/orders.php" style="display:flex;align-items:center;gap:10px;padding:12px 18px;color:#1f2937;text-decoration:none;font-size:0.9rem;transition:0.2s;" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='white'">
                            <i class="fas fa-shopping-bag" style="color:#15803d;"></i> My Orders
                        </a>
                        <a href="<?php echo $rootPath ?? ''; ?>auth/logout.php" style="display:flex;align-items:center;gap:10px;padding:12px 18px;color:#dc2626;text-decoration:none;font-size:0.9rem;border-top:1px solid #f3f4f6;transition:0.2s;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='white'">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo $rootPath ?? ''; ?>auth/login.php" class="login-link">
                    <i class="fas fa-user-circle"></i>
                    <span>Login</span>
                </a>
            <?php endif; ?>

            <div class="cart-status" id="cart-btn" style="cursor:pointer;">
                <i class="fas fa-shopping-cart"></i>
                <span id="cart-count">0</span>
            </div>

            <a href="tel:+91<?php echo $shopPhone; ?>" class="call-btn">
                <i class="fas fa-phone"></i> Call Now
            </a>
        </div>
    </div>
</nav>
<script>
function toggleUserMenu() {
    const d = document.getElementById('userDropdown');
    d.style.display = d.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function(e) {
    const menu = document.getElementById('userDropdown');
    if (menu && !e.target.closest('.user-menu')) {
        menu.style.display = 'none';
    }
});
</script>
