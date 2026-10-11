<?php
session_start();
header("Access-Control-Allow-Origin: *");

// Simple authentication check based on admin session
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    die("Accès non autorisé.");
}

require 'db.php';

$purchaseId = $_GET['id'] ?? null;

if (!$purchaseId) {
    die("ID de commande manquant.");
}

try {
    // 1. Fetch the purchase details
    $stmt = $pdo->prepare("SELECT * FROM purchases WHERE id = ?");
    $stmt->execute([$purchaseId]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        die("Commande introuvable.");
    }

    if ($purchase['status'] !== 'approved') {
        die("Impossible de livrer : le paiement n'est pas encore approuvé.");
    }

    // 2. Prepare the Payload for n8n
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $basePath = str_replace('/api/trigger_delivery.php', '', $_SERVER['SCRIPT_NAME']);
    $baseUrl = $protocol . "://" . $host . $basePath;

    $payload = [
        'event' => 'deliver_ebooks',
        'purchase_id' => $purchase['id'],
        'customer' => [
            'name' => $purchase['full_name'],
            'email' => $purchase['email'],
            'whatsapp' => preg_replace('/[^0-9]/', '', $purchase['whatsapp'] ?? ''),
        ],
        'files' => [
            'guide_ultime' => $baseUrl . '/assets/Guide_Ultime.pdf',
            'bonus_1' => $baseUrl . '/assets/Bonus_1.pdf',
            'bonus_2' => $baseUrl . '/assets/Bonus_2.pdf',
            'bonus_3' => $baseUrl . '/assets/Bonus_3.pdf'
        ]
    ];

    if ($purchase['upsell_cv']) {
        $payload['files']['cv_pro'] = $baseUrl . '/assets/Modele_CV_Pro.zip';
    }

    // 3. Send Payload to n8n Webhook
    $n8nWebhookUrl = 'https://n8n.srv1140349.hstgr.cloud/webhook-test/e98e83c9-692e-4cef-bcda-c8137fbba726';

    $ch = curl_init($n8nWebhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
    $result = curl_exec($ch);
    curl_close($ch);

    // 4. Update the Database Status
    $updateStmt = $pdo->prepare("UPDATE purchases SET delivery_status = 'delivered_n8n' WHERE id = ?");
    $updateStmt->execute([$purchaseId]);

    // Redirect back to the admin panel with a success message
    header("Location: ../admin/index.php?success=n8n_delivered#commandes");
    exit();

} catch (Exception $e) {
    die("Erreur interne : " . $e->getMessage());
}
?>
