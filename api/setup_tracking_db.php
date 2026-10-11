<?php
// api/setup_tracking_db.php
// Setup script to initialize tracking database tables for MailBatch Pro.
// Supported methods: GET

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS requests
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

// 2. Security Check (Validate pre-shared secret key)
$expectedToken = getenv('MONCV_MAILBATCH_TOKEN') ?: '';
$token = $_GET['token'] ?? '';

if (empty($token) || $token !== $expectedToken) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid security token']);
    exit;
}

try {
    // 3. Create tracking table
    $sql = "
    CREATE TABLE IF NOT EXISTS mailbatch_tracking (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        action_type VARCHAR(50) NOT NULL, -- 'open', 'click'
        subject_version VARCHAR(10) DEFAULT 'A', -- 'A' or 'B'
        campaign_id VARCHAR(100) DEFAULT '',
        ip_address VARCHAR(100) DEFAULT '',
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $pdo->exec($sql);

    // 4. Migrate prospects status and add cv_score / target_job columns
    try {
        // Convert status to VARCHAR(50) to prevent ENUM errors with 'lead_audit'
        $pdo->exec("ALTER TABLE prospects MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");
    } catch (Exception $e) {
        // Ignore if error
    }

    try {
        // Add cv_score if not exists
        $pdo->exec("ALTER TABLE prospects ADD COLUMN cv_score INT DEFAULT NULL AFTER has_recruiter_list");
    } catch (Exception $e) {
        // Ignore if already exists
    }

    try {
        // Add target_job if not exists
        $pdo->exec("ALTER TABLE prospects ADD COLUMN target_job VARCHAR(255) DEFAULT NULL AFTER cv_score");
    } catch (Exception $e) {
        // Ignore if already exists
    }

    echo json_encode([
        'success' => true,
        'message' => 'MailBatch Pro tracking table and prospects migration setup successfully.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to initialize database: ' . $e->getMessage()
    ]);
}
?>
