<?php
// ============================================================
// auth/login.php — Customer Login
// ============================================================
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, is_verified, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if ($user['status'] === 'blocked') {
                $error = 'Your account has been blocked. Contact admin.';
            } elseif (!$user['is_verified']) {
                $error = 'Please verify your email first. <a href="verify-email.php?resend=1&email=' . urlencode($email) . '">Resend verification email</a>.';
            } elseif (!password_verify($password, $user['password'])) {
                $error = 'Invalid email or password.';
            } else {
                // Successful login
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email']= $user['email'];

                $redirect = $_GET['redirect'] ?? '../index.php';
                header('Location: ' . $redirect);
                exit;
            }
        } else {
            $error = 'No account found with this email. <a href="register.php">Register?</a>';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | IFFDC Maharajpur</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #f0fdf4, #dcfce7); }
        .auth-page { display:flex; justify-content:center; align-items:center; min-height:100vh; padding:20px; }
        .auth-card {
            background:white; padding:2.5rem; border-radius:20px;
            box-shadow:0 20px 60px rgba(0,0,0,0.12); width:100%; max-width:420px;
        }
        .auth-logo { text-align:center; margin-bottom:1.5rem; }
        .auth-logo h2 { color:#15803d; font-size:1.6rem; margin:0; }
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
        .forgot-link { text-align:right; font-size:0.82rem; margin-top:5px; }
        .forgot-link a { color:#15803d; text-decoration:none; }
        .btn-primary {
            width:100%; background:linear-gradient(135deg,#15803d,#166534);
            color:white; border:none; padding:14px; border-radius:10px;
            font-weight:bold; font-size:1rem; cursor:pointer; transition:0.3s;
        }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(21,128,61,0.3); }
        .alert-error { background:#fef2f2; color:#dc2626; padding:12px 15px; border-radius:10px; margin-bottom:1rem; font-size:0.9rem; border-left:4px solid #dc2626; }
        .alert-error a { color:#dc2626; }
        .auth-footer { text-align:center; margin-top:1.5rem; font-size:0.85rem; color:#666; }
        .auth-footer a { color:#15803d; font-weight:bold; text-decoration:none; }
        .divider { display:flex; align-items:center; gap:10px; margin:1rem 0; }
        .divider::before, .divider::after { content:''; flex:1; height:1px; background:#e5e7eb; }
        .divider span { color:#9ca3af; font-size:0.85rem; }
    </style>
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div style="font-size:3rem;">🌿</div>
            <h2>Welcome Back!</h2>
            <p>Login to your IFFDC account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="login_email">Email Address</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="login_email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label for="login_password">Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="login_password" name="password" placeholder="Your password" required>
                </div>
                <div class="forgot-link"><a href="forgot-password.php">Forgot Password?</a></div>
            </div>

            <button type="submit" class="btn-primary" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="divider"><span>or</span></div>
        <div class="auth-footer">
            Don't have an account? <a href="register.php">Register Now</a><br><br>
            <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Shop</a>
        </div>
    </div>
</div>
<script>
document.getElementById('loginForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
});
</script>
</body>
</html>
