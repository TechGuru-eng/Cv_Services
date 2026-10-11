<?php
// Initialize SQLite database
$dbFile = __DIR__ . '/../premium_ebook.sqlite';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create tables if they don't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS visitors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT UNIQUE,
    email TEXT,
    whatsapp TEXT,
    is_converted INTEGER DEFAULT 0,
    last_seen DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS subscribers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT,
    email TEXT,
    whatsapp TEXT,
    city TEXT,
    profession TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Safely add is_contacted column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE visitors ADD COLUMN is_contacted INTEGER DEFAULT 0");
} catch (Exception $e) {
    // Ignore if column already exists
}
?>
