<?php
session_start();
require_once 'admin/config/db.php';
require_once 'includes/upload_helper.php';
if ((!empty($_GET['ref']) || $_SERVER['REQUEST_METHOD'] === 'POST') && !rate_limit_ok('track-cv', 30, 600)) {
    http_response_code(429);
    die('Trop de tentatives. Réessayez dans quelques minutes.');
}

$ref = $_GET['ref'] ?? '';
$error = '';
$order = null;

if (!empty($ref)) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_ref = ? LIMIT 1");
    $stmt->execute([$ref]);
    $order = $stmt->fetch();
    if (!$order) {
        $error = "Référence de commande introuvable.";
    } else {
        // Track SMS click if src=sms is detected
        if (isset($_GET['src']) && $_GET['src'] === 'sms') {
            try {
                $clientPhone = $order['whatsapp'] ?? '';
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

// Handle Manual Form Search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_ref'])) {
    $enteredRef = trim($_POST['order_ref']);
    $enteredEmail = trim($_POST['email']);
    
    if (empty($enteredRef) || empty($enteredEmail)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_ref = ? AND email = ? LIMIT 1");
        $stmt->execute([$enteredRef, $enteredEmail]);
        $order = $stmt->fetch();
        if ($order) {
            header("Location: track-cv.php?ref=" . urlencode($order['order_ref']));
            exit;
        } else {
            $error = "Aucune commande correspondante trouvée.";
        }
    }
}

// Automatic Redirection to Retrieve CV if final is ready and verified
if ($order && !empty($order['completed_cv_path']) && $order['payment_status'] === 'verified') {
    header("Location: retrieve-cv.php?ref=" . urlencode($order['order_ref']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi de ma Commande — HireMe</title>
    
    <!-- Premium Preview Meta Tags -->
    <meta property="og:title" content="Suivi de Commande en Temps Réel — HireMe" />
    <meta property="og:description" content="Suivez l'évolution de la rédaction de votre CV par nos experts." />
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/cv-retrieval-preview.png" />
    <meta property="og:type" content="website" />
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
    <div class="<?= $order ? 'max-w-2xl' : 'max-w-md' ?> w-full transition-all duration-300">
        <!-- Logo -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-neutral">
                HireMe <span class="text-primary">CV</span>
            </h1>
            <p class="text-gray-500 mt-2 text-sm font-medium">Suivi de commande & livraison</p>
        </div>

        <div class="glass-effect border border-gray-200 p-8 rounded-3xl shadow-xl">
            <?php if ($order): 
                $paymentStatus = $order['payment_status'];
                $draftPath = $order['draft_cv_path'];
                $completedPath = $order['completed_cv_path'];
                $balanceDue = (!empty($order['draft_balance_due']) && $order['draft_balance_due'] > 0) ? $order['draft_balance_due'] : $order['amount'];
                $orderId = $order['id'];
                $orderRef = htmlspecialchars($order['order_ref']);
                
                // Determine current step index
                $step1 = true;
                $step2 = (!empty($draftPath) || !empty($completedPath));
                $step3 = !empty($draftPath);
                $step4 = (!empty($completedPath) && $paymentStatus === 'verified');
                
                $activeStep = 1;
                if ($step4) {
                    $activeStep = 4;
                } elseif ($step3) {
                    $activeStep = 3;
                } elseif ($step2) {
                    $activeStep = 2;
                }
            ?>
                <div class="space-y-6">
                    <!-- Header -->
                    <div class="text-center pb-5 border-b border-gray-100 mb-6">
                        <div class="inline-block bg-sky-50 text-primary font-bold px-3 py-1 rounded-full text-xs mb-3">
                            Commande #<?= $orderRef ?>
                        </div>
                        <h2 class="text-2xl font-extrabold text-neutral">Suivi de votre commande</h2>
                        <p class="text-gray-500 text-xs mt-1">Bonjour <?= htmlspecialchars($order['name']) ?>, voici l'avancement en temps réel de votre CV professionnel.</p>
                    </div>

                    <!-- Visual Timeline -->
                    <div class="mb-8 px-2">
                        <div class="flex items-center justify-between relative">
                            <!-- Background line -->
                            <div class="absolute left-0 right-0 top-4 -translate-y-1/2 h-1 bg-gray-100 z-0 rounded"></div>
                            <!-- Active progress line -->
                            <div class="absolute left-0 top-4 -translate-y-1/2 h-1 bg-primary z-0 rounded transition-all duration-500" style="width: <?= ($activeStep - 1) / 3 * 100 ?>%;"></div>
                            
                            <!-- Steps -->
                            <div class="flex flex-col items-center z-10">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 <?= $step1 ? 'bg-primary text-white ring-4 ring-sky-100' : 'bg-white text-gray-400 border-2 border-gray-200' ?>">
                                    1
                                </div>
                                <span class="text-[9px] font-black uppercase text-neutral mt-2 tracking-tighter">Enregistrée</span>
                            </div>

                            <div class="flex flex-col items-center z-10">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 <?= $step2 ? 'bg-primary text-white ring-4 ring-sky-100' : ($activeStep === 1 ? 'bg-white text-primary border-2 border-primary animate-pulse' : 'bg-white text-gray-400 border-2 border-gray-200') ?>">
                                    2
                                </div>
                                <span class="text-[9px] font-black uppercase text-neutral mt-2 tracking-tighter">Rédaction</span>
                            </div>

                            <div class="flex flex-col items-center z-10">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 <?= $step3 ? 'bg-primary text-white ring-4 ring-sky-100' : ($activeStep === 2 ? 'bg-white text-primary border-2 border-primary animate-pulse' : 'bg-white text-gray-400 border-2 border-gray-200') ?>">
                                    3
                                </div>
                                <span class="text-[9px] font-black uppercase text-neutral mt-2 tracking-tighter">Brouillon</span>
                            </div>

                            <div class="flex flex-col items-center z-10">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 <?= $step4 ? 'bg-primary text-white ring-4 ring-sky-100' : ($activeStep === 3 ? 'bg-white text-primary border-2 border-primary animate-pulse' : 'bg-white text-gray-400 border-2 border-gray-200') ?>">
                                    4
                                </div>
                                <span class="text-[9px] font-black uppercase text-neutral mt-2 tracking-tighter">Livré</span>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic status card -->
                    <div class="space-y-4">
                        <?php if (!empty($completedPath) && $paymentStatus !== 'verified'): ?>
                            <!-- Case 1: CV is completed but payment is not verified (Pay After Delivery balance due) -->
                            <div class="bg-amber-50 border border-amber-100 p-6 rounded-2xl text-center">
                                <div class="w-12 h-12 bg-amber-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-xl font-bold">💰</div>
                                <h3 class="font-extrabold text-amber-900 text-lg">CV finalisé ! Solde restant dû</h3>
                                <p class="text-xs text-amber-700 mt-1 mb-5 leading-relaxed">
                                    Excellente nouvelle ! Votre CV final a été rédigé avec succès par notre expert. 
                                    Veuillez régler le solde restant de <strong><?= number_format($balanceDue, 0, ',', ' ') ?> FCFA</strong> pour débloquer instantanément le téléchargement.
                                </p>
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <?php if (!empty($draftPath)): ?>
                                        <a href="view-draft.php?id=<?= $orderId ?>" class="flex-1 bg-white border-2 border-amber-200 text-amber-800 font-bold py-3 rounded-xl hover:bg-amber-50 transition text-sm">
                                            🔍 Voir le Brouillon
                                        </a>
                                    <?php endif; ?>
                                    <a href="draft-payment.php?id=<?= $orderId ?>" class="flex-1 bg-amber-500 text-white font-bold py-3 rounded-xl hover:bg-amber-600 transition text-sm shadow-md shadow-amber-500/10">
                                        💳 Payer le Solde
                                    </a>
                                </div>
                            </div>
                        <?php elseif (!empty($draftPath) && $paymentStatus !== 'verified'): ?>
                            <!-- Case 2: Draft is available, awaiting balance payment -->
                            <div class="bg-blue-50 border border-blue-100 p-6 rounded-2xl text-center">
                                <div class="w-12 h-12 bg-blue-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-xl font-bold">👀</div>
                                <h3 class="font-extrabold text-blue-900 text-lg">Brouillon de CV disponible !</h3>
                                <p class="text-xs text-blue-700 mt-1 mb-5 leading-relaxed">
                                    Le brouillon filigrané de votre CV est disponible pour relecture. Examinez-le dès maintenant et réglez le solde restant de <strong><?= number_format($balanceDue, 0, ',', ' ') ?> FCFA</strong> pour lancer la livraison finale.
                                </p>
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <a href="view-draft.php?id=<?= $orderId ?>" class="flex-1 bg-white border-2 border-blue-200 text-blue-800 font-bold py-3 rounded-xl hover:bg-blue-50 transition text-sm">
                                        🔍 Relire le Brouillon
                                    </a>
                                    <a href="draft-payment.php?id=<?= $orderId ?>" class="flex-1 bg-blue-500 text-white font-bold py-3 rounded-xl hover:bg-blue-600 transition text-sm shadow-md shadow-blue-500/10">
                                        💳 Valider & Payer
                                    </a>
                                </div>
                            </div>
                        <?php elseif ($paymentStatus === 'verified' && empty($completedPath)): ?>
                            <!-- Case 3: Paid but drafting in progress -->
                            <div class="bg-indigo-50 border border-indigo-100 p-6 rounded-2xl text-center">
                                <div class="w-12 h-12 bg-indigo-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-xl font-bold animate-pulse">✍️</div>
                                <h3 class="font-extrabold text-indigo-900 text-lg">Rédaction en cours...</h3>
                                <p class="text-xs text-indigo-700 mt-1 mb-4 leading-relaxed">
                                    Votre paiement a été validé avec succès ! Nos rédacteurs experts rédigent actuellement votre CV premium final. Vous recevrez une notification et il sera téléchargeable directement sur votre Espace de Téléchargement dès qu'il sera prêt (sous 24h).
                                </p>
                                <div class="inline-flex items-center gap-2 bg-indigo-100 text-indigo-800 text-xs px-3 py-1.5 rounded-full font-bold">
                                    ⏳ Livraison estimée : Moins de 24h
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Case 4: Waiting redaction (standard unpaid/draft not ready) -->
                            <div class="bg-slate-50 border border-slate-200 p-6 rounded-2xl text-center">
                                <div class="w-12 h-12 bg-slate-400 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-xl font-bold">⏳</div>
                                <h3 class="font-extrabold text-slate-800 text-lg">Commande en traitement</h3>
                                <p class="text-xs text-slate-600 mt-1 mb-4 leading-relaxed">
                                    Votre commande a été enregistrée. Notre équipe analyse votre profil pour commencer la rédaction stratégique de votre nouveau CV. Revenez très bientôt pour relire votre premier brouillon !
                                </p>
                            </div>
                        <?php endif; ?>

                        <!-- WhatsApp Support Widget -->
                        <div class="bg-slate-50 border border-gray-100 p-4 rounded-xl flex items-center justify-between text-left">
                            <div>
                                <h4 class="text-xs font-bold text-gray-700">Besoin d'aide ou de modifications ?</h4>
                                <p class="text-[10px] text-gray-400 mt-0.5">Discutez directement avec votre rédacteur.</p>
                            </div>
                            <a href="https://wa.me/237654250688?text=<?= urlencode("Bonjour, je souhaite échanger sur ma commande de CV #" . $orderRef) ?>" target="_blank" class="bg-[#25D366] hover:bg-[#20b858] text-white font-bold text-[10px] px-3.5 py-2 rounded-lg flex items-center gap-1.5 shadow-sm transition">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-6.912-11.975 1.701-26.241 15.688-26.241 8.528 0 15.467 6.938 15.467 15.466 0 8.527-6.939 15.466-15.468 15.466-4.088 0-7.947-1.585-10.957-4.469l-6.417 1.687zm9.648-9.982c-2.617-1.125-2.731-1.378-2.67-1.637.07-.3.738-1.527 1.258-1.848.514-.32.61-.267 1.053-.08.437.186 2.76.997 2.898 1.155.138.16.29.69.043 1.082-.246.392-.511.666-.889.85-.377.183-1.691.564-4.821-2.029-3.13-2.592-3.832-3.805-4.04-4.22-.208-.415-.06-.599.123-.781.183-.183.398-.291.603-.43.204-.138.384-.33.585-.544.199-.214.288-.358.418-.616.128-.258.07-.638-.179-1.23-.243-.591-1.31-3.155-1.742-3.486-.432-.33-.614-.234-.848-.052-.234.181-.782.529-1.157.94-.374.412-1.428 1.638-1.428 4.226 0 2.589 1.93 5.378 2.163 5.7.234.321 3.535 5.869 8.785 7.973 5.251 2.106 5.251 1.404 6.182 1.346.931-.059 3.016-1.23 3.447-2.433.432-1.203.432-2.228.307-2.433-.124-.204-.455-.325-.939-.567z"/></svg>
                                WhatsApp
                            </a>
                        </div>
                    </div>
                    
                    <div class="text-center pt-6 border-t border-gray-100">
                        <a href="track-cv.php" class="text-xs font-bold text-gray-400 hover:text-gray-600 underline">Quitter le suivi</a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-5">
                    <?php if ($error): ?>
                        <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl text-xs font-bold flex items-center gap-2">
                           <span>⚠️</span> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <div class="text-center pb-4 border-b border-gray-100">
                        <h2 class="text-xl font-bold text-neutral">Suivre mon CV Pro</h2>
                        <p class="text-gray-500 text-xs mt-1">Saisissez vos identifiants de commande</p>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Référence de Commande</label>
                        <input type="text" name="order_ref" required placeholder="EX: CMD-XXXXXX-XXXX" 
                            class="w-full bg-white border border-gray-200 p-3.5 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition text-sm font-mono uppercase">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Votre Email</label>
                        <input type="email" name="email" required placeholder="votre@email.com" 
                            class="w-full bg-white border border-gray-200 p-3.5 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition text-sm">
                    </div>

                    <button type="submit" name="search_ref" class="w-full bg-primary text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/20 hover:bg-sky-600 transition uppercase tracking-tight text-sm">
                        Lancer la recherche
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
</body>
</html>
