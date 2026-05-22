<?php
// ============================================================
// auth/forgot-password.php — Send OTP to Email
// ============================================================
require_once '../config/db.php';
require_once '../config/mailer.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? AND is_verified = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = 'No verified account found with this email.';
        } else {
            // Generate 6-digit OTP
            $otp       = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // Delete old OTPs for this email
            $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->bind_param("s", $email);
            $del->execute();
            $del->close();

            // Insert new OTP
            $ins = $conn->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $email, $otp, $expiresAt);
            $ins->execute();
            $ins->close();

            $result = sendMail($email, $user['name'], 'Password Reset OTP – IFFDC Maharajpur', getResetEmailHTML($user['name'], $otp));

            if ($result['success']) {
                $_SESSION['reset_email'] = $email;
                header('Location: reset-password.php');
                exit;
            } else {
                // Dev fallback - show OTP directly
                $success = "OTP: <strong style='font-size:1.5rem;letter-spacing:5px;'>$otp</strong> (Email failed – SMTP not configured). <a href='reset-password.php'>Enter OTP</a>";
                $_SESSION['reset_email'] = $email;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | IFFDC Maharajpur</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background:linear-gradient(135deg,#f0fdf4,#dcfce7); display:flex; justify-content:center; align-items:center; min-height:100vh; }
        .auth-card { background:white; padding:2.5rem; border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.12); width:100%; max-width:420px; }
        .auth-logo { text-align:center; margin-bottom:1.5rem; }
        .auth-logo .icon { font-size:3rem; }
        .auth-logo h2 { color:#15803d; font-size:1.4rem; margin:0.5rem 0 0; }
        .auth-logo p  { color:#666; font-size:0.9rem; }
        .form-group { margin-bottom:1.2rem; }
        .form-group label { display:block; margin-bottom:6px; font-weight:600; font-size:0.9rem; color:#374151; }
        .input-wrap { position:relative; }
        .input-wrap i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9ca3af; }
        .form-group input { width:100%; padding:12px 12px 12px 40px; border:1.5px solid #e5e7eb; border-radius:10px; outline:none; font-size:0.95rem; box-sizing:border-box; transition:0.2s; }
        .form-group input:focus { border-color:#15803d; box-shadow:0 0 0 3px rgba(21,128,61,0.1); }
        .btn-primary { width:100%; background:linear-gradient(135deg,#15803d,#166534); color:white; border:none; padding:14px; border-radius:10px; font-weight:bold; font-size:1rem; cursor:pointer; transition:0.3s; }
        .btn-primary:hover { transform:translateY(-2px); }
        .alert-error   { background:#fef2f2; color:#dc2626; padding:12px 15px; border-radius:10px; margin-bottom:1rem; font-size:0.9rem; border-left:4px solid #dc2626; }
        .alert-success { background:#f0fdf4; color:#15803d; padding:12px 15px; border-radius:10px; margin-bottom:1rem; font-size:0.9rem; border-left:4px solid #15803d; }
        .auth-footer { text-align:center; margin-top:1.5rem; font-size:0.85rem; color:#666; }
        .auth-footer a { color:#15803d; font-weight:bold; text-decoration:none; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-logo">
            <div class="icon">🔑</div>
            <h2>Forgot Password?</h2>
            <p>Enter your email to receive a reset OTP</p>
        </div>

        <?php if ($error):   ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert-success"><?php echo $success; ?></div><?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" id="forgotForm">
            <div class="form-group">
                <label>Email Address</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autofocus>
                </div>
            </div>
            <button type="submit" class="btn-primary" id="forgotBtn">
                <i class="fas fa-paper-plane"></i> Send OTP
            </button>
        </form>
        <?php endif; ?>

        <div class="auth-footer">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
<script>
document.getElementById('forgotForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('forgotBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending OTP...';
});
</script>
</body>
</html>
