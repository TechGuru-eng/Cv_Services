<?php
require_once __DIR__ . '/config/db.php';

$sqlFile = __DIR__ . '/sql/exit_popups.sql';
$sql = file_get_contents($sqlFile);

try {
    $pdo->exec($sql);
    // Seed one default popup if empty
    $chk = $pdo->query("SELECT COUNT(*) FROM exit_popups")->fetchColumn();
    if ($chk == 0) {
        $stmt = $pdo->prepare("INSERT INTO exit_popups (title, message, discount_code, discount_percent, cta_text, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([
            "ATTENDEZ ! NE PARTEZ PAS VIDE.",
            "C'est votre jour de chance ! Bénéficiez d'une réduction flash exceptionnelle.",
            "MonTour",
            50,
            "BOOSTER MON CV À -50% ! 🚀"
        ]);
        echo "seeded default popup. ";
    }
    echo "<h1>Exit Popups Table Ready</h1>";
} catch (PDOException $e) {
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
