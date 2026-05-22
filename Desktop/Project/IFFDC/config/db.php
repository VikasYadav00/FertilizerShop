<?php
// ============================================================
// config/db.php - Database Connection
// IFFDC Maharajpur - Krishak Seva Kendra
// ============================================================

// ---- Dynamic Site Base URL (works on localhost AND InfinityFree) ----
// Detects HTTPS automatically and never uses a hardcoded sub-folder path.
if (!defined('SITE_BASE_URL')) {
    $__protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $__host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('SITE_BASE_URL', rtrim($__protocol . '://' . $__host, '/'));
    unset($__protocol, $__host);
}

define('DB_HOST', 'sql202.infinityfree.com');
define('DB_USER', 'if0_41974540');
define('DB_PASS', '8953933110ybL');
define('DB_NAME', 'if0_41974540_shopdb');

// Create database connection
try {
    // Enable error reporting for connection to see exact issue
    mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
} catch (Exception $e) {
    die("<div style='background:#fee2e2;color:#dc2626;padding:20px;margin:20px;border-radius:10px;font-family:sans-serif;'>
        <h3>Database Connection Failed</h3>
        <p><strong>Error Message:</strong> " . $e->getMessage() . "</p>
        <p>Please double-check your DB_HOST, DB_USER, DB_PASS, and DB_NAME in <code>config/db.php</code>.</p>
        </div>");
}

// Set charset to UTF-8 for Hindi/Unicode support
$conn->set_charset("utf8mb4");

// ---- Site Settings Helper ----
function getSiteSetting($conn, $key) {
    $stmt = $conn->prepare("SELECT setting_value FROM website_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return null;
}

// ---- Check maintenance mode (for customer-facing pages) ----
function checkMaintenanceMode($conn) {
    $status = getSiteSetting($conn, 'maintenance_mode');
    if ($status === '1') {
        // Don't block admin pages
        $currentPage = basename($_SERVER['PHP_SELF']);
        $adminPages = ['login.php', 'dashboard.php', 'products.php', 'orders.php', 'settings.php', 'add-product.php', 'edit-product.php', 'customers.php'];
        
        // Check if we're inside /admin/ folder
        $isAdminPage = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
        
        if (!$isAdminPage) {
            include __DIR__ . '/../includes/maintenance.php';
            exit;
        }
    }
}
?>
