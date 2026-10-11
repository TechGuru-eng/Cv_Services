<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../admin/config/db.php';
require_once __DIR__ . '/../admin/includes/prospect_helper.php';
prepareProspectTracking($pdo);
$pdo->beginTransaction();
register_shutdown_function(function () use ($pdo) { if ($pdo->inTransaction()) $pdo->rollBack(); });
$mode = $argv[1] ?? 'live';
session_start(); $_SESSION['admin'] = 'integration-fixture'; session_write_close();
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PHP_SELF'] = '/admin/' . $mode . '.php';
$_GET['audience'] = 'prospects';
chdir(__DIR__ . '/../admin');
ob_start(); require $mode . '.php'; $html = ob_get_clean();
if ($mode === 'live') { echo $html; }
else {
    if ($mode === 'sms-panel' && (!str_contains($html, 'Sans commande') || !str_contains($html, 'name="prospect_id"'))) throw new RuntimeException('SMS audience rendering failed');
    if ($mode === 'prospects' && !str_contains($html, 'paniers + audits')) throw new RuntimeException('Prospect filters missing');
    echo "$mode rendering passed\n";
}
