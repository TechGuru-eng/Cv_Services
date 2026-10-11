<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$allowed = ['reservation.php', 'cv-care.php', 'cv-models.php', 'cv-builder.php', 'cover-letter-builder.php',
    'blog.php', 'remboursement.php', 'ambassadeurs.php', 'ebook-guide.php', 'linkedin/index.php',
    'linkedin/commander.php', 'linkedin/diagnostic.php', 'linkedin/suivi.php', 'club/login.php', 'club/register.php',
    'admin/index.php', 'admin/live.php', 'admin/prospects.php', 'admin/finance.php', 'admin/sms-panel.php',
    'admin/club_platform.php', 'admin/linkedin/index.php', 'admin/linkedin/commandes.php', 'admin/linkedin/leads.php', 'admin/linkedin/packages.php', 'admin/linkedin/upsells.php', 'admin/linkedin/chariow.php', 'admin/linkedin/parametres.php', 'admin/linkedin/promotions.php', 'admin/linkedin/order_details.php'];
$renderPage = $argv[1] ?? '';
if (!in_array($renderPage, $allowed, true)) throw new InvalidArgumentException('Page outside render-test allowlist');
require_once __DIR__ . '/../admin/config/db.php';
$pdo->beginTransaction();
register_shutdown_function(function () use ($pdo) { if ($pdo->inTransaction()) $pdo->rollBack(); });
$_SERVER = array_merge($_SERVER, ['REQUEST_METHOD' => 'GET', 'SCRIPT_NAME' => '/' . $renderPage,
    'PHP_SELF' => '/' . $renderPage, 'REQUEST_URI' => '/' . $renderPage, 'REMOTE_ADDR' => '127.0.0.1',
    'HTTP_HOST' => 'localhost', 'SERVER_PORT' => 80, 'HTTP_USER_AGENT' => 'MonCV regression bot']);
session_start();
$_SESSION = ['admin' => true, 'admin_logged_in' => true, 'admin_id' => 0, 'admin_email' => 'fixture@example.invalid', 'csrf_token' => str_repeat('a', 64)];
session_write_close();
$_GET = []; $_POST = [];
parse_str($argv[3] ?? '', $_GET);
if (isset($argv[4])) {
    if (!in_array($renderPage, ['admin/linkedin/packages.php', 'admin/linkedin/parametres.php'], true)) throw new InvalidArgumentException('POST render-test page is not allowed');
    parse_str($argv[4], $_POST);
    if (!in_array($_POST['action'] ?? '', ['update_package', 'update_addon', 'save_settings'], true)) throw new InvalidArgumentException('POST render-test action is not allowed');
    $_SERVER['REQUEST_METHOD'] = 'POST';
}
if ($renderPage === 'admin/linkedin/order_details.php') $_GET['id'] = (int)$pdo->query('SELECT id FROM linkedin_orders ORDER BY id DESC LIMIT 1')->fetchColumn();
chdir(dirname(__DIR__ . '/../' . $renderPage));
// Collect diagnostics without printing any rendered customer information.
$warnings = [];
set_error_handler(function ($severity, $message) use (&$warnings) {
    if (!(error_reporting() & $severity)) return false;
    $warnings[] = $message;
    return true;
});
ob_start();
try {
    require __DIR__ . '/../' . $renderPage;
    $html = ob_get_clean();
    restore_error_handler();
    if (!str_contains(strtolower($html), '<html')) throw new RuntimeException('Page did not render HTML');
    if ($warnings) throw new RuntimeException(count($warnings) . ' PHP warning(s): ' . implode(' | ', $warnings));
    if (($argv[2] ?? '') === '--html') echo $html; else echo $renderPage . " renders without PHP warnings.\n";
} catch (Throwable $e) {
    if (ob_get_level()) ob_end_clean();
    restore_error_handler();
    fwrite(STDERR, $renderPage . ': ' . $e->getMessage() . "\n");
    exit(1);
}
