<?php
require 'db.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit;
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("UPDATE purchases SET delivery_status = 'delivered_whatsapp' WHERE id = ?");
$stmt->execute([$id]);

echo "OK";
?>
