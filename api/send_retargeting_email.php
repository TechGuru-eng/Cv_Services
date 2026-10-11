<?php
require_once __DIR__ . '/../includes/manual-email-approval.php';
// api/send_retargeting_email.php
// Secure API endpoint for MailBatch Pro to send a retargeting email.
// Supported methods: POST, OPTIONS

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1. Load PHPMailer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../PHPMailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/../PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'PHPMailer library not found on the server']);
    exit;
}

// 2. Establish DB connection (resilient connection for tracking)
$pdo = null;
$dbPath = __DIR__ . '/../admin/config/db.php';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../config/db.php';
}
if (file_exists($dbPath)) {
    try {
        require_once $dbPath;
    } catch (Exception $e) {
        // Continue without DB connection if it fails, email sending is primary
    }
}

// 3. Security Check (Validate pre-shared secret key)
$expectedToken = getenv('MONCV_MAILBATCH_TOKEN') ?: '';
$token = '';

// Check Authorization Header
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

// 4. Parse Request Data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$recipientEmail = $input['email'] ?? '';
$subject = $input['subject'] ?? '';
$body = $input['body'] ?? '';
$fromName = $input['fromName'] ?? $input['from_name'] ?? 'HireMe CV Services';

if (empty($recipientEmail) || empty($subject) || empty($body)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: email, subject, body']);
    exit;
}

if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid recipient email format']);
    exit;
}

// 4.5 Process Tracking (Inject tracking pixel & Rewrite links)
$campaignId = $input['campaign'] ?? $input['campaign_id'] ?? 'mailbatch_manual';
$version = $input['version'] ?? $input['subject_version'] ?? 'A';

$encodedEmail = urlencode($recipientEmail);
$encodedCampaign = urlencode($campaignId);

// Dynamically build base tracking URL matching current request host/path
$httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
$protocol = $isHttps ? "https://" : "http://";
$trackingBase = $protocol . $httpHost . dirname($_SERVER['SCRIPT_NAME']);
$trackingBase = rtrim($trackingBase, '/\\');

// Append open tracking pixel
$openPixelUrl = "{$trackingBase}/track_email_open?email={$encodedEmail}&campaign={$encodedCampaign}&version={$version}";
$openPixelTag = "\n<img src=\"{$openPixelUrl}\" width=\"1\" height=\"1\" style=\"display:none;\" />";
$body = $body . $openPixelTag;

// Rewrite CTA links to go through track_email_click
$pattern = '/href="([^"]*(?:hiremeguide\.com|localhost|moncv\.test|hireme\.test)[^"]*)"/i';
$body = preg_replace_callback($pattern, function($matches) use ($trackingBase, $encodedEmail, $encodedCampaign, $version) {
    $originalUrl = $matches[1];
    if (strpos($originalUrl, 'track_email_click') !== false || strpos($originalUrl, 'track_email_open') !== false) {
        return $matches[0];
    }
    $redirectUrl = "{$trackingBase}/track_email_click?email={$encodedEmail}&campaign={$encodedCampaign}&version={$version}&url=" . urlencode($originalUrl);
    return 'href="' . $redirectUrl . '"';
}, $body);

// 5. Send Email via Hostinger SMTP
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'admin@hiremegroup.com';
    $mail->Password   = (getenv('SMTP_PASSWORD') ?: '');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    // Recipients
    $mail->setFrom('admin@hiremegroup.com', $fromName);
    $mail->addAddress($recipientEmail);
    $mail->addReplyTo('admin@hiremegroup.com', $fromName);

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $body;
    // Create text version by stripping tags
    $mail->AltBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $body));

    $mail->send();

    // 6. DB Tracking Update (Optional & Silent failure)
    if ($pdo) {
        try {
            // Update prospects table: increment followup_stage, record last_email_sent
            $stmt = $pdo->prepare("
                UPDATE prospects 
                SET followup_stage = followup_stage + 1, 
                    last_email_sent = NOW() 
                WHERE email = ? AND status IN ('pending', 'lead_audit')
            ");
            $stmt->execute([$recipientEmail]);
        } catch (Exception $dbEx) {
            // Silent catch to prevent DB issues from failing the email success response
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Email sent successfully via Hostinger SMTP.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Mailer Error: ' . $mail->ErrorInfo
    ]);
}
?>
