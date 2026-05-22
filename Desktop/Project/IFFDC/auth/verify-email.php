<?php
// ============================================================
// auth/verify-email.php — Email Verification Handler
// ============================================================
require_once '../config/db.php';
require_once '../config/mailer.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';
$type    = 'error';

// --- Resend verification ---
if (isset($_GET['resend']) && $_GET['resend'] == 1) {
    $email = trim(strtolower($_GET['email'] ?? ''));
    if ($email) {
        $stmt = $conn->prepare("SELECT id, name, is_verified FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && !$user['is_verified']) {
            // Delete old token
            $del = $conn->prepare("DELETE FROM email_verifications WHERE user_id = ?");
            $del->bind_param("i", $user['id']);
            $del->execute();
            $del->close();

            // Create new token
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $ins = $conn->prepare("INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param("iss", $user['id'], $token, $expiresAt);
            $ins->execute();
            $ins->close();

            // Build verification URL — uses dynamic SITE_BASE_URL (no hardcoded /IFFDC/ path)
            $verifyLink = SITE_BASE_URL . "/auth/verify-email.php?token=$token";

            $result = sendMail($email, $user['name'], 'Verify Your Email – IFFDC Maharajpur', getVerificationEmailHTML($user['name'], $verifyLink));
            $message = $result['success'] ? 'Verification email resent! Please check your inbox.' : 'Could not send email. Dev link: <a href="'.$verifyLink.'">'.$verifyLink.'</a>';
            $type    = $result['success'] ? 'success' : 'error';
        } else {
            $message = $user ? 'Account is already verified.' : 'Email not found.';
        }
    }
}

// --- Verify token ---
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    $now   = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("SELECT ev.user_id, ev.expires_at, u.name FROM email_verifications ev JOIN users u ON u.id = ev.user_id WHERE ev.token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        $message = 'Invalid or already used verification link.';
    } elseif ($row['expires_at'] < $now) {
        $message = 'Verification link has expired. Please register again or resend.';
    } else {
        // Mark user as verified
        $upd = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $upd->bind_param("i", $row['user_id']);
        $upd->execute();
        $upd->close();

        // Delete token
        $del = $conn->prepare("DELETE FROM email_verifications WHERE user_id = ?");
        $del->bind_param("i", $row['user_id']);
        $del->execute();
        $del->close();

        $message = 'Email verified successfully! You can now <a href="login.php">login to your account</a>.';
        $type    = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | IFFDC Maharajpur</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg,#f0fdf4,#dcfce7); display:flex; justify-content:center; align-items:center; min-height:100vh; }
        .verify-card { background:white; padding:3rem; border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.12); max-width:460px; width:90%; text-align:center; }
        .icon-circle { width:80px; height:80px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2rem; margin:0 auto 1.5rem; }
        .success-icon { background:#f0fdf4; color:#15803d; }
        .error-icon   { background:#fef2f2; color:#dc2626; }
        h2 { font-size:1.4rem; margin-bottom:1rem; }
        p { color:#666; line-height:1.7; }
        p a { color:#15803d; font-weight:bold; text-decoration:none; }
        .btn { display:inline-block; background:#15803d; color:white; padding:12px 28px; border-radius:10px; text-decoration:none; font-weight:bold; margin-top:1.5rem; transition:0.3s; }
        .btn:hover { background:#14532d; }
    </style>
</head>
<body>
    <div class="verify-card">
        <?php if ($message): ?>
            <div class="icon-circle <?php echo $type === 'success' ? 'success-icon' : 'error-icon'; ?>">
                <i class="fas <?php echo $type === 'success' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
            </div>
            <h2><?php echo $type === 'success' ? 'Verified!' : 'Oops!'; ?></h2>
            <p><?php echo $message; ?></p>
            <a href="../index.php" class="btn">Go to Shop</a>
        <?php else: ?>
            <div class="icon-circle success-icon"><i class="fas fa-envelope"></i></div>
            <h2>Check Your Email</h2>
            <p>A verification link has been sent to your email address. Please click the link to activate your account.</p>
            <a href="../index.php" class="btn">Go to Shop</a>
        <?php endif; ?>
    </div>
</body>
</html>
