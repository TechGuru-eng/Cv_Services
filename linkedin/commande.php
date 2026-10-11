<?php
// linkedin/commande.php
// Individual Customer Order Hub, Post-Purchase Upsells & Digital Resource Delivery Page

header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/../admin/config/db.php';

$reference = strtoupper(trim($_GET['ref'] ?? ''));
$token = trim($_GET['token'] ?? '');

if (empty($reference)) {
    die("<h1>Erreur</h1><p>Référence de commande manquante.</p>");
}

// Fetch Order
$stmt = $pdo->prepare("SELECT o.*, p.name as package_name, p.deliverables_json 
                       FROM linkedin_orders o 
                       LEFT JOIN linkedin_packages p ON o.package_id = p.id 
                       WHERE o.reference = ?");
$stmt->execute([$reference]);
$order = $stmt->fetch();

if (!$order) {
    die("<h1>Commande introuvable</h1><p>Aucune commande associée à cette référence.</p>");
}

// Access Security Token Check
header('Cache-Control: private, no-store');
header('Referrer-Policy: no-referrer');
if (empty($order['access_token']) || $token === '' || !hash_equals($order['access_token'], $token)) {
    header('Location: ' . preg_replace('~/(?:linkedin(?:/.*)?|suivi-linkedin)/?$~', '/linkedin/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) . 'suivi.php?ref=' . urlencode($reference));
    exit;
}

// Fetch Order Line Items (Relational items with snapshots)
$stmtItems = $pdo->prepare("SELECT * FROM linkedin_order_items WHERE order_id = ?");
$stmtItems->execute([$order['id']]);
$orderItems = $stmtItems->fetchAll();

$purchasedProductIds = [];
$purchasedAddons = [];
$purchasedDigitalProducts = [];

foreach ($orderItems as $item) {
    if ($item['item_type'] === 'product') {
        $purchasedProductIds[] = (int)$item['item_id'];
        
        // Fetch digital product details if digital
        $stmtDig = $pdo->prepare("SELECT id, name, description, is_digital, digital_file_path FROM linkedin_products WHERE id = ?");
        $stmtDig->execute([$item['item_id']]);
        $prodInfo = $stmtDig->fetch();
        if ($prodInfo && $prodInfo['is_digital']) {
            $purchasedDigitalProducts[] = $prodInfo;
        }
    } elseif ($item['item_type'] === 'addon') {
        $purchasedAddons[] = $item;
    }
}

// Fetch Uploads (CV)
$stmtUp = $pdo->prepare("SELECT id, file_name, file_size, category, created_at FROM linkedin_uploads WHERE order_id = ?");
$stmtUp->execute([$order['id']]);
$uploads = $stmtUp->fetchAll();

// Fetch Admin Deliverables (Optimization report, custom banner, etc.)
$stmtDeliv = $pdo->prepare("SELECT id, title, description, file_path, file_size, file_type, created_at FROM linkedin_deliverables WHERE order_id = ? ORDER BY id DESC");
$stmtDeliv->execute([$order['id']]);
$deliverables = $stmtDeliv->fetchAll();
foreach ($deliverables as &$deliverable) {
    $deliverable['file_name'] = basename($deliverable['file_path']);
}
unset($deliverable);

// Fetch History Log
$stmtHist = $pdo->prepare("SELECT action, note, created_by, created_at FROM linkedin_order_history WHERE order_id = ? ORDER BY id ASC");
$stmtHist->execute([$order['id']]);
$history = $stmtHist->fetchAll();

$waText = urlencode("Bonjour HireMe, je souhaite échanger concernant ma commande d'optimisation LinkedIn " . $order['reference']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php $linkedinBase = preg_replace('~/(?:linkedin(?:/.*)?|suivi-linkedin)/?$~', '/linkedin/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)); ?>
    <base href="<?= htmlspecialchars($linkedinBase, ENT_QUOTES, 'UTF-8') ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { yellow: '#FACC15', dark: '#0F172A' }
                    },
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] }
                }
            }
        }
    </script>
    <script src="../assets/js/site-language.js?v=6" defer></script>
    <link rel="stylesheet" href="../assets/css/linkedin-hub.css?v=3">
