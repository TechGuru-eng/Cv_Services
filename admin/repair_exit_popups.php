<?php
require_once __DIR__ . '/config/db.php';

echo "<h1>Repairing Database...</h1>";

try {
    // 1. Drop the incorrect table
    $pdo->exec("DROP TABLE IF EXISTS exit_popups");
    echo "Dropped old 'exit_popups' table.<br>";

    // 2. Re-create with FULL schema
    $sql = "CREATE TABLE `exit_popups` (
      `id` int NOT NULL AUTO_INCREMENT,
      `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
      `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
      `discount_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
      `discount_percent` int NOT NULL,
      `cta_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'PROFITER DE L''OFFRE',
      `is_active` tinyint(1) DEFAULT '0',
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sql);
    echo "Created new 'exit_popups' table.<br>";

    // 3. Seed default data
    $stmt = $pdo->prepare("INSERT INTO exit_popups (title, message, discount_code, discount_percent, cta_text, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->execute([
        "ATTENDEZ ! NE PARTEZ PAS VIDE.",
        "C'est votre jour de chance ! Bénéficiez d'une réduction flash exceptionnelle.",
        "MonTour",
        50,
        "BOOSTER MON CV À -50% ! 🚀"
    ]);
    echo "Seeded default popup data.<br>";
    
    echo "<h2 style='color:green'>SUCCESS! The database is fixed.</h2>";
    echo "<p>You can now go back to <a href='exit_popups.php'>Popups Sortie</a>.</p>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>Error: " . $e->getMessage() . "</h2>";
}
