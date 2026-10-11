<?php
session_start();
require_once 'admin/config/db.php';

// Helper function to generate clean slugs (for double checking)
function slugifyName($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text);
}

// Get the requested name slug
$requestedSlug = trim($_GET['name'] ?? $_GET['slug'] ?? '');
if (empty($requestedSlug)) {
    die("Profil introuvable ou lien incorrect.");
}

$candidate = null;
$type = ''; // 'order', 'pack', 'sponsorship'

// Parse parts of the slug
$slugParts = array_filter(explode('-', strtolower($requestedSlug)));

if (!empty($slugParts)) {
    // 0. Check for EXACT digital CV slug
    $stmtDigital = $pdo->prepare("SELECT id FROM cv_builder_leads WHERE slug = ? LIMIT 1");
    $stmtDigital->execute([$requestedSlug]);
    if ($stmtDigital->fetch()) {
        require 'view-digital-cv.php';
        exit;
    }

    // 1. Search in orders table (Standard Paid CVs)
    $firstPart = reset($slugParts);
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE name LIKE ? ORDER BY id DESC");
    $stmt->execute(['%' . $firstPart . '%']);
    $ordersList = $stmt->fetchAll();
    
    foreach ($ordersList as $ord) {
        $storedParts = array_filter(explode('-', slugifyName($ord['name'])));
        $allMatched = true;
        foreach ($slugParts as $part) {
            if (!in_array($part, $storedParts)) {
                $allMatched = false;
                break;
            }
        }
        if ($allMatched && !empty($ord['completed_cv_path']) && $ord['payment_status'] === 'verified') {
            $candidate = [
                'name' => $ord['name'],
                'email' => $ord['email'],
                'phone' => $ord['whatsapp'], // WhatsApp serves as direct phone contact
                'job_title' => $ord['job_title'] ?? $ord['package_name'] ?? 'Candidat Premium',
                'skills' => $ord['skills'] ?? '',
                'cv_path' => $ord['completed_cv_path'],
                'clean_draft_cv_path' => $ord['clean_draft_cv_path'] ?? null,
                'type' => 'order'
            ];
            $type = 'order';
            break;
        }
    }
    
    // 2. If not found, search in sponsorship_requests table
    if (!$candidate) {
        $stmtSpons = $pdo->prepare("SELECT * FROM sponsorship_requests WHERE name LIKE ? AND status = 'approved' ORDER BY id DESC");
        $stmtSpons->execute(['%' . $firstPart . '%']);
        $sponsList = $stmtSpons->fetchAll();
        
        foreach ($sponsList as $spons) {
            $storedParts = array_filter(explode('-', slugifyName($spons['name'])));
            $allMatched = true;
            foreach ($slugParts as $part) {
                if (!in_array($part, $storedParts)) {
                    $allMatched = false;
                    break;
                }
            }
            if ($allMatched && !empty($spons['recovery_link'])) {
                $candidate = [
                    'name' => $spons['name'],
                    'email' => $spons['email'],
                    'phone' => $spons['whatsapp'],
                    'job_title' => 'Lauréat Programme HireMe',
                    'skills' => '',
                    'cv_path' => $spons['recovery_link'],
                    'type' => 'sponsorship'
                ];
                $type = 'sponsorship';
                break;
            }
        }
    }
    
    // 3. If not found, search in cv_packs table
    if (!$candidate) {
        $stmtPack = $pdo->prepare("SELECT * FROM cv_packs WHERE full_name LIKE ? AND status = 'verified' ORDER BY id DESC");
        $stmtPack->execute(['%' . $firstPart . '%']);
        $packsList = $stmtPack->fetchAll();
        
        foreach ($packsList as $pack) {
            $storedParts = array_filter(explode('-', slugifyName($pack['full_name'])));
            $allMatched = true;
            foreach ($slugParts as $part) {
                if (!in_array($part, $storedParts)) {
                    $allMatched = false;
                    break;
                }
            }
            if ($allMatched && !empty($pack['pack_link'])) {
                $candidate = [
                    'name' => $pack['full_name'],
                    'email' => $pack['email'],
                    'phone' => '', // Packs table doesn't store phone, fall back elegantly
                    'job_title' => 'Client Premium MonCvPro',
                    'skills' => '',
                    'cv_path' => $pack['pack_link'],
                    'type' => 'pack'
                ];
                $type = 'pack';
                break;
            }
        }
    }
}

