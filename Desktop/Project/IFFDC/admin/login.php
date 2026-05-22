<?php
// ============================================================
// admin/login.php — Admin Login (separate from customer login)
// ============================================================
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['admin_id'])) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt     = $conn->prepare("SELECT id, name, password FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid admin credentials.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | IFFDC Maharajpur</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#0f2027,#203a43,#2c5364);min-height:100vh;display:flex;align-items:center;justify-content:center;}
.card{background:white;border-radius:24px;padding:3rem 2.5rem;width:100%;max-width:420px;box-shadow:0 30px 80px rgba(0,0,0,.4);}
.logo-section{text-align:center;margin-bottom:2rem;}
.logo-icon{width:70px;height:70px;background:linear-gradient(135deg,#15803d,#14532d);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.8rem;color:white;}
h2{font-size:1.4rem;color:#1f2937;margin-bottom:.3rem;}
.sub{color:#9ca3af;font-size:.85rem;}
.form-group{margin-bottom:1.2rem;}
label{display:block;font-weight:600;font-size:.9rem;color:#374151;margin-bottom:6px;}
.input-wrap{position:relative;}
.input-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#9ca3af;}
input{width:100%;padding:12px 12px 12px 42px;border:1.5px solid #e5e7eb;border-radius:12px;outline:none;font-size:.95rem;transition:.2s;background:#f9fafb;}
input:focus{border-color:#15803d;box-shadow:0 0 0 3px rgba(21,128,61,.12);background:white;}
.btn{width:100%;background:linear-gradient(135deg,#15803d,#14532d);color:white;border:none;padding:14px;border-radius:12px;font-weight:bold;font-size:1rem;cursor:pointer;transition:.3s;margin-top:.5rem;}
.btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(21,128,61,.35);}
.alert{background:#fef2f2;color:#dc2626;padding:12px;border-radius:10px;font-size:.9rem;margin-bottom:1rem;border-left:4px solid #dc2626;}
.back-link{text-align:center;margin-top:1.5rem;font-size:.85rem;color:#9ca3af;}
.back-link a{color:#15803d;text-decoration:none;font-weight:600;}
.admin-chip{display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;color:#15803d;border:1px solid #dcfce7;border-radius:20px;padding:4px 12px;font-size:.78rem;font-weight:600;margin-top:.5rem;}
</style>
</head>
<body>
<div class="card">
  <div class="logo-section">
    <div class="logo-icon"><i class="fas fa-leaf"></i></div>
    <h2>Admin Panel</h2>
    <p class="sub">IFFDC Maharajpur Management</p>
    <div class="admin-chip"><i class="fas fa-shield-alt"></i> Secure Admin Access</div>
  </div>
  <?php if($error):?><div class="alert"><i class="fas fa-exclamation-circle"></i> <?php echo $error;?></div><?php endif;?>
  <form method="POST" id="adminLoginForm">
    <div class="form-group">
      <label>Admin Email</label>
      <div class="input-wrap"><i class="fas fa-envelope"></i><input type="email" name="email" placeholder="admin@iffdc.com" required autofocus value="<?php echo htmlspecialchars($_POST['email']??'');?>"></div>
    </div>
    <div class="form-group">
      <label>Password</label>
      <div class="input-wrap"><i class="fas fa-lock"></i><input type="password" name="password" placeholder="Admin password" required></div>
    </div>
    <button type="submit" class="btn" id="loginBtn"><i class="fas fa-sign-in-alt"></i> Login to Admin Panel</button>
  </form>
  <div class="back-link"><a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Shop</a></div>
</div>
<script>
document.getElementById('adminLoginForm').addEventListener('submit',function(){
  const btn=document.getElementById('loginBtn');
  btn.disabled=true;
  btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Logging in...';
});
</script>
</body></html>
