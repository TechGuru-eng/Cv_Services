<?php
// Hosting terminal: php tools/repair-database.php [--apply]
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/database-connect.php';
if (!extension_loaded('pdo_mysql')) { fwrite(STDERR, "Enable PHP pdo_mysql in the hosting panel.\n"); exit(1); }
try { $pdo = hiremeDatabaseConnect(); }
catch (Throwable $error) {
    $code = $error instanceof PDOException ? (int)($error->errorInfo[1] ?? 0) : 0;
    $advice = [1045 => 'Set DB_USER and DB_PASSWORD to the hosting database credentials.', 1049 => 'Check DB_NAME. Restore a missing database from backup.', 1044 => 'Assign the database user to the database in the hosting panel.', 2002 => 'Check DB_HOST/DB_PORT and whether MySQL is running.'];
    fwrite(STDERR, 'Connection failed. ' . ($advice[$code] ?? 'Check database credentials, server availability and hosting error logs.') . "\n"); exit(1);
}
$apply = in_array('--apply', $argv, true);
echo 'Connection OK. ' . ($apply ? 'Applying additive repairs.' : 'Preview only. Run with --apply to repair.') . "\n";
function statements(string $sql): array {
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
    $result = []; $part = ''; $quote = null; $length = strlen($sql);
    for ($i = 0; $i < $length; $i++) {
        $c = $sql[$i]; $next = $sql[$i + 1] ?? '';
        if ($quote !== null) {
            $part .= $c;
            if ($c === '\\' && $quote !== '`' && $i + 1 < $length) { $part .= $sql[++$i]; continue; }
            if ($c === $quote) { if ($next === $quote) $part .= $sql[++$i]; else $quote = null; }
            continue;
        }
        if ($c === "'" || $c === '"' || $c === '`') { $quote = $c; $part .= $c; continue; }
        if ($c === '#' || ($c === '-' && $next === '-' && ctype_space($sql[$i + 2] ?? ' '))) {
            while ($i < $length && $sql[$i] !== "\n") $i++;
            $part .= "\n"; continue;
        }
        if ($c === '/' && $next === '*') {
            $end = strpos($sql, '*/', $i + 2);
            if ($end === false) throw new RuntimeException('Unterminated SQL comment.');
            $i = $end + 1; $part .= ' '; continue;
        }
        if ($c === ';') { if (trim($part) !== '') $result[] = trim($part); $part = ''; }
        else $part .= $c;
    }
    if ($quote !== null) throw new RuntimeException('Unterminated SQL string.');
    if (trim($part) !== '') $result[] = trim($part);
    return $result;
}
function existsInDatabase(PDO $pdo, string $table, ?string $column = null): bool {
    $sql = $column === null ? 'SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?' : 'SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?';
    $query = $pdo->prepare($sql);
    $query->execute($column === null ? [$table] : [$table, $column]);
    return (bool)$query->fetchColumn();
}
$files = glob(__DIR__ . '/../admin/sql/*.sql');
if (!$files) { fwrite(STDERR, "No SQL files found. Upload admin/sql together with this script.\n"); exit(1); }
$base = __DIR__ . '/../admin/sql/full_schema_sync.sql';
if (is_file($base)) $files = array_merge([$base], array_values(array_diff($files, [$base])));
$pending = []; $seen = []; $skipped = [];
foreach ($files as $file) {
    foreach (statements(file_get_contents($file)) as $statement) {
        if (preg_match('/^CREATE TABLE IF NOT EXISTS\s+`?([a-zA-Z0-9_]+)`?\s*\(/i', $statement, $m)) {
            $key = 'table:' . $m[1];
            if (existsInDatabase($pdo, $m[1])) continue;
        } elseif (preg_match('/^ALTER TABLE\s+`?([a-zA-Z0-9_]+)`?\s+ADD COLUMN\s+(?:IF NOT EXISTS\s+)?`?([a-zA-Z0-9_]+)`?\s+(.+)$/is', $statement, $m) && !preg_match('/,\s*(?:ADD|DROP|MODIFY|CHANGE)\b/i', $m[3])) {
            $key = 'column:' . $m[1] . '.' . $m[2];
            if (existsInDatabase($pdo, $m[1], $m[2])) continue;
            $definition = preg_replace('/\s+(?:AFTER\s+`?[a-zA-Z0-9_]+`?|FIRST)\s*$/i', '', $m[3]);
            $statement = "ALTER TABLE `{$m[1]}` ADD COLUMN `{$m[2]}` $definition";
        } else { $skipped[basename($file)] = true; continue; }
        if (isset($seen[$key])) continue;
        $seen[$key] = true; $pending[$key] = $statement;
    }
}
do {
    $progress = false; $failures = [];
    foreach ($pending as $key => $statement) {
        if (!$apply) { echo "Would add $key\n"; unset($pending[$key]); continue; }
        try { $pdo->exec($statement); echo "Added $key\n"; unset($pending[$key]); $progress = true; }
        catch (PDOException $error) { $failures[$key] = (string)($error->errorInfo[1] ?? $error->getCode()); }
    }
} while ($apply && $progress && $pending);
foreach ($pending as $key => $statement) echo "Failed $key (database error {$failures[$key]}). Review database privileges and schema dependencies.\n";
foreach (array_keys($skipped) as $file) echo "Manual review needed for unsupported statements in $file\n";
echo "Existing data was not deleted or overwritten. Existing column types, indexes, PHP migrations and undeclared schema differences require separate review.\n";
exit($pending ? 1 : 0);
