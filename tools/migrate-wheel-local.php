<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../admin/config/db.php';
$pdo->exec(file_get_contents(__DIR__ . '/../admin/sql/wheel-plays.sql'));
echo 'Wheel play table ready.';
