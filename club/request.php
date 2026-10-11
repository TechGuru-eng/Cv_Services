<?php
require_once '../admin/config/db.php';
require_once __DIR__ . '/../includes/upload_helper.php';
session_start();

if (!isset($_SESSION['club_member_id'])) { header('Location: login.php'); exit; }

$memberId = $_SESSION['club_member_id'];
$error    = '';

$stmt = $pdo->prepare("SELECT * FROM club_members WHERE id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch();
if (!$member) { unset($_SESSION['club_member_id']); header('Location: login.php'); exit; }

if ($member['status'] !== 'active') { header('Location: dashboard.php'); exit; }

$remaining = $member['cv_allowance'] - $member['cv_used'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($member['expiry_date']) && strtotime($member['expiry_date']) <= time()) {
        $error = 'Votre abonnement est expiré. Renouvelez-le avant de demander un CV.';
    } elseif ($remaining <= 0) {
        $error = "Vous avez atteint votre limite mensuelle. Passez au pack supérieur ou attendez le prochain cycle.";
    } else {
        $mode           = $_POST['mode'] ?? 'link';   // 'link' or 'text'
        $jobUrl         = trim($_POST['job_url'] ?? '');
        $jobDescription = trim($_POST['job_description'] ?? '');
        $notes          = trim($_POST['notes'] ?? '');

        // Build stored values based on mode
        if ($mode === 'link') {
            $storedUrl   = $jobUrl;
            $storedNotes = $notes;
            if (empty($storedUrl)) {
                $error = "Veuillez fournir le lien de l'offre.";
            }
        } else {
            // Text mode — store sentinel in job_url, description in notes
            $storedUrl   = '[description]';
            $storedNotes = $jobDescription . ($notes ? "\n\n--- Notes supplémentaires ---\n" . $notes : '');
            if (empty($jobDescription)) {
                $error = "Veuillez coller la description du poste.";
            }
        }

        if (empty($error)) {
            // CV file upload
            $cvPath = '';
            if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === 0) {
                $cvPath = secure_upload($_FILES['cv_file'], ['pdf', 'doc', 'docx'], 'uploads/cv_base', 'BASE-') ?? '';
            }

            if (empty($cvPath)) {
                $error = "Veuillez uploader votre CV actuel (PDF ou Word).";
            } else {
                try {
                    $pdo->beginTransaction();
                    $upd = $pdo->prepare("UPDATE club_members SET cv_used = cv_used + 1 WHERE id = ? AND status = 'active' AND cv_used < cv_allowance AND (expiry_date IS NULL OR expiry_date > NOW())");
                    $upd->execute([$memberId]);
                    if ($upd->rowCount() !== 1) throw new RuntimeException('Quota or membership no longer valid');
                    $ins = $pdo->prepare("INSERT INTO club_cv_requests (member_id, job_url, current_cv_path, notes) VALUES (?, ?, ?, ?)");
                    $ins->execute([$memberId, $storedUrl, $cvPath, $storedNotes]);
                    $pdo->commit();
                    $_SESSION['success_msg'] = "Demande envoyée ! Notre équipe vous prépare un CV sous 48h.";
                    header('Location: dashboard.php');
                    exit;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    @unlink(__DIR__ . '/../' . $cvPath);
                    $error = "Demande impossible. Vérifiez votre abonnement et votre quota, puis réessayez.";
                }
            }
        }
    }
}

