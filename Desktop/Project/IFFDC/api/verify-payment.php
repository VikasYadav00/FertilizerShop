<?php
// ============================================================
// api/verify-payment.php — Verifies Razorpay Payment Success
// ============================================================
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId   = intval($input['order_id'] ?? 0);
$paymentId = trim($input['payment_id'] ?? '');

if (!$orderId || !$paymentId) {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
    exit;
}

// In a real production application, you MUST verify the razorpay_signature here 
// using your Razorpay Key Secret to ensure the payment is genuine.
// Since this is a template/demonstration, we mark it as paid.

$stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid', payment_id = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("sii", $paymentId, $orderId, $_SESSION['user_id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
$stmt->close();
?>
