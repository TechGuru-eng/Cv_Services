<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require 'db.php';

try {
    $purchaseId = $_POST['purchase_id'] ?? null;
    $transactionId = $_POST['transactionId'] ?? null;
    
    if (!$purchaseId || !$transactionId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Informations manquantes.']);
        exit;
    }

    $proofFilePath = null;
    if (isset($_FILES['proofFile']) && $_FILES['proofFile']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/proofs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileInfo = pathinfo($_FILES['proofFile']['name']);
        $ext = strtolower($fileInfo['extension']);
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array($ext, $allowed)) {
            $fileName = 'proof_' . $purchaseId . '_' . time() . '.' . $ext;
            $destination = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['proofFile']['tmp_name'], $destination)) {
                $proofFilePath = 'uploads/proofs/' . $fileName;
            } else {
                throw new Exception('Erreur lors du téléchargement du fichier.');
            }
        } else {
            throw new Exception('Format de fichier non autorisé.');
        }
    } else {
        throw new Exception('Fichier de preuve manquant.');
    }

    $stmt = $pdo->prepare("UPDATE purchases SET transaction_id = ?, proof_file_path = ?, status = 'paid_unverified' WHERE id = ?");
    $stmt->execute([$transactionId, $proofFilePath, $purchaseId]);

    // --- NOTIFICATION n8n (WhatsApp) ---
    // 1. Récupérer les infos de l'acheteur pour le message WhatsApp
    $stmtUser = $pdo->prepare("SELECT full_name, email, whatsapp, price_paid FROM purchases WHERE id = ?");
    $stmtUser->execute([$purchaseId]);
    $buyer = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($buyer) {
        $n8nWebhookUrl = 'https://n8n.srv1140349.hstgr.cloud/webhook-test/e98e83c9-692e-4cef-bcda-c8137fbba726'; // <-- COLREZ LE LIEN WEBHOOK n8n ICI
        
        if ($n8nWebhookUrl !== 'METTEZ_VOTRE_URL_TEST_N8N_ICI') {
            $orderData = [
                'event' => 'new_payment_proof',
                'purchase_id' => $purchaseId,
                'transaction_id' => $transactionId,
                'nom' => $buyer['full_name'],
                'email' => $buyer['email'],
                'whatsapp' => $buyer['whatsapp'],
                'montant' => $buyer['price_paid'],
                'date' => date('Y-m-d H:i:s')
            ];
            
            $options = [
                'http' => [
                    'method'  => 'POST',
                    'header'  => 'Content-Type: application/json',
                    'content' => json_encode($orderData),
                    'timeout' => 2 // Ne pas bloquer l'utilisateur si n8n est lent
                ]
            ];
            $context = stream_context_create($options);
            @file_get_contents($n8nWebhookUrl, false, $context);
        }
    }
    // -----------------------------------

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
