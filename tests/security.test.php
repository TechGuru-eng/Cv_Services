<?php
if (PHP_SAPI !== 'cli') exit;
require_once __DIR__ . '/../admin/config/db.php';
require_once __DIR__ . '/../admin/includes/Security.php';
Security::init($pdo);
$checks = 0;
function check_security(bool $ok, string $name): void {
    global $checks;
    if (!$ok) throw new RuntimeException($name);
    $checks++;
}
check_security(Security::detect(['q' => 'UNION SELECT 1'], '/') === 'Injection SQL', 'SQL detection');
check_security(Security::detect(['nested' => ['q' => '<svg onload=alert(1)>']], '/') === 'Script injecté (XSS)', 'Nested XSS detection');
check_security(Security::detect(['q' => '../../etc/passwd'], '/') === 'Traversal de fichier', 'Traversal detection');
check_security(Security::detect([], '/MonCv/.env') !== null, 'Sensitive path detection');
check_security(Security::detect([], '/MonCv/%2egit/config') !== null, 'Encoded path detection');
check_security(Security::detect(['experience' => 'I worked at a credit union and select candidates.'], '/') === null, 'Normal CV text');
check_security(Security::detect(['skills' => 'JavaScript, SQL, information_schema, alert()'], '/') === null, 'Technical CV text');
check_security(Security::detect(['password' => '<script>opaque password'], '/') === null, 'Opaque password');
$_SERVER['REMOTE_ADDR'] = '198.51.100.241';
$_SERVER['REQUEST_URI'] = '/test?password=must-not-be-logged';
$_SERVER['HTTP_USER_AGENT'] = 'security-test';
$pdo->beginTransaction();
try {
    $pdo->exec("INSERT INTO settings (setting_key, setting_value) VALUES ('ip_bans_enabled', '1') ON DUPLICATE KEY UPDATE setting_value = '1'");
    $pdo->prepare('DELETE FROM security_logs WHERE ip_address = ?')->execute([Security::getIP()]);
    $pdo->prepare('DELETE FROM banned_ips WHERE ip_address = ?')->execute([Security::getIP()]);
    for ($i = 0; $i < 2; $i++) Security::recordAttack('Injection SQL');
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM banned_ips WHERE ip_address = ?');
    $stmt->execute([Security::getIP()]);
    check_security((int)$stmt->fetchColumn() === 0, 'No ban before threshold');
    Security::recordAttack('Injection SQL');
    $stmt->execute([Security::getIP()]);
    check_security((int)$stmt->fetchColumn() === 1, 'Automatic ban at threshold');
    check_security(!Security::banIP(Security::getIP(), 'duplicate'), 'Ban deduplication');
    $stmt = $pdo->prepare('SELECT request_uri FROM security_logs WHERE ip_address = ? LIMIT 1');
    $stmt->execute([Security::getIP()]);
    check_security($stmt->fetchColumn() === '/test', 'No query secrets logged');
    $stmt = $pdo->prepare('SELECT id FROM banned_ips WHERE ip_address = ?');
    $stmt->execute([Security::getIP()]);
    check_security(Security::unbanIP((int)$stmt->fetchColumn()), 'Unban');
    for ($i = 0; $i < 8; $i++) Security::recordFailedLogin();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM banned_ips WHERE ip_address = ?');
    $stmt->execute([Security::getIP()]);
    check_security((int)$stmt->fetchColumn() === 1, 'Repeated failed-login ban');
    $pdo->exec("UPDATE settings SET setting_value = '0' WHERE setting_key = 'ip_bans_enabled'");
    check_security(!Security::bansEnabled(), 'Ban setting disabled');
    Security::checkBan();
    check_security(!Security::banIP('198.51.100.242', 'automatic while disabled'), 'Automatic bans disabled');
    $stmt->execute([Security::getIP()]);
    check_security((int)$stmt->fetchColumn() === 1, 'Existing bans preserved while suspended');
    $pdo->exec("UPDATE settings SET setting_value = '1' WHERE setting_key = 'ip_bans_enabled'");
    check_security(Security::bansEnabled(), 'Ban setting re-enabled');
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $protected = false;
    try { Security::banIP('127.0.0.1', 'local', 'Admin'); } catch (InvalidArgumentException $e) { $protected = true; }
    check_security($protected, 'Local lockout prevention');
} finally { $pdo->rollBack(); }
echo "$checks security checks passed; database fixtures rolled back.\n";
