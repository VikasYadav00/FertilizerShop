<?php
// ============================================================
// admin/customers.php — Customer Management
// ============================================================
$pageTitle = 'Customers';
require_once 'includes/layout.php';

$msg  = '';
$type = 'success';

// Toggle block/unblock
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId    = intval($_POST['user_id']);
    $newStatus = $_POST['new_status'] ?? '';
    if (in_array($newStatus, ['active','blocked'])) {
        $stmt = $conn->prepare("UPDATE users SET status=? WHERE id=?");
        $stmt->bind_param("si", $newStatus, $userId);
        $stmt->execute();
        $stmt->close();
        $msg = 'Customer status updated.';
    }
}

$search = trim($_GET['q'] ?? '');
$where  = $search ? "WHERE u.name LIKE '%$search%' OR u.email LIKE '%$search%' OR u.phone LIKE '%$search%'" : '';

$customers = $conn->query("
    SELECT u.*, COUNT(o.id) AS order_count, COALESCE(SUM(o.total_amount),0) AS total_spent
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id AND o.status != 'cancelled'
    $where
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<?php if($msg):?><div class="alert-success"><?php echo $msg;?></div><?php endif;?>

<div class="card-box" style="margin-bottom:1.5rem;">
  <div style="padding:1rem 1.5rem;">
    <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
      <div>
        <label style="font-size:.82rem;font-weight:600;color:#64748b;display:block;margin-bottom:4px;">Search Customers</label>
        <input type="text" name="q" placeholder="Name, email or phone..." value="<?php echo htmlspecialchars($search);?>" style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:10px;outline:none;font-size:.88rem;width:280px;">
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
      <a href="customers.php" class="btn" style="background:#f1f5f9;color:#1e293b;">Reset</a>
    </form>
  </div>
</div>

<div class="card-box">
  <div class="card-box-header">
    <h3><i class="fas fa-users" style="color:#15803d;"></i> Customers (<?php echo count($customers);?>)</h3>
    <button onclick="exportCustomers()" class="btn btn-primary btn-sm"><i class="fas fa-download"></i> Export CSV</button>
  </div>
  <div style="overflow-x:auto;">
    <table id="customersTable">
      <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Verified</th><th>Status</th><th>Joined</th><th>Action</th></tr></thead>
      <tbody>
      <?php if(empty($customers)):?>
        <tr><td colspan="10" style="text-align:center;padding:3rem;color:#9ca3af;">No customers found.</td></tr>
      <?php else: foreach($customers as $i => $c):?>
        <tr>
          <td style="color:#9ca3af;"><?php echo $i+1;?></td>
          <td style="font-weight:600;"><?php echo htmlspecialchars($c['name']);?></td>
          <td style="font-size:.85rem;"><?php echo htmlspecialchars($c['email']);?></td>
          <td><?php echo htmlspecialchars($c['phone']);?></td>
          <td><span style="background:#f0fdf4;color:#15803d;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:600;"><?php echo $c['order_count'];?></span></td>
          <td style="font-weight:600;color:#15803d;">₹<?php echo number_format($c['total_spent'],0);?></td>
          <td><?php echo $c['is_verified']?'<span style="color:#15803d;font-size:.9rem;">✅ Yes</span>':'<span style="color:#f59e0b;font-size:.9rem;">⏳ No</span>';?></td>
          <td>
            <span style="background:<?php echo $c['status']==='active'?'#dcfce7':'#fee2e2';?>;color:<?php echo $c['status']==='active'?'#14532d':'#991b1b';?>;padding:4px 12px;border-radius:20px;font-size:.8rem;font-weight:600;">
              <?php echo ucfirst($c['status']);?>
            </span>
          </td>
          <td style="color:#9ca3af;font-size:.8rem;"><?php echo date('d M Y',strtotime($c['created_at']));?></td>
          <td>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="user_id" value="<?php echo $c['id'];?>">
              <input type="hidden" name="new_status" value="<?php echo $c['status']==='active'?'blocked':'active';?>">
              <button type="submit" class="btn btn-sm <?php echo $c['status']==='active'?'btn-danger':'btn-primary';?>" onclick="return confirm('<?php echo $c['status']==='active'?'Block':'Unblock';?> this customer?')">
                <i class="fas fa-<?php echo $c['status']==='active'?'ban':'check';?>"></i>
                <?php echo $c['status']==='active'?'Block':'Unblock';?>
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif;?>
      </tbody>
    </table>
  </div>
</div>

</div></div>
<script>
function exportCustomers() {
    const rows = document.querySelectorAll('#customersTable tr');
    let csv = '';
    rows.forEach(row => {
        const cols = Array.from(row.querySelectorAll('th,td')).map(c => '"' + c.innerText.replace(/\n/g,' ').trim() + '"');
        csv += cols.join(',') + '\n';
    });
    const blob = new Blob([csv], {type:'text/csv'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'iffdc_customers_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
}
</script>
</body></html>
