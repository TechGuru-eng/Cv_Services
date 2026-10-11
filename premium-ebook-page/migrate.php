<?php
// migrate.php - Script de déploiement et de vérification
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Vérification et Migration du Déploiement</h1>";
echo "<ul>";

// 1. Initialiser et migrer la base de données SQLite
try {
    // L'inclusion de db.php lance automatiquement les requêtes CREATE TABLE IF NOT EXISTS
    // et les ALTER TABLE nécessaires.
    require __DIR__ . '/api/db.php';
    echo "<li style='color:green;'>✅ <b>Base de données SQLite :</b> Fichier connecté et tables vérifiées/migrées avec succès.</li>";
} catch (Exception $e) {
    echo "<li style='color:red;'>❌ <b>Erreur Base de données :</b> " . htmlspecialchars($e->getMessage()) . "</li>";
}

// 2. Vérifier/Créer le dossier d'upload pour les CVs
$uploads_dir = __DIR__ . '/uploads/cvs';
if (!file_exists($uploads_dir)) {
    if (mkdir($uploads_dir, 0775, true)) {
        echo "<li style='color:green;'>✅ <b>Dossier d'Uploads :</b> Dossier `uploads/cvs` créé avec les permissions requises.</li>";
    } else {
        echo "<li style='color:red;'>❌ <b>Erreur Dossier :</b> Impossible de créer `uploads/cvs`. Le serveur vous bloque (Problème de permissions). Changez le CHMOD du dossier racine.</li>";
    }
} else {
    // Tenter de rendre le dossier inscriptible si ce n'est pas le cas
    chmod($uploads_dir, 0775);
    if(is_writable($uploads_dir)) {
        echo "<li style='color:green;'>✅ <b>Dossier d'Uploads :</b> Le dossier `uploads/cvs` existe et est prêt pour recevoir des images.</li>";
    } else {
        echo "<li style='color:orange;'>⚠️ <b>Dossier d'Uploads :</b> Le dossier existe mais n'a pas les droits d'écriture. Vous ne pourrez pas uploader de CV depuis le panel admin. Appliquez la permission 775 sur le dossier.</li>";
    }
}

echo "</ul>";
echo "<hr>";
echo "<h3>Toutes les migrations sont terminées !</h3>";
echo "<p style='color: red;'>⚠️ <b>TRÈS IMPORTANT :</b> Par mesure de sécurité, supprimez ce fichier <code>migrate.php</code> une fois en ligne.</p>";
?>
