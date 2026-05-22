<?php
// ============================================================
// admin/settings.php — Site Settings & Maintenance Mode
// ============================================================
$pageTitle = 'Settings';
require_once 'includes/layout.php';

$msg  = '';
$type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Toggle maintenance mode
    if ($action === 'toggle_maintenance') {
        $current = getSiteSetting($conn, 'maintenance_mode');
        $new     = $current === '1' ? '0' : '1';
        $stmt    = $conn->prepare("UPDATE website_settings SET setting_value=? WHERE setting_key='maintenance_mode'");
        $stmt->bind_param("s", $new);
        $stmt->execute();
        $stmt->close();
        $msg = $new === '1' ? '⚠ Website is now OFFLINE (maintenance mode ON).' : '✅ Website is now ONLINE.';
        $type = $new === '1' ? 'error' : 'success';
    }

    // Update general settings
    if ($action === 'update_settings') {
        $fields = ['maintenance_message','shop_phone','shop_whatsapp','shop_address','shop_email','low_stock_alert'];
        foreach ($fields as $key) {
            if (isset($_POST[$key])) {
                $value = trim($_POST[$key]);
                $stmt  = $conn->prepare("UPDATE website_settings SET setting_value=? WHERE setting_key=?");
                $stmt->bind_param("ss", $value, $key);
                $stmt->execute();
                $stmt->close();
            }
        }
        $msg = 'Settings saved successfully!';
    }

    // Change admin password
    if ($action === 'change_password') {
        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        $adminId  = $_SESSION['admin_id'];
        $stmt     = $conn->prepare("SELECT password FROM admins WHERE id=?");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!password_verify($current, $admin['password'])) {
            $msg = 'Current password is incorrect.'; $type = 'error';
        } elseif (strlen($new) < 6) {
            $msg = 'New password must be at least 6 characters.'; $type = 'error';
        } elseif ($new !== $confirm) {
            $msg = 'New passwords do not match.'; $type = 'error';
        } else {
            $hashed = password_hash($new, PASSWORD_BCRYPT);
            $stmt   = $conn->prepare("UPDATE admins SET password=? WHERE id=?");
            $stmt->bind_param("si", $hashed, $adminId);
            $stmt->execute();
            $stmt->close();
            $msg = 'Password changed successfully!';
        }
    }
}

// Fetch all settings
$settings = [];
$result   = $conn->query("SELECT setting_key, setting_value FROM website_settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$maintenanceOn = ($settings['maintenance_mode'] ?? '0') === '1';
?>

<?php if($msg):?><div class="alert-<?php echo $type;?>"><?php echo $msg;?></div><?php endif;?>

<!-- MAINTENANCE MODE TOGGLE -->
<div class="card-box" style="margin-bottom:1.5rem;overflow:hidden;">
  <div style="background:<?php echo $maintenanceOn?'linear-gradient(135deg,#dc2626,#991b1b)':'linear-gradient(135deg,#15803d,#14532d)';?>;padding:2rem;color:white;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
      <div>
        <h2 style="margin:0;font-size:1.3rem;">
          <?php echo $maintenanceOn ? '🔴 Website is OFFLINE' : '🟢 Website is ONLINE';?>
        </h2>
        <p style="margin:.5rem 0 0;opacity:.85;font-size:.9rem;">
          <?php echo $maintenanceOn ? 'Customers see the maintenance page. Admin panel works normally.' : 'Your shop is live and accepting orders.';?>
        </p>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="toggle_maintenance">
        <button type="submit" style="background:rgba(255,255,255,.2);backdrop-filter:blur(5px);color:white;border:2px solid rgba(255,255,255,.4);padding:12px 28px;border-radius:12px;font-weight:bold;font-size:1rem;cursor:pointer;transition:.3s;" onmouseover="this.style.background='rgba(255,255,255,.35)'" onmouseout="this.style.background='rgba(255,255,255,.2)'">
          <?php echo $maintenanceOn ? '▶ Turn Website ONLINE' : '⏸ Turn Website OFFLINE';?>
        </button>
      </form>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;flex-wrap:wrap;">

<!-- GENERAL SETTINGS -->
<div class="card-box">
  <div class="card-box-header"><h3><i class="fas fa-store" style="color:#15803d;"></i> Shop Settings</h3></div>
  <div style="padding:1.5rem;">
    <form method="POST">
      <input type="hidden" name="action" value="update_settings">
      <div class="form-group">
        <label>Shop Phone Number</label>
        <input type="text" name="shop_phone" value="<?php echo htmlspecialchars($settings['shop_phone']??'');?>" placeholder="91XXXXXXXXXX">
      </div>
      <div class="form-group">
        <label>WhatsApp Number</label>
        <input type="text" name="shop_whatsapp" value="<?php echo htmlspecialchars($settings['shop_whatsapp']??'');?>" placeholder="91XXXXXXXXXX">
      </div>
      <div class="form-group">
        <label>Shop Email</label>
        <input type="email" name="shop_email" value="<?php echo htmlspecialchars($settings['shop_email']??'');?>" placeholder="info@iffdc.com">
      </div>
      <div class="form-group">
        <label>Shop Address</label>
        <textarea name="shop_address" rows="2"><?php echo htmlspecialchars($settings['shop_address']??'');?></textarea>
      </div>
      <div class="form-group">
        <label>Low Stock Alert Threshold (qty)</label>
        <input type="number" name="low_stock_alert" value="<?php echo htmlspecialchars($settings['low_stock_alert']??'10');?>" min="1">
        <div style="font-size:.78rem;color:#9ca3af;margin-top:4px;">Alert shown when stock falls below this number.</div>
      </div>
      <div class="form-group">
        <label>Maintenance Page Message</label>
        <textarea name="maintenance_message" rows="2"><?php echo htmlspecialchars($settings['maintenance_message']??'');?></textarea>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
    </form>
  </div>
</div>

<!-- CHANGE PASSWORD -->
<div class="card-box">
  <div class="card-box-header"><h3><i class="fas fa-key" style="color:#15803d;"></i> Change Admin Password</h3></div>
  <div style="padding:1.5rem;">
    <form method="POST">
      <input type="hidden" name="action" value="change_password">
      <div class="form-group">
        <label>Current Password</label>
        <input type="password" name="current_password" placeholder="••••••••" required>
      </div>
      <div class="form-group">
        <label>New Password</label>
        <input type="password" name="new_password" placeholder="Minimum 6 characters" required>
      </div>
      <div class="form-group">
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" placeholder="Re-enter new password" required>
      </div>
      <button type="submit" class="btn btn-warning"><i class="fas fa-lock"></i> Change Password</button>
    </form>
  </div>
  <!-- Admin Info -->
  <div style="padding:0 1.5rem 1.5rem;">
    <div style="background:#f8fafc;border-radius:12px;padding:1rem;font-size:.88rem;">
      <div style="font-weight:600;color:#64748b;margin-bottom:.5rem;">Admin Account</div>
      <div>👤 <?php echo htmlspecialchars($_SESSION['admin_name']??'Admin');?></div>
      <div style="margin-top:.3rem;">🔐 Default: admin@iffdc.com / Admin@1234</div>
      <div style="margin-top:.3rem;color:#dc2626;font-weight:600;">⚠ Change default password immediately!</div>
    </div>
  </div>
</div>

</div><!-- end grid -->

</div></div>
</body></html>
