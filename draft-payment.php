<?php
require_once 'admin/config/db.php';
$id = (int)($_GET["id"] ?? 0);
$ref = (string)($_GET["ref"] ?? "");
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order || $ref === "" || !hash_equals((string)$order["order_ref"], $ref)) {
    die("Commande introuvable.");
}
$balanceDue = $order['draft_balance_due'] ?? $order['amount'];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['proofFile'])) {
    require_once 'includes/upload_helper.php';
    $transactionId = trim($_POST['transactionId'] ?? '');
    $paymentMethod = trim($_POST['paymentMethod'] ?? '');

    $proofPath = ($transactionId !== '' && rate_limit_ok('draft-payment', 10, 3600))
        ? secure_upload($_FILES['proofFile'], ['jpg', 'jpeg', 'png', 'gif', 'pdf'], 'uploads/proofs', 'DRAFT-PROOF-')
        : null;

    if ($proofPath !== null) {
        // Update Order
        $stmtUpd = $pdo->prepare("UPDATE orders SET payment_status = 'pending_draft', transaction_number = ?, payment_proof = ?, payment_method = ? WHERE id = ?");
        $stmtUpd->execute([$transactionId, $proofPath, $paymentMethod, $id]);

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement du Solde – HireMe CV</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: { primary: '#0ea5e9', accent: '#22c55e', danger: '#ef4444', neutral: '#111827', bg: '#f8fafc' }
                }
            }
        }
    </script>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>

