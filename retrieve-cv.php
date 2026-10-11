<?php
require_once 'admin/config/db.php';
require_once 'includes/upload_helper.php';
if ((!empty($_GET['ref']) || $_SERVER['REQUEST_METHOD'] === 'POST') && !rate_limit_ok('retrieve-cv', 20, 600)) {
    http_response_code(429);
    die('Trop de tentatives. Réessayez dans quelques minutes.');
}

function slugifyName($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text);
}

$result = null;
$error = '';

$ref = $_GET['ref'] ?? '';
if (!empty($ref)) {
    if (filter_var($ref, FILTER_VALIDATE_EMAIL)) {
        // Email-only lookups are refused: anyone knowing an email address could otherwise read the customer's CV.
        // The customer must use the POST form (email + full name) or their order reference.
        $error = "Pour votre sécurité, utilisez votre référence de commande, ou saisissez votre email ET votre nom complet ci-dessous.";
    } else {
        // Magic link via Order Ref (Standard CV Orders)
        $stmtOrder = $pdo->prepare("SELECT * FROM orders WHERE order_ref = ? LIMIT 1");
        $stmtOrder->execute([$ref]);
        $ord = $stmtOrder->fetch();
        if ($ord) {
            $result = [
                'type' => 'order',
                'id' => $ord['id'],
                'full_name' => $ord['name'],
                'payment_status' => $ord['payment_status'],
                'draft_cv_path' => $ord['draft_cv_path'],
                'completed_cv_path' => $ord['completed_cv_path'],
                'amount' => $ord['amount'],
                'draft_balance_due' => $ord['draft_balance_due'],
                'order_ref' => $ord['order_ref'] ?? ('MONCV-' . $ord['id'])
            ];
        }
    }
    
    if (!$result) {
        $error = "Lien magique de récupération invalide ou expiré.";
    } else {
        // Track SMS click if src=sms is detected
        if (isset($_GET['src']) && $_GET['src'] === 'sms' && isset($result['id']) && isset($result['type']) && $result['type'] === 'order') {
            try {
                $stmtPhone = $pdo->prepare("SELECT whatsapp FROM orders WHERE id = ?");
                $stmtPhone->execute([$result['id']]);
                $clientPhone = $stmtPhone->fetchColumn();
                
                if (!empty($clientPhone)) {
                    require_once 'admin/config/sms_helper.php';
                    $formattedPhone = SMSHelper::formatPhoneNumber($clientPhone);
                    
                    $stmtTrack = $pdo->prepare("UPDATE sms_logs SET clicked = 1 WHERE recipient = ? ORDER BY id DESC LIMIT 1");
                    $stmtTrack->execute([$formattedPhone]);
                }
            } catch (Exception $e) {
                // Silently ignore tracking errors to avoid breaking the client page
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $enteredName = trim($_POST['full_name']);

    if (empty($email) || empty($enteredName)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM cv_packs WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $pack = $stmt->fetch();

        if ($pack) {
            if ($pack['status'] !== 'verified') {
                $error = "Paiement non vérifié.";
            } else {
                $storedParts = array_filter(explode(' ', strtolower($pack['full_name'])));
                $enteredParts = array_filter(explode(' ', strtolower($enteredName)));
                
                $matches = 0;
                foreach ($enteredParts as $ePart) {
                    if (in_array($ePart, $storedParts)) {
                        $matches++;
                    }
                }

                if ($matches >= 2) {
                    $result = $pack;
                } else {
                    $error = "Aucun enregistrement correspondant trouvé.";
                }
            }
        } else {
            // Check Sponsorships
            $stmtSpons = $pdo->prepare("SELECT * FROM sponsorship_requests WHERE email = ? AND status = 'approved' AND is_realized = 1 LIMIT 1");
            $stmtSpons->execute([$email]);
            $spons = $stmtSpons->fetch();
            
            if ($spons) {
                $storedParts = array_filter(explode(' ', strtolower($spons['name'])));
                $enteredParts = array_filter(explode(' ', strtolower($enteredName)));
                
                $matches = 0;
                foreach ($enteredParts as $ePart) {
                    if (in_array($ePart, $storedParts)) {
                        $matches++;
                    }
                }

                if ($matches >= 2) {
                    $result = [
                        'full_name' => $spons['name'],
                        'pack_link' => $spons['recovery_link']
                    ];
                } else {
                    $error = "Aucun enregistrement correspondant trouvé.";
                }
            } else {
                // Check Standard Orders (Individual Paid CVs)
                $stmtOrder = $pdo->prepare("SELECT * FROM orders WHERE email = ? ORDER BY id DESC LIMIT 1");
                $stmtOrder->execute([$email]);
                $ord = $stmtOrder->fetch();

                if ($ord) {
                    $storedParts = array_filter(explode(' ', strtolower($ord['name'])));
                    $enteredParts = array_filter(explode(' ', strtolower($enteredName)));
                    
                    $matches = 0;
                    foreach ($enteredParts as $ePart) {
                        if (in_array($ePart, $storedParts)) {
                            $matches++;
                        }
                    }

                    if ($matches >= 1 || strtolower($ord['name']) === strtolower($enteredName)) {
                        $result = [
                            'type' => 'order',
                            'id' => $ord['id'],
                            'full_name' => $ord['name'],
                            'payment_status' => $ord['payment_status'],
                            'draft_cv_path' => $ord['draft_cv_path'],
                            'completed_cv_path' => $ord['completed_cv_path'],
                            'amount' => $ord['amount'],
                            'draft_balance_due' => $ord['draft_balance_due'],
                            'order_ref' => $ord['order_ref'] ?? ('MONCV-' . $ord['id'])
                        ];
                    } else {
                        $error = "Aucun enregistrement correspondant trouvé.";
                    }
                } else {
                    $error = "Aucun enregistrement correspondant trouvé.";
                }
            }
        }
    }
}

// Redirect standard orders to track-cv if not ready and verified yet
if ($result && isset($result['type']) && $result['type'] === 'order') {
    $isReadyAndPaid = (!empty($result['completed_cv_path']) && $result['payment_status'] === 'verified');
    if (!$isReadyAndPaid) {
        header("Location: track-cv.php?ref=" . urlencode($result['order_ref']));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récupérer mon Pack CV — HireMe</title>
    
    <!-- Favicons -->
    <!-- Premium Preview Meta Tags -->
    <meta property="og:title" content="HireMe CV — Portail de Récupération" />
    <meta property="og:description" content="Accédez à vos documents professionnels personnalisés en toute sécurité." />
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/cv-retrieval-preview.png" />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:image" content="https://moncvpro.hiremeguide.com/assets/cv-retrieval-preview.png" />
    
    <link rel="canonical" href="https://moncvpro.hiremeguide.com/retrieve-cv.php" />
    <meta name="theme-color" content="#0ea5e9">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary: '#0ea5e9',
                        accent: '#22c55e',
                        neutral: '#111827',
                        bg: '#f8fafc'
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #f8fafc; color: #111827; }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="min-h-screen flex items-center justify-center p-6 bg-slate-50">
    <div class="max-w-md w-full transition-all duration-300">
        <!-- Logo -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-neutral">
                HireMe <span class="text-primary">CV</span>
            </h1>
            <p class="text-gray-500 mt-2 text-sm font-medium">Portail de récupération sécurisé</p>
        </div>

        <div class="glass-effect border border-gray-200 p-8 rounded-3xl shadow-xl">
            <?php if ($result): 
                $firstName = htmlspecialchars(explode(' ', $result['full_name'])[0]);
            ?>
                <div class="space-y-6">
                    <!-- Header -->
                    <div class="text-center pb-4 border-b border-gray-100">
                        <div class="w-16 h-16 bg-blue-50 text-primary rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">✓</div>
                        <h2 class="text-2xl font-bold text-neutral">Bienvenue, <?= $firstName ?> !</h2>
                        <p class="text-gray-500 text-sm mt-1">Votre espace personnel HireMe CV</p>
                    </div>

                    <!-- My Files Section -->
                    <?php 
                        $downloadUrl = htmlspecialchars($result['pack_link'] ?? $result['completed_cv_path'] ?? '');
                        $downloadLabel = isset($result['type']) && $result['type'] === 'order' ? 'TÉLÉCHARGER MON CV FINAL (PREMIUM)' : 'TÉLÉCHARGER MON DOSSIER';
                        $sectionTitle = isset($result['type']) && $result['type'] === 'order' ? 'Mon CV Premium Finalisé' : 'Mes Fichiers (Pack Complet)';
                        $sectionDesc = isset($result['type']) && $result['type'] === 'order' ? 'Votre nouveau CV professionnel haut de gamme rédigé stratégiquement par nos experts.' : 'Ce dossier contient tous vos documents finaux (CV, Photo Pro, etc.). Il est sécurisé et valable pendant 1 an.';
                    ?>
                    <div class="bg-blue-50 border border-blue-100 p-5 rounded-2xl">
                        <h3 class="font-bold text-blue-900 mb-1 flex items-center gap-2">
                            <span>📁</span> <?= $sectionTitle ?>
                        </h3>
                        <p class="text-xs text-blue-700 mb-4 leading-relaxed">
                            <?= $sectionDesc ?>
                        </p>
                        <a href="<?= $downloadUrl ?>" target="_blank" class="block w-full bg-primary text-white font-bold py-3.5 rounded-xl shadow-lg shadow-primary/20 hover:bg-sky-600 transition text-center uppercase tracking-tight text-sm">
                            <?= $downloadLabel ?>
                        </a>
                    </div>

                    <!-- QR Code & Dynamic CV Section -->
                    <?php 
                        $candidateName = $result['full_name'] ?? $result['name'] ?? '';
                        $candidateSlug = slugifyName($candidateName);
                        
                        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
                        $host = $_SERVER['HTTP_HOST'];
                        $scriptName = $_SERVER['SCRIPT_NAME']; // e.g. /MonCv/retrieve-cv.php
                        $dir = str_replace('\\', '/', dirname($scriptName));
                        if ($dir !== '/') {
                            $dir = rtrim($dir, '/');
                        } else {
                            $dir = '';
                        }
                        $baseUrl = $protocol . "://" . $host . $dir . "/";
                        
                        $portfolioUrl = $baseUrl . "cv.php?name=" . $candidateSlug;
                        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($portfolioUrl);
                    ?>
                    <div class="bg-gradient-to-br from-slate-50 to-sky-50/50 border border-sky-100 p-5 rounded-2xl shadow-sm text-center space-y-4">
                        <h3 class="font-bold text-slate-800 flex items-center justify-center gap-2">
                            <span>📱</span> Votre QR Code & CV en Ligne
                        </h3>
                        
                        <p class="text-xs text-slate-600 leading-relaxed max-w-md mx-auto">
                            Félicitations ! Nous avons généré un **QR Code professionnel unique** relié à votre espace dynamique en ligne. Imprimez ce QR Code sur votre CV papier ou signature d'email pour permettre aux recruteurs de vous contacter en 1 clic !
                        </p>

                        <!-- QR Code Display -->
                        <div class="bg-white p-4 rounded-xl border border-sky-100/50 w-44 h-44 mx-auto shadow-sm flex items-center justify-center">
                            <img src="<?= $qrCodeUrl ?>" alt="QR Code Professionnel" class="w-full h-full object-contain">
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-2.5 pt-1">
                            <a href="<?= $portfolioUrl ?>" target="_blank" class="flex-1 bg-white border border-sky-200 text-sky-700 font-bold py-2.5 px-4 rounded-xl hover:bg-sky-50 transition text-xs flex items-center justify-center gap-1.5 shadow-sm">
                                🔗 Voir mon CV en ligne
                            </a>
                            <a href="<?= $qrCodeUrl ?>" target="_blank" download="QRCode_<?= $candidateSlug ?>.png" class="flex-1 bg-primary text-white font-bold py-2.5 px-4 rounded-xl hover:bg-sky-600 transition text-xs flex items-center justify-center gap-1.5 shadow-sm shadow-primary/10">
                                📥 Télécharger le QR Code (PNG)
                            </a>
                        </div>
                    </div>

                    <!-- Free Bonus -->
                    <div class="bg-white border border-gray-200 p-5 rounded-2xl shadow-sm">
                        <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                            <span>🎁</span> Vos Bonus Gratuits
                        </h3>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-100">
                            <div>
                                <p class="text-sm font-bold text-gray-800">Guide Anti-Arnaques (PDF)</p>
                                <p class="text-[10px] text-gray-500 mt-0.5">Sécurisez votre recherche d'emploi au Cameroun.</p>
                            </div>
                            <a href="https://boutique.hiremeguide.com/prd_pj5reu" target="_blank" class="bg-neutral text-white text-[10px] font-bold px-3 py-2 rounded-lg hover:bg-gray-800 shadow-sm transition whitespace-nowrap ml-2">
                                Obtenir (0 F)
                            </a>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-5 bg-white rounded-2xl border border-gray-200">
                        <p class="text-sm font-bold text-gray-800">10 raisons pourquoi tu chômes + plan de 30 jours</p>
                        <a href="https://boutique.hiremeguide.com/prd_ajbvfder" target="_blank" rel="noopener noreferrer" class="self-start sm:self-auto bg-neutral text-white text-[10px] font-bold px-3 py-2 rounded-lg hover:bg-gray-800 transition whitespace-nowrap">Télécharger (gratuit)</a>
                    </div>

                    <!-- Premium Upgrades -->
                    <div class="bg-gradient-to-br from-neutral to-gray-800 p-5 rounded-2xl text-white shadow-lg relative overflow-hidden">
                        <div class="absolute -right-4 -top-4 text-6xl opacity-10">🚀</div>
                        <h3 class="font-bold mb-4 flex items-center gap-2">
                            <span>⭐</span> Améliorez votre candidature
                        </h3>
                        <div class="space-y-3 relative z-10">
                            <?php
                            $paidEbooks = [
                                ['Guide Premium des cabinets RH au Cameroun + 6 bonus', '5 000 XAF', 'prd_0cm5a17j'],
                                ['50 questions d’entretien et réponses', '999 XAF', 'prd_wp8pbysn'],
                                ['Apprends n’importe quoi en ligne gratuitement', '1 500 XAF', 'prd_k3qt8yto'],
                                ['100 messages professionnels prêts à personnaliser', '≈650 XAF', 'prd_7ocsyau0'],
                                ['Annuaire de 686 entreprises camerounaises', '500 XAF', 'prd_m214cn48'],
                            ];
                            foreach ($paidEbooks as [$ebookTitle, $ebookPrice, $ebookSlug]): ?>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 bg-white/10 rounded-xl border border-white/10">
                                <p class="text-sm font-bold"><?= htmlspecialchars($ebookTitle) ?></p>
                                <a href="https://boutique.hiremeguide.com/<?= htmlspecialchars($ebookSlug) ?>" target="_blank" rel="noopener noreferrer" class="self-start sm:self-auto bg-white text-neutral text-[10px] font-bold px-3 py-2 rounded-lg hover:bg-gray-100 transition whitespace-nowrap" aria-label="Acheter : <?= htmlspecialchars($ebookTitle) ?>">
                                    <?= htmlspecialchars($ebookPrice) ?>
                                </a>
                            </div>
                            <?php endforeach; ?>
                            <div class="flex items-center justify-between p-3 bg-white/10 rounded-xl border border-white/10 backdrop-blur-sm">
                                <div>
                                    <p class="text-sm font-bold">Pack 300 Lettres de Motivation</p>
                                    <p class="text-[10px] text-gray-300 mt-0.5">Prêtes à l'emploi pour tout secteur.</p>
                                </div>
                                <a href="https://boutique.hiremeguide.com/packlettres" target="_blank" rel="noopener noreferrer" class="bg-white text-neutral text-[10px] font-bold px-3 py-2 rounded-lg hover:bg-gray-100 shadow-sm transition whitespace-nowrap ml-2">
                                    600 XAF
                                </a>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-white/10 rounded-xl border border-white/10 backdrop-blur-sm">
                                <div>
                                    <p class="text-sm font-bold">60 Modèles CV (Word)</p>
                                    <p class="text-[10px] text-gray-300 mt-0.5">Designs professionnels modifiables.</p>
                                </div>
                                <a href="https://boutique.hiremeguide.com/60modelescv" target="_blank" class="bg-white text-neutral text-[10px] font-bold px-3 py-2 rounded-lg hover:bg-gray-100 shadow-sm transition whitespace-nowrap ml-2">
                                    2 000 XAF
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Referral Section -->
                    <div class="bg-green-50 border border-green-200 p-5 rounded-2xl text-center">
                        <h3 class="font-bold text-green-800 mb-1">🤝 Parrainez un ami</h3>
                        <p class="text-xs text-green-700 mb-4 leading-relaxed">Recommandez Mon CV Pro et aidez votre entourage à obtenir plus d'entretiens.</p>
                        <?php 
                            $refText = "Salut ! J'ai refait mon CV avec HireMe et le résultat est incroyable. Je te conseille vivement leurs services pour booster ta carrière : https://moncvpro.hiremeguide.com";
                        ?>
                        <a href="https://wa.me/?text=<?= urlencode($refText) ?>" target="_blank" class="inline-flex items-center gap-2 bg-[#25D366] text-white font-bold py-2.5 px-5 rounded-xl shadow-md hover:bg-[#20b858] transition text-sm">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-6.912-11.975 1.701-26.241 15.688-26.241 8.528 0 15.467 6.938 15.467 15.466 0 8.527-6.939 15.466-15.468 15.466-4.088 0-7.947-1.585-10.957-4.469l-6.417 1.687zm9.648-9.982c-2.617-1.125-2.731-1.378-2.67-1.637.07-.3.738-1.527 1.258-1.848.514-.32.61-.267 1.053-.08.437.186 2.76.997 2.898 1.155.138.16.29.69.043 1.082-.246.392-.511.666-.889.85-.377.183-1.691.564-4.821-2.029-3.13-2.592-3.832-3.805-4.04-4.22-.208-.415-.06-.599.123-.781.183-.183.398-.291.603-.43.204-.138.384-.33.585-.544.199-.214.288-.358.418-.616.128-.258.07-.638-.179-1.23-.243-.591-1.31-3.155-1.742-3.486-.432-.33-.614-.234-.848-.052-.234.181-.782.529-1.157.94-.374.412-1.428 1.638-1.428 4.226 0 2.589 1.93 5.378 2.163 5.7.234.321 3.535 5.869 8.785 7.973 5.251 2.106 5.251 1.404 6.182 1.346.931-.059 3.016-1.23 3.447-2.433.432-1.203.432-2.228.307-2.433-.124-.204-.455-.325-.939-.567z"/></svg>
                            Partager sur WhatsApp
                        </a>
                    </div>
                    
                    <div class="text-center pt-2">
                        <a href="retrieve-cv.php" class="text-xs font-bold text-gray-400 hover:text-gray-600 underline">Déconnexion</a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-5">
                    <?php if ($error): ?>
                        <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl text-xs font-bold flex items-center gap-2">
                           <span>⚠️</span> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Email de commande</label>
                        <input type="email" name="email" required placeholder="votre@email.com" 
                            class="w-full bg-white border border-gray-200 p-3.5 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition text-sm">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Nom Complet</label>
                        <input type="text" name="full_name" required placeholder="Ex: Jean Paul" 
                            class="w-full bg-white border border-gray-200 p-3.5 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition text-sm">
                    </div>

                    <button type="submit" class="w-full bg-primary text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/20 hover:bg-sky-600 transition uppercase tracking-tight text-sm">
                        RÉCUPÉRER MON PACK
                    </button>
                    
                    <div class="pt-6 border-t border-gray-100 text-center">
                        <a href="https://wa.me/237654250688" class="text-gray-400 hover:text-primary transition-colors text-xs font-medium">
                            Besoin d'aide ? Contactez-nous sur WhatsApp
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="mt-8 text-center text-gray-400 text-[10px] font-bold uppercase tracking-widest">
            &copy; <?= date('Y') ?> HireMe CV Services
        </div>
    </div>
    <script src="https://chariow.com/widget.js"></script>
</body>
</html>
