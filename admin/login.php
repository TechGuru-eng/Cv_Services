<?php
// admin/login.php
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();
require_once __DIR__ . '/../includes/upload_helper.php';
require_once 'config/db.php';
require_once 'includes/Security.php';
Security::init($pdo);
require_once 'includes/csrf.php';
require_once 'includes/activity_helper.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); // 1. Check CSRF

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // 2. Anti-Brute Force (Basic)
    if (!rate_limit_ok('admin-login', 8, 900)) {
        http_response_code(429);
        $error = 'Trop de tentatives. Réessayez dans 15 minutes.';
    } else {
    usleep(400000);

    // Verify admin
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true); // prevent session fixation
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin'] = true; // For compatibility
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_email'] = $admin['email'];
        log_activity("Connexion réussie", "L'administrateur " . $admin['email'] . " s'est connecté.");
        header('Location: index.php');
        exit;
    } else {
        if (class_exists('Security')) {
            Security::recordFailedLogin();
        }
        log_activity("Échec de connexion", "Tentative échouée avec l'email: " . htmlspecialchars($email));
        $error = "Identifiants incorrects.";
    }
    } // end rate-limit else
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin — HireMe</title>

    <!-- Favicons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .mesh-bg {
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 0% 0%, hsla(222,47%,11%,1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(217,91%,60%,0.2) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(222,47%,11%,1) 0, transparent 50%);
        }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(16px); }
    </style>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="../assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="mesh-bg min-h-screen flex items-center justify-center p-6 text-slate-200">

    <div class="w-full max-w-md animate-in fade-in zoom-in duration-500">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl shadow-lg shadow-blue-500/20 mb-6">
                <i data-lucide="shield-check" class="w-8 h-8 text-white"></i>
            </div>
            <h1 class="text-3xl font-black tracking-tight text-white mb-2">HireMe Admin</h1>
            <p class="text-slate-400 font-medium">Connexion sécurisée au panneau de gestion</p>
        </div>

        <div class="glass border border-white/10 rounded-[2.5rem] p-8 md:p-10 shadow-2xl">
            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-2xl mb-6 text-sm flex items-center gap-2 animate-shake">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <?php csrf_field(); ?>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Adresse Email</label>
                    <div class="relative group">
                        <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-blue-400 transition-colors"></i>
                        <input type="email" name="email" required class="w-full pl-12 pr-4 py-4 bg-slate-800/50 border-2 border-transparent focus:border-blue-500 focus:bg-slate-800 rounded-2xl outline-none transition-all placeholder-slate-600 text-white" placeholder="admin@hireme.com">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Mot de passe</label>
                    <div class="relative group">
                        <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-blue-400 transition-colors"></i>
                        <input type="password" name="password" required class="w-full pl-12 pr-4 py-4 bg-slate-800/50 border-2 border-transparent focus:border-blue-500 focus:bg-slate-800 rounded-2xl outline-none transition-all placeholder-slate-600 text-white" placeholder="••••••••">
                    </div>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white font-black py-5 rounded-2xl hover:bg-blue-500 hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-blue-500/20 flex items-center justify-center gap-2">
                    Lancer la session <i data-lucide="log-in" class="w-5 h-5"></i>
                </button>
            </form>
        </div>
        
        <p class="text-center mt-8 text-xs font-bold uppercase tracking-widest text-slate-600">
            HireMe CV Services &bull; Build with &hearts;
        </p>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
