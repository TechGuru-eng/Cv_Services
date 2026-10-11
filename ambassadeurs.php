<?php
session_start();
require_once 'admin/config/db.php';

$message = "";
$error = "";
$success = false;
$referralLink = "";

// LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ambassadeurs.php");
    exit;
}

// 1. Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    
    // Simple Cameroon phone validation (optional)
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if(strlen($phone) == 9 && substr($phone, 0, 1) == '6') $phone = '237' . $phone;

    if (strlen($password) < 4) {
        $error = "Le mot de passe doit contenir au moins 4 caractères.";
    } elseif (empty($phone)) {
        $error = "Le numéro de téléphone est requis.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $code = strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $name), 0, 4) . rand(100, 999));

        try {
            $stmt = $pdo->prepare("INSERT INTO affiliates (name, email, phone, password, referral_code) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $hashedPassword, $code]);
            
            $_SESSION['ambassador_id'] = $pdo->lastInsertId();
            $_SESSION['ambassador_code'] = $code;
            $_SESSION['ambassador_name'] = $name;
            header("Location: ambassadeurs.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Cet email ou ce téléphone est déjà enregistré.";
            } else {
                $error = "Une erreur est survenue : " . $e->getMessage();
            }
        }
    }
}

// 2. Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $emailOrPhone = trim($_POST['email_phone']);
    $password = $_POST['password'];

    // Normalize phone for login if it looks like one
    $normalizedPhone = preg_replace('/[^0-9]/', '', $emailOrPhone);
    if(strlen($normalizedPhone) == 9 && substr($normalizedPhone, 0, 1) == '6') $normalizedPhone = '237' . $normalizedPhone;

    $stmt = $pdo->prepare("SELECT * FROM affiliates WHERE email = ? OR phone = ? OR phone = ?");
    $stmt->execute([($emailOrPhone), ($emailOrPhone), $normalizedPhone]);
    $aff = $stmt->fetch();

    if ($aff && password_verify($password, $aff['password'])) {
        $_SESSION['ambassador_id'] = $aff['id'];
        $_SESSION['ambassador_code'] = $aff['referral_code'];
        $_SESSION['ambassador_name'] = $aff['name'];
        header("Location: ambassadeurs.php");
        exit;
    } else {
        $error = "Informations de connexion incorrectes.";
    }
}

