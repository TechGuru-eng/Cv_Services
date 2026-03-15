<?php
require_once 'config/db.php';

try {
    echo "<h1>Fixing Database for Emojis 🚀</h1>";

    // 1. Alter Database Character Set (Optional but good)
    // $pdo->exec("ALTER DATABASE " . DB_NAME . " CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci");
    
    // 2. Alter Table Character Set
    $pdo->exec("ALTER TABLE affiliate_messages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color:green'>✅ Table 'affiliate_messages' converted to utf8mb4.</p>";

    // 3. Alter Column explicitely (just to be safe)
    $pdo->exec("ALTER TABLE affiliate_messages CHANGE message message TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");
    echo "<p style='color:green'>✅ Column 'message' converted to utf8mb4.</p>";

    echo "<h3>Success! You can now use emojis in your messages.</h3>";
    echo "<a href='affiliates.php'>Return to Dashboard</a>";

} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
