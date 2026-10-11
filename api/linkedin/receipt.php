<?php
// Acknowledges an order, including pending payments; never grants an entitlement.
require_once __DIR__ . '/../../admin/config/db.php';
require_once __DIR__ . '/../../includes/linkedin-receipt-pdf.php';
header('Cache-Control: private, no-store');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
$reference = strtoupper(trim((string) ($_GET['ref'] ?? '')));
$token = (string) ($_GET['token'] ?? '');
if (!preg_match('/^[A-Z0-9-]{1,20}$/', $reference) || !preg_match('/^[a-f0-9]{32,64}$/', $token)) {
    http_response_code(403);
    exit('Accès non autorisé.');
}
try {
    $stmt = $pdo->prepare('SELECT id, reference, access_token, total_amount, discount_amount, payment_status, created_at FROM linkedin_orders WHERE reference = ?');
    $stmt->execute([$reference]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order || empty($order['access_token']) || !hash_equals($order['access_token'], $token)) {
        http_response_code(403);
        exit('Accès non autorisé.');
    }
    $stmt = $pdo->prepare('SELECT item_name_snapshot, final_price_snapshot FROM linkedin_order_items WHERE order_id = ? ORDER BY id');
    $stmt->execute([$order['id']]);
    $pdfBytes = linkedin_receipt_pdf($order, $stmt->fetchAll(PDO::FETCH_ASSOC));
    $pdo->prepare('INSERT INTO linkedin_order_history (order_id, action, note, created_by) VALUES (?, ?, ?, ?)')
        ->execute([$order['id'], 'RECEIPT_DOWNLOADED', 'Téléchargement du reçu de commande.', 'customer']);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="recu-' . $reference . '.pdf"');
    header('Content-Length: ' . strlen($pdfBytes));
    echo $pdfBytes;
} catch (Exception $e) {
    http_response_code(500);
    echo 'Le reçu est temporairement indisponible. Veuillez réessayer.';
}
