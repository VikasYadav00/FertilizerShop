<?php
// ============================================================
// admin/dashboard.php — Main Admin Dashboard
// ============================================================
$pageTitle = 'Dashboard';
require_once 'includes/layout.php';

// Stats
$totalCustomers = $conn->query("SELECT COUNT(*) FROM users WHERE is_verified=1")->fetch_row()[0];
$totalProducts  = $conn->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetch_row()[0];
$totalOrders    = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$totalRevenue   = $conn->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'cancelled'")->fetch_row()[0];
$pendingCount   = $conn->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetch_row()[0];

// Low stock products
$lowStockQty   = intval(getSiteSetting($conn,'low_stock_alert') ?? 10);
$lowStockProds = $conn->query("SELECT p.name, p.stock_qty, c.name AS cat FROM products p JOIN categories c ON c.id=p.category_id WHERE p.stock_qty <= $lowStockQty AND p.is_active=1 ORDER BY p.stock_qty ASC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Revenue by month (last 6 months)
$revenueData   = $conn->query("SELECT DATE_FORMAT(created_at,'%b %Y') AS month, SUM(total_amount) AS rev FROM orders WHERE status!='cancelled' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY MONTH(created_at), YEAR(created_at) ORDER BY created_at ASC")->fetch_all(MYSQLI_ASSOC);

// Recent orders
$recentOrders  = $conn->query("SELECT o.order_number, o.total_amount, o.status, o.created_at, u.name AS customer FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
?>

<!-- STATS -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:#dcfce7;color:#15803d;"><i class="fas fa-users"></i></div>
    <div><div class="stat-value"><?php echo number_format($totalCustomers);?></div><div class="stat-label">Total Customers</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fas fa-box"></i></div>
    <div><div class="stat-value"><?php echo number_format($totalProducts);?></div><div class="stat-label">Active Products</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fef3c7;color:#b45309;"><i class="fas fa-shopping-bag"></i></div>
    <div><div class="stat-value"><?php echo number_format($totalOrders);?></div><div class="stat-label">Total Orders</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#f3e8ff;color:#7e22ce;"><i class="fas fa-rupee-sign"></i></div>
    <div><div class="stat-value">₹<?php echo number_format($totalRevenue);?></div><div class="stat-label">Total Revenue</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-clock"></i></div>
    <div><div class="stat-value"><?php echo $pendingCount;?></div><div class="stat-label">Pending Orders</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-exclamation-triangle"></i></div>
    <div><div class="stat-value"><?php echo $lowStockCount;?></div><div class="stat-label">Low Stock Items</div></div>
  </div>
</div>

<!-- CHART + LOW STOCK -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
  <div class="card-box">
    <div class="card-box-header"><h3><i class="fas fa-chart-bar" style="color:#15803d;"></i> Revenue (Last 6 Months)</h3></div>
    <div style="padding:1.5rem;"><canvas id="revenueChart" height="120"></canvas></div>
  </div>
  <div class="card-box">
    <div class="card-box-header"><h3><i class="fas fa-exclamation-triangle" style="color:#f59e0b;"></i> Low Stock Alert</h3><a href="products.php" class="btn btn-warning btn-sm">View All</a></div>
    <div style="padding:.5rem 1rem;">
      <?php if(empty($lowStockProds)):?>
        <p style="text-align:center;padding:2rem;color:#94a3b8;">✅ All products well stocked</p>
      <?php else: foreach($lowStockProds as $lp):?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.7rem 0;border-bottom:1px solid #f1f5f9;">
          <div>
            <div style="font-weight:600;font-size:.88rem;"><?php echo htmlspecialchars($lp['name']);?></div>
            <div style="font-size:.75rem;color:#9ca3af;"><?php echo htmlspecialchars($lp['cat']);?></div>
          </div>
          <span style="background:<?php echo $lp['stock_qty']==0?'#fee2e2':'#fef3c7';?>;color:<?php echo $lp['stock_qty']==0?'#dc2626':'#b45309';?>;padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:bold;">
            <?php echo $lp['stock_qty'] == 0 ? 'Out of Stock' : $lp['stock_qty'].' left';?>
          </span>
        </div>
      <?php endforeach; endif;?>
    </div>
  </div>
</div>

<!-- RECENT ORDERS -->
<div class="card-box">
  <div class="card-box-header">
    <h3><i class="fas fa-shopping-bag" style="color:#15803d;"></i> Recent Orders</h3>
    <a href="orders.php" class="btn btn-primary btn-sm">View All</a>
  </div>
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>Order #</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach($recentOrders as $o):?>
        <tr>
          <td style="font-weight:600;color:#1e293b;"><?php echo htmlspecialchars($o['order_number']);?></td>
          <td><?php echo htmlspecialchars($o['customer']);?></td>
          <td style="font-weight:600;color:#15803d;">₹<?php echo number_format($o['total_amount'],2);?></td>
          <td><span class="badge badge-<?php echo $o['status'];?>"><?php echo ucfirst($o['status']);?></span></td>
          <td style="color:#94a3b8;font-size:.82rem;"><?php echo date('d M Y',strtotime($o['created_at']));?></td>
          <td><a href="orders.php?status=<?php echo $o['status'];?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a></td>
        </tr>
        <?php endforeach;?>
      </tbody>
    </table>
  </div>
</div>

</div></div><!-- end .page-content .main-content -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('revenueChart');
if (ctx) {
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?php echo json_encode(array_column($revenueData,'month'));?>,
      datasets: [{
        label: 'Revenue (₹)',
        data: <?php echo json_encode(array_column($revenueData,'rev'));?>,
        backgroundColor: 'rgba(21,128,61,.2)',
        borderColor: '#15803d',
        borderWidth: 2,
        borderRadius: 8,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { callback: v => '₹' + Number(v).toLocaleString() } },
        x: { grid: { display: false } }
      }
    }
  });
}
</script>
</body></html>