if (!$candidate) {
    die("Le profil demandé n'est pas encore disponible en ligne ou a expiré.");
}

$displayName = htmlspecialchars($candidate['name']);
$displayEmail = htmlspecialchars($candidate['email']);
$displayPhone = htmlspecialchars($candidate['phone']);
$displayJob = htmlspecialchars($candidate['job_title']);
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$dir = str_replace('\\', '/', dirname($scriptName));
if ($dir !== '/') {
    $dir = rtrim($dir, '/');
} else {
    $dir = '';
}
$baseUrl = $protocol . "://" . $host . $dir . "/";

$rawCvPath = $candidate['cv_path'];

// Dynamic override for mobile devices to prioritize image format display
$isMobile = false;
$ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $ua)) {
    $isMobile = true;
}

if ($isMobile && !empty($candidate['clean_draft_cv_path'])) {
    $rawCvPath = $candidate['clean_draft_cv_path'];
}

if (!preg_match('/^https?:\/\//i', $rawCvPath)) {
    $displayCv = $baseUrl . $rawCvPath;
} else {
    $displayCv = $rawCvPath;
}
$cvExt = strtolower(pathinfo($rawCvPath, PATHINFO_EXTENSION));


// Parse skills into an array
$skillsArr = [];
if (!empty($candidate['skills'])) {
    $skillsArr = array_filter(array_map('trim', explode(',', $candidate['skills'])));
}

