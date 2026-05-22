<?php
// ============================================================
// auth/register.php — Customer Registration
// ============================================================
require_once '../config/db.php';
require_once '../config/mailer.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Sanitize inputs ---
    $name     = trim(htmlspecialchars($_POST['name'] ?? ''));
    $email    = trim(strtolower($_POST['email'] ?? ''));
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // --- Validate ---
    if (empty($name))                   $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!preg_match('/^[0-9]{10}$/', $phone))       $errors[] = 'Enter a valid 10-digit phone number.';
    if (strlen($password) < 6)          $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)         $errors[] = 'Passwords do not match.';

    // --- Check duplicate email ---
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'This email is already registered. <a href="login.php">Login instead?</a>';
        }
        $stmt->close();
    }

    // --- Create account ---
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);

        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            $stmt->close();

            // Generate verification token
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $stmt2 = $conn->prepare("INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt2->bind_param("iss", $userId, $token, $expiresAt);
            $stmt2->execute();
            $stmt2->close();

            // Build verification URL — uses dynamic SITE_BASE_URL (no hardcoded /IFFDC/ path)
            $verifyLink = SITE_BASE_URL . "/auth/verify-email.php?token=$token";

            // Send verification email
            $htmlBody = getVerificationEmailHTML($name, $verifyLink);
            $mailResult = sendMail($email, $name, 'Verify Your Email – IFFDC Maharajpur', $htmlBody);

            if ($mailResult['success']) {
                $success = 'Registration successful! Please check your email to verify your account.';
            } else {
                // Account created but email failed — show manual link for dev
                $success = "Account created! Email sending failed (check SMTP config). <br>
                            <small style='color:#666;'>Dev Link: <a href='$verifyLink'>$verifyLink</a></small>";
            }
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | IFFDC Maharajpur</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #f0fdf4, #dcfce7); }
        .auth-page { display:flex; justify-content:center; align-items:center; min-height:100vh; padding:20px; }
        .auth-card {
            background: white; padding: 2.5rem; border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.12); width:100%; max-width:450px;
        }
        .auth-logo { text-align:center; margin-bottom:1.5rem; }
        .auth-logo h2 { color:#15803d; font-size:1.5rem; margin:0; }
        .auth-logo p  { color:#666; font-size:0.9rem; margin:5px 0 0; }
        .form-group { margin-bottom:1.2rem; }
        .form-group label { display:block; margin-bottom:6px; font-weight:600; font-size:0.9rem; color:#374151; }
        .input-wrap { position:relative; }
        .input-wrap i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9ca3af; }
        .form-group input {
            width:100%; padding:12px 12px 12px 40px; border:1.5px solid #e5e7eb;
            border-radius:10px; outline:none; font-size:0.95rem; box-sizing:border-box; transition:0.2s;
        }
        .form-group input:focus { border-color:#15803d; box-shadow:0 0 0 3px rgba(21,128,61,0.1); }
        .btn-primary {
            width:100%; background:linear-gradient(135deg,#15803d,#166534);
            color:white; border:none; padding:14px; border-radius:10px;
            font-weight:bold; font-size:1rem; cursor:pointer; transition:0.3s;
        }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(21,128,61,0.3); }
        .alert-error  { background:#fef2f2; color:#dc2626; padding:12px 15px; border-radius:10px; margin-bottom:1rem; font-size:0.9rem; border-left:4px solid #dc2626; }
        .alert-success{ background:#f0fdf4; color:#15803d; padding:12px 15px; border-radius:10px; margin-bottom:1rem; font-size:0.9rem; border-left:4px solid #15803d; }
        .auth-footer { text-align:center; margin-top:1.5rem; font-size:0.85rem; color:#666; }
        .auth-footer a { color:#15803d; font-weight:bold; text-decoration:none; }
        .divider { display:flex; align-items:center; gap:10px; margin:1rem 0; }
        .divider::before, .divider::after { content:''; flex:1; height:1px; background:#e5e7eb; }
        .divider span { color:#9ca3af; font-size:0.85rem; }
        .password-hint { font-size:0.78rem; color:#9ca3af; margin-top:5px; }
    </style>
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <h2>🌿 IFFDC Maharajpur</h2>
            <p>Create your Krishak account</p>
        </div>

        <?php if ($success): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <div class="auth-footer">Already verified? <a href="login.php">Login here</a></div>
        <?php else: ?>

        <?php if (!empty($errors)): ?>
            <div class="alert-error">
                <?php foreach ($errors as $e): ?>
                    <div>⚠ <?php echo $e; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="registerForm">
            <div class="form-group">
                <label for="reg_name">Full Name</label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" id="reg_name" name="name" placeholder="Your full name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="reg_email">Email Address</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="reg_email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="reg_phone">Phone Number</label>
                <div class="input-wrap">
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="reg_phone" name="phone" placeholder="10-digit mobile number" maxlength="10" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="reg_password">Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="reg_password" name="password" placeholder="Minimum 6 characters" required>
                </div>
                <div class="password-hint">Use at least 6 characters with letters and numbers.</div>
            </div>
            <div class="form-group">
                <label for="reg_confirm">Confirm Password</label>
                <div class="input-wrap">
                    <i class="fas fa-shield-alt"></i>
                    <input type="password" id="reg_confirm" name="confirm_password" placeholder="Re-enter password" required>
                </div>
            </div>

            <button type="submit" class="btn-primary" id="registerBtn">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="divider"><span>or</span></div>
        <div class="auth-footer">
            Already have an account? <a href="login.php">Login here</a><br><br>
            <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Shop</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<script>
document.getElementById('registerForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('registerBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
});
// Phone: numbers only
document.getElementById('reg_phone')?.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 10);
});
</script>
</body>
</html>
