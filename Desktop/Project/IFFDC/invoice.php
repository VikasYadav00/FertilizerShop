<?php
// ============================================================
// invoice.php — Generates Printable Order Invoice
// ============================================================
require_once 'config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$orderId = intval($_GET['id'] ?? 0);
if (!$orderId) die("Invalid Order ID");

// Verify Access: Must be logged in as Admin OR the Customer who owns the order
$userId  = $_SESSION['user_id'] ?? null;
$adminId = $_SESSION['admin_id'] ?? null;

if (!$userId && !$adminId) {
    die("Unauthorized Access. Please login.");
}

// Fetch Order Details
$stmt = $conn->prepare("SELECT o.*, u.name as customer_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) die("Order not found.");

// If not admin, verify ownership
if (!$adminId && $order['user_id'] != $userId) {
    die("Unauthorized Access. This order does not belong to you.");
}

// Fetch Order Items
$itemsStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();
$items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$itemsStmt->close();

// Shop Settings
$shopAddress = getSiteSetting($conn, 'shop_address');
$shopPhone   = getSiteSetting($conn, 'shop_phone');
$shopEmail   = getSiteSetting($conn, 'shop_email');
$shopGst     = getSiteSetting($conn, 'shop_gst') ?? '09AXXXX0000X1Z5'; // Replace with real GST
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - <?php echo htmlspecialchars($order['order_number']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f3f4f6; padding: 20px; color: #1f2937; margin: 0; }
        .invoice-container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 12px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #e5e7eb; padding-bottom: 20px; margin-bottom: 30px; }
        .shop-info h1 { margin: 0; color: #15803d; font-size: 24px; }
        .shop-info p { margin: 5px 0 0; color: #6b7280; font-size: 14px; }
        .invoice-details { text-align: right; }
        .invoice-details h2 { margin: 0 0 10px; color: #1f2937; font-size: 28px; letter-spacing: 2px; text-transform: uppercase; }
        .invoice-details p { margin: 4px 0; font-size: 14px; font-weight: bold; }
        
        .billing-flex { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .billing-box { width: 48%; background: #f9fafb; padding: 15px; border-radius: 8px; border: 1px solid #e5e7eb; }
        .billing-box h3 { margin: 0 0 10px; font-size: 14px; color: #6b7280; text-transform: uppercase; }
        .billing-box p { margin: 4px 0; font-size: 14px; }
        
        .table-responsive { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .table-responsive th { background: #15803d; color: white; padding: 12px; text-align: left; font-size: 14px; }
        .table-responsive td { padding: 12px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        .table-responsive .right { text-align: right; }
        
        .totals { display: flex; justify-content: flex-end; }
        .totals-table { width: 300px; border-collapse: collapse; }
        .totals-table td { padding: 8px 0; font-size: 15px; }
        .totals-table .total-row { font-size: 18px; font-weight: bold; color: #15803d; border-top: 2px solid #e5e7eb; padding-top: 10px; }
        
        .footer { text-align: center; margin-top: 50px; border-top: 1px solid #e5e7eb; padding-top: 20px; color: #9ca3af; font-size: 13px; }
        
        .print-btn { background: #2563eb; color: white; border: none; padding: 12px 24px; font-size: 16px; border-radius: 8px; cursor: pointer; display: block; margin: 20px auto; font-weight: bold; }
        .print-btn i { margin-right: 8px; }
        
        .payment-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .paid { background: #dcfce7; color: #15803d; }
        .pending { background: #fef08a; color: #854d0e; }
        
        @media print {
            body { background: white; padding: 0; }
            .invoice-container { box-shadow: none; padding: 0; margin: 0; width: 100%; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print Invoice</button>
    
    <div class="invoice-container">
        <div class="header">
            <div class="shop-info">
                <h1>IFFDC Maharajpur</h1>
                <p>Krishak Seva Kendra</p>
                <p><i class="fas fa-file-invoice-dollar"></i> <strong>GSTIN:</strong> <?php echo htmlspecialchars($shopGst); ?></p>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($shopAddress); ?></p>
                <p><i class="fas fa-phone"></i> +91 <?php echo htmlspecialchars($shopPhone); ?> | <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($shopEmail); ?></p>
            </div>
            <div class="invoice-details">
                <h2>INVOICE</h2>
                <p style="color: #6b7280;">Order #: <span style="color:#1f2937;"><?php echo htmlspecialchars($order['order_number']); ?></span></p>
                <p style="color: #6b7280;">Date: <span style="color:#1f2937;"><?php echo date('d M, Y h:i A', strtotime($order['created_at'])); ?></span></p>
                <p style="color: #6b7280;">Status: <span style="color:#1f2937; text-transform:capitalize;"><?php echo htmlspecialchars($order['status']); ?></span></p>
            </div>
        </div>
        
        <div class="billing-flex">
            <div class="billing-box">
                <h3>Billed To (Shipping Address)</h3>
                <p><strong><?php echo htmlspecialchars($order['shipping_name']); ?></strong></p>
                <p><?php echo htmlspecialchars($order['address']); ?></p>
                <p>Pincode: <?php echo htmlspecialchars($order['pincode']); ?></p>
                <p>Phone: +91 <?php echo htmlspecialchars($order['shipping_phone']); ?></p>
            </div>
            <div class="billing-box">
                <h3>Payment Info</h3>
                <p><strong>Method:</strong> <?php echo strtoupper($order['payment_method']); ?></p>
                <p><strong>Status:</strong> 
                    <span class="payment-badge <?php echo $order['payment_status'] === 'paid' ? 'paid' : 'pending'; ?>">
                        <?php echo htmlspecialchars($order['payment_status']); ?>
                    </span>
                </p>
                <?php if($order['payment_id']): ?>
                <p><strong>Transaction ID:</strong> <span style="font-family:monospace;"><?php echo htmlspecialchars($order['payment_id']); ?></span></p>
                <?php endif; ?>
            </div>
        </div>
        
        <table class="table-responsive">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th>Unit</th>
                    <th class="right">Qty</th>
                    <th class="right">Price</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                    <td class="right"><?php echo $item['quantity']; ?></td>
                    <td class="right">₹<?php echo number_format($item['price'], 2); ?></td>
                    <td class="right">₹<?php echo number_format($item['subtotal'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="totals">
            <table class="totals-table">
                <tr>
                    <td>Subtotal:</td>
                    <td align="right">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                </tr>
                <tr>
                    <td>Shipping:</td>
                    <td align="right">Free</td>
                </tr>
                <tr>
                    <td class="total-row">Grand Total:</td>
                    <td class="total-row" align="right">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="footer">
            <p>Thank you for your business! IFFDC Maharajpur provides official and quality fertilizers.</p>
        </div>
    </div>
</body>
</html>
