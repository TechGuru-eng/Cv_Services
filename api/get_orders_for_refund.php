<?php
// api/get_orders_for_refund.php
header('Content-Type: application/json');
require_once '../admin/config/db.php';

if (!isset($_GET['identifier']) || empty(trim($_GET['identifier']))) {
    echo json_encode(['success' => false, 'error' => 'Identifiant manquant.']);
    exit;
}

$identifier = trim($_GET['identifier']);

try {
    // We select orders that are verified, NOT refunded already, and NOT currently pending/accepted in refund_requests
    $stmt = $pdo->prepare("
        SELECT id, package_name, amount, created_at 
        FROM orders 
        WHERE (email = ? OR whatsapp = ?) 
        AND payment_status = 'verified'
        AND id NOT IN (
            SELECT order_id FROM refund_requests
        )
        ORDER BY created_at DESC
    ");
    $stmt->execute([$identifier, $identifier]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($orders)) {
        // Let's check if there are orders but they are already in refund_requests
        $checkStmt = $pdo->prepare("
            SELECT id FROM orders 
            WHERE (email = ? OR whatsapp = ?) 
            AND payment_status = 'verified'
        ");
        $checkStmt->execute([$identifier, $identifier]);
        $hasOrders = $checkStmt->fetch();

        if ($hasOrders) {
            echo json_encode(['success' => false, 'error' => 'Vous avez déjà soumis une demande de remboursement pour toutes vos commandes éligibles.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Aucune commande validée trouvée avec cet identifiant.']);
        }
    } else {
        echo json_encode(['success' => true, 'orders' => $orders]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur serveur.']);
}
?>
