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

$pdo->exec("CREATE TABLE IF NOT EXISTS purchases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT,
    email TEXT,
    whatsapp TEXT,
    city TEXT,
    profession TEXT,
    price_paid INTEGER,
    coupon_used TEXT,
    transaction_id TEXT,
    proof_file_path TEXT,
    status TEXT DEFAULT 'pending',
    delivery_status TEXT DEFAULT 'pending',
    upsell_cv INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS coupons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE,
    discount_amount INTEGER,
    max_uses INTEGER,
    current_uses INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    expires_at DATETIME,
    is_default INTEGER DEFAULT 0
)");

// Safely add is_contacted column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE visitors ADD COLUMN is_contacted INTEGER DEFAULT 0");
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE purchases ADD COLUMN transaction_id TEXT");
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE purchases ADD COLUMN proof_file_path TEXT");
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE purchases ADD COLUMN status TEXT DEFAULT 'pending'");
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE purchases ADD COLUMN upsell_cv INTEGER DEFAULT 0");
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE purchases ADD COLUMN delivery_status TEXT DEFAULT 'pending'");
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE coupons ADD COLUMN expires_at DATETIME");
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE coupons ADD COLUMN expires_at DATETIME");
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE coupons ADD COLUMN is_default INTEGER DEFAULT 0");
} catch (Exception $e) {}

// Add Settings Table for Sales Features
$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT
)");

// Insert default settings if they don't exist
$defaultSettings = [
    'scarcity_end_date' => date('Y-m-d\TH:i', strtotime('+3 days')),
    'toast_enabled' => '1',
    'toast_frequency_seconds' => '12',
    'magic_link_coupon' => ''
];

$stmt = $pdo->prepare("INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
foreach ($defaultSettings as $key => $value) {
    $stmt->execute([$key, $value]);
}
?>
