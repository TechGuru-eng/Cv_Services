<?php
require_once __DIR__ . '/config/db.php';

$sqlFile = __DIR__ . '/sql/add_popup_type.sql';
if (!file_exists($sqlFile)) {
    die("SQL file not found.");
}

$sql = file_get_contents($sqlFile);
# Split commands by semicolon to execute individually if needed, 
# but pdo->exec might handle it. Let's try individual for safety.
$commands = array_filter(array_map('trim', explode(';', $sql)));

try {
    foreach ($commands as $cmd) {
        if (!empty($cmd)) {
            $pdo->exec($cmd);
            echo "Executed: " . substr($cmd, 0, 50) . "...<br>";
        }
    }
    
    // Seed default spin wheel data for the existing active popup
    $pdo->exec("UPDATE exit_popups SET popup_type = 'spin_wheel', slices = '[
        {\"text\": \"10% OFF\", \"value\": \"10\", \"probability\": 10},
        {\"text\": \"PERDU\", \"value\": \"0\", \"probability\": 30},
        {\"text\": \"20% OFF\", \"value\": \"20\", \"probability\": 10},
        {\"text\": \"50% OFF\", \"value\": \"50\", \"probability\": 50},
        {\"text\": \"PERDU\", \"value\": \"0\", \"probability\": 0},
        {\"text\": \"JACKPOT\", \"value\": \"60\", \"probability\": 0}
    ]' WHERE is_active = 1");
    
    echo "<h1>Migration Complete</h1>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "<h1>Columns already exist (Skipped)</h1>";
        // Attempt to update data anyway
         $pdo->exec("UPDATE exit_popups SET popup_type = 'spin_wheel', slices = '[
            {\"text\": \"10% OFF\", \"value\": \"10\", \"probability\": 10},
            {\"text\": \"PERDU\", \"value\": \"0\", \"probability\": 30},
            {\"text\": \"20% OFF\", \"value\": \"20\", \"probability\": 10},
            {\"text\": \"50% OFF\", \"value\": \"50\", \"probability\": 50},
            {\"text\": \"PERDU\", \"value\": \"0\", \"probability\": 0},
            {\"text\": \"JACKPOT\", \"value\": \"60\", \"probability\": 0}
        ]' WHERE is_active = 1");
        echo "Updated existing popup to spin_wheel.";
    } else {
        echo "<h1>Error</h1>";
        echo "<p>" . $e->getMessage() . "</p>";
    }
}
