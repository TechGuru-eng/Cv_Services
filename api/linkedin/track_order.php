<?php
// api/linkedin/track_order.php
header('Content-Type: application/json');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../admin/config/db.php';
require_once __DIR__ . '/../../includes/linkedin-order-validation.php';
require_once __DIR__ . '/../../includes/upload_helper.php';
if (!rate_limit_ok('linkedin-tracking', 30, 600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Trop de tentatives. Veuillez réessayer plus tard.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$reference = strtoupper(trim($_POST['reference'] ?? ''));
$queryContact = trim($_POST['contact'] ?? ''); // Email or phone number

if (empty($reference) || empty($queryContact)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez saisir votre numéro de commande et votre adresse email ou téléphone.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT o.id, o.reference, o.access_token, o.name, o.first_name, o.email, o.phone, o.payment_status, o.order_status, o.total_amount, o.created_at, p.name as package_name FROM linkedin_orders o LEFT JOIN linkedin_packages p ON o.package_id = p.id WHERE o.reference = ? LIMIT 1");
    $stmt->execute([$reference]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($order && !linkedin_order_contact_matches($order['email'], $order['phone'], $queryContact)) $order = false;

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Aucune commande trouvée correspondant à ces informations. Vérifiez le numéro de référence (ex: HM-LI-284731).']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'order' => [
            'reference' => $order['reference'],
            'access_token' => $order['access_token'],
            'client_name' => $order['first_name'] . ' ' . $order['name'],
            'package_name' => $order['package_name'],
            'total_amount' => (float)$order['total_amount'],
            'payment_status' => $order['payment_status'],
            'order_status' => $order['order_status'],
            'created_at' => $order['created_at'],
            'view_url' => 'linkedin/commande.php?ref=' . $order['reference'] . '&token=' . $order['access_token']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la recherche. Veuillez réessayer.']);
}
?>
