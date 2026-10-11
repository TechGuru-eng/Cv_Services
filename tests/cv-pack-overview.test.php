<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../admin/config/db.php';
require_once __DIR__ . '/../admin/includes/cv-pack-overview.php';
require_once __DIR__ . '/../admin/includes/admin-page-schema.php';
$mode = $argv[1] ?? 'overview';
$ordersSchema = $pdo->query('SHOW CREATE TABLE orders')->fetch(PDO::FETCH_NUM)[1];
$ordersSchema = preg_replace('/\bAUTO_INCREMENT=\d+\s*/', '', $ordersSchema);
$db = 'moncv_recovery_test_' . bin2hex(random_bytes(5));
$pdo->exec("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
register_shutdown_function(static function () use ($pdo, $db) { $pdo->exec("DROP DATABASE `$db`"); });
$pdo->exec("USE `$db`");
$pdo->exec($ordersSchema);
$pdo->exec(file_get_contents(__DIR__ . '/../admin/sql/cv_packs.sql'));
ensureAdminPageTable($pdo, 'cv_builder_leads');
ensureAdminPageTable($pdo, 'before_after_cvs');
ensureAdminPageTable($pdo, 'cv_builder_leads');
$add = $pdo->prepare('INSERT INTO orders (order_ref,name,email,whatsapp,package_name,amount,payment_status) VALUES (?,?,?,?,?,1000,?)');
foreach ([['A','Alice','alice@example.invalid','verified'], ['B','Bob','bob@example.invalid','verified'], ['C','Bob','bob@example.invalid','verified'], ['D','Pending','pending@example.invalid','pending']] as $r) {
 $add->execute([$r[0],$r[1],$r[2],'+237 699-123-456','Test CV',$r[3]]);
}
$pdo->exec("INSERT INTO cv_packs (order_id,full_name,email,pack_link,status) VALUES (1,'Alice','alice@example.invalid','https://example.invalid/alice','verified'), (1,'Alice Duplicate','alice@example.invalid','https://example.invalid/duplicate','verified'), (2,'Bob','bob@example.invalid','https://example.invalid/bob','pending')");
$assert = static function ($ok, $message) { if (!$ok) throw new RuntimeException($message); };
$result = cvPackOverview($pdo);
$assert($result['metrics'] === ['clients'=>2,'added_clients'=>1,'missing_clients'=>1,'orders'=>3,'added_orders'=>2,'missing_orders'=>1], 'Customer/order metrics or duplicate handling failed');
$assert(count(cvPackOverview($pdo,'bob','missing')['rows']) === 1, 'Missing customer search failed');
$assert(count(cvPackOverview($pdo,'699123456')['rows']) === 3, 'Phone search failed');
$assert(count(cvPackOverview($pdo,"' OR 1=1 --")['rows']) === 0, 'Literal search failed');
$assert(count(cvPackOverview($pdo,'alice','added')['rows']) === 1, 'Added search failed');
if ($mode !== 'overview') {
 session_start(); $_SESSION['admin']=true; session_write_close();
 $_SERVER['REQUEST_METHOD']='GET'; $_SERVER['PHP_SELF']='/admin/' . $mode . '.php'; $_GET=['q'=>'bob','recovery'=>'missing'];
 chdir(__DIR__ . '/../admin');
 ob_start(); require $mode . '.php'; $html=ob_get_clean();
 $assert(strlen($html)>1000, 'Page failed to render');
 if ($mode==='cv_packs') $assert(str_contains($html,'Pas encore ajouté') && str_contains($html,'name="csrf_token"'), 'Recovery UI missing');
}
echo "PASS: recovery metrics, duplicates, repeat purchases, search, missing table setup ($mode)\n";
