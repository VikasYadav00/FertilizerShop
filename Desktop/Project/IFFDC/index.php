<?php
require_once 'config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
checkMaintenanceMode($conn);
$isLoggedIn = isset($_SESSION['user_id']);
$userName   = $isLoggedIn ? htmlspecialchars($_SESSION['user_name']) : '';
$shopPhone  = getSiteSetting($conn, 'shop_whatsapp') ?? '910000000000';
$cats       = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$products   = $conn->query("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1 ORDER BY p.id DESC")->fetch_all(MYSQLI_ASSOC);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IFFDC Maharajpur | Krishak Seva Kendra</title>
<meta name="description" content="Buy IFFDC fertilizers, DAP, Nano Urea, Bio-Fertilizers and Seeds in Maharajpur.">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
.discount-badge{background:#dc2626;color:#fff;font-size:.7rem;font-weight:bold;padding:3px 8px;border-radius:12px;position:absolute;top:10px;right:10px;}
.card-img-wrap{position:relative;}
.price-original{font-size:.85rem;color:#9ca3af;text-decoration:line-through;margin-right:6px;}
.price-offer{font-size:1.4rem;font-weight:bold;color:#15803d;}
.out-of-stock-overlay{position:absolute;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;}
.out-of-stock-overlay span{background:#dc2626;color:#fff;padding:6px 16px;border-radius:20px;font-weight:bold;font-size:.85rem;}
.user-bar{background:#f0fdf4;border-bottom:2px solid #dcfce7;padding:8px 1rem;text-align:center;font-size:.9rem;color:#15803d;font-weight:600;}
.user-bar a{color:#15803d;margin:0 8px;text-decoration:none;}
.user-bar a:last-child{color:#dc2626;}
</style>
</head>
<body>
<?php if($isLoggedIn):?>
<div class="user-bar">👋 Welcome, <?php echo $userName;?>!
  <a href="customer/orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a>
  <a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
<?php endif;?>
<nav class="navbar"><div class="nav-container">
  <a href="index.php" class="logo" style="text-decoration:none;color:inherit;"><div><h1>IFFDC Maharajpur</h1><span>KRISHAK SEVA KENDRA</span></div></a>
  <div class="nav-actions">
    <?php if($isLoggedIn):?>
      <div style="position:relative;">
        <button onclick="document.getElementById('ud').style.display=document.getElementById('ud').style.display==='none'?'block':'none'" style="background:none;border:none;color:white;cursor:pointer;display:flex;align-items:center;gap:6px;font-weight:600;">
          <i class="fas fa-user-circle" style="font-size:1.3rem;"></i><?php echo $userName;?>
        </button>
        <div id="ud" style="display:none;position:absolute;top:120%;right:0;background:white;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.15);min-width:170px;z-index:999;overflow:hidden;">
          <a href="customer/orders.php" style="display:flex;gap:10px;padding:12px 16px;color:#1f2937;text-decoration:none;font-size:.9rem;"><i class="fas fa-shopping-bag" style="color:#15803d;"></i>My Orders</a>
          <a href="auth/logout.php" style="display:flex;gap:10px;padding:12px 16px;color:#dc2626;text-decoration:none;font-size:.9rem;border-top:1px solid #f3f4f6;"><i class="fas fa-sign-out-alt"></i>Logout</a>
        </div>
      </div>
    <?php else:?>
      <a href="auth/login.php" class="login-link"><i class="fas fa-user-circle"></i><span>Login</span></a>
    <?php endif;?>
    <div class="cart-status" id="cart-btn" style="cursor:pointer;"><i class="fas fa-shopping-cart"></i><span id="cart-count">0</span></div>
    <a href="tel:+91<?php echo $shopPhone;?>" class="call-btn"><i class="fas fa-phone"></i> Call Now</a>
  </div>
</div></nav>

<header class="hero">
  <h2>Quality Fertilizers for Maharajpur Farmers</h2>
  <p>Official IFFDC products to help you grow more and earn more.</p>
  <?php if(!$isLoggedIn):?>
  <div style="margin-top:1.5rem;">
    <a href="auth/login.php" style="background:white;color:#15803d;padding:12px 26px;border-radius:30px;font-weight:bold;text-decoration:none;margin:5px;display:inline-block;"><i class="fas fa-sign-in-alt"></i> Login to Order</a>
    <a href="auth/register.php" style="background:#facc15;color:#14532d;padding:12px 26px;border-radius:30px;font-weight:bold;text-decoration:none;margin:5px;display:inline-block;"><i class="fas fa-user-plus"></i> Register Free</a>
  </div>
  <?php endif;?>
</header>

<div class="search-container"><div class="search-box"><i class="fas fa-search"></i><input type="text" id="product-search" placeholder="Search for urea, dap, seeds..."></div></div>

<div class="filter-container" id="filters">
  <button class="filter-btn active" data-cat="All">All</button>
  <?php foreach($cats as $cat):?><button class="filter-btn" data-cat="<?php echo htmlspecialchars($cat['name']);?>"><?php echo htmlspecialchars($cat['name']);?></button><?php endforeach;?>
</div>

<main class="product-grid" id="product-list"></main>
<div id="pagination" style="display:flex;justify-content:center;gap:8px;padding:1rem 0 3rem;flex-wrap:wrap;"></div>

<!-- CART SIDEBAR -->
<div id="cart-sidebar" class="cart-sidebar">
  <div class="cart-header"><h2><i class="fas fa-shopping-cart"></i> Cart</h2><button id="close-cart" class="close-btn">&times;</button></div>
  <div id="cart-items-container" class="cart-items-container"></div>
  <div class="cart-footer">
    <div class="total-row"><span>Total:</span><span id="cart-total">₹0</span></div>
    <?php if($isLoggedIn):?>
      <div id="checkout-fields" style="margin-bottom:15px;text-align:left;">
        <label style="font-size:.8rem;color:#666;font-weight:bold;margin-bottom:4px;display:block;">Delivery Details <span style="color:red">*</span></label>
        <input type="text" id="checkout-name" placeholder="Full Name" style="width:100%;padding:10px;margin-bottom:8px;border-radius:8px;border:1px solid #e5e7eb;box-sizing:border-box;outline:none;" required>
        <input type="text" id="checkout-phone" placeholder="Phone Number" maxlength="10" style="width:100%;padding:10px;margin-bottom:8px;border-radius:8px;border:1px solid #e5e7eb;box-sizing:border-box;outline:none;" required>
        <input type="text" id="checkout-address" placeholder="Full Address" style="width:100%;padding:10px;margin-bottom:8px;border-radius:8px;border:1px solid #e5e7eb;box-sizing:border-box;outline:none;" required>
        <input type="text" id="checkout-pincode" placeholder="Pincode (e.g. 208001)" maxlength="6" style="width:100%;padding:10px;border-radius:8px;border:1px solid #e5e7eb;box-sizing:border-box;outline:none;" required>
        
        <label style="font-size:.8rem;color:#666;font-weight:bold;margin:10px 0 4px;display:block;">Payment Method <span style="color:red">*</span></label>
        <div style="display:flex;gap:10px;margin-bottom:8px;">
            <label style="flex:1;background:#f9fafb;border:1px solid #e5e7eb;padding:10px;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:6px;font-size:.9rem;"><input type="radio" name="payment_method" value="cod" checked> <i class="fas fa-truck" style="color:#15803d;"></i> Cash on Delivery</label>
            <label style="flex:1;background:#f9fafb;border:1px solid #e5e7eb;padding:10px;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:6px;font-size:.9rem;"><input type="radio" name="payment_method" value="online"> <i class="fas fa-credit-card" style="color:#2563eb;"></i> Pay Online</label>
        </div>
      </div>
      <button class="checkout-btn" onclick="checkoutOrder()" style="background:linear-gradient(135deg,#15803d,#166534);margin-bottom:10px;"><i class="fas fa-check-circle"></i> Place Order</button>
      <button class="checkout-btn" onclick="checkoutWhatsApp()"><i class="fab fa-whatsapp"></i> WhatsApp Order</button>
    <?php else:?>
      <button class="checkout-btn" onclick="window.location.href='auth/login.php'" style="background:#1d4ed8;"><i class="fas fa-lock"></i> Login to Checkout</button>
    <?php endif;?>
  </div>
</div>
<div id="cart-overlay" class="cart-overlay"></div>

<footer>
  <div style="max-width:1200px;margin:auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:2rem;text-align:left;">
    <div><h3 style="color:white;margin-bottom:.5rem;">IFFDC Maharajpur</h3><p style="margin:0;font-size:.85rem;">Krishak Seva Kendra</p><p style="font-size:.85rem;margin-top:1rem;line-height:1.6;"><?php echo htmlspecialchars(getSiteSetting($conn,'shop_address')??'');?></p></div>
    <div><h4 style="color:white;margin-bottom:1rem;">Quick Links</h4>
      <a href="auth/login.php" style="display:block;color:#9ca3af;text-decoration:none;margin-bottom:.5rem;font-size:.9rem;">🔐 Login</a>
      <a href="auth/register.php" style="display:block;color:#9ca3af;text-decoration:none;margin-bottom:.5rem;font-size:.9rem;">📝 Register</a>
      <a href="customer/orders.php" style="display:block;color:#9ca3af;text-decoration:none;font-size:.9rem;">📦 My Orders</a>
    </div>
    <div><h4 style="color:white;margin-bottom:1rem;">Contact</h4>
      <p style="font-size:.85rem;margin:0 0 .5rem;">📧 skvs3044@gmail.com</p>
      <p style="font-size:.85rem;margin:0;">📞 +91 8318257541</p>
    </div>
  </div>
  <hr style="border-color:#374151;margin:2rem 0;">
  <p style="text-align:center;margin:0;font-size:.85rem;">© 2026 IFFDC Maharajpur. Designed by Vikas Yadav.</p>
</footer>

<script>
const IS_LOGGED_IN = <?php echo $isLoggedIn?'true':'false';?>;
const SHOP_PHONE   = '<?php echo $shopPhone;?>';
const PRODUCTS_DATA = <?php echo json_encode($products);?>;
</script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="script.js?v=<?php echo time(); ?>"></script>
</body></html>
