<?php
// ============================================================
// admin/includes/layout.php — Reusable admin HTML layout
// Usage: ob_start() your content, then include this file
// ============================================================
require_once __DIR__ . '/auth-check.php';
require_once __DIR__ . '/../../config/db.php';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$adminName   = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');

// Count badges for sidebar
$pendingOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetch_row()[0];
$lowStockAlert = intval(getSiteSetting($conn,'low_stock_alert') ?? 10);
$lowStockCount = $conn->query("SELECT COUNT(*) FROM products WHERE stock_qty <= $lowStockAlert AND is_active=1")->fetch_row()[0];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle ?? 'Admin Panel';?> | IFFDC Maharajpur</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f1f5f9;color:#1e293b;display:flex;min-height:100vh;}
/* SIDEBAR */
.sidebar{width:260px;min-height:100vh;background:linear-gradient(180deg,#0f2027 0%,#14532d 100%);position:fixed;top:0;left:0;z-index:100;display:flex;flex-direction:column;transition:.3s;}
.sidebar-logo{padding:1.5rem;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:12px;}
.sidebar-logo .icon{width:42px;height:42px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:white;}
.sidebar-logo h2{color:white;font-size:1rem;line-height:1.3;}
.sidebar-logo p{color:rgba(255,255,255,.6);font-size:.72rem;}
.sidebar-nav{flex:1;padding:1rem 0;overflow-y:auto;}
.nav-section{padding:.5rem 1rem;font-size:.7rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:1px;margin-top:.5rem;}
.nav-item{display:flex;align-items:center;gap:12px;padding:.85rem 1.5rem;color:rgba(255,255,255,.75);text-decoration:none;transition:.2s;font-size:.9rem;position:relative;}
.nav-item:hover{background:rgba(255,255,255,.1);color:white;}
.nav-item.active{background:rgba(255,255,255,.18);color:white;font-weight:600;}
.nav-item.active::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:#4ade80;border-radius:0 3px 3px 0;}
.nav-item i{width:20px;text-align:center;font-size:.95rem;}
.badge-pill{background:#dc2626;color:white;font-size:.68rem;font-weight:bold;padding:2px 7px;border-radius:10px;margin-left:auto;}
.badge-pill.warning{background:#f59e0b;}
.sidebar-footer{padding:1rem 1.5rem;border-top:1px solid rgba(255,255,255,.1);}
.admin-info{display:flex;align-items:center;gap:10px;}
.admin-avatar{width:36px;height:36px;background:linear-gradient(135deg,#22c55e,#15803d);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:bold;color:white;}
.admin-name{color:white;font-size:.88rem;font-weight:600;}
.admin-role{color:rgba(255,255,255,.5);font-size:.72rem;}
/* MAIN */
.main-content{margin-left:260px;flex:1;min-height:100vh;display:flex;flex-direction:column;}
.topbar{background:white;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;box-shadow:0 1px 4px rgba(0,0,0,.06);position:sticky;top:0;z-index:50;}
.topbar h1{font-size:1.2rem;color:#1e293b;}
.topbar-actions{display:flex;align-items:center;gap:12px;}
.topbar-btn{padding:8px 16px;border-radius:10px;border:none;cursor:pointer;font-weight:600;font-size:.85rem;display:flex;align-items:center;gap:6px;text-decoration:none;transition:.2s;}
.page-content{padding:2rem;flex:1;}
/* CARDS */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2rem;}
.stat-card{background:white;border-radius:16px;padding:1.5rem;box-shadow:0 2px 10px rgba(0,0,0,.06);display:flex;align-items:center;gap:1rem;transition:.3s;}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1);}
.stat-icon{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;}
.stat-value{font-size:1.8rem;font-weight:800;line-height:1;}
.stat-label{font-size:.82rem;color:#94a3b8;margin-top:.3rem;}
/* TABLES */
.card-box{background:white;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);overflow:hidden;margin-bottom:1.5rem;}
.card-box-header{padding:1.2rem 1.5rem;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #f1f5f9;}
.card-box-header h3{font-size:1rem;color:#1e293b;}
table{width:100%;border-collapse:collapse;}
th{background:#f8fafc;padding:.9rem 1rem;text-align:left;font-size:.8rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.5px;}
td{padding:.9rem 1rem;border-bottom:1px solid #f1f5f9;font-size:.88rem;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#f8fafc;}
.badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:600;text-transform:capitalize;}
.badge-pending{background:#fef3c7;color:#92400e;}
.badge-confirmed{background:#dbeafe;color:#1e40af;}
.badge-delivered{background:#dcfce7;color:#14532d;}
.badge-cancelled{background:#fee2e2;color:#991b1b;}
.badge-instock{background:#dcfce7;color:#14532d;}
.badge-outofstock{background:#fee2e2;color:#991b1b;}
/* FORMS */
.form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem;}
.form-group{margin-bottom:1rem;}
.form-group label{display:block;font-weight:600;font-size:.85rem;color:#374151;margin-bottom:6px;}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:10px;outline:none;font-size:.9rem;transition:.2s;background:#f8fafc;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:#15803d;box-shadow:0 0 0 3px rgba(21,128,61,.1);background:white;}
.form-group textarea{resize:vertical;min-height:80px;}
.btn{padding:10px 22px;border:none;border-radius:10px;font-weight:600;font-size:.9rem;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:7px;text-decoration:none;}
.btn-primary{background:linear-gradient(135deg,#15803d,#14532d);color:white;}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(21,128,61,.3);}
.btn-danger{background:#dc2626;color:white;}
.btn-danger:hover{background:#b91c1c;}
.btn-warning{background:#f59e0b;color:white;}
.btn-info{background:#3b82f6;color:white;}
.btn-sm{padding:7px 14px;font-size:.8rem;border-radius:8px;}
.alert-success{background:#f0fdf4;color:#15803d;padding:12px 16px;border-radius:10px;border-left:4px solid #15803d;margin-bottom:1rem;font-size:.9rem;}
.alert-error{background:#fef2f2;color:#dc2626;padding:12px 16px;border-radius:10px;border-left:4px solid #dc2626;margin-bottom:1rem;font-size:.9rem;}
/* Hamburger mobile */
.sidebar-toggle{display:none;background:none;border:none;font-size:1.3rem;cursor:pointer;color:#1e293b;}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%);}
  .sidebar.open{transform:translateX(0);}
  .main-content{margin-left:0;}
  .sidebar-toggle{display:block;}
  .stats-grid{grid-template-columns:repeat(2,1fr);}
}
/* Image preview */
.img-preview{width:80px;height:80px;object-fit:cover;border-radius:10px;border:2px solid #e5e7eb;}
</style>
</head>
<body>
<div class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="icon"><i class="fas fa-leaf"></i></div>
    <div><h2>IFFDC Admin</h2><p>Maharajpur Panel</p></div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <a href="dashboard.php" class="nav-item <?php echo $currentPage==='dashboard'?'active':'';?>"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>

    <div class="nav-section">Shop</div>
    <a href="products.php"  class="nav-item <?php echo $currentPage==='products'?'active':'';?>"><i class="fas fa-box"></i><span>Products</span><?php if($lowStockCount>0):?><span class="badge-pill warning"><?php echo $lowStockCount;?></span><?php endif;?></a>
    <a href="add-product.php" class="nav-item <?php echo $currentPage==='add-product'?'active':'';?>"><i class="fas fa-plus-circle"></i><span>Add Product</span></a>

    <div class="nav-section">Orders & Customers</div>
    <a href="orders.php"    class="nav-item <?php echo $currentPage==='orders'?'active':'';?>"><i class="fas fa-shopping-bag"></i><span>Orders</span><?php if($pendingOrders>0):?><span class="badge-pill"><?php echo $pendingOrders;?></span><?php endif;?></a>
    <a href="customers.php" class="nav-item <?php echo $currentPage==='customers'?'active':'';?>"><i class="fas fa-users"></i><span>Customers</span></a>

    <div class="nav-section">Settings</div>
    <a href="settings.php"  class="nav-item <?php echo $currentPage==='settings'?'active':'';?>"><i class="fas fa-cog"></i><span>Settings</span></a>
    <a href="../index.php"  class="nav-item" target="_blank"><i class="fas fa-external-link-alt"></i><span>View Shop</span></a>
    <a href="logout.php"    class="nav-item" style="color:rgba(248,113,113,.8);"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
  </nav>
  <div class="sidebar-footer">
    <div class="admin-info">
      <div class="admin-avatar"><?php echo strtoupper(substr($adminName,0,1));?></div>
      <div><div class="admin-name"><?php echo $adminName;?></div><div class="admin-role">Administrator</div></div>
    </div>
  </div>
</div>

<div class="main-content">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:12px;">
      <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
      <h1><?php echo $pageTitle ?? 'Dashboard';?></h1>
    </div>
    <div class="topbar-actions">
      <span style="font-size:.85rem;color:#94a3b8;"><?php echo date('d M Y');?></span>
      <a href="settings.php" class="topbar-btn" style="background:#f1f5f9;color:#1e293b;"><i class="fas fa-cog"></i></a>
    </div>
  </div>
  <div class="page-content">
