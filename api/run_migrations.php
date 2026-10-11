<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// api/run_migrations.php
header('Content-Type: text/html; charset=UTF-8');

$dbPath = __DIR__ . '/../admin/config/db.php';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../config/db.php';
}
if (!file_exists($dbPath)) {
    die("<h3>Erreur: Fichier de configuration de la base de données introuvable.</h3>");
}
require_once $dbPath;

echo "<h2>Démarrage des migrations de base de données en ligne...</h2>";

try {
    // 1. Modify prospects status column
    $pdo->exec("ALTER TABLE prospects MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");
    echo "<p style='color:green;'>✓ Colonne 'status' de la table 'prospects' convertie en VARCHAR(50).</p>";
} catch (Exception $e) {
    echo "<p style='color:orange;'>⚠️ Information 'status': " . $e->getMessage() . "</p>";
}

try {
    // 2. Add cv_score column
    $pdo->exec("ALTER TABLE prospects ADD COLUMN cv_score INT DEFAULT NULL");
    echo "<p style='color:green;'>✓ Colonne 'cv_score' ajoutée à la table 'prospects'.</p>";
} catch (Exception $e) {
    echo "<p style='color:orange;'>⚠️ Information 'cv_score': " . $e->getMessage() . " (Existe probablement déjà)</p>";
}

try {
    // 3. Add target_job column
    $pdo->exec("ALTER TABLE prospects ADD COLUMN target_job VARCHAR(255) DEFAULT NULL");
    echo "<p style='color:green;'>✓ Colonne 'target_job' ajoutée à la table 'prospects'.</p>";
} catch (Exception $e) {
    echo "<p style='color:orange;'>⚠️ Information 'target_job': " . $e->getMessage() . " (Existe probablement déjà)</p>";
}

try {
    // 4. Create mailbatch_queue
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS mailbatch_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        name VARCHAR(150) DEFAULT '',
        subject VARCHAR(255) NOT NULL,
        body LONGTEXT NOT NULL,
        alt_body TEXT NOT NULL,
        from_name VARCHAR(100) DEFAULT 'HireMe CV Services',
        campaign_id VARCHAR(100) DEFAULT '',
        version VARCHAR(10) DEFAULT 'A',
        status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
        scheduled_at DATETIME NOT NULL,
        sent_at DATETIME NULL,
        error_message TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_status_scheduled (status, scheduled_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<p style='color:green;'>✓ Table 'mailbatch_queue' créée ou validée.</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Erreur 'mailbatch_queue': " . $e->getMessage() . "</p>";
}

try {
    // 5. Create mailbatch_tracking
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS mailbatch_tracking (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        action_type VARCHAR(50) NOT NULL,
        subject_version VARCHAR(10) DEFAULT 'A',
        campaign_id VARCHAR(100) DEFAULT '',
        ip_address VARCHAR(100) DEFAULT '',
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<p style='color:green;'>✓ Table 'mailbatch_tracking' créée ou validée.</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Erreur 'mailbatch_tracking': " . $e->getMessage() . "</p>";
}

echo "<h3>Migrations terminées avec succès !</h3>";
?>
