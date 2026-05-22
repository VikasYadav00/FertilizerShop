<?php
// ============================================================
// customer/orders.php — Customer Order History
// ============================================================
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php?redirect=../customer/orders.php'); exit; }

$userId = $_SESSION['user_id'];
$orders = $conn->query("SELECT o.*, COUNT(oi.id) AS item_count FROM orders o LEFT JOIN order_items oi ON oi.order_id = o.id WHERE o.user_id = $userId GROUP BY o.id ORDER BY o.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Order detail popup
$orderId = intval($_GET['id'] ?? 0);
$orderDetail = null;
$orderItems  = [];
if ($orderId) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $orderDetail = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($orderDetail) {
        $orderItems = $conn->query("SELECT * FROM order_items WHERE order_id = $orderId")->fetch_all(MYSQLI_ASSOC);
    }
}

$statusColors = ['pending'=>'#f59e0b','confirmed'=>'#3b82f6','delivered'=>'#15803d','cancelled'=>'#dc2626'];
$statusIcons  = ['pending'=>'clock','confirmed'=>'check-circle','delivered'=>'truck','cancelled'=>'times-circle'];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders | IFFDC Maharajpur</title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
body{background:#f9fafb;}
.page-header{background:linear-gradient(135deg,#15803d,#14532d);color:white;padding:2rem 1rem;}
.page-header h1{margin:0;font-size:1.5rem;}
.page-header p{margin:.3rem 0 0;opacity:.85;font-size:.9rem;}
.container{max-width:900px;margin:2rem auto;padding:0 1rem;}
.order-card{background:white;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.06);margin-bottom:1.2rem;overflow:hidden;transition:.3s;}
.order-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.1);}
.order-top{display:flex;justify-content:space-between;align-items:center;padding:1.2rem 1.5rem;flex-wrap:wrap;gap:.5rem;}
.order-number{font-weight:bold;font-size:.95rem;color:#1f2937;}
.order-meta{font-size:.82rem;color:#9ca3af;margin-top:.2rem;}
.badge{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;font-size:.8rem;font-weight:600;text-transform:capitalize;}
.order-bottom{border-top:1px solid #f3f4f6;padding:.8rem 1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;}
.btn-sm{padding:7px 18px;border-radius:8px;border:none;cursor:pointer;font-weight:600;font-size:.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:.2s;}
.empty-state{text-align:center;padding:4rem 1rem;color:#9ca3af;}
.empty-state i{font-size:3.5rem;display:block;margin-bottom:1rem;}
/* Modal */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;align-items:center;justify-content:center;}
.modal-bg.active{display:flex;}
.modal{background:white;border-radius:20px;max-width:600px;width:90%;max-height:85vh;overflow-y:auto;}
.modal-header{background:linear-gradient(135deg,#15803d,#14532d);color:white;padding:1.2rem 1.5rem;border-radius:20px 20px 0 0;display:flex;justify-content:space-between;align-items:center;}
.modal-body{padding:1.5rem;}
.item-row{display:flex;justify-content:space-between;align-items:center;padding:.8rem 0;border-bottom:1px solid #f3f4f6;font-size:.9rem;}
.item-row:last-child{border-bottom:none;}
.total-row-modal{display:flex;justify-content:space-between;font-weight:bold;font-size:1.1rem;padding-top:1rem;border-top:2px solid #dcfce7;color:#15803d;}
</style>
</head>
<body>
<nav class="navbar"><div class="nav-container">
  <a href="../index.php" class="logo" style="text-decoration:none;color:inherit;"><div><h1>IFFDC Maharajpur</h1><span>KRISHAK SEVA KENDRA</span></div></a>
  <div class="nav-actions">
    <a href="../auth/logout.php" class="login-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    <a href="../index.php" class="call-btn"><i class="fas fa-store"></i> Shop</a>
  </div>
</div></nav>

<div class="page-header">
  <div style="max-width:900px;margin:auto;">
    <h1><i class="fas fa-shopping-bag"></i> My Orders</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']);?> — track all your IFFDC orders here.</p>
  </div>
</div>

<div class="container">
<?php if(empty($orders)):?>
  <div class="empty-state">
    <i class="fas fa-box-open"></i>
    <h2>No Orders Yet</h2>
    <p>You haven't placed any orders yet.</p>
    <a href="../index.php" style="display:inline-block;margin-top:1rem;background:#15803d;color:white;padding:12px 28px;border-radius:10px;text-decoration:none;font-weight:bold;"><i class="fas fa-store"></i> Start Shopping</a>
  </div>
<?php else:?>
  <p style="color:#9ca3af;font-size:.9rem;margin-bottom:1rem;"><?php echo count($orders);?> order(s) found.</p>
  <?php foreach($orders as $o):
    $color = $statusColors[$o['status']] ?? '#9ca3af';
    $icon  = $statusIcons[$o['status']] ?? 'info-circle';
  ?>
  <div class="order-card">
    <div class="order-top">
      <div>
        <div class="order-number"><i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($o['order_number']);?></div>
        <div class="order-meta"><?php echo $o['item_count'];?> item(s) · <?php echo date('d M Y, h:i A', strtotime($o['created_at']));?></div>
      </div>
      <span class="badge" style="background:<?php echo $color;?>20;color:<?php echo $color;?>;border:1px solid <?php echo $color;?>40;">
        <i class="fas fa-<?php echo $icon;?>"></i> <?php echo ucfirst($o['status']);?>
      </span>
    </div>
    <div class="order-bottom">
      <span style="font-weight:bold;color:#15803d;font-size:1.1rem;">₹<?php echo number_format($o['total_amount'],2);?></span>
      <div style="display:flex;gap:8px;">
        <a href="../invoice.php?id=<?php echo $o['id'];?>" target="_blank" class="btn-sm" style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;">
          <i class="fas fa-file-invoice"></i> Invoice
        </a>
        <a href="?id=<?php echo $o['id'];?>" class="btn-sm" style="background:#f0fdf4;color:#15803d;border:1px solid #dcfce7;">
          <i class="fas fa-eye"></i> Details
        </a>
      </div>
    </div>
  </div>
  <?php endforeach;?>
<?php endif;?>
</div>

<!-- Order Detail Modal -->
<?php if($orderDetail && $orderItems):
  $color = $statusColors[$orderDetail['status']] ?? '#9ca3af';
  $icon  = $statusIcons[$orderDetail['status']]  ?? 'info-circle';
?>
<div class="modal-bg active" id="orderModal" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <div class="modal-header">
      <div>
        <div style="font-weight:bold;font-size:1rem;"><?php echo htmlspecialchars($orderDetail['order_number']);?></div>
        <div style="font-size:.82rem;opacity:.8;"><?php echo date('d M Y, h:i A', strtotime($orderDetail['created_at']));?></div>
      </div>
      <button onclick="closeModal()" style="background:none;border:none;color:white;font-size:1.5rem;cursor:pointer;">&times;</button>
    </div>
    <div class="modal-body">
      <div style="display:flex;justify-content:space-between;margin-bottom:1rem;">
        <span style="font-weight:600;">Order Status:</span>
        <span class="badge" style="background:<?php echo $color;?>20;color:<?php echo $color;?>;border:1px solid <?php echo $color;?>40;">
          <i class="fas fa-<?php echo $icon;?>"></i> <?php echo ucfirst($orderDetail['status']);?>
        </span>
      </div>
      <h4 style="margin:0 0 .5rem;color:#374151;">Items Ordered</h4>
      <?php foreach($orderItems as $item):?>
      <div class="item-row">
        <div>
          <div style="font-weight:600;"><?php echo htmlspecialchars($item['product_name']);?></div>
          <div style="font-size:.82rem;color:#9ca3af;"><?php echo htmlspecialchars($item['unit']);?> × <?php echo $item['quantity'];?></div>
        </div>
        <span style="font-weight:bold;color:#15803d;">₹<?php echo number_format($item['subtotal'],2);?></span>
      </div>
      <?php endforeach;?>
      <div class="total-row-modal">
        <span>Total Amount</span>
        <span>₹<?php echo number_format($orderDetail['total_amount'],2);?></span>
      </div>
      <div style="margin-top:1.5rem;text-align:center;display:flex;gap:10px;justify-content:center;">
        <a href="orders.php" class="btn-sm" style="background:#15803d;color:white;">← Back</a>
        <a href="../invoice.php?id=<?php echo $orderDetail['id'];?>" target="_blank" class="btn-sm" style="background:#2563eb;color:white;">
          <i class="fas fa-print"></i> Print Invoice
        </a>
      </div>
    </div>
  </div>
</div>
<?php endif;?>

<script>
function closeModal() {
    document.getElementById('orderModal')?.classList.remove('active');
    history.replaceState(null,'','orders.php');
}
</script>
</body></html>
