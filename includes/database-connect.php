<?php
require_once __DIR__ . '/environment.php';
function hiremeDatabaseConnect(): PDO {
    $env = static function ($key, $default) { $value = getenv($key); return $value === false ? $default : $value; };
    $host = $env('DB_HOST', 'localhost');
    $port = $env('DB_PORT', '3306');
    $name = $env('DB_NAME', 'hireme_db');
    if (!ctype_digit($port) || (int)$port < 1 || (int)$port > 65535 || preg_match('/[;\x00-\x1f]/', $host . $name)) throw new RuntimeException('Invalid database configuration.');
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $env('DB_USER', 'root'), $env('DB_PASSWORD', ''), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 10]);
    $pdo->exec("SET time_zone = '+01:00'");
    return $pdo;
}