<title>Votre espace commande LinkedIn | HireMe</title>
    <meta name="description" content="Accédez à votre espace privé HireMe pour consulter votre commande LinkedIn et récupérer les livrables de votre prestation.">
    <meta name="robots" content="noindex, follow">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="HireMe">
    <meta property="og:title" content="Votre espace commande LinkedIn | HireMe">
    <meta property="og:description" content="Accédez à votre espace privé HireMe pour consulter votre commande LinkedIn et récupérer les livrables de votre prestation.">
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/images/linkedin/linkedin-workspace.jpg">
    <meta property="og:image:alt" content="Espace de travail professionnel — HireMe LinkedIn">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Votre espace commande LinkedIn | HireMe">
    <meta name="twitter:description" content="Accédez à votre espace privé HireMe pour consulter votre commande LinkedIn et récupérer les livrables de votre prestation.">
    <meta name="twitter:image" content="https://moncvpro.hiremeguide.com/assets/images/linkedin/linkedin-workspace.jpg">
    <meta name="twitter:image:alt" content="Espace de travail professionnel — HireMe LinkedIn">

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="../assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="hm-hub font-sans antialiased min-h-screen">

    <div class="max-w-4xl mx-auto space-y-8">
        
        <!-- Header -->
        <div class="hm-hub-header flex flex-col sm:flex-row items-start sm:items-center justify-between border-b border-slate-800 pb-6 gap-4">
            <a href="index.php" class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-brand-yellow font-black text-slate-950 flex items-center justify-center text-lg shadow-lg">H</div>
                <span class="hm-hub-brand" translate="no">Hire<span class="text-brand-yellow">Me</span><small>MonCv Pro · LinkedIn</small></span>
            </a>
            <div class="flex items-center gap-3">
                <span class="text-xs bg-slate-900 border border-slate-800 text-slate-300 px-3 py-1.5 rounded-full font-mono">
                    Réf: <strong class="text-brand-yellow"><?= htmlspecialchars($order['reference']) ?></strong>
                </span>
                <a href="https://wa.me/237654250688?text=<?= $waText ?>" target="_blank" class="text-xs bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-3.5 py-1.5 rounded-full flex items-center gap-1.5">
                    <i class="fa-brands fa-whatsapp"></i> Support
                </a>
            </div>
        </div>

        <section class="hm-order-hero">
            <div><span class="hm-eyebrow">VOTRE ESPACE MONCV PRO</span><h1>Votre prochaine étape<br><em>professionnelle.</em></h1><p>Votre commande, vos documents et votre suivi. Tout est ici.</p><span class="hm-agency-location">Agence en ligne · Sangmélima, Cameroun · Filiale de HireMe</span></div>
            <div class="hm-hero-total"><span>VOTRE COMMANDE</span><strong><?= number_format($order['total_amount'], 0, ',', ' ') ?> <small>XAF</small></strong><p><?= htmlspecialchars($order['package_name']) ?></p><a href="../api/linkedin/receipt.php?ref=<?= urlencode($reference) ?>&amp;token=<?= urlencode($token) ?>"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i> Télécharger le reçu de commande</a></div>
        </section>
        <ol class="hm-progress" aria-label="Progression de la commande">
            <li class="is-done"><span>01</span><div><strong>Commande reçue</strong><small>Votre dossier est enregistré</small></div></li>
            <li class="<?= $order['payment_status'] === 'CONFIRMED' ? 'is-done' : 'is-current' ?>"><span>02</span><div><strong>Paiement</strong><small><?= $order['payment_status'] === 'CONFIRMED' ? 'Paiement confirmé' : 'Confirmation en attente' ?></small></div></li>
            <li class="<?= $order['payment_status'] === 'CONFIRMED' ? 'is-current' : '' ?>"><span>03</span><div><strong>Vos livrables</strong><small><?= count($deliverables) ? 'Documents disponibles ci-dessous' : 'Disponibles une fois finalisés' ?></small></div></li>
        </ol>

        <!-- Order Summary Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 space-y-6 shadow-2xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <span class="text-xs uppercase font-extrabold text-brand-yellow tracking-wider block mb-1">Informations Client</span>
                    <h2 class="text-xl font-black text-white"><?= htmlspecialchars($order['first_name'] . ' ' . $order['name']) ?></h2>
                    <p class="text-xs text-slate-300 mt-1"><i class="fa-solid fa-envelope text-slate-500 mr-1.5"></i> <?= htmlspecialchars($order['email']) ?></p>
                    <p class="text-xs text-slate-300 mt-0.5"><i class="fa-solid fa-phone text-slate-500 mr-1.5"></i> <?= htmlspecialchars($order['phone']) ?></p>
                    <p class="text-xs text-slate-300 mt-0.5"><i class="fa-brands fa-linkedin text-blue-400 mr-1.5"></i> <a href="<?= htmlspecialchars($order['linkedin_url']) ?>" target="_blank" class="hover:underline text-blue-400 font-medium">Voir le profil transmis</a></p>
                </div>

                <div class="bg-slate-950 p-5 rounded-xl border border-slate-800 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-400"><span>Formule choisie :</span><strong class="text-white"><?= htmlspecialchars($order['package_name']) ?></strong></div>
                    <?php if (!empty($orderItems)): ?>
                    <div class="flex justify-between text-slate-400"><span>Éléments commandés :</span><strong class="text-white"><?= count($orderItems) ?> élément(s)</strong></div>
                    <?php endif; ?>
                    <div class="flex justify-between text-slate-400 pt-1 border-t border-slate-900"><span>Montant total :</span><strong class="text-brand-yellow font-bold text-sm"><?= number_format($order['total_amount'], 0, ',', ' ') ?> FCFA</strong></div>
                    <div class="flex justify-between text-slate-400"><span>Statut paiement :</span><strong class="text-yellow-400"><?= $order['payment_status'] === 'CONFIRMED' ? 'Paiement confirmé' : 'Paiement non confirmé' ?></strong></div>
                    <div class="flex justify-between text-slate-400"><span>Statut commande :</span><strong class="text-emerald-400"><?= htmlspecialchars(['COMMANDE_RECUE' => 'Commande reçue', 'PAIEMENT_EN_VERIFICATION' => 'Paiement en vérification', 'PAIEMENT_CONFIRME' => 'Paiement confirmé', 'EN_COURS' => 'En cours', 'TERMINE' => 'Terminé'][$order['order_status']] ?? 'Suivi en cours') ?></strong></div>
                </div>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
            <p class="text-sm text-amber-300">Le travail ne commencera qu’après confirmation de votre paiement par notre équipe. Le reçu de commande ne constitue pas une confirmation de paiement.</p>
            <a href="../api/linkedin/receipt.php?ref=<?= urlencode($reference) ?>&amp;token=<?= urlencode($token) ?>" class="inline-flex items-center gap-2 bg-brand-yellow text-slate-950 font-bold px-4 py-3 rounded-lg"><i class="fa-solid fa-download" aria-hidden="true"></i> Télécharger le reçu de commande</a>
        </div>

        <!-- DIGITAL PRODUCTS DELIVERY ("Mes ressources digitalisées") -->
        <?php if (!empty($purchasedDigitalProducts)): ?>
        <div class="bg-slate-900 border border-brand-yellow/40 rounded-2xl p-6 sm:p-8 space-y-6 shadow-2xl">
            <div class="flex justify-between items-center border-b border-slate-800 pb-4">
                <div>
                    <h3 class="text-lg font-black text-white flex items-center gap-2">
                        <i class="fa-solid fa-box-open text-brand-yellow"></i> Mes Ressources Digitalisées
                    </h3>
                    <p class="text-xs text-slate-400">Accédez instantanément à vos ressources digitales incluses dans votre commande.</p>
                </div>
                <span class="text-xs bg-emerald-500/20 text-emerald-400 font-bold px-3 py-1 rounded-full border border-emerald-500/30">
                    Accès Inclus
                </span>
            </div>

            <?php if ($order['payment_status'] !== 'CONFIRMED'): ?>
            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs font-semibold flex items-center gap-3">
                <i class="fa-solid fa-clock text-lg"></i>
                <span>Vos ressources digitales seront débloquées dès la confirmation de votre paiement par notre équipe.</span>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php foreach ($purchasedDigitalProducts as $dig): ?>
                <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <strong class="text-sm font-bold text-white block"><?= htmlspecialchars($dig['name']) ?></strong>
                        <p class="text-xs text-slate-400"><?= htmlspecialchars($dig['description']) ?></p>
                    </div>
                    <a href="../api/linkedin/download_file.php?type=product&id=<?= $dig['id'] ?>&token=<?= urlencode($token) ?>" class="bg-brand-yellow hover:bg-yellow-400 text-slate-950 font-extrabold text-xs px-4 py-2.5 rounded-lg shadow shrink-0 flex items-center gap-1.5">
                        <i class="fa-solid fa-download"></i> Télécharger
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- DELIVERABLES SECTION (Admin Uploaded Final Optimization Output) -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 space-y-6 shadow-2xl">
            <div class="flex justify-between items-center border-b border-slate-800 pb-4">
                <div>
                    <h3 class="text-lg font-black text-white flex items-center gap-2">
                        <i class="fa-solid fa-folder-open text-brand-yellow"></i> Vos Livrables LinkedIn
                    </h3>
                    <p class="text-xs text-slate-400">Retrouvez ici votre rapport d'optimisation et vos visuels prêts à copier-coller.</p>
                </div>
                <span class="text-xs bg-brand-yellow/10 text-brand-yellow font-bold px-3 py-1 rounded-full border border-brand-yellow/20">
                    <?= count($deliverables) ?> fichier(s)
                </span>
            </div>

            <?php if (empty($deliverables)): ?>
            <div class="text-center py-10 space-y-3 bg-slate-950 rounded-xl border border-slate-800/80 p-6">
                <i class="fa-solid fa-spinner fa-spin text-3xl text-brand-yellow"></i>
                <p class="text-sm font-bold text-white"><?= $order['payment_status'] === 'CONFIRMED' ? 'Vos livrables ne sont pas encore disponibles' : 'En attente de confirmation du paiement' ?></p>
                <p class="text-xs text-slate-400 max-w-md mx-auto">Le travail ne commence qu’après confirmation du paiement. Vos livrables seront disponibles ici une fois l’optimisation finalisée.</p>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php foreach ($deliverables as $deliv): ?>
                <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 hover:border-brand-yellow/50 transition-colors flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <strong class="text-sm font-bold text-white block"><?= htmlspecialchars($deliv['title']) ?></strong>
                        <p class="text-xs text-slate-400"><?= htmlspecialchars($deliv['description'] ?: 'Fichier livrable') ?></p>
                        <span class="text-[10px] text-slate-500 block"><?= htmlspecialchars($deliv['file_name']) ?> &bull; <?= number_format($deliv['file_size']/1024, 1) ?> KB</span>
                    </div>
                    <a href="../api/linkedin/download_file.php?type=deliverable&id=<?= $deliv['id'] ?>&token=<?= urlencode($token) ?>" class="bg-brand-yellow hover:bg-yellow-400 text-slate-950 font-extrabold text-xs px-4 py-2.5 rounded-lg shadow shrink-0 flex items-center gap-1.5">
                        <i class="fa-solid fa-download"></i> Télécharger
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php require __DIR__ . '/../includes/linkedin-shop-cards.php'; ?>

        <!-- UPLOADED CV -->
        <?php if (!empty($uploads)): ?>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 space-y-4">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-paperclip text-slate-400"></i> Fichier CV transmis lors de la commande
            </h3>
            <div class="space-y-2">
                <?php foreach ($uploads as $up): ?>
                <div class="p-3 rounded-lg bg-slate-950 border border-slate-800 flex justify-between items-center text-xs text-slate-300">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-file-lines text-brand-yellow"></i>
                        <span><?= htmlspecialchars($up['file_name']) ?></span>
                    </div>
                    <a href="../api/linkedin/download_file.php?type=upload&id=<?= $up['id'] ?>&token=<?= urlencode($token) ?>" class="text-brand-yellow hover:underline font-bold text-xs">
                        Consulter
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- HISTORY TIMELINE -->
        <?php if (!empty($history)): ?>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 space-y-4">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-slate-400"></i> Historique de suivi
            </h3>
            <div class="space-y-3">
                <?php foreach ($history as $h): ?>
                <div class="text-xs border-l-2 border-brand-yellow/50 pl-3 py-0.5 space-y-0.5">
                    <div class="flex justify-between text-slate-400">
                        <strong class="text-slate-200"><?= htmlspecialchars($h['action']) ?></strong>
                        <span class="text-[10px] text-slate-500"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></span>
                    </div>
                    <?php if ($h['note']): ?>
                    <p class="text-slate-400"><?= htmlspecialchars($h['note']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <footer class="hm-hub-contact"><strong>MonCv Pro · Sangmélima, Cameroun</strong><p>Agence en ligne de création de CV, optimisation LinkedIn et conception de lettres de motivation. Filiale de HireMe.</p><p>HireMe collecte et diffuse des offres d’emploi, et accompagne les employeurs avec des campagnes publicitaires payantes.</p>Besoin d’aide ? <a href="mailto:moncvpro@hiremeguide.com">moncvpro@hiremeguide.com</a></footer>
    </div>

</body>
</html>
