<?php
// Local integration checks. All fixture writes are rolled back; no SMS/email is sent.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../admin/config/db.php';
require_once __DIR__ . '/../admin/includes/prospect_helper.php';
prepareProspectTracking($pdo);
function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
$mode = $argv[1] ?? 'data';
$pdo->beginTransaction();
if (strpos($mode, 'manual') === 0) {
    $initial = $mode === 'manual-repeat' ? 1 : 0;
    $pdo->prepare("INSERT INTO prospects (name, whatsapp, status, manual_followup_stage) VALUES ('Integration fixture', '237699000991', 'pending', ?)")->execute([$initial]);
    $id = $pdo->lastInsertId();
    $_GET = ['id' => $id, 'type' => $mode === 'manual-skip' ? 'L3' : 'L1', 'expected_stage' => 0, 'url' => 'https://wa.me/237699000991'];
    session_start(); $_SESSION['admin'] = 'integration-fixture'; session_write_close();
    register_shutdown_function(function () use ($pdo, $id, $mode) {
        $stage = (int)$pdo->query('SELECT manual_followup_stage FROM prospects WHERE id = ' . (int)$id)->fetchColumn();
        $pdo->rollBack();
        $expected = $mode === 'manual-skip' ? 0 : 1;
        if ($stage !== $expected) { fwrite(STDERR, "$mode failed\n"); exit(1); }
        echo "$mode passed\n";
    });
    require __DIR__ . '/../admin/api/track_relance.php';
}
try {
    check(prospectMatches(['whatsapp' => '699000991'], ['whatsapp' => '+237 699 000 991']), 'Cameroon prefix match');
    check(!prospectMatches(['name'=>'Same Name','whatsapp'=>'123'], ['name'=>'Same Name','whatsapp'=>'237699000991']), 'Short phones/names must not convert');
    $pdo->prepare("INSERT INTO abandoned_carts (name, whatsapp, email, source_page, cart_details) VALUES ('Integration fixture', '237699000991', 'fixture-flow@example.invalid', 'test', '{}')")->execute();
    importAbandonedProspects($pdo);
    $stmt = $pdo->prepare("SELECT id FROM prospects WHERE email = 'fixture-flow@example.invalid'"); $stmt->execute();
    $id = $stmt->fetchColumn(); check((bool)$id, 'Cart appears in CRM');
    importAbandonedProspects($pdo);
    check((int)$pdo->query("SELECT COUNT(*) FROM prospects WHERE email = 'fixture-flow@example.invalid'")->fetchColumn() === 1, 'Cart import is idempotent');
    check(in_array($id, array_column(prospectsWithoutOrders($pdo), 'id')), 'No-order SMS audience includes cart');
    $pdo->prepare("INSERT INTO orders (order_ref, name, whatsapp, email, package_name, amount, payment_status) VALUES ('TEST-FLOW-ROLLBACK', 'Integration fixture', '+237699000991', 'fixture-flow@example.invalid', 'Test', 100, 'pending')")->execute();
    check(!in_array($id, array_column(prospectsWithoutOrders($pdo), 'id')), 'Existing orders excluded from no-order audience');
    echo "Identity, cart import, duplicate prevention and SMS audience checks passed\n";
} finally { $pdo->rollBack(); }
