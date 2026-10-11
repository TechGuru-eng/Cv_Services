<?php
// api/launch_relance.php
// API endpoint to initialize and schedule automated sequential email relances.
// Supported methods: POST, OPTIONS

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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

// 2. Security Token Verification
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

// 3. Create Mail Queue Table if not exists
try {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS mailbatch_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        name VARCHAR(150) DEFAULT '',
        subject VARCHAR(255) NOT NULL,
        body LONGTEXT NOT NULL,
        alt_body TEXT NOT NULL,
        from_name VARCHAR(100) DEFAULT 'HireMe CV Services',
        campaign_id VARCHAR(100) DEFAULT '',
        version VARCHAR(10) DEFAULT 'A',
        status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
        scheduled_at DATETIME NOT NULL,
        sent_at DATETIME NULL,
        error_message TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_status_scheduled (status, scheduled_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to initialize mail queue table: ' . $e->getMessage()]);
    exit;
}

// 4. Parse payload
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$target = $input['target'] ?? 'all'; // 'all', 'prospects', 'orders'
$durationHours = floatval($input['duration_hours'] ?? 12);
$useABTesting = !empty($input['use_ab_testing']);
$subjectSingle = $input['subject_single'] ?? '';
$subjectA = $input['subject_a'] ?? '';
$subjectB = $input['subject_b'] ?? '';
$fromName = $input['from_name'] ?? 'HireMe CV Services';
$campaignId = $input['campaign_id'] ?? ('auto_relance_' . date('Ymd_His'));

// Customized variables from customizer
$vars = $input['customizer_vars'] ?? [];
$defaultPackage = $vars['package_name'] ?? 'CV Premium';
$couponCode = $vars['coupon_code'] ?? 'WELCOME20';
$cvScore = $vars['cv_score'] ?? '45';
$targetJob = $vars['target_job'] ?? 'Responsable Marketing';
$defaultPrice = $vars['price'] ?? '14 900 FCFA';
$ctaLink = $vars['cta_link'] ?? 'https://moncvpro.hiremeguide.com/';

// Email templates with placeholders
$templateHtml = $input['template_html'] ?? '';
$templateText = $input['template_text'] ?? '';

$blacklist = $input['blacklist'] ?? [];
$blacklistSet = array_map('strtolower', array_map('trim', $blacklist));

if (empty($templateHtml)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing template HTML body']);
    exit;
}

try {
    // 5. Query Target Clients
    $clients = [];

    // Prospects (pending / lead_audit)
    if ($target === 'all' || $target === 'prospects') {
        $stmt = $pdo->query("
            SELECT name, email, whatsapp, package_name, total_amount, 'prospect' as type 
            FROM prospects 
            WHERE status IN ('pending', 'lead_audit')
        ");
        if ($stmt) {
            while ($row = $stmt->fetch()) {
                $clients[] = $row;
            }
        }
    }

    // Audits (lead_audit only)
    if ($target === 'audits') {
        $stmt = $pdo->query("
            SELECT name, email, whatsapp, package_name, total_amount, 'prospect' as type 
            FROM prospects 
            WHERE status = 'lead_audit'
        ");
        if ($stmt) {
            while ($row = $stmt->fetch()) {
                $clients[] = $row;
            }
        }
    }

    // Orders (pending payment)
    if ($target === 'all' || $target === 'orders') {
        $stmt = $pdo->query("
            SELECT name, email, whatsapp, package_name, amount as total_amount, 'order' as type 
            FROM orders 
            WHERE payment_status = 'pending'
        ");
        if ($stmt) {
            while ($row = $stmt->fetch()) {
                $clients[] = $row;
            }
        }
    }

    // 6. De-duplicate & Filter targets
    $uniqueTargets = [];
    $seenEmails = [];

    foreach ($clients as $c) {
        $email = strtolower(trim($c['email'] ?? ''));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }
        if (in_array($email, $blacklistSet)) {
            continue; // Skip blacklisted
        }
        if (in_array($email, $seenEmails)) {
            continue; // Skip duplicates
        }

        $seenEmails[] = $email;
        $uniqueTargets[] = $c;
    }

    $totalTargets = count($uniqueTargets);
    if ($totalTargets === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No eligible clients found to launch relance.',
            'queued_count' => 0
        ]);
        exit;
    }

    // 7. Calculate Schedule Intervals
    // Spread $totalTargets emails over $durationHours
    $totalSeconds = $durationHours * 3600;
    $interval = $totalTargets > 1 ? ($totalSeconds / ($totalTargets - 1)) : 0;

    // Clear any previous PENDING queue items for clean launch
    $pdo->exec("DELETE FROM mailbatch_queue WHERE status = 'pending'");

    // 8. Insert into Queue
    $stmtInsert = $pdo->prepare("
        INSERT INTO mailbatch_queue (email, name, subject, body, alt_body, from_name, campaign_id, version, scheduled_at, status)
        VALUES (:email, :name, :subject, :body, :alt_body, :from_name, :campaign_id, :version, :scheduled_at, 'pending')
    ");

    $now = new DateTime();
    $queuedCount = 0;

    foreach ($uniqueTargets as $index => $c) {
        $email = strtolower(trim($c['email']));
        $name = trim($c['name']);

        // Determine A/B subject version
        $version = 'A';
        if ($useABTesting) {
            $version = ($index % 2 === 0) ? 'A' : 'B';
            $subject = $version === 'A' ? ($subjectA ?: $subjectSingle) : ($subjectB ?: $subjectSingle);
        } else {
            $subject = $subjectSingle;
        }

        // 1. Personalize Template placeholders
        $firstName = explode(' ', $name)[0];
        if (empty($firstName)) $firstName = 'Candidat';

        $packageName = trim($c['package_name'] ?? '');
        if (empty($packageName)) $packageName = $defaultPackage;

        $price = $defaultPrice;
        if (!empty($c['total_amount']) && floatval($c['total_amount']) > 0) {
            $price = number_format(floatval($c['total_amount']), 0, '', ' ') . ' FCFA';
        }

        // Replace placeholders in Subject
        $personalizedSubject = str_replace(
            ['{{FIRST_NAME}}', '{{PACKAGE_NAME}}', '{{COUPON_CODE}}', '{{CV_SCORE}}', '{{TARGET_JOB}}', '{{PRICE}}', '{{CTA_LINK}}'],
            [$firstName, $packageName, $couponCode, $cvScore, $targetJob, $price, $ctaLink],
            $subject
        );

        // Replace placeholders in HTML Body
        $personalizedBody = str_replace(
            ['{{FIRST_NAME}}', '{{PACKAGE_NAME}}', '{{COUPON_CODE}}', '{{CV_SCORE}}', '{{TARGET_JOB}}', '{{PRICE}}', '{{CTA_LINK}}'],
            [$firstName, $packageName, $couponCode, $cvScore, $targetJob, $price, $ctaLink],
            $templateHtml
        );

        // Replace placeholders in Text Body
        $personalizedAltBody = str_replace(
            ['{{FIRST_NAME}}', '{{PACKAGE_NAME}}', '{{COUPON_CODE}}', '{{CV_SCORE}}', '{{TARGET_JOB}}', '{{PRICE}}', '{{CTA_LINK}}'],
            [$firstName, $packageName, $couponCode, $cvScore, $targetJob, $price, $ctaLink],
            $templateText
        );

        // 2. Compute scheduled time
        $scheduledTime = clone $now;
        $secondsToAdd = round($index * $interval);
        $scheduledTime->modify("+{$secondsToAdd} seconds");

        // Insert
        $stmtInsert->execute([
            ':email' => $email,
            ':name' => $name,
            ':subject' => $personalizedSubject,
            ':body' => $personalizedBody,
            ':alt_body' => $personalizedAltBody,
            ':from_name' => $fromName,
            ':campaign_id' => $campaignId,
            ':version' => $version,
            ':scheduled_at' => $scheduledTime->format('Y-m-d H:i:s')
        ]);

        $queuedCount++;
    }

    echo json_encode([
        'success' => true,
        'message' => "Campaign '$campaignId' launched successfully. $queuedCount emails queued sequentially.",
        'queued_count' => $queuedCount,
        'duration_hours' => $durationHours,
        'average_interval_seconds' => round($interval, 1),
        'start_time' => $now->format('Y-m-d H:i:s'),
        'end_time' => $now->modify('+' . round($totalSeconds) . ' seconds')->format('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error launching campaign: ' . $e->getMessage()]);
}
?>
