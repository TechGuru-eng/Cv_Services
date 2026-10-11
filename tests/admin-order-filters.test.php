<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../admin/config/db.php';
$mode = $argv[1] ?? 'status';
$marker = 'FILTERTEST' . bin2hex(random_bytes(4));
$pdo->beginTransaction();
register_shutdown_function(static function () use ($pdo) { if ($pdo->inTransaction()) $pdo->rollBack(); });
$insert = $pdo->prepare('INSERT INTO orders (order_ref,name,email,whatsapp,package_name,amount,payment_status,order_type,transaction_number) VALUES (?,?,?,?,?,1000,?,?,?)');
foreach (['pending', 'verified', 'failed'] as $index => $status) {
    $insert->execute([$marker . $index, $marker . ' customer', $marker . '@example.invalid', '+237 699-123-456', 'Test CV', $status, $index === 2 ? 'advance' : 'standard', $marker . 'TX']);
}
$_GET = ['q' => $marker];
if ($mode === 'status') $_GET['status'] = 'verified';
elseif ($mode === 'reservation') $_GET['type'] = 'advance';
elseif ($mode === 'literal') $_GET['q'] = $marker . '%';
elseif ($mode === 'injection') $_GET['q'] = $marker . "' OR 1=1 --";
elseif ($mode === 'phone') { $_GET['q'] = '699123456'; $_GET['status'] = 'verified'; }
elseif ($mode === 'pagination') {
    for ($i = 3; $i < 53; $i++) $insert->execute([$marker . $i, $marker, $marker . '@example.invalid', '', 'Test CV', 'pending', 'standard', '']);
    $_GET['page'] = '2';
}
session_start(); $_SESSION['admin'] = true; session_write_close();
$_SERVER['PHP_SELF'] = '/admin/orders.php';
chdir(__DIR__ . '/../admin');
ob_start(); require 'orders.php'; $html = ob_get_clean();
$assert = static function ($condition, $message) { if (!$condition) throw new RuntimeException($message); };
if ($mode === 'status') {
    $assert(count($orders) === 1 && $orders[0]['payment_status'] === 'verified', 'Status filter failed');
    $assert($counts['pending'] === 1 && $counts['verified'] === 1 && $counts['failed'] === 1, 'Search counts failed');
    $assert(str_contains($html, 'Approuvées') && str_contains($html, 'name="status"'), 'Approved label/filter missing');
} elseif ($mode === 'reservation') $assert(count($orders) === 1 && $orders[0]['order_type'] === 'advance', 'Reservation filter failed');
elseif ($mode === 'literal' || $mode === 'injection') $assert(count($orders) === 0 && str_contains($html, 'Aucune commande'), 'Literal search or SQL binding failed');
elseif ($mode === 'phone') $assert(in_array($marker . '1', array_column($orders, 'order_ref'), true), 'Formatted phone search failed');
elseif ($mode === 'pagination') $assert($total === 53 && count($orders) === 3 && str_contains($html, 'Précédente'), 'Pagination failed');
$pdo->rollBack();
echo "PASS: admin order filters ($mode)\n";
