<?php
require_once 'config/db.php';
$stmt = $conn->query("SELECT p.* FROM products p LIMIT 2");
$products = $stmt->fetch_all(MYSQLI_ASSOC);
echo json_encode($products);
?>
