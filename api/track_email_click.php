<?php
// api/track_email_click.php
// Tracking redirect link for MailBatch Pro to monitor email clicks.
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
        // Continue even if DB connection fails
    }
}

// 2. Read Request Parameters
$email = $_GET['email'] ?? '';
$campaignId = $_GET['campaign'] ?? $_GET['campaign_id'] ?? '';
$subjectVersion = $_GET['version'] ?? $_GET['subject_version'] ?? 'A';
$targetUrl = $_GET['url'] ?? '';

// Default fallback URL if missing
if (empty($targetUrl)) {
    $targetUrl = 'https://moncvpro.hiremeguide.com';
} else {
    // Decode target URL
    $targetUrl = filter_var($targetUrl, FILTER_SANITIZE_URL);
}

// 3. Log click in database
if (!empty($email) && isset($pdo)) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt = $pdo->prepare("
            INSERT INTO mailbatch_tracking (email, action_type, subject_version, campaign_id, ip_address, user_agent)
            VALUES (?, 'click', ?, ?, ?, ?)
        ");
        $stmt->execute([$email, $subjectVersion, $campaignId, $ip, $ua]);
    } catch (Exception $dbEx) {
        // Silent catch to always guarantee redirection
    }
}

// 4. Redirect browser to final checkout / landing page URL
header("Location: " . $targetUrl, true, 302);
exit;
?>
