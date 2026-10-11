<?php
require_once __DIR__ . '/../includes/manual-email-approval.php';
// api/process_queue.php
// Processes and sends pending emails from the mailbatch_queue database table.
// Supported methods: GET, POST, OPTIONS, or CLI

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 1. Establish DB connection
$dbPath = __DIR__ . '/../admin/config/db.php';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../config/db.php';
}
if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection helper not found']);
    exit;
}
require_once $dbPath;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 2. Load PHPMailer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../PHPMailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/../PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'PHPMailer library not found']);
    exit;
}

// 3. Security Token Verification (Bypassed if run via CLI)
$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    $expectedToken = getenv('MONCV_MAILBATCH_TOKEN') ?: '';
    $token = '';

    $headers = [];
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
    }
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    } else {
        $token = $_GET['token'] ?? $_POST['token'] ?? '';
    }

    if (empty($token) || $token !== $expectedToken) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid security token']);
        exit;
    }
}

// 4. Fetch Pending Queue Items (process 5 at a time to prevent timeout)
try {
    $stmt = $pdo->query("
        SELECT * FROM mailbatch_queue 
        WHERE status = 'pending' AND scheduled_at <= NOW() 
        ORDER BY scheduled_at ASC LIMIT 5
    ");
    
    $queueItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $processedCount = 0;
    $sentCount = 0;
    $failedCount = 0;
    $results = [];

    if (count($queueItems) === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No pending emails to send at this moment.',
            'processed' => 0
        ]);
        exit;
    }

    if (!getenv('SMTP_PASSWORD')) {
        http_response_code(503);
        exit(json_encode(['success' => false, 'message' => 'Service email non configuré.']));
    }
    // 5. Send SMTP Mail Config
    $smtp_config = [
        'host' => 'smtp.hostinger.com',
        'username' => 'admin@hiremegroup.com',
        'password' => (getenv('SMTP_PASSWORD') ?: ''),
        'port' => 465,
    ];

    foreach ($queueItems as $item) {
        $processedCount++;
        $id = $item['id'];
        $recipientEmail = $item['email'];
        $subject = $item['subject'];
        $body = $item['body'];
        $altBody = $item['alt_body'];
        $fromName = $item['from_name'];
        $campaignId = $item['campaign_id'];
        $version = $item['version'];

        // --- Process Tracking Hooks (Pixel & Click URL rewriting) ---
        $encodedEmail = urlencode($recipientEmail);
        $encodedCampaign = urlencode($campaignId);

        // Dynamically build base tracking URL
        $httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
        $protocol = $isHttps ? "https://" : "http://";
        
        // Fallback for CLI/local script path
        if ($isCli) {
            $trackingBase = "https://moncvpro.hiremeguide.com/api";
        } else {
            $trackingBase = $protocol . $httpHost . dirname($_SERVER['SCRIPT_NAME']);
            $trackingBase = rtrim($trackingBase, '/\\');
        }

        // 1. Append Open tracking pixel
        $openPixelUrl = "{$trackingBase}/track_email_open?email={$encodedEmail}&campaign={$encodedCampaign}&version={$version}";
        $openPixelTag = "\n<img src=\"{$openPixelUrl}\" width=\"1\" height=\"1\" style=\"display:none;\" />";
        $body = $body . $openPixelTag;

        // 2. Rewrite CTA links to go through track_email_click
        $pattern = '/href="([^"]*(?:hiremeguide\.com|localhost|moncv\.test|hireme\.test)[^"]*)"/i';
        $body = preg_replace_callback($pattern, function($matches) use ($trackingBase, $encodedEmail, $encodedCampaign, $version) {
            $originalUrl = $matches[1];
            if (strpos($originalUrl, 'track_email_click') !== false || strpos($originalUrl, 'track_email_open') !== false) {
                return $matches[0];
            }
            $redirectUrl = "{$trackingBase}/track_email_click?email={$encodedEmail}&campaign={$encodedCampaign}&version={$version}&url=" . urlencode($originalUrl);
            return 'href="' . $redirectUrl . '"';
        }, $body);

        // Send using PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $smtp_config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_config['username'];
            $mail->Password   = $smtp_config['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = $smtp_config['port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($smtp_config['username'], $fromName);
            $mail->addAddress($recipientEmail);
            $mail->addReplyTo($smtp_config['username'], $fromName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $body));

            $mail->send();

            // Success: Update database log
            $stmtUpdate = $pdo->prepare("
                UPDATE mailbatch_queue 
                SET status = 'sent', sent_at = NOW(), error_message = NULL 
                WHERE id = ?
            ");
            $stmtUpdate->execute([$id]);

            // Update prospects database stage (Optional)
            try {
                $stmtProspect = $pdo->prepare("
                    UPDATE prospects 
                    SET followup_stage = followup_stage + 1, last_email_sent = NOW() 
                    WHERE email = ? AND status IN ('pending', 'lead_audit')
                ");
                $stmtProspect->execute([$recipientEmail]);
            } catch (Exception $e) {
                // Silent
            }

            $sentCount++;
            $results[] = ['id' => $id, 'email' => $recipientEmail, 'status' => 'sent'];

        } catch (Exception $mailEx) {
            // Failure: Log error in queue
            $errorMsg = $mail->ErrorInfo ?: $mailEx->getMessage();
            $stmtUpdate = $pdo->prepare("
                UPDATE mailbatch_queue 
                SET status = 'failed', error_message = ? 
                WHERE id = ?
            ");
            $stmtUpdate->execute([$errorMsg, $id]);

            $failedCount++;
            $results[] = ['id' => $id, 'email' => $recipientEmail, 'status' => 'failed', 'error' => $errorMsg];
        }
    }

    echo json_encode([
        'success' => true,
        'processed' => $processedCount,
        'sent' => $sentCount,
        'failed' => $failedCount,
        'details' => $results
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Queue process failed: ' . $e->getMessage()]);
}
?>
