<?php
// install_mailer.php
// Script to download PHPMailer dependencies on Hostinger

echo "<h1>Installation de PHPMailer...</h1>";

$dir = 'PHPMailer/src';
if (!file_exists($dir)) {
    mkdir($dir, 0755, true);
    echo "Dossier créé: $dir<br>";
} else {
    echo "Dossier existant: $dir<br>";
}

$files = [
    'PHPMailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php',
    'SMTP.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php',
    'Exception.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php'
];

foreach ($files as $file => $url) {
    echo "Téléchargement de $file... ";
    $content = @file_get_contents($url);
    if ($content) {
        file_put_contents("$dir/$file", $content);
        echo "<span style='color:green'>OK</span><br>";
    } else {
        echo "<span style='color:red'>ERREUR</span> - Impossible de télécharger $url<br>";
    }
}

echo "<hr>";
echo "<h3>Installation terminée !</h3>";
echo "1. <a href='index.html'>Retourner au site</a><br>";
echo "2. <strong style='color:red'>IMPORTANT : Supprimez ce fichier (install_mailer.php) via votre gestionnaire de fichiers Hostinger par sécurité.</strong>";