$firstName = explode(' ', $member['full_name'])[0];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle demande — HireMe Club</title>
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
        .mode-tab { padding:10px 20px; border-radius:12px; font-size:13px; font-weight:700; transition:all .2s; cursor:pointer; border:none; }
        .mode-tab.active { background:#0ea5e9; color:#fff; box-shadow:0 4px 14px rgba(14,165,233,.3); }
        .mode-tab:not(.active) { background:transparent; color:#94a3b8; }
        .mode-panel { display:none; }
        .mode-panel.visible { display:block; }
    </style>
    <script src="../assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="../assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="antialiased bg-slate-50 min-h-screen pb-10">

<!-- Top nav -->
<nav class="bg-white border-b border-slate-100 sticky top-0 z-30">
    <div class="max-w-2xl mx-auto px-5 h-16 flex items-center justify-between">
        <a href="dashboard.php" class="flex items-center gap-2 text-slate-500 hover:text-slate-900 transition-colors group">
            <i data-lucide="arrow-left" class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform"></i>
            <span class="text-sm font-semibold">Retour</span>
        </a>
        <span class="font-black text-slate-900 tracking-tight text-sm">HireMe<span class="text-sky-500">Club</span></span>
        <div class="flex items-center gap-1.5 bg-sky-50 border border-sky-100 rounded-full px-3 py-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
            <span class="text-[11px] font-black text-sky-600"><?= $remaining ?> crédit<?= $remaining>1?'s':'' ?></span>
        </div>
    </div>
</nav>

<main class="max-w-2xl mx-auto px-4 py-8 md:py-12">

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight mb-1">Optimiser un CV</h1>
        <p class="text-slate-500 text-sm">Chaque offre mérite une version sur-mesure, <?= htmlspecialchars($firstName) ?>.</p>
    </div>

    <?php if ($remaining <= 0): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center">
        <i data-lucide="alert-triangle" class="w-8 h-8 text-amber-500 mx-auto mb-3"></i>
        <p class="font-black text-slate-900 mb-2">Quota mensuel atteint</p>
        <p class="text-slate-500 text-sm mb-4">Vos crédits se renouvellent au prochain cycle ou passez à un pack supérieur.</p>
        <a href="https://wa.me/237654250688?text=Bonjour+je+veux+upgrader+mon+pack+HireMe+Club"
           target="_blank"
           class="inline-block px-6 py-3 bg-sky-500 text-white font-black rounded-xl text-sm uppercase tracking-wide hover:bg-sky-600 transition-all">
            Contacter le support
        </a>
    </div>
    <?php else: ?>

    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-100 rounded-2xl flex items-start gap-3">
        <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5"></i>
        <p class="text-red-600 text-sm font-medium"><?= htmlspecialchars($error) ?></p>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-5">
        <input type="hidden" name="mode" id="modeInput" value="link">

        <!-- Mode toggle -->
        <div class="bg-white border border-slate-100 rounded-2xl p-1.5 flex gap-1 shadow-sm">
            <button type="button" id="tabLink" onclick="setMode('link')" class="mode-tab active flex-1 flex items-center justify-center gap-2">
                <i data-lucide="link" class="w-4 h-4"></i>
                J'ai un lien d'offre
            </button>
            <button type="button" id="tabText" onclick="setMode('text')" class="mode-tab flex-1 flex items-center justify-center gap-2">
                <i data-lucide="align-left" class="w-4 h-4"></i>
                Je n'ai pas de lien
            </button>
        </div>

        <!-- MODE: Link -->
        <div id="panelLink" class="mode-panel visible bg-white border border-slate-100 rounded-2xl p-5 md:p-6 shadow-sm">
            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">
                Lien de l'offre d'emploi *
            </label>
            <input type="url" name="job_url" id="jobUrlInput"
                   placeholder="https://linkedin.com/jobs/view/... ou Indeed, JobBenin, etc."
                   class="field">
            <p class="text-[11px] text-slate-400 font-medium mt-2 pl-1">
                Copiez le lien depuis LinkedIn, Indeed, le site de l'entreprise, etc.
            </p>
        </div>

        <!-- MODE: Text description -->
        <div id="panelText" class="mode-panel bg-white border border-slate-100 rounded-2xl p-5 md:p-6 shadow-sm">
            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">
                Description du poste *
            </label>
            <textarea name="job_description" rows="8"
                      placeholder="Collez ici la description complète du poste (titre, missions, compétences requises, entreprise, etc.)..."
                      class="field resize-none leading-relaxed"></textarea>
            <p class="text-[11px] text-slate-400 font-medium mt-2 pl-1">
                Plus vous donnez de détails, meilleur sera votre CV optimisé.
            </p>
        </div>

        <!-- CV Upload -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 md:p-6 shadow-sm">
            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3 pl-1">
                Votre CV actuel *
                <span class="ml-2 normal-case font-normal text-slate-300">(PDF, DOC, DOCX)</span>
            </label>
            <label class="block w-full border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center cursor-pointer hover:border-sky-400 hover:bg-sky-50/40 transition-all group">
                <input type="file" name="cv_file" required accept=".pdf,.doc,.docx" class="hidden" id="cvInput" onchange="showCvName(this)">
                <div id="cvPlaceholder">
                    <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:bg-sky-100 transition-colors">
                        <i data-lucide="upload-cloud" class="w-6 h-6 text-slate-400 group-hover:text-sky-500 transition-colors"></i>
                    </div>
                    <p class="text-sm font-bold text-slate-600">Cliquez pour uploader votre CV</p>
                    <p class="text-xs text-slate-400 mt-1">PDF ou Word — Max 10MB</p>
                </div>
                <div id="cvFileName" class="hidden items-center justify-center gap-2">
                    <div class="w-8 h-8 bg-emerald-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="check" class="w-4 h-4 text-emerald-600"></i>
                    </div>
                    <span class="text-sm font-bold text-emerald-700" id="cvNameText"></span>
                </div>
            </label>
        </div>

        <!-- Notes (optional) -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 md:p-6 shadow-sm">
            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">
                Notes supplémentaires <span class="normal-case font-normal">(optionnel)</span>
            </label>
            <textarea name="notes" rows="3"
                      placeholder="Ex : Je veux mettre en avant mon expérience en gestion de projet. Poste à Douala."
                      class="field resize-none"></textarea>
        </div>

        <!-- Submit -->
        <button type="submit"
                class="w-full py-4 bg-sky-500 text-white font-black rounded-2xl hover:bg-sky-600 transition-all shadow-lg shadow-sky-200 active:scale-95 text-sm uppercase tracking-wide flex items-center justify-center gap-2 touch-manipulation">
            <i data-lucide="send" class="w-4 h-4"></i>
            Envoyer ma demande
        </button>

        <p class="text-center text-xs text-slate-400 font-medium">
            Il vous reste <strong class="text-sky-500"><?= $remaining ?></strong> crédit<?= $remaining>1?'s':'' ?> ce mois · Livraison sous 48h
        </p>

    </form>
    <?php endif; ?>

</main>

<script>
lucide.createIcons();

function setMode(m) {
    document.getElementById('modeInput').value = m;

    const tabLink = document.getElementById('tabLink');
    const tabText = document.getElementById('tabText');
    const panLink = document.getElementById('panelLink');
    const panText = document.getElementById('panelText');
    const urlInput = document.getElementById('jobUrlInput');

    if (m === 'link') {
        tabLink.classList.add('active');
        tabText.classList.remove('active');
        panLink.classList.add('visible');
        panText.classList.remove('visible');
        if (urlInput) urlInput.required = true;
    } else {
        tabText.classList.add('active');
        tabLink.classList.remove('active');
        panText.classList.add('visible');
        panLink.classList.remove('visible');
        if (urlInput) urlInput.required = false;
    }
}

function showCvName(input) {
    if (input.files && input.files[0]) {
        document.getElementById('cvPlaceholder').classList.add('hidden');
        const fn = document.getElementById('cvFileName');
        fn.classList.remove('hidden');
        fn.classList.add('flex');
        document.getElementById('cvNameText').textContent = input.files[0].name;
        lucide.createIcons();
    }
}
</script>
</body>
</html>