// 3. Handle Withdrawal Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw'])) {
    $amount = floatval($_POST['amount']);
    $method = $_POST['method'];
    $phone = trim($_POST['payout_phone']);
    $affId = $_SESSION['ambassador_id'] ?? null;

    if (!$affId) {
         $error = "Session expirée.";
    } else {
        // Get current balance
        $stmt = $pdo->prepare("SELECT balance FROM affiliates WHERE id = ?");
        $stmt->execute([$affId]);
        $currentBalance = $stmt->fetchColumn();

        if ($amount < 500) {
            $error = "Le montant minimum de retrait est de 500 FCFA.";
        } elseif ($amount > $currentBalance) {
            $error = "Solde insuffisant.";
        } else {
            $pdo->beginTransaction();
            try {
                // Subtract from balance
                $stmt = $pdo->prepare("UPDATE affiliates SET balance = balance - ? WHERE id = ?");
                $stmt->execute([$amount, $affId]);

                // Create withdrawal entry
                $stmt = $pdo->prepare("INSERT INTO withdrawals (affiliate_id, amount, method, phone, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$affId, $amount, $method, $phone]);

                $pdo->commit();
                $message = "Votre demande de retrait a été envoyée avec succès.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }
}

// 4. Get Data if logged in
$stats = null;
$referrals = [];
$withdrawals = [];
$rankings = [];
$myRank = null;

if (isset($_SESSION['ambassador_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM affiliates WHERE id = ?");
    $stmt->execute([$_SESSION['ambassador_id']]);
    $stats = $stmt->fetch();
    $referralLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/index.html?ref=" . $_SESSION['ambassador_code'];

    // Get Sales (Referrals)
    $stmt = $pdo->prepare("SELECT r.*, o.name as client_name, o.payment_status FROM affiliate_referrals r 
                           JOIN orders o ON r.order_id = o.id 
                           WHERE r.affiliate_id = ? ORDER BY r.created_at DESC");
    $stmt->execute([$_SESSION['ambassador_id']]);
    $referrals = $stmt->fetchAll();

    // Get Withdrawals
    $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE affiliate_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['ambassador_id']]);
    $withdrawals = $stmt->fetchAll();

    // 5. Get Rankings (Top 10)
    $stmtRank = $pdo->query("SELECT name, total_earned FROM affiliates ORDER BY total_earned DESC LIMIT 10");
    $rankings = $stmtRank->fetchAll();

    // Find My Rank
    $stmtMyRank = $pdo->query("SELECT id, total_earned FROM affiliates ORDER BY total_earned DESC");
    $allAffs = $stmtMyRank->fetchAll();
    foreach ($allAffs as $index => $row) {
        if ($row['id'] == $_SESSION['ambassador_id']) {
            $myRank = $index + 1;
            break;
        }
    }


    // 6. Get Admin Message
    $adminMsg = $pdo->query("SELECT message FROM affiliate_messages WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1")->fetchColumn();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-0WXT4Q08R2"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-0WXT4Q08R2');
    </script>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-0WXT4Q08R2"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-0WXT4Q08R2');
    </script>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-0WXT4Q08R2"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-0WXT4Q08R2');
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambassadeurs HireMe – Espace Personnel</title>
    
    <!-- Meta Preview pour les réseaux sociaux -->
    <meta property="og:title" content="Devenez Ambassadeur HireMe – Gagnez du Cash">
    <meta property="og:description" content="Partagez votre lien et recevez 500 FCFA par recommandation. Le programme d'affiliation le plus simple au Cameroun.">
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/ambassadeur-preview-v1.png">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="https://moncvpro.hiremeguide.com/assets/ambassadeur-preview-v1.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        html, body { font-family: 'Outfit', sans-serif; overflow-x: hidden; width: 100%; position: relative; background: var(--bg); color: #0f172a; }
        
        /* $10k Mesh Background */
        .mesh-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2;
            background: radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.1) 0, transparent 50%),
                        radial-gradient(at 100% 0%, rgba(59, 130, 246, 0.1) 0, transparent 50%),
                        radial-gradient(at 50% 100%, rgba(147, 197, 253, 0.1) 0, transparent 50%);
            background-color: #ffffff;
        }
        .mesh-bg::after {
            content: ''; position: absolute; inset: 0;
            background: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
            opacity: 0.02; pointer-events: none;
        }

        .glass { background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); }
        .tab-btn { transition: all 0.5s cubic-bezier(0.2, 0.8, 0.2, 1); }
        .tab-btn.active { background: white; color: var(--primary); transform: translateY(-2px); box-shadow: 0 20px 40px -10px rgba(37, 99, 235, 0.2); }
        
        .auth-card { animation: entry 0.8s cubic-bezier(0.2, 0.8, 0.2, 1); }
        @keyframes entry { 
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .reveal { opacity: 0; transform: translateY(20px); transition: all 0.8s cubic-bezier(0.2, 0.8, 0.2, 1); }
        .reveal.active { opacity: 1; transform: translateY(0); }

        .stat-card { transition: all 0.4s cubic-bezier(0.2, 0.8, 0.2, 1); border: 1px solid transparent; }
        .stat-card:hover { transform: translateY(-8px) scale(1.02); border-color: rgba(37, 99, 235, 0.1); box-shadow: 0 40px 80px -20px rgba(37, 99, 235, 0.1); }
        
        .floating-blob {
            position: absolute; width: 40vw; height: 40vw; border-radius: 50%;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.08) 0%, transparent 70%);
            filter: blur(60px); z-index: -1; animation: float 25s infinite alternate ease-in-out;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg) scale(1); }
            33% { transform: translate(5vw, 10vh) rotate(120deg) scale(1.1); }
            66% { transform: translate(-5vw, 5vh) rotate(240deg) scale(0.9); }
            100% { transform: translate(0, 0) rotate(360deg) scale(1); }
        }

        .btn-shine {
            position: relative; overflow: hidden;
        }
        .btn-shine::after {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.2), transparent);
            transform: rotate(45deg); transition: 0.5s;
        }
        .btn-shine:hover::after { left: 100%; transition: 0.5s; }

        .no-scrollbar::-webkit-scrollbar { display: none; }
        
        /* Mobile Premium Fixes */
        @media (max-width: 640px) {
            .auth-card { border-radius: 2.5rem; padding: 1.5rem !important; }
            h1 { font-size: 2.2rem !important; }
            .p-14 { padding: 1.5rem !important; }
            .p-10 { padding: 1.25rem !important; }
            .rounded-\[4rem\] { border-radius: 2rem !important; }
            .rounded-\[3\.5rem\] { border-radius: 2rem !important; }
            .stat-card { padding: 1rem !important; border-radius: 1.5rem !important; }
            .stat-card p.text-2xl { font-size: 1.25rem !important; }
        }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="text-slate-900 min-h-screen flex flex-col">

    <div class="fixed inset-0 pointer-events-none overflow-hidden" style="z-index: -1;">
        <div class="mesh-bg"></div>
        <?php if(!$stats): ?>
        <div class="floating-blob" style="top: -10vh; left: -10vw;"></div>
        <div class="floating-blob" style="bottom: -10vh; right: -10vw; background: rgba(59, 130, 246, 0.1);"></div>
        <?php endif; ?>
    </div>

    <header class="p-6 md:p-10 flex justify-between items-center max-w-7xl mx-auto w-full relative z-10 reveal">
        <a href="index.html" class="flex items-center gap-3 group">
            <div class="bg-blue-600 p-2.5 rounded-2xl group-hover:rotate-[15deg] group-hover:scale-110 transition-all duration-500 shadow-lg shadow-blue-200">
                <i data-lucide="award" class="w-7 h-7 text-white"></i>
            </div>
            <div class="flex flex-col">
                <span class="text-2xl font-black text-slate-900 leading-none tracking-tighter">HireMe</span>
                <span class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] mt-1">Ambassadeurs</span>
            </div>
        </a>
        <?php if(isset($_SESSION['ambassador_id'])): ?>
            <div class="flex items-center gap-6">
                <div class="hidden md:flex flex-col items-end">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Espace de</span>
                    <span class="text-sm font-black text-slate-900"><?= htmlspecialchars($_SESSION['ambassador_name']) ?></span>
                </div>
                <a href="?logout=1" class="bg-slate-900 text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all duration-300 flex items-center gap-2 shadow-xl shadow-slate-200">
                    Déconnexion <i data-lucide="log-out" class="w-3 h-3"></i>
                </a>
            </div>
        <?php endif; ?>
    </header>

    <main class="max-w-6xl mx-auto p-4 md:p-10 w-full flex-1 flex flex-col items-center justify-center relative z-10">

        <?php if(!$stats): ?>
        <!-- SUPERIOR AUTH SECTION -->
        <div class="w-full max-w-2xl mb-24 text-center reveal">
            <div class="inline-flex items-center gap-2 bg-blue-50 text-blue-600 px-5 py-2 rounded-full text-[11px] font-black uppercase tracking-[0.15em] mb-8 animate-bounce shadow-sm">
                <i data-lucide="zap" class="w-4 h-4 fill-current"></i> Commissions Instantanées
            </div>
            <h1 class="text-6xl md:text-8xl font-black mb-8 tracking-tighter leading-[0.85] text-slate-900">
                Gagnez du <span class="bg-gradient-to-r from-blue-600 to-blue-400 bg-clip-text text-transparent">pognon</span> en partageant.
            </h1>
            <p class="text-slate-500 font-medium text-lg md:text-xl max-w-lg mx-auto leading-relaxed mb-12">
                Le programme d'affiliation le plus simple au Cameroun. 1 recommandation = 500 FCFA cash.
            </p>
            
            <div class="glass border-2 border-white/90 rounded-[3.5rem] shadow-[0_50px_100px_-20px_rgba(0,0,0,0.1)] overflow-hidden auth-card max-w-lg mx-auto">
                <div class="flex bg-slate-100/40 p-2.5 m-2.5 rounded-[2.5rem] relative">
                    <button onclick="toggleAuth('login')" id="toggle-login-btn" class="flex-1 py-4 text-[10px] font-black uppercase tracking-[0.2em] auth-toggle-btn active rounded-[2rem] z-10">Connexion</button>
                    <button onclick="toggleAuth('signup')" id="toggle-signup-btn" class="flex-1 py-4 text-[10px] font-black uppercase tracking-[0.2em] auth-toggle-btn rounded-[2rem] z-10">Inscription</button>
                </div>

                <div class="p-10 md:p-14">
                    
                    <?php if($error): ?>
                        <div class="bg-red-50 text-red-600 p-4 rounded-2xl mb-6 text-sm flex items-center gap-2 animate-shake">
                            <i data-lucide="alert-circle" class="w-4 h-4"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <!-- LOGIN FORM -->
                    <div id="login-form-container" class="space-y-6">
                        <form method="POST" class="space-y-5">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Email ou Téléphone</label>
                                <div class="relative group">
                                    <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-blue-500 transition-colors"></i>
                                    <input type="text" name="email_phone" required class="w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-2xl outline-none transition-all placeholder-slate-300" placeholder="6xx xxx xxx">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Mot de passe</label>
                                <div class="relative group">
                                    <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-blue-500 transition-colors"></i>
                                    <input type="password" name="password" required class="w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-2xl outline-none transition-all placeholder-slate-300" placeholder="••••••••">
                                </div>
                            </div>
                            <button type="submit" name="login" class="w-full bg-blue-600 text-white font-black py-5 rounded-2xl hover:bg-blue-700 hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-blue-200 flex items-center justify-center gap-2 btn-shine">
                                Accéder maintenant <i data-lucide="arrow-right" class="w-5 h-5"></i>
                            </button>
                        </form>
                    </div>

                    <!-- SIGNUP FORM -->
                    <div id="signup-form-container" class="hidden space-y-6">
                        <form method="POST" class="space-y-5">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nom Complet</label>
                                <input type="text" name="name" required class="w-full px-5 py-4 bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-2xl outline-none transition-all" placeholder="Jean Dupont">
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Email</label>
                                    <input type="email" name="email" required class="w-full px-5 py-4 bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-2xl outline-none transition-all" placeholder="jean@mail.com">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Téléphone</label>
                                    <input type="tel" name="phone" required class="w-full px-5 py-4 bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-2xl outline-none transition-all" placeholder="6xx xxx xxx">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Choisir un mot de passe</label>
                                <div class="relative group">
                                    <i data-lucide="shield-check" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-blue-500 transition-colors"></i>
                                    <input type="password" name="password" required class="w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-2xl outline-none transition-all placeholder-slate-300" placeholder="Minimum 4 caractères">
                                </div>
                            </div>
                            <button type="submit" name="signup" class="w-full bg-slate-900 text-white font-black py-5 rounded-2xl hover:bg-black hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-slate-200 btn-shine">
                                Activer mon compte <i data-lucide="sparkles" class="w-5 h-5 ml-2"></i>
                            </button>
                        </form>
                    </div>

                    <p class="text-center mt-8 text-xs text-slate-400 font-medium">
                        Besoin d'aide ? <a href="https://wa.me/237654250688" class="text-blue-500 font-bold hover:underline">Contactez le support</a>
                    </p>
                </div>
            </div>

            <!-- Trust Bar -->
            <div class="mt-20 flex flex-wrap justify-center gap-12 reveal">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-14 h-14 rounded-2xl bg-white shadow-xl shadow-green-100 flex items-center justify-center text-green-500">
                        <i data-lucide="check-circle-2" class="w-8 h-8"></i>
                    </div>
                    <span class="font-black text-[9px] uppercase tracking-widest text-slate-400">Paiement 24h</span>
                </div>
                <div class="flex flex-col items-center gap-3">
                    <div class="w-14 h-14 rounded-2xl bg-white shadow-xl shadow-blue-100 flex items-center justify-center text-blue-500">
                        <i data-lucide="shield-check" class="w-8 h-8"></i>
                    </div>
                    <span class="font-black text-[9px] uppercase tracking-widest text-slate-400">100% Vérifié</span>
                </div>
                <div class="flex flex-col items-center gap-3">
                    <div class="w-14 h-14 rounded-2xl bg-white shadow-xl shadow-yellow-100 flex items-center justify-center text-yellow-500">
                        <i data-lucide="rocket" class="w-8 h-8"></i>
                    </div>
                    <span class="font-black text-[9px] uppercase tracking-widest text-slate-400">Setup instantané</span>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- DASHBOARD SECTION -->
        <div class="py-6 w-full animate-in fade-in slide-in-from-top-4 duration-700">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
                <div>
                    <h1 class="text-4xl font-black tracking-tight text-slate-900 leading-none mb-2">Salut, <?= htmlspecialchars($stats['name']) ?> ! 👋</h1>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400 text-sm font-medium italic">Parrainage :</span>
                        <code class="bg-blue-50 text-blue-600 px-3 py-1 rounded-lg font-black text-xs tracking-widest"><?= $stats['referral_code'] ?></code>
                    </div>
                </div>
                <div class="bg-white/80 backdrop-blur p-2 pr-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-500 rounded-xl flex items-center justify-center">
                        <i data-lucide="activity" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none">Statut du compte</p>
                        <p class="text-xs font-black text-green-600 uppercase">Premium Ambassadeur</p>
                    </div>
                </div>
                </div>
            </div>

            <?php if(isset($adminMsg) && $adminMsg): ?>
            <div class="mb-10 bg-gradient-to-r from-indigo-500 to-purple-600 text-white p-5 md:p-8 rounded-[2rem] shadow-lg shadow-indigo-300 flex flex-col md:flex-row items-start gap-4 transform transition-transform">
                <div class="bg-white/20 p-3 rounded-xl backdrop-blur-sm shrink-0">
                    <i data-lucide="megaphone" class="w-6 h-6 text-white"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-black text-[10px] uppercase tracking-widest text-indigo-100 mb-2">Message de l'Admin</p>
                    <?php 
                        $escapedMsg = htmlspecialchars($adminMsg);
                        // Auto-link URLs
                        $linkedMsg = preg_replace(
                            '/\b(?:(http(s?):\/\/)|(?=www\.))(\S+)/is',
                            '<a href="http$2://$3" target="_blank" class="underline text-yellow-300 font-black hover:text-white transition-colors">$0</a>',
                            $escapedMsg
                        );
                    ?>
                    <div class="font-bold text-xs md:text-sm leading-relaxed break-words whitespace-pre-wrap"><?= $linkedMsg ?></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Stats Overview -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6 mb-10 md:mb-12">
                <div class="bg-white/70 p-4 md:p-6 rounded-[1.5rem] md:rounded-[2.5rem] shadow-sm border border-slate-100 stat-card reveal" style="transition-delay: 0.1s;">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 md:mb-2 opacity-60">Solde Cash</p>
                    <p class="text-xl md:text-4xl font-black text-blue-600 leading-none"><?= number_format($stats['balance'], 0, ',', ' ') ?> <span class="text-[10px]">FCFA</span></p>
                </div>
                <div class="bg-white/70 p-4 md:p-6 rounded-[1.5rem] md:rounded-[2.5rem] shadow-sm border border-slate-100 stat-card reveal" style="transition-delay: 0.2s;">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 md:mb-2 opacity-60">Gains Totaux</p>
                    <p class="text-xl md:text-4xl font-black text-slate-800 leading-none"><?= number_format($stats['total_earned'], 0, ',', ' ') ?> <span class="text-[10px]">FCFA</span></p>
                </div>
                <div class="bg-white/70 p-4 md:p-6 rounded-[1.5rem] md:rounded-[2.5rem] shadow-sm border border-slate-100 stat-card reveal" style="transition-delay: 0.3s;">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 md:mb-2 opacity-60">Ventes</p>
                    <p class="text-xl md:text-4xl font-black text-slate-800 leading-none"><?= count($referrals) ?></p>
                </div>
                <div class="bg-gradient-to-br from-blue-600 to-blue-800 p-4 md:p-6 rounded-[1.5rem] md:rounded-[2.5rem] shadow-2xl shadow-blue-200 text-white stat-card reveal" style="transition-delay: 0.4s;">
                    <p class="text-white/60 text-[9px] font-black uppercase tracking-widest mb-1 md:mb-2">Position</p>
                    <p class="text-xl md:text-4xl font-black leading-none">#<?= $myRank ?: '?' ?></p>
                </div>
            </div>

            <!-- Referral Link -->
            <div class="bg-slate-900 p-8 md:p-14 rounded-[4rem] text-white shadow-[0_40px_80px_-15px_rgba(0,0,0,0.3)] mb-16 overflow-hidden relative group reveal">
                <div class="relative z-10 max-w-xl">
                    <div class="inline-flex items-center gap-2 bg-blue-500/20 text-blue-400 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest mb-6">
                        <i data-lucide="link" class="w-3 h-3"></i> Votre lien exclusif
                    </div>
                    <h2 class="text-4xl md:text-5xl font-black mb-4 tracking-tighter leading-none text-white">Prêt à encaisser ? ✨</h2>
                    <p class="text-slate-400 text-base md:text-lg mb-10 font-medium leading-relaxed">
                        Chaque vente générée via ce lien déclenche un virement automatique de <span class="text-white font-black underline decoration-blue-500 decoration-4 underline-offset-4">500 FCFA</span> sur votre compte.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <input type="text" id="refLink" readonly value="<?= $referralLink ?>" class="flex-1 p-5 bg-white/5 border border-white/10 rounded-[2rem] text-white font-semibold focus:outline-none focus:ring-4 focus:ring-blue-500/20 transition-all text-sm md:text-base">
                        <button onclick="copyLink()" class="bg-blue-600 text-white font-black px-12 py-5 rounded-[2rem] hover:bg-blue-700 active:scale-95 transition-all flex items-center justify-center gap-3 whitespace-nowrap shadow-2xl shadow-blue-900/50 btn-shine">
                            <i data-lucide="copy" class="w-5 h-5"></i> Copier le lien
                        </button>
                    </div>
                </div>
                <!-- Abstract decorations -->
                <div class="absolute -right-20 -top-20 w-[30rem] h-[30rem] bg-blue-600/20 rounded-full blur-[120px] group-hover:bg-blue-600/30 transition-all duration-1000"></div>
                <i data-lucide="sparkles" class="absolute top-10 right-10 w-24 h-24 text-white/[0.03] animate-pulse"></i>
            </div>

            <!-- Tabs Integration -->
            <div class="bg-slate-100/70 p-1.5 rounded-3xl grid grid-cols-2 md:flex gap-1 mb-8">
                <button onclick="switchTab('sales')" id="btn-sales" class="tab-btn active flex-1 py-3 px-4 md:py-4 md:px-6 rounded-2xl md:rounded-xl font-bold text-xs md:text-sm transition-all flex items-center justify-center gap-2">
                    <i data-lucide="trending-up" class="w-4 h-4"></i> Mes Ventes
                </button>
                <button onclick="switchTab('rankings')" id="btn-rankings" class="tab-btn flex-1 py-3 px-4 md:py-4 md:px-6 rounded-2xl md:rounded-xl font-bold text-xs md:text-sm transition-all flex items-center justify-center gap-2">
                    <i data-lucide="trophy" class="w-4 h-4"></i> Classement
                </button>
                <button onclick="switchTab('withdraw')" id="btn-withdraw" class="tab-btn flex-1 py-3 px-4 md:py-4 md:px-6 rounded-2xl md:rounded-xl font-bold text-xs md:text-sm transition-all flex items-center justify-center gap-2">
                    <i data-lucide="wallet" class="w-4 h-4"></i> Retrait
                </button>
                <button onclick="switchTab('history')" id="btn-history" class="tab-btn flex-1 py-3 px-4 md:py-4 md:px-6 rounded-2xl md:rounded-xl font-bold text-xs md:text-sm transition-all flex items-center justify-center gap-2">
                    <i data-lucide="clock" class="w-4 h-4"></i> Historique
                </button>
            </div>

            <!-- TAB: SALES -->
            <div id="tab-sales" class="tab-content">
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden animate-in fade-in duration-300">
                    <div class="p-6 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                        <h3 class="font-bold flex items-center gap-2"><i data-lucide="users" class="w-5 h-5 text-blue-500"></i> Clients recommandés</h3>
                        <span class="text-[10px] font-black uppercase text-slate-400"><?= count($referrals) ?> au total</span>
                    </div>
                    <?php if(empty($referrals)): ?>
                        <div class="p-20 text-center text-slate-400 italic">
                            <div class="bg-slate-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 grayscale">
                                <i data-lucide="shopping-bag" class="w-8 h-8 opacity-20"></i>
                            </div>
                            <p class="font-medium">Vous n'avez pas encore de ventes.</p>
                            <p class="text-xs mt-1">Partagez votre lien pour commencer !</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50/50 text-[10px] text-slate-400 uppercase font-black">
                                    <tr>
                                        <th class="p-5">Désignation</th>
                                        <th class="p-5">Date</th>
                                        <th class="p-5">Statut</th>
                                        <th class="p-5 text-right">Commission</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 text-sm">
                                    <?php foreach($referrals as $r): ?>
                                    <tr class="hover:bg-blue-50/30 transition-colors">
                                        <td class="p-5 font-bold text-slate-800"><?= htmlspecialchars($r['client_name']) ?></td>
                                        <td class="p-5 text-slate-500"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                                        <td class="p-5">
                                            <?php if($r['payment_status'] == 'verified'): ?>
                                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter">Payé</span>
                                            <?php else: ?>
                                                <span class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter">Attente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-5 text-right font-black text-blue-600">+500 <span class="text-[10px]">XAF</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TAB: RANKINGS -->
            <div id="tab-rankings" class="tab-content hidden">
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden animate-in fade-in duration-300">
                    <div class="p-6 border-b border-slate-50 bg-gradient-to-r from-blue-50 to-transparent flex justify-between items-center">
                        <h3 class="font-bold flex items-center gap-2"><i data-lucide="trophy" class="w-5 h-5 text-yellow-500"></i> Top Ambassadeurs</h3>
                        <span class="text-[10px] font-black uppercase text-slate-400">Classement en temps réel</span>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach($rankings as $index => $r): 
                                $isMe = ($r['name'] === $stats['name']);
                                $nameParts = explode(' ', trim($r['name']));
                                $firstName = $nameParts[0];
                                $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
                                $obscuredName = mb_substr($firstName, 0, 1) . '***' . ($lastName ? ' ' . mb_substr($lastName, 0, 1) . '***' : '');
                                if ($isMe) $obscuredName = $r['name'] . " (Vous)";
                            ?>
                            <div class="flex items-center justify-between p-4 rounded-2xl <?= $isMe ? 'bg-blue-50 border-2 border-blue-200' : 'bg-slate-50' ?>">
                                <div class="flex items-center gap-4">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-black <?= $index < 3 ? 'bg-yellow-400 text-white' : 'bg-slate-200 text-slate-500' ?>">
                                        <?= $index + 1 ?>
                                    </div>
                                    <span class="font-bold <?= $isMe ? 'text-blue-700' : 'text-slate-700' ?>"><?= $isMe ? htmlspecialchars($r['name']) : htmlspecialchars($obscuredName) ?></span>
                                </div>
                                <div class="text-right">
                                    <span class="block font-black text-slate-900"><?= number_format($r['total_earned'], 0, ',', ' ') ?> <span class="text-[10px] text-slate-400">FCFA</span></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-8 p-6 bg-blue-600 rounded-3xl text-white text-center relative overflow-hidden">
                            <i data-lucide="rocket" class="absolute -left-4 -bottom-4 w-20 h-20 text-white/10 -rotate-12"></i>
                            <h4 class="text-lg font-black mb-1">Envie de monter sur le podium ?</h4>
                            <p class="text-blue-100 text-sm mb-4">Partagez plus votre lien et multipliez vos gains !</p>
                            <button onclick="switchTab('sales')" class="bg-white text-blue-600 px-6 py-2 rounded-full font-black text-xs hover:scale-105 transition-transform">Copier mon lien</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: WITHDRAW -->
            <div id="tab-withdraw" class="tab-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                    <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 reveal">
                        <h3 class="text-xl font-black mb-8 flex items-center gap-3">
                            <div class="bg-blue-600 text-white w-10 h-10 rounded-2xl flex items-center justify-center">
                                <i data-lucide="send" class="w-5 h-5"></i>
                            </div>
                            Demande de Paiement
                        </h3>
                        
                        <?php if($message): ?>
                            <div class="bg-green-50 text-green-600 p-4 rounded-2xl mb-6 text-sm flex items-center gap-3">
                                <i data-lucide="check-circle" class="w-5 h-5"></i> 
                                <span class="font-semibold"><?= $message ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Montant à retirer (FCFA)</label>
                                <input type="number" name="amount" min="500" step="500" required value="<?= $stats['balance'] >= 500 ? floor($stats['balance']/500)*500 : 500 ?>" class="w-full p-5 bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-2xl outline-none font-black text-2xl transition-all">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Méthode préférée</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="method" value="MTN" checked class="hidden peer">
                                        <div class="p-4 rounded-2xl bg-slate-50 border-2 border-transparent peer-checked:border-orange-400 peer-checked:bg-orange-50 text-center flex flex-col items-center gap-1 transition-all">
                                            <span class="font-black text-sm">MTN Money</span>
                                            <span class="text-[9px] uppercase font-bold text-slate-400">Jaune</span>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="method" value="Orange" class="hidden peer">
                                        <div class="p-4 rounded-2xl bg-slate-50 border-2 border-transparent peer-checked:border-orange-600 peer-checked:bg-orange-50 text-center flex flex-col items-center gap-1 transition-all">
                                            <span class="font-black text-sm">Orange Money</span>
                                            <span class="text-[9px] uppercase font-bold text-slate-400">Orange</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Confirmer votre numéro MOMO</label>
                                <input type="tel" name="payout_phone" required value="<?= $stats['phone'] ?>" class="w-full p-5 bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-2xl outline-none font-bold transition-all">
                            </div>
                            <button type="submit" name="withdraw" class="w-full bg-blue-600 text-white font-black py-5 rounded-2xl hover:bg-blue-700 hover:scale-[1.01] active:scale-95 transition-all shadow-xl shadow-blue-100 disabled:opacity-50 disabled:cursor-not-allowed group" <?= $stats['balance'] < 500 ? 'disabled' : '' ?>>
                                <span class="flex items-center justify-center gap-2">Recevoir mon argent <i data-lucide="sparkles" class="w-5 h-5 group-hover:animate-spin"></i></span>
                            </button>
                            <?php if($stats['balance'] < 500): ?>
                                <p class="text-[10px] text-center text-red-500 font-bold italic bg-red-50 p-3 rounded-xl border border-red-100">Solde insuffisant (Min: 500 FCFA)</p>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-blue-600 p-8 rounded-[2rem] text-white shadow-xl relative overflow-hidden group">
                            <h4 class="font-black text-xl mb-4 flex items-center gap-2"><i data-lucide="shield-check" class="w-6 h-6 text-blue-200"></i> Zone de Confiance</h4>
                            <ul class="space-y-4 text-sm text-blue-100 font-medium">
                                <li class="flex items-start gap-3">
                                    <div class="bg-white/20 w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 text-[10px] font-black">1</div>
                                    <span>Traitement ultra-rapide sous <strong>24h Max</strong>.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="bg-white/20 w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 text-[10px] font-black">2</div>
                                    <span>Paiement direct sur <strong>Momo/OM</strong> sans frais.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="bg-white/20 w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 text-[10px] font-black">3</div>
                                    <span>Assistance VIP via WhatsApp en cas de souci.</span>
                                </li>
                            </ul>
                            <i data-lucide="crown" class="absolute -right-4 -bottom-4 w-24 h-24 text-white/5 rotate-12 group-hover:rotate-0 transition-transform duration-500"></i>
                        </div>
                        
                        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 flex items-center gap-4">
                            <div class="bg-slate-100 w-12 h-12 rounded-2xl flex items-center justify-center text-slate-400">
                                <i data-lucide="history" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <p class="font-black text-slate-800">Précision</p>
                                <p class="text-xs text-slate-500 font-medium">Vérifiez bien votre numéro avant d'envoyer.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: HISTORY -->
            <div id="tab-history" class="tab-content hidden">
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden animate-in fade-in duration-300">
                    <div class="p-6 border-b border-slate-50 bg-slate-50/50">
                        <h3 class="font-black flex items-center gap-2"><i data-lucide="history" class="w-5 h-5 text-blue-500"></i> Journal des Retraits</h3>
                    </div>
                    <?php if(empty($withdrawals)): ?>
                        <div class="p-20 text-center text-slate-400 italic">
                            <i data-lucide="layers" class="w-12 h-12 mx-auto mb-4 opacity-10"></i>
                            Vous n'avez pas encore demandé de retrait.
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50/50 text-[10px] text-slate-400 uppercase font-black">
                                    <tr>
                                        <th class="p-5">Montant</th>
                                        <th class="p-5">Date</th>
                                        <th class="p-5">Méthode</th>
                                        <th class="p-5 text-right">Statut</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 text-sm font-medium">
                                    <?php foreach($withdrawals as $w): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="p-5 font-black text-slate-800"><?= number_format($w['amount'], 0, ',', ' ') ?> FCFA</td>
                                        <td class="p-5 text-slate-500 text-xs"><?= date('d/m/Y H:i', strtotime($w['created_at'])) ?></td>
                                        <td class="p-5"><span class="bg-slate-100 px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-tighter"><?= $w['method'] ?></span></td>
                                        <td class="p-5 text-right">
                                            <?php if($w['status'] == 'pending'): ?>
                                                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter flex items-center justify-end gap-1">
                                                    Attente <span class="w-1 h-1 bg-blue-500 rounded-full animate-ping"></span>
                                                </span>
                                            <?php elseif($w['status'] == 'approved'): ?>
                                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter">Réalisé ✨</span>
                                            <?php else: ?>
                                                <span class="bg-red-50 text-red-600 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter">Annulé</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <footer class="py-10 text-center text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-auto">
        <p>&copy; <?= date('Y') ?> HireMe CV Services &bull; L'excellence pour tous</p>
    </footer>

    <script>
        lucide.createIcons();

        // High-end Reveal Logic
        function reveal() {
            var reveals = document.querySelectorAll(".reveal");
            for (var i = 0; i < reveals.length; i++) {
                var windowHeight = window.innerHeight;
                var elementTop = reveals[i].getBoundingClientRect().top;
                var elementVisible = 150;
                if (elementTop < windowHeight - elementVisible) {
                    reveals[i].classList.add("active");
                }
            }
        }
        window.addEventListener("scroll", reveal);
        window.addEventListener("load", () => {
            setTimeout(reveal, 100);
        });

        function toggleAuth(type) {
            const loginBtn = document.getElementById('toggle-login-btn');
            const signupBtn = document.getElementById('toggle-signup-btn');
            const loginForm = document.getElementById('login-form-container');
            const signupForm = document.getElementById('signup-form-container');

            if (type === 'login') {
                loginBtn.classList.add('active');
                signupBtn.classList.remove('active');
                loginForm.classList.remove('hidden');
                signupForm.classList.add('hidden');
            } else {
                signupBtn.classList.add('active');
                loginBtn.classList.remove('active');
                signupForm.classList.remove('hidden');
                loginForm.classList.add('hidden');
            }
        }

        function copyLink() {
            var copyText = document.getElementById("refLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");
            
            const btn = event.currentTarget;
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="check" class="w-5 h-5"></i> Copié !';
            btn.classList.replace('bg-blue-600', 'bg-green-500');
            lucide.createIcons();
            
            // Add a temporary glow effect
            copyText.classList.add('ring-4', 'ring-green-500/20');

            setTimeout(() => {
                btn.innerHTML = originalContent;
                btn.classList.replace('bg-green-500', 'bg-blue-600');
                copyText.classList.remove('ring-4', 'ring-green-500/20');
                lucide.createIcons();
            }, 2000);
        }

        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-' + tabId).classList.remove('hidden');
            document.getElementById('btn-' + tabId).classList.add('active');
            lucide.createIcons();
            // Trigger reveal for new content immersion
            setTimeout(reveal, 50); 
        }

        <?php if(isset($_POST['signup']) && $error): ?>
            toggleAuth('signup');
        <?php endif; ?>
    </script>

</body>
</html>
