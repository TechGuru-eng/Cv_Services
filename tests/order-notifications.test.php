<?php
require_once __DIR__ . '/../admin/config/db.php';
require_once __DIR__ . '/../includes/order-notifications.php';
require_once __DIR__ . '/../admin/config/sms_helper.php';
// Temporary table shadows the real outbox. No provider calls or real orders.
$pdo->exec(str_replace('CREATE TABLE IF NOT EXISTS', 'CREATE TEMPORARY TABLE', file_get_contents(__DIR__ . '/../admin/sql/transactional-notifications.sql')));
function check_notification($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
$cv = ['id'=>987654, 'order_ref'=>'TEST-CV', 'name'=>'Alice', 'email'=>'alice@example.com', 'whatsapp'=>'654250688', 'amount'=>10000, 'created_at'=>'2026-10-11 12:00:00'];
$li = ['id'=>987654, 'reference'=>'TEST-LI', 'name'=>'Bob', 'first_name'=>'Jean', 'email'=>'bob@example.com', 'phone'=>'+237690000000', 'total_amount'=>25000, 'access_token'=>'private-test-token', 'created_at'=>'2026-10-11 13:00:00'];
foreach (['cv'=>$cv, 'linkedin'=>$li] as $type=>$order) {
    $pdo->beginTransaction();
    queue_order_event($pdo, $type, $order, 'completed');
    $pdo->rollBack();
    check_notification((int)$pdo->query('SELECT COUNT(*) FROM transactional_notifications')->fetchColumn() === 0, 'Rolled back checkout must not notify');
}
foreach (['cv'=>$cv, 'linkedin'=>$li] as $type=>$order) {
    queue_order_event($pdo, $type, $order, 'completed');
    queue_order_event($pdo, $type, $order, 'completed');
    queue_order_event($pdo, $type, $order, 'approved');
    queue_order_event($pdo, $type, $order, 'approved');
}
check_notification((int)$pdo->query('SELECT COUNT(*) FROM transactional_notifications')->fetchColumn() === 8, 'Events must deduplicate and separate order types/channels');
check_notification((int)$pdo->query("SELECT COUNT(*) FROM transactional_notifications WHERE event='completed' AND channel='email'")->fetchColumn() === 2, 'Admin order emails enabled');
foreach ($pdo->query("SELECT * FROM transactional_notifications WHERE event='completed'") as $row) {
    check_notification($row['recipient'] === ($row['channel'] === 'sms' ? '+237654250688' : (getenv('ORDER_ADMIN_EMAIL') ?: 'moncvpro@hiremeguide.com')), 'Admin recipient');
    check_notification(strpos($row['body'], 'FCFA') !== false && strpos($row['body'], '2026-10-11') !== false && strpos($row['body'], '/admin/') !== false, 'Admin amount/date/link');
}
foreach ($pdo->query("SELECT * FROM transactional_notifications WHERE event='approved'") as $row) {
    check_notification(strpos($row['body'], 'Suivi :') !== false || strpos($row['body'], 'espace de suivi') !== false, 'Customer tracking message');
    check_notification(strpos($row['body'], $row['order_type'] === 'linkedin' ? 'token=private-test-token' : '/track-cv.php?ref=TEST-CV') !== false, 'Correct tracking link');
}
$delivered = [];
send_order_notifications($pdo, function ($item) use (&$delivered) {
    if ($item['channel'] === 'sms') throw new RuntimeException('Simulated outage');
    $delivered[] = $item['id'];
});
check_notification(count($delivered) === 4, 'SMS outage must not block email');
check_notification((int)$pdo->query('SELECT COUNT(*) FROM transactional_notifications WHERE sent_at IS NULL AND attempts=1 AND next_attempt_at IS NOT NULL')->fetchColumn() === 4, 'Failed messages remain queued');
$pdo->exec('UPDATE transactional_notifications SET next_attempt_at = NULL WHERE sent_at IS NULL');
send_order_notifications($pdo, function ($item) use (&$delivered) { $delivered[] = $item['id']; });
send_order_notifications($pdo, function () { throw new RuntimeException('Sent events must not repeat'); });
check_notification(count($delivered) === 8, 'Only pending deliveries retried');
check_notification(SMSHelper::formatPhoneNumber('654250688') === '+237654250688', 'Cameroon phone format');
echo "PASS: completion, approval, links, deduplication, rollback and independent retry delivery.\n";
