<?php
require 'db.php';

if (!isset($_GET['id']) || !isset($_GET['token']) || !isset($_GET['type'])) {
    die("Paramètres manquants.");
}

$id = (int)$_GET['id'];
$token = $_GET['token'];
$type = $_GET['type']; // 'ebook' or 'cv'

// Simple token generation: md5(id + email + secret)
// To keep it simple without adding a secret to config, we use a fixed string "hireme_secret"
$stmt = $pdo->prepare("SELECT * FROM purchases WHERE id = ?");
$stmt->execute([$id]);
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$purchase) {
    die("Commande introuvable.");
}

$expectedToken = md5($purchase['id'] . $purchase['email'] . 'hireme_secret');

if ($token !== $expectedToken) {
    die("Lien invalide ou expiré.");
}

if ($purchase['status'] !== 'completed' && $purchase['status'] !== 'approved') {
    die("Le paiement de cette commande n'a pas encore été validé.");
}

$fileToServe = '';
$filename = '';

if ($type === 'ebook1') {
    $fileToServe = __DIR__ . '/../assets/Guide_Ultime.pdf';
    $filename = "Le_Guide_Ultime_Cabinets_RH.pdf";
} elseif ($type === 'ebook2') {
    $fileToServe = __DIR__ . '/../assets/Bonus_1.pdf';
    $filename = "Bonus_1.pdf";
} elseif ($type === 'ebook3') {
    $fileToServe = __DIR__ . '/../assets/Bonus_2.pdf';
    $filename = "Bonus_2.pdf";
} elseif ($type === 'ebook4') {
    $fileToServe = __DIR__ . '/../assets/Bonus_3.pdf';
    $filename = "Bonus_3.pdf";
} elseif ($type === 'cv' && $purchase['upsell_cv']) {
    $fileToServe = __DIR__ . '/../assets/Modele_CV_Pro.zip';
    $filename = "Modele_CV_Pro.zip";
} else {
    die("Fichier non autorisé.");
}

if (file_exists($fileToServe)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($fileToServe));
    readfile($fileToServe);
    exit;
} else {
    die("Le fichier n'existe plus sur le serveur.");
}
?>
