<?php
// ============================================================
// api/place-order.php — AJAX endpoint to place an order
// ============================================================
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$input   = json_decode(file_get_contents('php://input'), true);
$cart    = $input['cart'] ?? [];
$name    = trim($input['name'] ?? '');
$phone   = trim($input['phone'] ?? '');
$address = trim($input['address'] ?? '');
$pincode = trim($input['pincode'] ?? '');

if (empty($cart) || empty($address) || empty($pincode) || empty($name) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Missing cart items or delivery details.']);
    exit;
}

$userId = $_SESSION['user_id'];

// Calculate total
$total = 0;
foreach ($cart as $item) {
    $price = floatval($item['offer_price'] ?? $item['price']);
    $qty   = intval($item['qty'] ?? 1);
    $total += $price * $qty;
}

// Generate unique order number
$orderNumber = 'IFFDC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

// Begin transaction
$conn->begin_transaction();
try {
    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders (order_number, user_id, total_amount, shipping_name, address, shipping_phone, pincode) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sidssss", $orderNumber, $userId, $total, $name, $address, $phone, $pincode);
    $stmt->execute();
    $orderId = $stmt->insert_id;
    $stmt->close();

    // Insert order items & reduce stock
    foreach ($cart as $item) {
        $productId   = intval($item['id']);
        $productName = $item['name'];
        $unit        = $item['unit'];
        $qty         = intval($item['qty'] ?? 1);
        $price       = floatval($item['price']);
        $discount    = floatval($item['discount'] ?? 0);
        $offerPrice  = floatval($item['offer_price'] ?? $price);
        $subtotal    = $offerPrice * $qty;

        // Verify product exists and has stock
        $check = $conn->prepare("SELECT stock_qty FROM products WHERE id = ? AND is_active = 1 AND stock_status = 'in_stock' FOR UPDATE");
        $check->bind_param("i", $productId);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$row || $row['stock_qty'] < $qty) {
            throw new Exception("Insufficient stock for product ID $productId");
        }

        // Insert order item
        $ins = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, unit, quantity, price, discount, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("iissiddd", $orderId, $productId, $productName, $unit, $qty, $price, $discount, $subtotal);
        $ins->execute();
        $ins->close();

        // Reduce stock
        $newQty = $row['stock_qty'] - $qty;
        $upd = $conn->prepare("UPDATE products SET stock_qty = ?, stock_status = IF(? <= 0, 'out_of_stock', 'in_stock') WHERE id = ?");
        $upd->bind_param("iii", $newQty, $newQty, $productId);
        $upd->execute();
        $upd->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'order_number' => $orderNumber, 'order_id' => $orderId]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
