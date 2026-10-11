<?php
// api/linkedin/download_file.php
session_start();
require_once __DIR__ . '/../../admin/config/db.php';

$type = $_GET['type'] ?? ''; // 'upload', 'deliverable', or 'product'
$fileId = (int)($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';
$ref = $_GET['ref'] ?? '';

$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if ($fileId <= 0) {
    die("Fichier non spécifié.");
}

try {
    $filePath = '';
    $fileName = '';
    $accessTokenRequired = '';

    if ($type === 'deliverable') {
        $stmt = $pdo->prepare("SELECT d.*, o.access_token FROM linkedin_deliverables d JOIN linkedin_orders o ON d.order_id = o.id WHERE d.id = ?");
        $stmt->execute([$fileId]);
        $record = $stmt->fetch();
        if ($record) {
            $filePath = __DIR__ . '/../../' . ltrim($record['file_path'], '/');
            $fileName = $record['file_name'] ?? basename($filePath);
            $accessTokenRequired = $record['access_token'];
        }
    } elseif ($type === 'upload') {
        $stmt = $pdo->prepare("SELECT u.*, o.access_token FROM linkedin_uploads u JOIN linkedin_orders o ON u.order_id = o.id WHERE u.id = ?");
        $stmt->execute([$fileId]);
        $record = $stmt->fetch();
        if ($record) {
            $filePath = __DIR__ . '/../../' . ltrim($record['file_path'], '/');
            $fileName = $record['file_name'] ?? basename($filePath);
            $accessTokenRequired = $record['access_token'];
        }
    } elseif ($type === 'product') {
        // Digital upsell deliverable product
        $stmt = $pdo->prepare("SELECT p.* FROM linkedin_products p WHERE p.id = ? AND p.is_digital = 1");
        $stmt->execute([$fileId]);
        $prod = $stmt->fetch();

        if ($prod && !empty($prod['digital_file_path'])) {
            // Check order token authorization if not admin
            if (!$isAdmin) {
                $stmtOrd = $pdo->prepare("SELECT o.access_token 
                                           FROM linkedin_orders o 
                                           JOIN linkedin_order_items oi ON o.id = oi.order_id 
                                           WHERE o.access_token = ? AND oi.item_type = 'product' AND oi.item_id = ? AND o.payment_status = 'CONFIRMED'");
                $stmtOrd->execute([$token, $fileId]);
                $accessTokenRequired = $stmtOrd->fetchColumn();
                if (!$accessTokenRequired) {
                    http_response_code(403);
                    die("Accès non autorisé à cette ressource ou paiement non encore confirmé.");
                }
            }
            $filePath = __DIR__ . '/../../' . ltrim($prod['digital_file_path'], '/');
            $fileName = basename($filePath);
        }
    } else {
        die("Type de fichier invalide.");
    }

    if (empty($filePath)) {
        die("Fichier introuvable.");
    }

    // Access control check: Admin OR valid access token
    if (!$isAdmin && $type !== 'product' && (empty($token) || $token !== $accessTokenRequired)) {
        http_response_code(403);
        die("Accès non autorisé à ce fichier.");
    }

    if (!file_exists($filePath)) {
        die("Le fichier n'existe plus sur le serveur.");
    }

    // Serve file with security headers
    $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    header('X-Content-Type-Options: nosniff');
    readfile($filePath);
    exit;

} catch (Exception $e) {
    die("Erreur de téléchargement : " . $e->getMessage());
}
?>
