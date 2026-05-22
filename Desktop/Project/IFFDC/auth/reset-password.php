<?php
// ============================================================
// auth/reset-password.php — OTP Verification & Password Reset
// ============================================================
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Must have email in session from forgot-password step
if (empty($_SESSION['reset_email'])) {
    header('Location: forgot-password.php');
    exit;
}
$email = $_SESSION['reset_email'];

$error   = '';
$success = '';
$step    = 'otp'; // 'otp' or 'password'

// Store verified OTP in session to move to step 2
if (isset($_SESSION['otp_verified'])) {
    $step = 'password';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // STEP 1: Verify OTP
    if (isset($_POST['otp']) && $step === 'otp') {
        $otp = trim($_POST['otp']);
        $now = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("SELECT id FROM password_resets WHERE email = ? AND otp = ? AND expires_at > ? AND used = 0");
        $stmt->bind_param("sss", $email, $otp, $now);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $_SESSION['otp_verified'] = true;
            header('Location: reset-password.php');
            exit;
        } else {
            $error = 'Invalid or expired OTP. Please try again.';
        }
    }

    // STEP 2: Set new password
    if (isset($_POST['password']) && $step === 'password') {
        $password = $_POST['password'];
        $confirm  = $_POST['confirm_password'];

        if (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $upd = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $upd->bind_param("ss", $hashed, $email);
            $upd->execute();
            $upd->close();

            // Mark OTP as used
            $mark = $conn->prepare("UPDATE password_resets SET used = 1 WHERE email = ?");
            $mark->bind_param("s", $email);
            $mark->execute();
            $mark->close();

            unset($_SESSION['reset_email'], $_SESSION['otp_verified']);
            $success = 'Password reset successfully! <a href="login.php">Login now</a>.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | IFFDC Maharajpur</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background:linear-gradient(135deg,#f0fdf4,#dcfce7); display:flex; justify-content:center; align-items:center; min-height:100vh; }
        .auth-card { background:white; padding:2.5rem; border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.12); width:100%; max-width:420px; }
        .auth-logo { text-align:center; margin-bottom:1.5rem; }
        .auth-logo h2 { color:#15803d; font-size:1.4rem; margin:0.5rem 0 0; }
        .auth-logo p  { color:#666; font-size:0.9rem; }
        .form-group { margin-bottom:1.2rem; }
        .form-group label { display:block; margin-bottom:6px; font-weight:600; font-size:0.9rem; color:#374151; }
        .input-wrap { position:relative; }
        .input-wrap i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9ca3af; }
        .form-group input { width:100%; padding:12px 12px 12px 40px; border:1.5px solid #e5e7eb; border-radius:10px; outline:none; font-size:0.95rem; box-sizing:border-box; transition:0.2s; }
        .form-group input:focus { border-color:#15803d; box-shadow:0 0 0 3px rgba(21,128,61,0.1); }
        .btn-primary { width:100%; background:linear-gradient(135deg,#15803d,#166534); color:white; border:none; padding:14px; border-radius:10px; font-weight:bold; font-size:1rem; cursor:pointer; transition:0.3s; }
        .alert-error   { background:#fef2f2; color:#dc2626; padding:12px 15px; border-radius:10px; margin-bottom:1rem; font-size:0.9rem; border-left:4px solid #dc2626; }
        .alert-success { background:#f0fdf4; color:#15803d; padding:12px 15px; border-radius:10px; margin-bottom:1rem; font-size:0.9rem; border-left:4px solid #15803d; }
        .alert-success a { color:#15803d; font-weight:bold; }
        .auth-footer { text-align:center; margin-top:1.5rem; font-size:0.85rem; }
        .auth-footer a { color:#15803d; font-weight:bold; text-decoration:none; }
        .otp-boxes { display:flex; gap:10px; justify-content:center; }
        .otp-boxes input { width:50px; height:55px; text-align:center; font-size:1.4rem; font-weight:bold; padding:0; border:2px solid #e5e7eb; border-radius:10px; }
        .otp-boxes input:focus { border-color:#15803d; outline:none; }
        .email-chip { background:#f0fdf4; color:#15803d; padding:6px 14px; border-radius:20px; font-size:0.85rem; font-weight:600; display:inline-block; margin-bottom:1rem; }
        .steps { display:flex; gap:10px; align-items:center; justify-content:center; margin-bottom:1.5rem; font-size:0.85rem; }
        .step { padding:4px 12px; border-radius:20px; font-weight:600; }
        .step.active { background:#15803d; color:white; }
        .step.done { background:#dcfce7; color:#15803d; }
        .step.inactive { background:#f3f4f6; color:#9ca3af; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-logo">
            <div style="font-size:2.5rem;"><?php echo $step === 'otp' ? '📩' : '🔒'; ?></div>
            <h2><?php echo $step === 'otp' ? 'Enter Your OTP' : 'Set New Password'; ?></h2>
            <p>Resetting password for: <span class="email-chip"><?php echo htmlspecialchars($email); ?></span></p>
        </div>

        <div class="steps">
            <span class="step <?php echo $step === 'otp' ? 'active' : 'done'; ?>">1. OTP</span>
            <span>→</span>
            <span class="step <?php echo $step === 'password' ? 'active' : 'inactive'; ?>">2. New Password</span>
        </div>

        <?php if ($error):   ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert-success"><?php echo $success; ?></div>
        <?php else: ?>

        <?php if ($step === 'otp'): ?>
        <form method="POST" id="otpForm">
            <div class="form-group" style="text-align:center;">
                <label>Enter the 6-digit OTP from your email</label>
                <div class="input-wrap" style="margin-top:10px;">
                    <i class="fas fa-key"></i>
                    <input type="text" name="otp" id="otpInput" placeholder="Enter 6-digit OTP" maxlength="6" style="letter-spacing:5px;font-size:1.2rem;text-align:center;" required autofocus>
                </div>
            </div>
            <button type="submit" class="btn-primary">
                <i class="fas fa-check"></i> Verify OTP
            </button>
        </form>

        <?php else: ?>
        <form method="POST" id="resetForm">
            <div class="form-group">
                <label>New Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Minimum 6 characters" required>
                </div>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <div class="input-wrap">
                    <i class="fas fa-shield-alt"></i>
                    <input type="password" name="confirm_password" placeholder="Re-enter password" required>
                </div>
            </div>
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Reset Password
            </button>
        </form>
        <?php endif; ?>

        <?php endif; ?>

        <div class="auth-footer">
            <a href="forgot-password.php"><i class="fas fa-arrow-left"></i> Start Over</a>
        </div>
    </div>
<script>
// Allow only numbers in OTP
document.getElementById('otpInput')?.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '');
});
</script>
</body>
</html>
