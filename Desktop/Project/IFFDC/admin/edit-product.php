<?php
// ============================================================
// admin/edit-product.php — Edit Existing Product
// ============================================================
$pageTitle = 'Edit Product';
require_once 'includes/layout.php';

$id      = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: products.php'); exit; }

$cats    = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$product = $conn->query("SELECT * FROM products WHERE id=$id AND is_active=1")->fetch_assoc();
if (!$product) { header('Location: products.php'); exit; }

$msg  = '';
$type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $catId       = intval($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $unit        = trim($_POST['unit'] ?? '');
    $price       = floatval($_POST['price'] ?? 0);
    $discount    = min(100, max(0, floatval($_POST['discount'] ?? 0)));
    $stockQty    = intval($_POST['stock_qty'] ?? 0);
    $stockStatus = $stockQty > 0 ? 'in_stock' : 'out_of_stock';
    $isActive    = isset($_POST['is_active']) ? 1 : 0;
    $imageName   = $product['image']; // keep old image by default

    if (empty($name) || $catId === 0 || empty($unit) || $price <= 0) {
        $msg = 'Please fill all required fields.';
        $type = 'error';
    } else {
        // Handle new image upload
        if (!empty($_FILES['image']['name'])) {
            $allowedTypes = ['image/jpeg','image/png','image/webp','image/gif'];
            $fileType     = mime_content_type($_FILES['image']['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                $msg = 'Invalid image type.'; $type = 'error';
            } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                $msg = 'Image must be under 2MB.'; $type = 'error';
            } else {
                $ext       = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $newImage  = 'prod_' . time() . '_' . random_int(100,999) . '.' . $ext;
                $uploadDir = __DIR__ . '/../uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newImage)) {
                    // Delete old uploaded image (not images/ folder originals)
                    $oldPath = $uploadDir . $product['image'];
                    if (file_exists($oldPath) && strpos($product['image'], 'prod_') === 0) {
                        @unlink($oldPath);
                    }
                    $imageName = $newImage;
                } else {
                    $msg = 'Image upload failed.'; $type = 'error';
                }
            }
        }

        if ($type !== 'error') {
            $stmt = $conn->prepare("UPDATE products SET category_id=?, name=?, description=?, unit=?, price=?, discount=?, stock_qty=?, stock_status=?, image=?, is_active=? WHERE id=?");
            $stmt->bind_param("isssdsissii", $catId, $name, $description, $unit, $price, $discount, $stockQty, $stockStatus, $imageName, $isActive, $id);
            if ($stmt->execute()) {
                $msg = 'Product updated successfully!';
                // Refresh product data
                $product = $conn->query("SELECT * FROM products WHERE id=$id")->fetch_assoc();
            } else {
                $msg = 'Update failed: ' . $conn->error; $type = 'error';
            }
            $stmt->close();
        }
    }
}

// Image path: check uploads/ first, then images/
$imgPath  = file_exists(__DIR__ . '/../uploads/' . $product['image'])
    ? '../uploads/' . $product['image']
    : '../images/' . $product['image'];
?>

<?php if($msg):?><div class="alert-<?php echo $type;?>"><?php echo $msg;?></div><?php endif;?>

<div class="card-box">
  <div class="card-box-header">
    <h3><i class="fas fa-edit" style="color:#15803d;"></i> Edit Product — <?php echo htmlspecialchars($product['name']);?></h3>
    <a href="products.php" class="btn btn-sm" style="background:#f1f5f9;color:#1e293b;"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
  <div style="padding:1.5rem;">
    <form method="POST" enctype="multipart/form-data">
      <div class="form-grid">
        <div class="form-group">
          <label>Product Name *</label>
          <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']);?>" required>
        </div>
        <div class="form-group">
          <label>Category *</label>
          <select name="category_id" required>
            <?php foreach($cats as $c):?><option value="<?php echo $c['id'];?>" <?php echo $product['category_id']==$c['id']?'selected':'';?>><?php echo htmlspecialchars($c['name']);?></option><?php endforeach;?>
          </select>
        </div>
        <div class="form-group">
          <label>Unit / Pack Size *</label>
          <input type="text" name="unit" value="<?php echo htmlspecialchars($product['unit']);?>" required>
        </div>
        <div class="form-group">
          <label>Original Price (₹) *</label>
          <input type="number" name="price" value="<?php echo $product['price'];?>" min="0" step="0.01" required id="priceInput" oninput="calcOffer()">
        </div>
        <div class="form-group">
          <label>Discount (%)</label>
          <input type="number" name="discount" value="<?php echo $product['discount'];?>" min="0" max="100" step="0.01" id="discountInput" oninput="calcOffer()">
        </div>
        <div class="form-group">
          <label>Offer Price (₹) — Auto</label>
          <input type="text" id="offerPreview" readonly style="background:#f0fdf4;color:#15803d;font-weight:bold;cursor:not-allowed;">
        </div>
        <div class="form-group">
          <label>Stock Quantity</label>
          <input type="number" name="stock_qty" value="<?php echo $product['stock_qty'];?>" min="0">
        </div>
        <div class="form-group">
          <label>Replace Image (optional)</label>
          <input type="file" name="image" accept="image/*" onchange="previewImage(this)">
          <div style="margin-top:10px;">
            <span style="font-size:.78rem;color:#9ca3af;">Current image:</span><br>
            <img id="imagePreview" src="<?php echo $imgPath;?>" style="width:100px;height:100px;object-fit:cover;border-radius:10px;border:2px solid #dcfce7;margin-top:5px;" onerror="this.src='https://placehold.co/100x100/dcfce7/15803d?text=IMG'">
          </div>
        </div>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="3"><?php echo htmlspecialchars($product['description']??'');?></textarea>
      </div>
      <div class="form-group" style="display:flex;align-items:center;gap:12px;">
        <input type="checkbox" name="is_active" id="isActive" value="1" <?php echo $product['is_active']?'checked':'';?> style="width:18px;height:18px;accent-color:#15803d;">
        <label for="isActive" style="margin:0;cursor:pointer;">Show this product on the shop (Active)</label>
      </div>
      <div style="display:flex;gap:10px;margin-top:1rem;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Product</button>
        <a href="products.php" class="btn" style="background:#f1f5f9;color:#1e293b;">Cancel</a>
      </div>
    </form>
  </div>
</div>

</div></div>
<script>
function calcOffer() {
    const price    = parseFloat(document.getElementById('priceInput').value)||0;
    const discount = parseFloat(document.getElementById('discountInput').value)||0;
    const offer    = price - (price * discount/100);
    document.getElementById('offerPreview').value = price>0 ? '₹'+offer.toFixed(2) : '';
}
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; };
        reader.readAsDataURL(input.files[0]);
    }
}
calcOffer();
</script>
</body></html>
