<?php
// api/track_email_open.php
// Tracking pixel for MailBatch Pro to monitor email opens.
// Supported methods: GET

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// 1. Establish DB connection
$dbPath = __DIR__ . '/../admin/config/db.php';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../config/db.php';
}
if (file_exists($dbPath)) {
    try {
        require_once $dbPath;
    } catch (Exception $e) {
        // Continue to output the image even if DB connection fails
    }
}

// 2. Read Request Parameters
$email = $_GET['email'] ?? '';
$campaignId = $_GET['campaign'] ?? $_GET['campaign_id'] ?? '';
$subjectVersion = $_GET['version'] ?? $_GET['subject_version'] ?? 'A';

// 3. Log open in database
if (!empty($email) && isset($pdo)) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt = $pdo->prepare("
            INSERT INTO mailbatch_tracking (email, action_type, subject_version, campaign_id, ip_address, user_agent)
            VALUES (?, 'open', ?, ?, ?, ?)
        ");
        $stmt->execute([$email, $subjectVersion, $campaignId, $ip, $ua]);
    } catch (Exception $dbEx) {
        // Silent catch to always guarantee image delivery
    }
}

// 4. Deliver 1x1 transparent GIF
header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
exit;
?>
