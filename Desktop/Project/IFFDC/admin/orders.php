<?php
// ============================================================
// admin/orders.php — Order Management
// ============================================================
$pageTitle = 'Orders';
require_once 'includes/layout.php';

$msg  = '';
$type = 'success';

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId   = intval($_POST['order_id']);
    $newStatus = $_POST['status'] ?? '';
    $allowed   = ['pending','confirmed','delivered','cancelled'];
    if (in_array($newStatus, $allowed)) {
        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->bind_param("si", $newStatus, $orderId);
        $stmt->execute();
        $stmt->close();

        // If cancelled, restore stock
        if ($newStatus === 'cancelled') {
            $items = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id=$orderId")->fetch_all(MYSQLI_ASSOC);
            foreach ($items as $item) {
                $pid = $item['product_id']; $qty = $item['quantity'];
                $conn->query("UPDATE products SET stock_qty = stock_qty + $qty, stock_status='in_stock' WHERE id=$pid");
            }
        }
        $msg = 'Order status updated successfully.';
    }
}

// Filters
$statusFilter = $_GET['status'] ?? '';
$searchFilter = trim($_GET['q'] ?? '');
$where        = ['1=1'];
if ($statusFilter) $where[] = "o.status = '$statusFilter'";
if ($searchFilter) $where[] = "(o.order_number LIKE '%$searchFilter%' OR u.name LIKE '%$searchFilter%' OR u.email LIKE '%$searchFilter%')";
$whereSQL = implode(' AND ', $where);