<body class="bg-bg text-neutral antialiased font-sans flex flex-col min-h-screen">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="track-cv.php?ref=<?= urlencode($order['order_ref']) ?>" class="text-xs font-bold text-gray-400 hover:text-gray-600 transition flex items-center gap-1">
                    ← Retour
                </a>
                <span class="text-xl font-extrabold text-primary tracking-tight">HireMe<span class="text-neutral">CV</span></span>
            </div>
        </div>
    </header>

    <main class="flex-grow py-12 px-4 sm:px-6">
        <div class="max-w-4xl mx-auto">
            
            <?php if (isset($success) && $success): ?>
            <div class="bg-white p-8 text-center rounded-2xl shadow-xl border border-green-100 max-w-lg mx-auto">
                <div class="w-20 h-20 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-black text-green-800 mb-4">Paiement Envoyé !</h2>
                <p class="text-gray-600 mb-6">Nous validons votre paiement dans les plus brefs délais (généralement en moins de 15 minutes). Dès confirmation, votre statut sera mis à jour en temps réel et vous serez redirigé vers votre espace client.</p>
                <a href="track-cv.php?ref=<?= urlencode($order['order_ref']) ?>" class="inline-block bg-green-500 text-white font-extrabold py-3 px-8 rounded-full hover:bg-green-600 transition shadow-lg shadow-green-500/20 text-sm">
                    💻 Espace de Suivi & Progression
                </a>
            </div>
            <?php else: ?>

            <div class="text-center mb-12">
                <h1 class="text-3xl sm:text-4xl font-extrabold text-neutral mb-4">Finaliser le Paiement</h1>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">Veuillez effectuer le paiement du solde pour débloquer la version finale PDF haute qualité de votre CV.</p>
                <div class="mt-4 bg-blue-50 text-blue-800 px-6 py-3 rounded-lg inline-block text-lg font-bold border border-blue-200 shadow-sm">
                    Montant à payer : <?= number_format($balanceDue, 0, ',', ' ') ?> FCFA
                </div>
            </div>

            <!-- Methods -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12 max-w-3xl mx-auto">
                <!-- MTN -->
                <div class="bg-white p-6 sm:p-8 rounded-[2rem] shadow-2xl shadow-yellow-500/5 border border-gray-50 border-t-8 border-t-yellow-400 hover:shadow-yellow-500/10 transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex flex-col items-center text-center">
                        <img src="assets/img/mtn_momo_logo.png" alt="MTN" class="h-12 mx-auto object-contain mb-4">
                        <h3 class="text-xl font-black text-slate-800 mb-1">Paiement MTN MoMo</h3>
                        <p class="text-[10px] font-bold text-yellow-600 uppercase tracking-widest mb-6">Instantané • 0 Frais</p>
                        
                        <div class="bg-slate-50 border border-slate-100 p-4 rounded-2xl mb-4 w-full">
                            <p class="text-xs text-slate-500 mb-3">Cliquez ci-dessous pour composer :</p>
                            <a href="tel:*126*14*654250688*<?= $balanceDue ?>%23" class="group relative block w-full bg-white border-2 border-yellow-400 rounded-xl p-4 shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all cursor-pointer overflow-hidden text-center">
                                <div class="absolute top-0 right-0 bg-yellow-400 text-yellow-900 text-[10px] font-black px-3 py-1 rounded-bl-lg animate-pulse tracking-tighter">
                                    COMPOSER MAINTENANT
                                </div>
                                <p class="font-mono font-black text-lg text-slate-800 break-all group-hover:text-yellow-700 transition-colors mt-2">
                                    *126*14*654250688*<span class="text-primary"><?= $balanceDue ?></span>#
                                </p>
                            </a>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">Bénéficiaire : NJAI NDINTEK HANDSON</p>
                    </div>
                </div>

                <!-- Orange -->
                <div class="bg-white p-6 sm:p-8 rounded-[2rem] shadow-2xl shadow-orange-500/5 border border-gray-50 border-t-8 border-t-orange-500 hover:shadow-orange-500/10 transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex flex-col items-center text-center">
                        <img src="assets/img/orange_money_logo.png" alt="Orange" class="h-12 mx-auto object-contain mb-4">
                        <h3 class="text-xl font-black text-slate-800 mb-1">Paiement Orange Money</h3>
                        <p class="text-[10px] font-bold text-orange-600 uppercase tracking-widest mb-6">Simple • Sécurisé</p>
                        
                        <div class="bg-slate-50 border border-slate-100 p-4 rounded-2xl mb-4 w-full">
                            <p class="text-xs text-slate-500 mb-3">Cliquez ci-dessous pour composer :</p>
                            <a href="tel:%23150*1*1*659197506*<?= $balanceDue ?>%23" class="group relative block w-full bg-white border-2 border-orange-500 rounded-xl p-4 shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all cursor-pointer overflow-hidden text-center">
                                <div class="absolute top-0 right-0 bg-orange-500 text-white text-[10px] font-black px-3 py-1 rounded-bl-lg animate-pulse tracking-tighter">
                                    COMPOSER MAINTENANT
                                </div>
                                <p class="font-mono font-black text-lg text-slate-800 break-all group-hover:text-orange-600 transition-colors mt-2">
                                    #150*1*1*659197506*<span class="text-primary"><?= $balanceDue ?></span>#
                                </p>
                            </a>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">Bénéficiaire : NJAI NDINTEK HANDSON</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 max-w-2xl mx-auto">
                <div class="bg-neutral py-6 px-8">
                    <h2 class="text-xl font-bold text-white flex items-center">Confirmer mon paiement</h2>
                </div>
                <form method="POST" enctype="multipart/form-data" class="p-8 space-y-6">
                    <div>
                        <span class="block text-sm font-medium text-gray-700 mb-2">Méthode utilisée</span>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex items-center space-x-3 p-4 border border-gray-200 rounded-xl cursor-pointer">
                                <input type="radio" name="paymentMethod" value="MTN" required class="h-5 w-5">
                                <span class="font-bold">MTN MoMo</span>
                            </label>
                            <label class="flex items-center space-x-3 p-4 border border-gray-200 rounded-xl cursor-pointer">
                                <input type="radio" name="paymentMethod" value="Orange" class="h-5 w-5">
                                <span class="font-bold">Orange Money</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de transaction (ID)</label>
                        <input type="text" name="transactionId" required placeholder="Ex: 2356894125" class="w-full rounded-lg border-gray-300 border p-3 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Preuve de paiement (Capture)</label>
                        <input type="file" name="proofFile" accept="image/*" required class="w-full text-sm">
                    </div>
                    <button type="submit" class="w-full mt-6 bg-accent text-white font-bold py-4 rounded-xl shadow-lg hover:bg-green-600 transition-all text-lg">
                        J’ai effectué mon paiement
                    </button>
                </form>
            </div>
            
            <?php endif; ?>
        </div>
    </main>
</body>
</html>