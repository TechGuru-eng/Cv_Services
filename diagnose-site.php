<?php
// Standalone read-only diagnostic. Remove this file after troubleshooting.
ini_set('display_errors', '0');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Content-Type: text/html; charset=UTF-8');
$diagnosticPassword = 'MonCv-Diag-7pQ4-rN8x-2026';
if (!isset($_POST['diagnostic_password']) || !hash_equals($diagnosticPassword, (string)$_POST['diagnostic_password'])) {
    echo '<!doctype html><meta name="viewport" content="width=device-width"><title>Site diagnostic</title><h1>Site diagnostic and repair</h1><form method="post"><p><label>Diagnostic password <input type="password" name="diagnostic_password" required autocomplete="off"></label></p><p><button name="action" value="check">Run checks</button></p><hr><p>Repair adds stats_visits.updated_at, creates missing LinkedIn tables and inserts missing default packages: Essentiel 5,000, Pro 10,000, Executive 25,000 XAF. Existing rows and prices are preserved. Back up your database first.</p><p><label><input type="checkbox" name="backup_confirmed" value="yes"> I have a database backup and want these repairs.</label></p><button name="action" value="repair">Apply database repair</button></form>';
    exit;
}
header('Content-Type: text/plain; charset=UTF-8');
$diagnosticRoot = __DIR__;
$diagnosticStage = 'Starting';
$diagnosticFinished = false;
$diagnosticSecrets = array($diagnosticPassword);
function diagnosticSafe($message) {
    global $diagnosticSecrets, $diagnosticRoot;
    foreach ($diagnosticSecrets as $secret) if (is_string($secret) && strlen($secret) > 0) $message = str_replace($secret, '[redacted]', $message);
    $message = str_replace(array($diagnosticRoot, str_replace('\\', '/', $diagnosticRoot)), '[site-root]', $message);
    $message = preg_replace("/Access denied for user '[^']*'@'[^']*'/i", 'Access denied for user [redacted]', $message);
    $message = preg_replace("/Unknown database '[^']*'/i", 'Unknown database [redacted]', $message);
    $message = preg_replace('/mysql:host=[^\s]+/i', '[redacted DSN]', $message);
    return $message;
}
register_shutdown_function(function () {
    global $diagnosticStage, $diagnosticFinished;
    if ($diagnosticFinished) return;
    $error = error_get_last();
    echo "\nSTOPPED during: " . $diagnosticStage . "\n";
    if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
        $connectionFile = basename($error['file']) === 'db.php' || basename($error['file']) === 'database-connect.php';
        echo ($connectionFile ? 'Fatal error in connection setup (message hidden to protect credentials).' : diagnosticSafe($error['message'])) . "\nFile: " . diagnosticSafe($error['file']) . ':' . $error['line'] . "\n";
    } else echo "A required file terminated execution. Check the connection configuration and hosting PHP error log.\n";
});
echo "MONCV DIAGNOSTIC REPORT\nUTC: " . gmdate('Y-m-d H:i:s') . "\nPHP: " . PHP_VERSION . "\n";
echo version_compare(PHP_VERSION, '8.1', '>=') ? "PASS: PHP meets the security code requirement (8.1+).\n" : "FAIL: This project requires PHP 8.1 or newer.\n";
foreach (array('pdo_mysql', 'mbstring', 'fileinfo', 'json', 'session') as $extension) echo (extension_loaded($extension) ? 'PASS' : 'FAIL') . ': extension ' . $extension . "\n";
$files = array('admin/config/db.php', 'includes/environment.php', 'includes/database-connect.php', 'includes/security-bootstrap.php', 'admin/includes/Security.php', 'includes/upload_helper.php', 'admin/includes/csrf.php', 'admin/includes/activity_helper.php', 'includes/visitor-tracking.php', 'admin/includes/header.php', 'admin/includes/nav.php', 'admin/login.php', 'admin/index.php', 'linkedin/commander.php');
foreach ($files as $file) {
    $diagnosticStage = 'Checking ' . $file;
    $path = $diagnosticRoot . '/' . $file;
    if (!is_readable($path)) { echo "MISSING: $file (database-connect.php is only needed by the updated connection setup).\n"; continue; }
    try { token_get_all(file_get_contents($path), TOKEN_PARSE); echo "PASS syntax: $file\n"; }
    catch (Throwable $error) { echo "FAIL syntax: $file at line " . $error->getLine() . " (message hidden to protect credentials).\n"; }
}
$diagnosticStage = 'Loading the installed admin/config/db.php';
echo "\nLoading your actual connection file (credentials are not printed).\n";
ob_start();
try {
    require $diagnosticRoot . '/admin/config/db.php';
    ob_end_clean();
    foreach (array('host', 'dbname', 'username', 'password') as $variable) if (isset($$variable) && is_string($$variable)) $diagnosticSecrets[] = $$variable;
    foreach (array('DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD') as $key) if (getenv($key) !== false) $diagnosticSecrets[] = getenv($key);
    if (!isset($pdo) || !($pdo instanceof PDO)) throw new RuntimeException('Connection file did not provide a PDO connection.');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->query('SELECT 1')->fetchColumn();
    echo "PASS: database connection and SELECT 1\n";
} catch (Throwable $error) {
    if (ob_get_level()) ob_end_clean();
    // Do not print a connection exception which might contain credentials.
    echo 'FAIL: connection setup (' . get_class($error) . ', code ' . $error->getCode() . ").\nCheck DB credentials, required files and PHP syntax.\n";
    $diagnosticFinished = true; exit;
}
if (($_POST['action'] ?? '') === 'repair') {
    if (($_POST['backup_confirmed'] ?? '') !== 'yes') {
        echo "REPAIR NOT RUN: confirm the backup checkbox on the form.\n";
        $diagnosticFinished = true; exit;
    }
    $diagnosticStage = 'Applying schema repairs';
    if (!defined('HIREME_SCHEMA_REPAIR')) define('HIREME_SCHEMA_REPAIR', true);
    require __DIR__ . '/includes/hosting-schema-repair.php';
    $repairPending = $repairSchema;
    do {
        $repairProgress = false; $repairFailures = array();
        foreach ($repairPending as $table => $sql) {
            try {
                $pdo->exec($sql);
                echo "READY: $table\n";
                unset($repairPending[$table]); $repairProgress = true;
            } catch (Throwable $error) { $repairFailures[$table] = diagnosticSafe($error->getMessage()); }
        }
    } while ($repairProgress && $repairPending);
    foreach ($repairFailures as $table => $failure) echo "REPAIR FAIL: $table: $failure\n";
    try {
        $column = $pdo->query("SHOW COLUMNS FROM stats_visits WHERE Field='updated_at'")->fetch();
        if (!$column) {
            // NULL keeps historical visits from being counted as new activity today.
            $pdo->exec('ALTER TABLE stats_visits ADD COLUMN updated_at DATETIME NULL DEFAULT NULL');
            echo "ADDED: stats_visits.updated_at (historical visit dates preserved)\n";
        } else echo "READY: stats_visits.updated_at already exists\n";
    } catch (Throwable $error) { echo 'REPAIR FAIL: visitor column: ' . diagnosticSafe($error->getMessage()) . "\n"; }
    try {
        $find = $pdo->prepare('SELECT id FROM linkedin_packages WHERE slug=?');
        $add = $pdo->prepare('INSERT INTO linkedin_packages (slug,name,price,tagline,is_popular,deliverables_json,sort_order,is_active) VALUES (?,?,?,?,?,?,?,1)');
        foreach ($repairPackages as $index => $package) {
            $find->execute(array($package['slug']));
            if ($find->fetchColumn()) { echo 'PRESERVED package: ' . $package['slug'] . "\n"; continue; }
            $add->execute(array($package['slug'], $package['name'], $package['price'], $package['tagline'], $package['is_popular'], $package['deliverables_json'], $index));
            echo 'ADDED default package: ' . $package['slug'] . "\n";
        }
    } catch (Throwable $error) { echo 'REPAIR FAIL: packages: ' . diagnosticSafe($error->getMessage()) . "\n"; }
    echo "No previous orders, add-ons or payment settings were restored. Missing historical data requires a backup.\n\n";
}
$checks = array(
    'Gateway settings' => "SELECT setting_value FROM settings WHERE setting_key='ip_bans_enabled' LIMIT 0",
    'Gateway bans' => 'SELECT id, ip_address FROM banned_ips LIMIT 0',
    'Security logging' => 'SELECT ip_address, user_agent, request_uri, reason, created_at FROM security_logs LIMIT 0',
    'Admin accounts' => 'SELECT id, email, password FROM admins LIMIT 0',
    'Admin orders' => 'SELECT id, amount, payment_status, created_at FROM orders LIMIT 0',
    'Admin prospects' => 'SELECT id, status, total_amount, created_at FROM prospects LIMIT 0',
    'Admin sponsorship requests' => 'SELECT status FROM sponsorship_requests LIMIT 0',
    'Admin sponsorship pool' => 'SELECT credits_remaining FROM sponsorship_pool LIMIT 0',
    'Admin school leads' => 'SELECT id FROM school_leads LIMIT 0',
    'Admin club members' => 'SELECT id FROM club_members LIMIT 0',
    'Admin activities' => 'SELECT user_id, user_email, action, details, ip_address, created_at FROM activities LIMIT 0',
    'Visitor tracking' => 'SELECT id, is_bot, created_at, updated_at FROM stats_visits LIMIT 0',
    'LinkedIn packages' => 'SELECT id, slug, name, price, tagline, deliverables_json, is_popular, is_active, sort_order FROM linkedin_packages LIMIT 0',
    'LinkedIn addons' => 'SELECT id, slug, name, description, price, is_active, sort_order FROM linkedin_addons LIMIT 0',
    'LinkedIn settings' => 'SELECT setting_key, setting_value FROM linkedin_settings LIMIT 0'
    , 'CV Builder leads export' => 'SELECT id,name,whatsapp,email,job_title,created_at FROM cv_builder_leads LIMIT 0'
    , 'Before/after gallery' => 'SELECT id,category,before_img,before_tag,before_score,before_quote,after_img,after_tag,after_score,after_badge,after_quote,display_order,is_active FROM before_after_cvs LIMIT 0'
);
foreach ($checks as $label => $sql) {
    $diagnosticStage = $label;
    try { $pdo->query($sql); echo "PASS: $label\n"; }
    catch (Throwable $error) { echo "FAIL: $label: " . diagnosticSafe($error->getMessage()) . "\n"; }
}
$diagnosticFinished = true;
echo "\nEND REPORT. Paste this report into our chat. Delete diagnose-site.php after troubleshooting.\n";
echo (($_POST['action'] ?? '') === 'repair') ? "The requested additive repairs were attempted; review any REPAIR FAIL lines above.\n" : "Checks do not alter database records; the installed connection file may run its own initialization.\n";
echo "Passing checks do not rule out routing or runtime errors elsewhere.\n";
