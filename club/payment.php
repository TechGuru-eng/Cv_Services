<?php
require_once '../admin/config/db.php';
require_once __DIR__ . '/../includes/upload_helper.php';
session_start();

if (!isset($_SESSION['club_member_id'])) { header('Location: register.php'); exit; }

$memberId = $_SESSION['club_member_id'];
$error    = '';
$success  = false;

$stmt = $pdo->prepare("SELECT * FROM club_members WHERE id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch();
if (!$member) { unset($_SESSION['club_member_id']); header('Location: login.php'); exit; }

if ($member['status'] === 'active' && (empty($member['expiry_date']) || strtotime($member['expiry_date']) > time())) { header('Location: dashboard.php'); exit; }

$checkPending = $pdo->prepare("SELECT id FROM club_payments WHERE member_id = ? AND status = 'pending'");
$checkPending->execute([$memberId]);
$pendingPayment = $checkPending->fetch();

$planDetails = [
    1 => ['name' => 'Basic',    'price' => 5000,  'cvs' => 4],
    2 => ['name' => 'Standard', 'price' => 7000,  'cvs' => 8],
    3 => ['name' => 'Premium',  'price' => 10000, 'cvs' => 14],
];
$plan = $planDetails[$member['plan_id']] ?? $planDetails[2];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pendingPayment) {
    $method        = $_POST['method']; // 'MTN' or 'OM'
    $transactionId = trim($_POST['transaction_id']);
    $transactionId = !empty($transactionId) ? $transactionId : null;

    $proofPath = '';
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === 0) {
        $proofPath = secure_upload($_FILES['proof'], ['jpg', 'jpeg', 'png', 'pdf'], 'uploads/payments', 'PAY-') ?? '';
        if ($proofPath === '') $error = 'Preuve invalide. PDF, JPG ou PNG de 5 Mo maximum.';
    }

    if (!in_array($method, ['MTN', 'OM'], true)) {
        $error = 'Méthode de paiement invalide.';
    } elseif (empty($transactionId) && empty($proofPath)) {
        $error = "Veuillez fournir l'ID de transaction ou une capture d'écran de paiement.";
    } elseif ($error === '') {
        try {
            $stmt = $pdo->prepare("INSERT INTO club_payments (member_id, amount, method, transaction_id, proof_file) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$memberId, $plan['price'], $method, $transactionId, $proofPath]);
            $success = true;
        } catch (PDOException $e) {
            if ($proofPath) @unlink(__DIR__ . '/../' . $proofPath);
            $error = "Erreur lors de l'enregistrement. Veuillez réessayer.";
        }
    }
}
if ($error !== '' && !empty($proofPath)) @unlink(__DIR__ . '/../' . $proofPath);

