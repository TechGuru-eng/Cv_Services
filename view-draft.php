<?php
require_once 'admin/config/db.php';

$id = (int)($_GET["id"] ?? 0);
$ref = (string)($_GET["ref"] ?? "");
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order || $ref === "" || !hash_equals((string)$order["order_ref"], $ref) || empty($order["draft_cv_path"])) {
    die("Brouillon introuvable ou lien expiré.");
}

$balanceDue = $order['draft_balance_due'] ?? $order['amount'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre Brouillon de CV - HireMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Disable text selection and right click visually */
        body {
            user-select: none;
            -webkit-user-select: none;
        }
        .watermark-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300"><text x="10" y="150" transform="rotate(-45 150 150)" fill="rgba(255,0,0,0.15)" font-size="24" font-family="Arial" font-weight="bold">CV NON PAYÉ</text></svg>');
            pointer-events: none;
            z-index: 10;
        }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="bg-gray-100 flex flex-col min-h-screen" oncontextmenu="return false;">

    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="track-cv.php?ref=<?= urlencode($order['order_ref']) ?>" class="text-xs font-bold text-gray-400 hover:text-gray-600 transition flex items-center gap-1">
                    ← Retour
                </a>
                <div class="font-black text-xl text-blue-600">HireMe<span class="text-gray-800">CV</span></div>
            </div>
            <div class="text-sm font-bold text-gray-500">Aperçu Sécurisé</div>
        </div>
    </header>

    <main class="flex-grow max-w-4xl mx-auto w-full p-4 flex flex-col items-center">
        
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 w-full rounded shadow-sm">
            <h2 class="font-bold text-blue-800 text-lg">Bonjour <?= htmlspecialchars(explode(' ', trim($order['name']))[0]) ?> !</h2>
            <p class="text-blue-700 text-sm mt-1">Voici l'aperçu de votre nouveau CV. Veuillez l'examiner attentivement. S'il vous convient, validez le paiement pour débloquer la version finale PDF haute qualité.</p>
        </div>

        <!-- Image Container -->
        <div class="relative w-full bg-white p-2 sm:p-6 shadow-xl rounded-xl border border-gray-200" style="min-height: 50vh;">
            <div class="watermark-overlay"></div>
            <!-- The image already has PHP GD watermark, but we add CSS overlay just for extra measure -->
            <img src="<?= htmlspecialchars($order['draft_cv_path']) ?>" alt="Brouillon CV" class="w-full h-auto pointer-events-none rounded border border-gray-100" draggable="false">
        </div>

        <!-- Action Bar -->
        <div class="w-full bg-white shadow-lg border border-gray-200 rounded-xl p-6 mt-8 text-center sticky bottom-4 z-40">
            <h3 class="text-gray-600 mb-2">Montant restant à payer :</h3>
            <div class="text-3xl font-black text-gray-800 mb-4"><?= number_format($balanceDue, 0, ',', ' ') ?> FCFA</div>
            
            <a href="draft-payment.php?id=<?= $id ?>&ref=<?= urlencode($ref) ?>" class="inline-block w-full sm:w-auto bg-green-500 text-white font-bold py-4 px-10 rounded-full shadow-lg hover:bg-green-600 transition transform hover:scale-105 text-lg">
                ✅ Valider & Payer Maintenant
            </a>
            <p class="text-xs text-gray-400 mt-4">Paiement sécurisé via MTN MoMo et Orange Money</p>
        </div>
        
    </main>

    <script>
        // Disable keyboard shortcuts for saving
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && (e.key === 's' || e.key === 'p' || e.key === 'u')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
