<?php
// ============================================================
// admin/products.php — Product Management (List, Delete, Toggle)
// ============================================================
$pageTitle = 'Products';
require_once 'includes/layout.php';

// Handle actions
$msg  = '';
$type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id   = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE products SET is_active=0 WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $msg = 'Product deleted (hidden from shop).';
    }

    if ($action === 'toggle_stock') {
        $id     = intval($_POST['id']);
        $status = $_POST['current'] === 'in_stock' ? 'out_of_stock' : 'in_stock';
        $stmt   = $conn->prepare("UPDATE products SET stock_status=? WHERE id=?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();
        $msg = 'Stock status updated.';
    }
}

// Filters
$filterCat    = intval($_GET['cat'] ?? 0);
$filterStatus = $_GET['status'] ?? '';
$search       = trim($_GET['q'] ?? '');

$where = ['p.is_active=1'];
$params = [];
$types  = '';

if ($filterCat) { $where[] = 'p.category_id=?'; $params[] = $filterCat; $types .= 'i'; }
if ($filterStatus) { $where[] = 'p.stock_status=?'; $params[] = $filterStatus; $types .= 's'; }
if ($search) { $where[] = 'p.name LIKE ?'; $params[] = "%$search%"; $types .= 's'; }

$whereSQL = implode(' AND ', $where);
$sql = "SELECT p.*, c.name AS cat_name FROM products p JOIN categories c ON c.id=p.category_id WHERE $whereSQL ORDER BY p.id DESC";
$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$cats = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$lowStock = intval(getSiteSetting($conn,'low_stock_alert') ?? 10);
?>

<?php if($msg):?><div class="alert-<?php echo $type;?>"><?php echo $msg;?></div><?php endif;?>

<!-- FILTERS -->
<div class="card-box" style="margin-bottom:1.5rem;">
  <div style="padding:1rem 1.5rem;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
      <div>
        <label style="font-size:.82rem;font-weight:600;color:#64748b;display:block;margin-bottom:4px;">Search</label>
        <input type="text" name="q" placeholder="Product name..." value="<?php echo htmlspecialchars($search);?>" style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:10px;outline:none;font-size:.88rem;width:220px;">
      </div>
      <div>
        <label style="font-size:.82rem;font-weight:600;color:#64748b;display:block;margin-bottom:4px;">Category</label>
        <select name="cat" style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:10px;outline:none;font-size:.88rem;">
          <option value="">All Categories</option>
          <?php foreach($cats as $c):?><option value="<?php echo $c['id'];?>" <?php echo $filterCat==$c['id']?'selected':'';?>><?php echo htmlspecialchars($c['name']);?></option><?php endforeach;?>
        </select>
      </div>
      <div>
        <label style="font-size:.82rem;font-weight:600;color:#64748b;display:block;margin-bottom:4px;">Stock Status</label>
        <select name="status" style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:10px;outline:none;font-size:.88rem;">
          <option value="">All</option>
          <option value="in_stock" <?php echo $filterStatus==='in_stock'?'selected':'';?>>In Stock</option>
          <option value="out_of_stock" <?php echo $filterStatus==='out_of_stock'?'selected':'';?>>Out of Stock</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
      <a href="products.php" class="btn" style="background:#f1f5f9;color:#1e293b;">Reset</a>
      <a href="add-product.php" class="btn btn-primary" style="margin-left:auto;"><i class="fas fa-plus"></i> Add Product</a>
    </form>
  </div>
</div>

<div class="card-box">
  <div class="card-box-header">
    <h3><i class="fas fa-box" style="color:#15803d;"></i> Products (<?php echo count($products);?>)</h3>
  </div>
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Discount</th><th>Offer Price</th><th>Stock Qty</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if(empty($products)):?>
        <tr><td colspan="9" style="text-align:center;padding:3rem;color:#9ca3af;">No products found.</td></tr>
      <?php else: foreach($products as $p):
        $isLow = $p['stock_qty'] <= $lowStock;
      ?>
        <tr>
          <td><img src="<?php echo (strpos($p['image'], 'prod_') === 0 ? '../uploads/' : '../images/') . htmlspecialchars($p['image']);?>" class="img-preview" onerror="this.src='https://placehold.co/80x80/dcfce7/15803d?text=IMG'"></td>
          <td>
            <div style="font-weight:600;font-size:.88rem;"><?php echo htmlspecialchars($p['name']);?></div>
            <div style="font-size:.75rem;color:#9ca3af;"><?php echo htmlspecialchars($p['unit']);?></div>
            <?php if(!empty($p['description'])): ?>
              <div style="font-size:.75rem;color:#6b7280;margin-top:2px;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo htmlspecialchars($p['description']);?>">
                <?php echo htmlspecialchars($p['description']);?>
              </div>
            <?php endif; ?>
          </td>
          <td><span style="background:#f0fdf4;color:#15803d;padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:600;"><?php echo htmlspecialchars($p['cat_name']);?></span></td>
          <td>₹<?php echo number_format($p['price'],2);?></td>
          <td><?php echo $p['discount']>0?'<span style="color:#dc2626;font-weight:600;">'.$p['discount'].'%</span>':'—';?></td>
          <td style="font-weight:bold;color:#15803d;">₹<?php echo number_format($p['offer_price'],2);?></td>
          <td>
            <span style="color:<?php echo $p['stock_qty']==0?'#dc2626':($isLow?'#f59e0b':'#15803d');?>;font-weight:600;">
              <?php echo $p['stock_qty'];?>
              <?php echo $p['stock_qty']==0?'<span style="font-size:.72rem;"> (Out)</span>':($isLow?'<span style="font-size:.72rem;"> ⚠ Low</span>':'');?>
            </span>
          </td>
          <td>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="action" value="toggle_stock">
              <input type="hidden" name="id" value="<?php echo $p['id'];?>">
              <input type="hidden" name="current" value="<?php echo $p['stock_status'];?>">
              <button type="submit" class="badge <?php echo $p['stock_status']==='in_stock'?'badge-instock':'badge-outofstock';?>" style="border:none;cursor:pointer;" title="Click to toggle">
                <?php echo $p['stock_status']==='in_stock'?'In Stock':'Out of Stock';?>
              </button>
            </form>
          </td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <a href="edit-product.php?id=<?php echo $p['id'];?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
              <form method="POST" onsubmit="return confirm('Delete this product?');" style="display:inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $p['id'];?>">
                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; endif;?>
      </tbody>
    </table>
  </div>
</div>

</div></div></body></html>
