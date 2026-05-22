<?php
// ============================================================
// admin/add-product.php — Add New Product
// ============================================================
$pageTitle = 'Add Product';
require_once 'includes/layout.php';

$cats  = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$msg   = '';
$type  = 'success';

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

    if (empty($name) || $catId === 0 || empty($unit) || $price <= 0) {
        $msg  = 'Please fill all required fields.';
        $type = 'error';
    } else {
        // Handle image upload
        $imageName = 'default.jpg';
        if (!empty($_FILES['image']['name'])) {
            $allowedTypes = ['image/jpeg','image/png','image/webp','image/gif'];
            $fileType     = mime_content_type($_FILES['image']['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                $msg  = 'Invalid image type. Use JPG, PNG, or WEBP.';
                $type = 'error';
            } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                $msg  = 'Image must be under 2MB.';
                $type = 'error';
            } else {
                $ext       = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $imageName = 'prod_' . time() . '_' . random_int(100, 999) . '.' . $ext;
                $uploadDir = __DIR__ . '/../uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName)) {
                    $msg       = 'Image upload failed.';
                    $type      = 'error';
                    $imageName = 'default.jpg';
                }
            }
        }

        if ($type !== 'error') {
            $stmt = $conn->prepare("INSERT INTO products (category_id, name, description, unit, price, discount, stock_qty, stock_status, image, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssdissi", $catId, $name, $description, $unit, $price, $discount, $stockQty, $stockStatus, $imageName, $isActive);
            if ($stmt->execute()) {
                $msg = 'Product added successfully! <a href="products.php">View all products</a>';
            } else {
                $msg  = 'Failed to add product: ' . $conn->error;
                $type = 'error';
            }
            $stmt->close();
        }
    }
}
?>

<?php if($msg):?><div class="alert-<?php echo $type;?>"><?php echo $msg;?></div><?php endif;?>

<div class="card-box">
  <div class="card-box-header">
    <h3><i class="fas fa-plus-circle" style="color:#15803d;"></i> Add New Product</h3>
    <a href="products.php" class="btn btn-sm" style="background:#f1f5f9;color:#1e293b;"><i class="fas fa-arrow-left"></i> Back to Products</a>
  </div>
  <div style="padding:1.5rem;">
    <form method="POST" enctype="multipart/form-data" id="addProductForm">
      <div class="form-grid">
        <!-- Product Name -->
        <div class="form-group">
          <label>Product Name <span style="color:#dc2626;">*</span></label>
          <input type="text" name="name" placeholder="e.g. IFFDC Nano Urea" value="<?php echo htmlspecialchars($_POST['name']??'');?>" required>
        </div>
        <!-- Category -->
        <div class="form-group">
          <label>Category <span style="color:#dc2626;">*</span></label>
          <select name="category_id" required>
            <option value="">-- Select Category --</option>
            <?php foreach($cats as $c):?><option value="<?php echo $c['id'];?>" <?php echo (($_POST['category_id']??0)==$c['id'])?'selected':'';?>><?php echo htmlspecialchars($c['name']);?></option><?php endforeach;?>
          </select>
        </div>
        <!-- Unit -->
        <div class="form-group">
          <label>Unit / Pack Size <span style="color:#dc2626;">*</span></label>
          <input type="text" name="unit" placeholder="e.g. 50kg Bag, 500ml, 1kg" value="<?php echo htmlspecialchars($_POST['unit']??'');?>" required>
        </div>
        <!-- Price -->
        <div class="form-group">
          <label>Original Price (₹) <span style="color:#dc2626;">*</span></label>
          <input type="number" name="price" placeholder="e.g. 1350" min="0" step="0.01" value="<?php echo htmlspecialchars($_POST['price']??'');?>" required id="priceInput" oninput="calcOffer()">
        </div>
        <!-- Discount -->
        <div class="form-group">
          <label>Discount (%)</label>
          <input type="number" name="discount" placeholder="0" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($_POST['discount']??'0');?>" id="discountInput" oninput="calcOffer()">
        </div>
        <!-- Offer Price (auto-calculated) -->
        <div class="form-group">
          <label>Offer Price (₹) — Auto Calculated</label>
          <input type="text" id="offerPreview" placeholder="Set price and discount above" readonly style="background:#f0fdf4;color:#15803d;font-weight:bold;cursor:not-allowed;">
        </div>
        <!-- Stock Quantity -->
        <div class="form-group">
          <label>Stock Quantity</label>
          <input type="number" name="stock_qty" placeholder="e.g. 100" min="0" value="<?php echo htmlspecialchars($_POST['stock_qty']??'');?>">
        </div>
        <!-- Image Upload -->
        <div class="form-group">
          <label>Product Image (max 2MB)</label>
          <input type="file" name="image" accept="image/*" onchange="previewImage(this)">
          <img id="imagePreview" style="display:none;margin-top:10px;width:100px;height:100px;object-fit:cover;border-radius:10px;border:2px solid #dcfce7;">
          <div style="font-size:.78rem;color:#9ca3af;margin-top:5px;">Accepted: JPG, PNG, WEBP</div>
        </div>
      </div>

      <!-- Description -->
      <div class="form-group">
        <label>Product Description</label>
        <textarea name="description" rows="3" placeholder="Describe the product benefits, usage, etc."><?php echo htmlspecialchars($_POST['description']??'');?></textarea>
      </div>

      <!-- Active Toggle -->
      <div class="form-group" style="display:flex;align-items:center;gap:12px;">
        <input type="checkbox" name="is_active" id="isActive" value="1" <?php echo (($_POST['is_active']??'1')==='1'||!isset($_POST['name']))?'checked':'';?> style="width:18px;height:18px;accent-color:#15803d;">
        <label for="isActive" style="margin:0;cursor:pointer;">Show this product on the shop (Active)</label>
      </div>

      <div style="display:flex;gap:10px;margin-top:1rem;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Product</button>
        <a href="products.php" class="btn" style="background:#f1f5f9;color:#1e293b;">Cancel</a>
      </div>
    </form>
  </div>
</div>

</div></div>
<script>
function calcOffer() {
    const price    = parseFloat(document.getElementById('priceInput').value) || 0;
    const discount = parseFloat(document.getElementById('discountInput').value) || 0;
    const offer    = price - (price * discount / 100);
    document.getElementById('offerPreview').value = price > 0 ? '₹' + offer.toFixed(2) : '';
}
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}
calcOffer();
</script>
</body></html>
