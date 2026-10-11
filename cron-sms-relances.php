<?php
// Reminders must be chosen and approved by the admin; never run interval campaigns.
if (PHP_SAPI !== 'cli') http_response_code(403);
exit("Relances SMS automatiques désactivées. Utilisez le tableau de bord SMS pour approuver chaque envoi.\n");
/**
 * Cron Job script for Automated SMS Reminders (Auto-Pilot)
 * Should be run once daily.
 * 
 * Command Line (CLI): php cron-sms-relances.php
 * Web Browser: https://yourdomain.com/cron-sms-relances.php?token=SECURE_HASH
 */

// Set time limit and default timezone
set_time_limit(300);
date_default_timezone_set('Africa/Douala');

header("Content-Type: text/plain; charset=UTF-8");
echo "=== HIREME AUTOMATED SMS FOLLOW-UPS (AUTO-PILOT) ===\n\n";

// 1. Establish DB connection
$dbPath = __DIR__ . '/admin/config/db.php';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/config/db.php';
}
if (!file_exists($dbPath)) {
    die("[✗] CRITICAL ERROR: Unable to load db.php!\n");
}
require_once $dbPath;

// 2. Security Check (CLI is always allowed, Web requires md5 token of TextBee API Key)
$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    // Fetch textbee API key to generate secure token
    $stmtKey = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'textbee_api_key' LIMIT 1");
    $stmtKey->execute();
    $apiKey = $stmtKey->fetchColumn();
    
    if (!$apiKey) {
        die("[✗] SECURITY ERROR: SMS gateway not configured yet.\n");
    }
    
    $expectedToken = md5($apiKey);
    $receivedToken = $_GET['token'] ?? '';
    
    if (empty($receivedToken) || $receivedToken !== $expectedToken) {
        header("HTTP/1.1 403 Forbidden");
        die("[✗] SECURITY ERROR: Invalid or missing token.\n");
    }
}

// 3. Load SMS Helper & Activity Helper
require_once __DIR__ . '/admin/config/sms_helper.php';
require_once __DIR__ . '/admin/includes/activity_helper.php';

try {
    // 4. Select pending/unpaid orders older than 24 hours
    // We only process orders with status = 'pending' (unpaid)
    $stmt = $pdo->query("
        SELECT id, order_ref, name, whatsapp, package_name, amount, created_at, relance_level, last_relance_at 
        FROM orders 
        WHERE payment_status = 'pending' 
          AND created_at <= NOW() - INTERVAL 1 DAY
          AND (relance_level IS NULL OR relance_level < 3)
        ORDER BY id ASC
    ");
    $unpaidOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($unpaidOrders)) {
        echo "[✓] No pending unpaid orders matching the age requirements found.\n";
        exit;
    }

    echo "Found " . count($unpaidOrders) . " pending unpaid order(s) for processing.\n\n";

    $successCount = 0;
    $failCount = 0;

    foreach ($unpaidOrders as $order) {
        $orderId = (int)$order['id'];
        $createdTime = strtotime($order['created_at']);
        $hoursAgo = (time() - $createdTime) / 3600;
        $currentLevel = (int)($order['relance_level'] ?? 0);
        
        $clientFirstName = explode(' ', trim($order['name'] ?? 'Client'))[0];
        $orderRef = htmlspecialchars($order['order_ref'] ?? $order['id']);
        $amount = number_format($order['amount'], 0, ',', ' ');
        $packageName = htmlspecialchars($order['package_name'] ?? 'CV');
        
        // Build tracking URL (Option 2 Shortener will intercept and shorten this automatically inside SMSHelper::sendSMS)
        $trackingUrl = "https://moncvpro.hiremeguide.com/track-cv.php?ref=" . urlencode($orderRef) . "&src=sms";
        
        $smsMessage = "";
        $nextLevel = 0;

        // Determine Level based on order age and current progress level
        if ($hoursAgo >= 24 && $hoursAgo < 48 && $currentLevel === 0) {
            // Level 1: 24h Friendly reminder
            $smsMessage = "Bonjour $clientFirstName! Votre commande de CV ($packageName) chez HireMe est toujours en attente de paiement. Finalisez-la ici pour lancer la redaction: $trackingUrl";
            $nextLevel = 1;
        } 
        elseif ($hoursAgo >= 48 && $hoursAgo < 72 && $currentLevel <= 1) {
            // Level 2: 48h Special Discount offer (BOOST10 code promo)
            $smsMessage = "Bonjour $clientFirstName! Exceptionnellement, profitez de 10% de reduction sur votre CV avec le code promo BOOST10. Validez vite votre commande ici: $trackingUrl";
            $nextLevel = 2;
        } 
        elseif ($hoursAgo >= 72 && $currentLevel <= 2) {
            // Level 3: 72h Final Warning before archival
            $smsMessage = "Derniere chance $clientFirstName! Votre dossier de CV HireMe va etre archive sous 24h. Finalisez votre commande des maintenant pour en profiter: $trackingUrl";
            $nextLevel = 3;
        }

        // If a message was generated, send it
        if (!empty($smsMessage) && $nextLevel > 0) {
            echo "-> Processing Order #$orderId ($orderRef) | Level $currentLevel -> Level $nextLevel | Phone: {$order['whatsapp']}...\n";
            
            $res = SMSHelper::sendSMS($order['whatsapp'], $smsMessage);
            
            if ($res['success']) {
                $stmtUp = $pdo->prepare("UPDATE orders SET relance_level = ?, last_relance_at = NOW() WHERE id = ?");
                $stmtUp->execute([$nextLevel, $orderId]);
                
                log_activity("Cron SMS Relance", "Relance Level $nextLevel envoyee automatiquement a " . $order['name']);
                echo "   [✓] SMS sent successfully!\n";
                $successCount++;
            } else {
                echo "   [✗] SMS failed: " . $res['error'] . "\n";
                $failCount++;
            }
        }
    }

    echo "\n=== RUN COMPLETED ===\n";
    echo "Successfully sent: $successCount relances | Failed: $failCount relances.\n";

} catch (Exception $e) {
    echo "[✗] ERROR DURING RUN: " . $e->getMessage() . "\n";
}
