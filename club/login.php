<?php
require_once '../admin/config/db.php';
require_once __DIR__ . '/../includes/upload_helper.php';
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();

if (isset($_SESSION['club_member_id'])) {
    header('Location: dashboard.php'); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $whatsapp = trim($_POST['whatsapp']);
    $password = $_POST['password'];

    $whatsappDigits = preg_replace('/[^0-9]/', '', $whatsapp);
    if (!str_starts_with($whatsappDigits, '237')) {
        $whatsappDigits = '237' . ltrim($whatsappDigits, '0');
    }

    if (!rate_limit_ok('club-login', 8, 900)) {
        http_response_code(429);
        $error = 'Trop de tentatives. Réessayez dans 15 minutes.';
    } elseif (empty($whatsapp) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM club_members WHERE whatsapp = ? OR whatsapp = ?");
            $stmt->execute([$whatsapp, $whatsappDigits]);
            $member = $stmt->fetch();
            if ($member && password_verify($password, $member['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['club_member_id']   = $member['id'];
                $_SESSION['club_member_name'] = $member['full_name'];
                header('Location: ' . ($member['status'] === 'pending_payment' ? 'payment.php' : 'dashboard.php'));
                exit;
            } else {
                $error = "Numéro WhatsApp ou mot de passe incorrect.";
            }
        } catch (PDOException $e) {
            $error = "Erreur de connexion. Veuillez réessayer.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — HireMe Club</title>
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
    </style>
    <script src="../assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="../assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="antialiased bg-slate-50 min-h-screen">

<!-- Full screen split layout on desktop -->
<div class="flex min-h-screen">

    <!-- LEFT: image (desktop only) -->
    <div class="hidden lg:flex lg:w-[45%] xl:w-[42%] flex-shrink-0 relative overflow-hidden">
        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=960&q=85"
             alt="Professionnelle camerounaise" class="absolute inset-0 w-full h-full object-cover object-top">
        <div class="absolute inset-0 bg-gradient-to-tr from-slate-900/80 via-slate-900/40 to-transparent"></div>
        <div class="relative z-10 p-10 xl:p-14 flex flex-col justify-between w-full">
            <a href="../abonnements.html" class="font-black text-white text-xl tracking-tight">
                HireMe<span class="text-sky-400">Club</span>
            </a>
            <div>
                <p class="text-slate-400 text-xs font-black uppercase tracking-widest mb-3">Bienvenue de retour</p>
                <h2 class="text-3xl xl:text-4xl font-black text-white leading-tight tracking-tight mb-4">
                    Votre prochain<br>entretien vous attend.
                </h2>
                <p class="text-slate-300 text-sm leading-relaxed">Connectez-vous pour accéder à vos CVs optimisés et soumettre de nouvelles demandes.</p>
            </div>
            <p class="text-xs text-slate-600 font-medium">&copy; 2026 HireMe Club Pro</p>
        </div>
    </div>

    <!-- RIGHT: form -->
    <div class="flex-1 flex flex-col justify-center items-center px-5 py-12 bg-white">
        <div class="w-full max-w-sm">

            <!-- Mobile logo -->
            <div class="lg:hidden text-center mb-10">
                <a href="../abonnements.html" class="font-black text-slate-900 text-2xl tracking-tight">
                    HireMe<span class="text-sky-500">Club</span>
                </a>
            </div>

            <!-- Back link (desktop) -->
            <a href="../abonnements.html" class="hidden lg:inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-slate-700 transition-colors mb-8 group">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform"></i>
                Retour aux offres
            </a>

            <h1 class="text-2xl md:text-3xl font-black text-slate-900 mb-1 tracking-tight">Connexion</h1>
            <p class="text-slate-500 text-sm mb-8">Accédez à votre espace membre.</p>

            <?php if ($error): ?>
            <div class="mb-5 p-4 bg-red-50 border border-red-100 rounded-2xl flex items-start gap-3">
                <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5"></i>
                <p class="text-red-600 text-sm font-medium"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">

                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Numéro WhatsApp</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-semibold select-none">+237</span>
                        <input type="tel" name="whatsapp" required placeholder="6XX XXX XXX"
                               class="field pl-[68px]" value="<?= htmlspecialchars($_POST['whatsapp'] ?? '') ?>" autocomplete="tel">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Mot de Passe</label>
                    <div class="relative">
                        <input type="password" name="password" id="pwd" required placeholder="••••••••" class="field pr-12" autocomplete="current-password">
                        <button type="button" onclick="togglePwd()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                            <i data-lucide="eye" class="w-4 h-4" id="pwd-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-4 bg-slate-900 text-white font-black rounded-2xl hover:bg-slate-800 transition-all shadow-lg active:scale-95 text-sm uppercase tracking-wide">
                        Me connecter →
                    </button>
                </div>
            </form>

            <div class="mt-6 space-y-3 text-center">
                <p class="text-sm text-slate-400">
                    Pas encore membre ?
                    <a href="../abonnements.html#tarifs" class="text-sky-500 font-bold hover:underline">Voir les offres</a>
                </p>
                <a href="https://wa.me/237654250688?text=Bonjour+j%27ai+oublié+mon+mot+de+passe+Club+HireMe"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-emerald-600 transition-colors font-medium">
                    <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                    Mot de passe oublié ? Contactez-nous
                </a>
            </div>
        </div>
    </div>
</div>

<script>
lucide.createIcons();
function togglePwd() {
    const i=document.getElementById('pwd'), ic=document.getElementById('pwd-icon');
    i.type = i.type==='password'?'text':'password';
    ic.setAttribute('data-lucide', i.type==='password'?'eye':'eye-off');
    lucide.createIcons();
}
</script>
</body>
</html>