$avatarInitials = '';
$nameParts = explode(' ', trim($candidate['name']));
if (count($nameParts) >= 2) {
    $avatarInitials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
} else {
    $avatarInitials = strtoupper(substr($nameParts[0], 0, 2));
}
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CV en Ligne Interactif — <?= $displayName ?></title>
    
    <!-- Professional SEO Metas -->
    <meta name="description" content="Découvrez le profil professionnel et le CV premium finalisé de <?= $displayName ?> sur HireMe.">
    <meta property="og:title" content="Portfolio de <?= $displayName ?> — HireMe" />
    <meta property="og:description" content="Consultez le CV rédigé stratégiquement et contactez ce candidat en un clic." />
    <meta property="og:type" content="profile" />
    <meta name="theme-color" content="#0ea5e9">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
                        title: ['Outfit', 'sans-serif']
                    },
                    colors: {
                        primary: '#0ea5e9',
                        dark: '#0f172a',
                        cardBg: 'rgba(255, 255, 255, 0.85)'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #bae6fd 100%);
            min-height: 100vh;
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .pdf-container iframe {
            border: none;
            width: 100%;
            height: 100%;
        }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="antialiased text-slate-800 p-3 sm:p-6 flex items-center justify-center min-h-screen">
    
    <div class="max-w-5xl w-full flex flex-col gap-6 lg:flex-row items-stretch">
        
        <!-- Sidebar : Candidate Profil Info -->
        <div class="w-full lg:w-96 glass-panel rounded-3xl p-6 sm:p-8 flex flex-col justify-between shadow-2xl relative overflow-hidden transition-all duration-300 transform hover:scale-[1.01]">
            <!-- Decorative circle -->
            <div class="absolute -right-12 -top-12 w-28 h-28 bg-sky-300/20 rounded-full blur-xl pointer-events-none"></div>
            
            <div class="space-y-6 relative">
                <!-- Platform Brand Header -->
                <div class="flex items-center gap-2 border-b border-sky-100/50 pb-4">
                    <span class="bg-primary text-white text-xs font-black px-2.5 py-1 rounded-full uppercase tracking-widest font-title shadow-sm shadow-primary/20">
                        HireMe CV
                    </span>
                    <span class="text-xs font-bold text-sky-600/80">Profil Certifié</span>
                </div>

                <!-- Avatar & Core Info -->
                <div class="text-center space-y-3">
                    <div class="w-24 h-24 bg-gradient-to-tr from-sky-400 to-sky-600 rounded-2xl flex items-center justify-center mx-auto text-3xl font-black text-white shadow-xl shadow-sky-500/10 font-title">
                        <?= $avatarInitials ?>
                    </div>
                    <div class="space-y-1">
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight font-title leading-tight">
                            <?= $displayName ?>
                        </h1>
                        <p class="text-sm font-bold text-sky-600 bg-sky-50 py-1 px-3 rounded-full inline-block">
                            <?= $displayJob ?>
                        </p>
                    </div>
                </div>

                <!-- Interactive Contact details -->
                <div class="space-y-3 pt-2">
                    <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest">Coordonnées Rapides</h2>
                    
                    <?php if (!empty($displayPhone)): 
                        $rawPhone = preg_replace('/[^0-9]/', '', $displayPhone);
                        if (strlen($rawPhone) == 9 && substr($rawPhone, 0, 1) == '6') {
                            $rawPhone = '237' . $rawPhone;
                        }
                    ?>
                        <!-- Phone link -->
                        <a href="tel:<?= $displayPhone ?>" class="flex items-center gap-3 p-3 bg-white/50 hover:bg-white rounded-xl transition border border-slate-100 group">
                            <span class="w-8 h-8 rounded-lg bg-sky-50 text-primary flex items-center justify-center font-bold text-sm group-hover:bg-primary group-hover:text-white transition">
                                📞
                            </span>
                            <div class="text-left">
                                <p class="text-[9px] text-slate-400 uppercase font-black tracking-wider leading-none mb-0.5">Téléphone</p>
                                <p class="text-xs font-bold text-slate-700 font-title"><?= $displayPhone ?></p>
                            </div>
                        </a>

                        <!-- WhatsApp chat link -->
                        <a href="https://wa.me/<?= $rawPhone ?>?text=<?= urlencode("Bonjour $displayName, j'ai consulté votre profil sur HireMe et souhaite échanger avec vous concernant une opportunité professionnelle.") ?>" target="_blank" class="flex items-center gap-3 p-3 bg-[#e8f5e9]/50 hover:bg-[#e8f5e9] rounded-xl transition border border-emerald-100 group">
                            <span class="w-8 h-8 rounded-lg bg-[#25D366] text-white flex items-center justify-center font-bold text-sm">
                                💬
                            </span>
                            <div class="text-left">
                                <p class="text-[9px] text-emerald-600 uppercase font-black tracking-wider leading-none mb-0.5">WhatsApp Direct</p>
                                <p class="text-xs font-bold text-emerald-800 font-title">Discuter maintenant</p>
                            </div>
                        </a>
                    <?php endif; ?>

                    <!-- Email link -->
                    <a href="mailto:<?= $displayEmail ?>?subject=<?= urlencode("Opportunité Professionnelle — HireMe CV") ?>" class="flex items-center gap-3 p-3 bg-white/50 hover:bg-white rounded-xl transition border border-slate-100 group">
                        <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm group-hover:bg-indigo-600 group-hover:text-white transition">
                            📧
                        </span>
                        <div class="text-left">
                            <p class="text-[9px] text-slate-400 uppercase font-black tracking-wider leading-none mb-0.5">Adresse Email</p>
                            <p class="text-xs font-bold text-slate-700 font-title truncate max-w-[180px]"><?= $displayEmail ?></p>
                        </div>
                    </a>
                </div>

                <!-- Skills section if available -->
                <?php if (!empty($skillsArr)): ?>
                    <div class="space-y-2 pt-2 border-t border-sky-100/50">
                        <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest">Compétences Phares</h2>
                        <div class="flex flex-wrap gap-1.5">
                            <?php foreach ($skillsArr as $skill): ?>
                                <span class="bg-white/80 text-slate-600 text-[10px] font-bold px-2.5 py-1 rounded-lg border border-slate-200">
                                    <?= htmlspecialchars($skill) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action buttons bottom -->
            <div class="mt-8 space-y-2.5">
                <?php 
                $btnLabel = "📥 Télécharger le CV (PDF)";
                if (in_array($cvExt, ['jpg', 'jpeg', 'png'])) {
                    $btnLabel = "📥 Télécharger le CV (Image)";
                } elseif (in_array($cvExt, ['docx', 'doc'])) {
                    $btnLabel = "📥 Télécharger le CV (Word)";
                }
                ?>
                <a href="<?= $displayCv ?>" <?php if($type === 'order' || $type === 'sponsorship') echo 'download'; else echo 'target="_blank"'; ?> class="block w-full bg-primary hover:bg-sky-600 text-white font-extrabold py-3.5 px-4 rounded-2xl text-center shadow-lg shadow-sky-500/20 transition transform hover:-translate-y-0.5 text-xs uppercase tracking-wider font-title">
                    <?= $btnLabel ?>
                </a>
                <p class="text-[9px] text-center text-slate-400 font-bold uppercase tracking-widest">
                    Certifié et Rédigé par HireMe CV
                </p>
            </div>
        </div>

        <!-- Main Panel : Interactive PDF View -->
        <div class="flex-grow glass-panel rounded-3xl shadow-2xl p-4 sm:p-6 flex flex-col justify-between" style="min-height: 75vh;">
            
            <div class="flex items-center justify-between border-b border-sky-100/50 pb-4 mb-4">
                <div class="flex items-center gap-2">
                    <?php if ($cvExt === 'pdf'): ?>
                        <span class="w-3.5 h-3.5 bg-rose-500 rounded-full flex items-center justify-center text-[9px] text-white font-bold">PDF</span>
                        <h2 class="font-black text-slate-800 text-sm font-title leading-none">Curriculum Vitae Officiel</h2>
                    <?php elseif (in_array($cvExt, ['jpg', 'jpeg', 'png'])): ?>
                        <span class="w-3.5 h-3.5 bg-emerald-500 rounded-full flex items-center justify-center text-[9px] text-white font-bold">IMG</span>
                        <h2 class="font-black text-slate-800 text-sm font-title leading-none">Curriculum Vitae Officiel</h2>
                    <?php else: ?>
                        <span class="w-3.5 h-3.5 bg-blue-600 rounded-full flex items-center justify-center text-[9px] text-white font-bold">DOC</span>
                        <h2 class="font-black text-slate-800 text-sm font-title leading-none">Curriculum Vitae (Word)</h2>
                    <?php endif; ?>
                </div>
                
                <span class="text-xs font-bold text-slate-400 hidden sm:inline">
                    Passez en plein écran ou téléchargez pour imprimer.
                </span>
            </div>

            <!-- Live PDF Embed Frame -->
            <div class="flex-grow relative rounded-2xl overflow-hidden bg-slate-50 border border-slate-200 flex flex-col items-center justify-center p-4 min-h-[500px]">
                
                <!-- Background Fallback Info (always visible behind or if iframe fails to load) -->
                <div class="absolute inset-0 hidden sm:flex flex-col items-center justify-center p-6 text-center space-y-4 z-0">
                    <div class="w-16 h-16 bg-sky-100 text-primary rounded-2xl flex items-center justify-center mx-auto text-2xl font-bold">📄</div>
                    <h3 class="font-extrabold text-slate-800 font-title text-lg">Visualisation du CV PDF</h3>
                    <p class="text-xs text-slate-500 leading-relaxed max-w-sm">
                        Si votre navigateur bloque l'aperçu intégré, cliquez ci-dessous pour ouvrir le document PDF en haute définition dans un nouvel onglet.
                    </p>
                    <a href="<?= $displayCv ?>" target="_blank" class="bg-primary hover:bg-sky-600 text-white font-bold py-3 px-6 rounded-xl shadow-md transition text-xs uppercase tracking-wider font-title">
                        📖 Ouvrir le CV en plein écran
                    </a>
                </div>

                <?php if ($type === 'order' || $type === 'sponsorship'): ?>
                    <?php if ($cvExt === 'pdf'): ?>
                        <!-- Universal Browser PDF iframe (hidden by default, shown dynamically if preview is supported) -->
                        <iframe id="pdfIframe" src="<?= $displayCv ?>#toolbar=1&navpanes=0&scrollbar=1" class="absolute inset-0 w-full h-full rounded-2xl border-none z-10 hidden bg-white"></iframe>
                        
                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                var hasPDFViewer = false;
                                if (navigator.pdfViewerEnabled !== undefined) {
                                    hasPDFViewer = navigator.pdfViewerEnabled;
                                } else {
                                    var plugins = navigator.plugins;
                                    if (plugins && plugins.length > 0) {
                                        for (var i = 0; i < plugins.length; i++) {
                                            if (plugins[i].name.toLowerCase().indexOf('pdf') !== -1) {
                                                hasPDFViewer = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                                
                                var isDesktop = window.innerWidth >= 640;
                                var iframe = document.getElementById("pdfIframe");
                                
                                if (hasPDFViewer && isDesktop) {
                                    iframe.classList.remove("hidden");
                                    iframe.classList.add("sm:block");
                                }
                            });
                        </script>
                        
                        <!-- Mobile Fallback Card (because mobile browsers do not support direct PDF embeds nicely) -->
                        <div class="sm:hidden text-center max-w-sm p-6 space-y-4 relative z-20">
                            <div class="w-16 h-16 bg-sky-100 text-primary rounded-2xl flex items-center justify-center mx-auto text-2xl font-bold">📄</div>
                            <h3 class="font-extrabold text-slate-800 font-title text-lg">Visualiser le Document PDF</h3>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Pour une relecture confortable et haute définition sur mobile, veuillez ouvrir directement le fichier PDF.
                            </p>
                            <a href="<?= $displayCv ?>" target="_blank" class="inline-block bg-primary hover:bg-sky-600 text-white font-bold py-3 px-6 rounded-xl shadow-md transition text-xs uppercase tracking-wider font-title">
                                📖 Ouvrir le CV dans un nouvel onglet
                            </a>
                        </div>
                    <?php elseif (in_array($cvExt, ['jpg', 'jpeg', 'png'])): ?>
                        <!-- Image CV Viewer (100% responsive, high-definition direct rendering) -->
                        <div class="absolute inset-0 w-full h-full flex items-center justify-center p-2 overflow-auto bg-white rounded-2xl z-20">
                            <img src="<?= $displayCv ?>" alt="Curriculum Vitae" class="max-w-full max-h-full object-contain rounded-lg shadow-sm">
                        </div>
                    <?php else: ?>
                        <!-- Word Document (DOCX / DOC) Premium card display (preventing blank screen and automatic triggers) -->
                        <div class="text-center max-w-md p-6 space-y-4 relative z-20">
                            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center mx-auto text-2xl font-bold">📝</div>
                            <h3 class="font-extrabold text-slate-800 font-title text-lg">Document Word (.<?= $cvExt ?>) Officiel</h3>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Votre CV final a été rédigé au format **Microsoft Word (.<?= $cvExt ?>)** afin de vous permettre de le modifier et de l'adapter facilement pour vos futures candidatures.
                            </p>
                            <p class="text-xs text-slate-400 leading-relaxed">
                                Les navigateurs web ne pouvant pas afficher directement les documents Word en ligne, veuillez le télécharger ci-dessous pour l'ouvrir.
                            </p>
                            <a href="<?= $displayCv ?>" download class="inline-block bg-primary hover:bg-sky-600 text-white font-bold py-3 px-6 rounded-xl shadow-md transition text-xs uppercase tracking-wider font-title">
                                📥 Télécharger mon CV Word (.<?= $cvExt ?>)
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- If it's a Drive/External Pack link, show a beautiful dynamic redirection card -->
                    <div class="text-center max-w-md p-8 space-y-4">
                        <div class="w-16 h-16 bg-sky-100 text-primary rounded-2xl flex items-center justify-center mx-auto text-2xl font-bold">📁</div>
                        <h3 class="font-extrabold text-slate-800 font-title text-lg">Dossier de Candidature Complet</h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Ce candidat possède un pack complet. Vous pouvez accéder à son dossier sécurisé contenant son CV final, ses lettres de motivation et autres éléments.
                        </p>
                        <a href="<?= $displayCv ?>" target="_blank" class="inline-block bg-primary hover:bg-sky-600 text-white font-bold py-3 px-6 rounded-xl shadow-md transition text-xs uppercase tracking-wider font-title">
                            🚀 Accéder au Dossier Candidat
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>

</body>
</html>