$orders = $conn->query("
    SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
           COUNT(oi.id) AS item_count
    FROM orders o
    JOIN users u ON u.id = o.user_id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE $whereSQL
    GROUP BY o.id
    ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$statusColors = ['pending'=>['bg'=>'#fef3c7','color'=>'#92400e'],'confirmed'=>['bg'=>'#dbeafe','color'=>'#1e40af'],'delivered'=>['bg'=>'#dcfce7','color'=>'#14532d'],'cancelled'=>['bg'=>'#fee2e2','color'=>'#991b1b']];

// Order detail
$viewId      = intval($_GET['view'] ?? 0);
$viewOrder   = null;
$viewItems   = [];
if ($viewId) {
    $viewOrder = $conn->query("SELECT o.*, u.name AS cname, u.email AS cemail, u.phone AS cphone FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=$viewId")->fetch_assoc();
    if ($viewOrder) $viewItems = $conn->query("SELECT * FROM order_items WHERE order_id=$viewId")->fetch_all(MYSQLI_ASSOC);
}
?>

<?php if($msg):?><div class="alert-<?php echo $type;?>"><?php echo $msg;?></div><?php endif;?>

<!-- FILTERS -->
<div class="card-box" style="margin-bottom:1.5rem;">
  <div style="padding:1rem 1.5rem;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
      <div>
        <label style="font-size:.82rem;font-weight:600;color:#64748b;display:block;margin-bottom:4px;">Search</label>
        <input type="text" name="q" placeholder="Order #, customer name..." value="<?php echo htmlspecialchars($searchFilter);?>" style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:10px;outline:none;font-size:.88rem;width:250px;">
      </div>
      <div>
        <label style="font-size:.82rem;font-weight:600;color:#64748b;display:block;margin-bottom:4px;">Status</label>
        <select name="status" style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:10px;outline:none;font-size:.88rem;">
          <option value="">All Statuses</option>
          <?php foreach(['pending','confirmed','delivered','cancelled'] as $s):?>
            <option value="<?php echo $s;?>" <?php echo $statusFilter===$s?'selected':'';?>><?php echo ucfirst($s);?></option>
          <?php endforeach;?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
      <a href="orders.php" class="btn" style="background:#f1f5f9;color:#1e293b;">Reset</a>
    </form>
  </div>
</div>

<!-- STATUS SUMMARY CHIPS -->
<div style="display:flex;gap:10px;margin-bottom:1.5rem;flex-wrap:wrap;">
  <?php foreach(['pending','confirmed','delivered','cancelled'] as $s):
    $cnt = $conn->query("SELECT COUNT(*) FROM orders WHERE status='$s'")->fetch_row()[0];
    $sc  = $statusColors[$s];
  ?>
  <a href="?status=<?php echo $s;?>" style="background:<?php echo $sc['bg'];?>;color:<?php echo $sc['color'];?>;padding:8px 18px;border-radius:20px;font-weight:600;font-size:.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
    <?php echo ucfirst($s);?> <span style="background:<?php echo $sc['color'];?>;color:white;border-radius:10px;padding:1px 8px;font-size:.75rem;"><?php echo $cnt;?></span>
  </a>
  <?php endforeach;?>
</div>

<div class="card-box">
  <div class="card-box-header">
    <h3><i class="fas fa-shopping-bag" style="color:#15803d;"></i> Orders (<?php echo count($orders);?>)</h3>
    <a href="?export=1" class="btn btn-primary btn-sm" onclick="exportTable()"><i class="fas fa-download"></i> Export CSV</a>
  </div>
  <div style="overflow-x:auto;">
    <table id="ordersTable">
      <thead><tr><th>Order #</th><th>Customer</th><th>Items</th><th>Amount</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if(empty($orders)):?>
        <tr><td colspan="7" style="text-align:center;padding:3rem;color:#9ca3af;">No orders found.</td></tr>
      <?php else: foreach($orders as $o):
        $sc = $statusColors[$o['status']] ?? ['bg'=>'#f1f5f9','color'=>'#64748b'];
      ?>
        <tr>
          <td style="font-weight:700;color:#15803d;"><?php echo htmlspecialchars($o['order_number']);?></td>
          <td>
            <div style="font-weight:600;font-size:.88rem;"><?php echo htmlspecialchars($o['customer_name']);?></div>
            <div style="font-size:.75rem;color:#9ca3af;"><?php echo htmlspecialchars($o['customer_email']);?></div>
          </td>
          <td><span style="background:#f1f5f9;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:600;"><?php echo $o['item_count'];?> item(s)</span></td>
          <td style="font-weight:700;">₹<?php echo number_format($o['total_amount'],2);?></td>
          <td>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="order_id" value="<?php echo $o['id'];?>">
              <select name="status" onchange="this.form.submit()" style="background:<?php echo $sc['bg'];?>;color:<?php echo $sc['color'];?>;border:none;padding:5px 10px;border-radius:20px;font-weight:600;font-size:.8rem;cursor:pointer;outline:none;">
                <?php foreach(['pending','confirmed','delivered','cancelled'] as $s):?>
                  <option value="<?php echo $s;?>" <?php echo $o['status']===$s?'selected':'';?>><?php echo ucfirst($s);?></option>
                <?php endforeach;?>
              </select>
            </form>
          </td>
          <td style="color:#94a3b8;font-size:.82rem;"><?php echo date('d M Y, H:i',strtotime($o['created_at']));?></td>
          <td>
            <a href="../invoice.php?id=<?php echo $o['id'];?>" target="_blank" class="btn btn-sm" style="background:#e0e7ff;color:#4338ca;"><i class="fas fa-file-invoice"></i></a>
            <a href="?view=<?php echo $o['id'];?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
          </td>
        </tr>
      <?php endforeach; endif;?>
      </tbody>
    </table>
  </div>
</div>

<!-- ORDER DETAIL MODAL -->
<?php if($viewOrder && $viewItems):?>
<div style="position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;display:flex;align-items:center;justify-content:center;" onclick="if(event.target===this)window.location='orders.php'">
  <div style="background:white;border-radius:20px;max-width:600px;width:90%;max-height:85vh;overflow-y:auto;box-shadow:0 30px 80px rgba(0,0,0,.3);">
    <div style="background:linear-gradient(135deg,#15803d,#14532d);color:white;padding:1.5rem;border-radius:20px 20px 0 0;display:flex;justify-content:space-between;align-items:center;">
      <div>
        <h3 style="margin:0;"><?php echo htmlspecialchars($viewOrder['order_number']);?></h3>
        <p style="margin:.3rem 0 0;opacity:.8;font-size:.85rem;"><?php echo date('d M Y, H:i',strtotime($viewOrder['created_at']));?></p>
      </div>
      <a href="orders.php" style="color:white;font-size:1.5rem;text-decoration:none;">&times;</a>
    </div>
    <div style="padding:1.5rem;">
      <!-- Customer Info -->
      <div style="background:#f8fafc;border-radius:12px;padding:1rem;margin-bottom:1.5rem;">
        <h4 style="margin:0 0 .8rem;color:#1e293b;">Customer Information</h4>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;font-size:.88rem;">
          <div><span style="color:#9ca3af;">Name:</span> <strong><?php echo htmlspecialchars($viewOrder['cname']);?></strong></div>
          <div><span style="color:#9ca3af;">Phone:</span> <strong><?php echo htmlspecialchars($viewOrder['cphone']);?></strong></div>
          <div style="grid-column:1/-1;"><span style="color:#9ca3af;">Email:</span> <strong><?php echo htmlspecialchars($viewOrder['cemail']);?></strong></div>
        </div>
      </div>
      <!-- Items -->
      <h4 style="margin:0 0 .8rem;color:#1e293b;">Order Items</h4>
      <?php foreach($viewItems as $item):?>
      <div style="display:flex;justify-content:space-between;padding:.8rem 0;border-bottom:1px solid #f1f5f9;font-size:.9rem;">
        <div>
          <div style="font-weight:600;"><?php echo htmlspecialchars($item['product_name']);?></div>
          <div style="color:#9ca3af;font-size:.8rem;"><?php echo htmlspecialchars($item['unit']);?> × <?php echo $item['quantity'];?>
          <?php if($item['discount']>0): echo ' <span style="color:#dc2626;">('.intval($item['discount']).'% off)</span>'; endif;?></div>
        </div>
        <strong style="color:#15803d;">₹<?php echo number_format($item['subtotal'],2);?></strong>
      </div>
      <?php endforeach;?>
      <div style="display:flex;justify-content:space-between;font-weight:bold;font-size:1.1rem;padding-top:1rem;color:#15803d;">
        <span>Total Amount</span><span>₹<?php echo number_format($viewOrder['total_amount'],2);?></span>
      </div>
      <!-- Update Status -->
      <form method="POST" style="margin-top:1.5rem;display:flex;gap:10px;align-items:center;">
        <input type="hidden" name="order_id" value="<?php echo $viewOrder['id'];?>">
        <select name="status" style="flex:1;padding:10px;border:1.5px solid #e2e8f0;border-radius:10px;outline:none;font-size:.9rem;">
          <?php foreach(['pending','confirmed','delivered','cancelled'] as $s):?>
            <option value="<?php echo $s;?>" <?php echo $viewOrder['status']===$s?'selected':'';?>><?php echo ucfirst($s);?></option>
          <?php endforeach;?>
        </select>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
        <a href="../invoice.php?id=<?php echo $viewOrder['id'];?>" target="_blank" class="btn" style="background:#2563eb;color:white;text-decoration:none;"><i class="fas fa-print"></i> Print</a>
      </form>
    </div>
  </div>
</div>
<?php endif;?>

</div></div>
<script>
function exportTable() {
    const rows = document.querySelectorAll('#ordersTable tr');
    let csv = '';
    rows.forEach(row => {
        const cols = row.querySelectorAll('th,td');
        const data = Array.from(cols).map(c => '"'+c.innerText.replace(/\n/g,' ').trim()+'"');
        csv += data.slice(0,6).join(',') + '\n';
    });
    const blob = new Blob([csv], {type:'text/csv'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'iffdc_orders_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
    event.preventDefault();
}
</script>
</body></html>