$memberName = $_SESSION['club_member_name'] ?? 'Membre';
$firstName  = explode(' ', $memberName)[0];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement — HireMe Club</title>
    <!-- Social Preview -->
    <meta property="og:type" content="website">
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/club-preview-premium.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="https://moncvpro.hiremeguide.com/assets/club-preview-premium.png">

    <!-- Favicons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { font-family:'Inter',sans-serif; }
        .field { width:100%; padding:13px 18px; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:12px; font-size:14px; font-weight:500; color:#0f172a; outline:none; transition:all .2s; }
        .field:focus { border-color:#0ea5e9; box-shadow:0 0 0 3px rgba(14,165,233,.1); background:#fff; }
        .field::placeholder { color:#94a3b8; font-weight:400; }
        .method-btn { transition: all .2s; }
        .method-btn.mtn-active { background:#FFCC00; border-color:#FFCC00; color:#1a1a1a; }
        .method-btn.om-active  { background:#FF6600; border-color:#FF6600; color:#fff; }
        .step-dot { width:8px; height:8px; border-radius:50%; background:#e2e8f0; }
        .step-dot.done { background:#0ea5e9; }
    </style>
    <script src="../assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="../assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="antialiased bg-slate-50 min-h-screen">

<!-- Top nav -->
<header class="bg-white border-b border-slate-100 px-5 py-4 flex items-center justify-between sticky top-0 z-10">
    <a href="../abonnements.html" class="font-black text-slate-900 text-base tracking-tight">HireMe<span class="text-sky-500">Club</span></a>
    <div class="flex items-center gap-2">
        <div class="w-2 h-2 rounded-full bg-sky-500"></div>
        <div class="w-2 h-2 rounded-full bg-sky-500"></div>
        <div class="w-2 h-2 rounded-full bg-slate-200"></div>
        <span class="text-xs text-slate-400 font-semibold ml-2">Étape 2/3</span>
    </div>
</header>

<main class="max-w-lg mx-auto px-4 py-10 md:py-14">

    <!-- Title -->
    <div class="text-center mb-8">
        <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight mb-2">Finalisez votre adhésion</h1>
        <div class="inline-flex items-center gap-2 px-5 py-2 bg-slate-900 rounded-full">
            <span class="text-xs font-black text-white uppercase tracking-wider">
                <?= $plan['name'] ?> · <?= number_format($plan['price'], 0, ',', ' ') ?> XAF / mois
            </span>
        </div>
    </div>

    <?php if ($success || $pendingPayment): ?>
    <!-- SUCCESS STATE -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-8 md:p-10 text-center">
        <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6 <?= $pendingPayment && !$success ? 'bg-amber-50' : 'bg-emerald-50' ?>">
            <i data-lucide="<?= $pendingPayment && !$success ? 'clock' : 'check-circle' ?>"
               class="w-10 h-10 <?= $pendingPayment && !$success ? 'text-amber-500' : 'text-emerald-500' ?>"></i>
        </div>
        <h2 class="text-xl font-black text-slate-900 mb-3">
            <?= $pendingPayment && !$success ? 'Paiement en cours de vérification' : 'Paiement envoyé !' ?>
        </h2>
        <p class="text-slate-500 text-sm leading-relaxed mb-8 max-w-xs mx-auto">
            <?= $pendingPayment && !$success
                ? "Vous avez déjà une demande en cours. Nous la vérifierons sous 1h à 4h."
                : "Nous validons votre transaction sous 1h à 4h. Vous recevrez l'accès à votre espace dès confirmation." ?>
        </p>
        <div class="flex flex-col gap-3">
            <a href="https://wa.me/237654250688?text=Bonjour+<?= urlencode($firstName) ?>+je+viens+de+payer+mon+adhésion+HireMe+Club+Pack+<?= $plan['name'] ?>"
               target="_blank"
               class="w-full py-4 bg-emerald-500 text-white rounded-2xl font-black text-sm uppercase tracking-wide flex items-center justify-center gap-2 hover:bg-emerald-600 transition-all active:scale-95">
                <i data-lucide="message-circle" class="w-4 h-4"></i>
                Confirmer sur WhatsApp
            </a>
            <a href="../index.html" class="w-full py-3.5 text-slate-400 font-semibold text-sm text-center hover:text-slate-700 transition-colors">
                Retour à l'accueil
            </a>
        </div>
    </div>

    <?php else: ?>
    <!-- PAYMENT FORM -->

    <!-- Step 1: Choose method -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 md:p-8 mb-4">
        <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4">1 — Choisissez votre opérateur</p>
        <div class="grid grid-cols-2 gap-3">
            <button id="btnMTN" onclick="setMethod('MTN')"
                    class="method-btn mtn-active h-16 rounded-2xl border-2 border-transparent font-black text-sm flex flex-col items-center justify-center gap-1 touch-manipulation">
                <span class="text-xs font-black uppercase tracking-wider">MTN</span>
                <span class="text-[10px] font-semibold opacity-70">Mobile Money</span>
            </button>
            <button id="btnOM" onclick="setMethod('OM')"
                    class="method-btn h-16 rounded-2xl border-2 border-slate-200 bg-white text-slate-600 font-black text-sm flex flex-col items-center justify-center gap-1 hover:border-orange-400 transition-all touch-manipulation">
                <span class="text-xs font-black uppercase tracking-wider">Orange</span>
                <span class="text-[10px] font-semibold opacity-70">Money</span>
            </button>
        </div>
    </div>

    <!-- Step 2: USSD code -->
    <div class="bg-slate-900 rounded-3xl p-6 md:p-8 mb-4">
        <p class="text-[11px] font-black text-slate-500 uppercase tracking-widest mb-5 text-center">2 — Composez ce code sur votre téléphone</p>
        <a id="ussdLink" href="tel:*126*14*654250688*<?= $plan['price'] ?>%23"
           class="block text-center group">
            <span id="ussdCode" class="text-2xl md:text-3xl font-black text-white tracking-tight group-hover:text-sky-400 transition-colors break-all">
                *126*14*654250688*<?= $plan['price'] ?>#
            </span>
            <div class="flex items-center justify-center gap-1.5 mt-3 text-slate-500 group-hover:text-sky-400 transition-colors">
                <i data-lucide="phone-call" class="w-3.5 h-3.5"></i>
                <span class="text-[11px] font-bold uppercase tracking-wide">Appuyer pour composer</span>
            </div>
        </a>
        <div class="mt-5 pt-5 border-t border-slate-800 flex items-center justify-between">
            <div>
                <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest">Destinataire</p>
                <p class="text-sm font-bold text-slate-300 mt-0.5">NJAI NDINTEK HANDSON</p>
            </div>
            <div class="text-right">
                <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest">Montant</p>
                <p class="text-sm font-black text-sky-400 mt-0.5"><?= number_format($plan['price'], 0, ',', ' ') ?> XAF</p>
            </div>
        </div>
    </div>

    <!-- Step 3: Proof -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 md:p-8">
        <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-5">3 — Confirmez votre paiement</p>

        <?php if ($error): ?>
        <div class="mb-5 p-4 bg-red-50 border border-red-100 rounded-2xl flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5"></i>
            <p class="text-red-600 text-sm font-medium"><?= htmlspecialchars($error) ?></p>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="method" id="selectedMethod" value="MTN">

            <div>
                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">
                    ID de Transaction <span class="normal-case font-normal">(recommandé)</span>
                </label>
                <input type="text" name="transaction_id" placeholder="Ex: 789456123"
                       class="field font-mono" autocomplete="off">
            </div>

            <div class="flex items-center gap-3">
                <div class="flex-1 h-px bg-slate-100"></div>
                <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">ou</span>
                <div class="flex-1 h-px bg-slate-100"></div>
            </div>

            <div>
                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">
                    Capture d'écran du paiement
                </label>
                <!-- Custom file area -->
                <label class="block w-full border-2 border-dashed border-slate-200 rounded-2xl p-5 text-center cursor-pointer hover:border-sky-400 hover:bg-sky-50/50 transition-all group">
                    <input type="file" name="proof" accept="image/*" class="hidden" id="proofInput" onchange="showFileName(this)">
                    <div id="proofPlaceholder" class="flex flex-col items-center gap-2">
                        <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center group-hover:bg-sky-100 transition-colors">
                            <i data-lucide="image" class="w-5 h-5 text-slate-400 group-hover:text-sky-500 transition-colors"></i>
                        </div>
                        <p class="text-sm font-semibold text-slate-500">Cliquez pour ajouter une image</p>
                        <p class="text-xs text-slate-400">JPG, PNG — Max 10MB</p>
                    </div>
                    <div id="proofFileName" class="hidden items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i>
                        <span class="text-sm font-semibold text-emerald-600" id="fileNameText"></span>
                    </div>
                </label>
            </div>

            <button type="submit"
                    class="w-full py-4 bg-sky-500 text-white font-black rounded-2xl hover:bg-sky-600 transition-all shadow-lg shadow-sky-200 active:scale-95 text-sm uppercase tracking-wide touch-manipulation">
                Valider mon paiement
            </button>
        </form>

        <p class="text-center text-slate-400 text-xs mt-5 font-medium">
            Accès activé dans les 4h après validation.
        </p>
    </div>

    <?php endif; ?>

</main>

<script>
lucide.createIcons();
const price = <?= $plan['price'] ?>;

function setMethod(m) {
    document.getElementById('selectedMethod').value = m;
    const btnMTN = document.getElementById('btnMTN');
    const btnOM  = document.getElementById('btnOM');

    btnMTN.className = btnMTN.className.replace(/mtn-active|om-active/g,'').trim();
    btnOM.className  = btnOM.className.replace(/mtn-active|om-active/g,'').trim();

    if (m === 'MTN') {
        btnMTN.classList.add('mtn-active');
        btnOM.style.cssText  = '';
        document.getElementById('ussdCode').textContent = `*126*14*654250688*${price}#`;
        document.getElementById('ussdLink').href = `tel:*126*14*654250688*${price}%23`;
    } else {
        btnOM.classList.add('om-active');
        document.getElementById('ussdCode').textContent = `#150*1*1*659197506*${price}#`;
        document.getElementById('ussdLink').href = `tel:%23150*1*1*659197506*${price}%23`;
    }
}

function showFileName(input) {
    if (input.files && input.files[0]) {
        document.getElementById('proofPlaceholder').classList.add('hidden');
        const fnDiv = document.getElementById('proofFileName');
        fnDiv.classList.remove('hidden');
        fnDiv.classList.add('flex');
        document.getElementById('fileNameText').textContent = input.files[0].name;
        lucide.createIcons();
    }
}
</script>
</body>
</html>
