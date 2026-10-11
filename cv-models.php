<?php
require_once 'admin/config/db.php';
$designs = $pdo->query("SELECT * FROM cv_designs ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
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
    <title>Modèles de CV - HireMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Open Graph / Default Preview -->
    <meta property="og:title" content="Modèles de CV Professionnels - HireMe">
    <meta property="og:description" content="Découvrez nos modèles de CV modernes et percutants.">
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/hireme-preview-v2.png">
    <meta property="og:url" content="https://moncvpro.hiremeguide.com/cv-models.php">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="https://moncvpro.hiremeguide.com/assets/hireme-preview-v2.png">

    <!-- Favicon -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: '#0ea5e9',
                        accent: '#22c55e',
                        danger: '#ef4444',
                        neutral: '#111827',
                        bg: '#f8fafc'
                    },
                    boxShadow: {
                        'soft': '0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03)',
                        'glow': '0 0 15px rgba(14, 165, 233, 0.3)',
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom Utilities */
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        /* Disable selection and drag */
        .no-select {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-touch-callout: none;
        }
        
        /* Protection Overlay */
        .protected-image-container {
            position: relative;
            overflow: hidden;
        }
        .protected-image-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0); /* Transparent */
            z-index: 10;
        }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="bg-gray-50 text-gray-800 font-sans" oncontextmenu="return false;">

    <!-- Navigation -->
    <nav class="sticky top-0 z-50 glass-effect border-b border-gray-100 dark:border-gray-800 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex-shrink-0 flex items-center">
                    <a href="index" class="text-2xl font-extrabold text-primary tracking-tight">HireMe<span class="text-neutral">CV</span></a>
                </div>
                <div class="hidden md:flex space-x-8">
                    <a href="cv-models.php" class="text-primary font-bold transition-colors">Modèles CV</a>
                    <a href="index#advantages" class="text-gray-600 hover:text-primary transition-colors">Pourquoi nous?</a>
                    <a href="index#pricing" class="text-gray-600 hover:text-primary transition-colors">Tarifs</a>
                    <a href="index#process" class="text-gray-600 hover:text-primary transition-colors">Comment ça marche</a>
                    <a href="index#contact" class="text-gray-600 hover:text-primary transition-colors">Contact</a>
                </div>
                <div class="hidden md:block">
                    <a href="index#pricing" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all transform hover:scale-105">
                        Commander
                    </a>
                </div>
                <!-- Mobile Menu Button -->
                <div class="flex md:hidden">
                    <button type="button" onclick="toggleMobileMenu()" class="text-gray-500 hover:text-primary focus:outline-none focus:text-primary p-2" aria-label="Menu principal">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
            <!-- Mobile Menu Dropdown -->
            <div id="mobileMenu" class="hidden md:hidden absolute top-16 left-0 right-0 bg-white border-b border-gray-100 shadow-xl z-40">
                <div class="px-4 pt-4 pb-6 space-y-2">
                    <a href="cv-models.php" class="block px-3 py-3 rounded-xl text-base font-bold text-primary bg-blue-50 transition-colors">Modèles CV</a>
                    <a href="index#advantages" class="block px-3 py-3 rounded-xl text-base font-medium text-gray-600 hover:text-primary hover:bg-blue-50 transition-colors">Pourquoi nous?</a>
                    <a href="index#pricing" class="block px-3 py-3 rounded-xl text-base font-medium text-gray-600 hover:text-primary hover:bg-blue-50 transition-colors">Tarifs</a>
                    <a href="index#process" class="block px-3 py-3 rounded-xl text-base font-medium text-gray-600 hover:text-primary hover:bg-blue-50 transition-colors">Comment ça marche</a>
                    <a href="index#contact" class="block px-3 py-3 rounded-xl text-base font-medium text-gray-600 hover:text-primary hover:bg-blue-50 transition-colors">Contact</a>
                    <a href="index#pricing" class="block w-full text-center mt-4 px-5 py-3 rounded-xl font-bold text-white bg-primary hover:bg-blue-600 shadow-lg">Commander (-60%)</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
        <div class="container mx-auto px-6 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Nos Modèles de CV</h1>
            <p class="text-xl text-blue-100 max-w-2xl mx-auto">
                Découvrez nos designs professionnels conçus pour maximiser vos chances de recrutement.
            </p>
        </div>
    </section>

    <!-- Gallery Grid -->
    <section class="py-16">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach($designs as $design): ?>
                    <div class="bg-white rounded-xl shadow-lg run-up overflow-hidden hover:shadow-2xl transition-shadow duration-300">
                        <div class="protected-image-container group relative">
                            <!-- Image -->
                            <img src="<?= htmlspecialchars($design['image_path']) ?>" 
                                 alt="Modèle CV" 
                                 class="w-full h-auto object-cover no-select pointer-events-none"
                                 draggable="false">
                            <!-- Overlay on hover (Optional Info) -->
                            <div class="absolute inset-0 bg-black/50 opacity-0 bg-opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center z-20">
                                <span class="bg-white text-gray-900 px-4 py-2 rounded-full font-bold text-sm transform translate-y-4 group-hover:translate-y-0 transition-transform">
                                    <?= htmlspecialchars($design['title']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="p-5 border-t border-gray-100">
                            <h3 class="font-bold text-lg mb-1"><?= htmlspecialchars($design['title']) ?></h3>
                            <a href="index#pricing" class="block text-center mt-3 w-full bg-gray-100 text-blue-600 font-bold py-2 rounded-lg hover:bg-blue-600 hover:text-white transition-colors">
                                Choisir ce modèle
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if(empty($designs)): ?>
                <div class="text-center py-20">
                    <div class="text-gray-300 text-6xl mb-4"><i class="fas fa-image"></i></div>
                    <p class="text-gray-500 text-xl">Aucun modèle disponible pour le moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-10">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; <?= date('Y') ?> HireMe CV Services. Tous droits réservés.</p>
        </div>
    </footer>

    <script>
        // Mobile Menu Toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }

        // Disable Right Click
        document.addEventListener('contextmenu', event => event.preventDefault());

        // Disable Drag and Drop
        document.ondragstart = function() { return false; };
        
        // Disable Keyboard Shortcuts (Ctrl+S, Ctrl+P) - Best Effort
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'p')) {
                e.preventDefault();
                alert("Le téléchargement est désactivé pour protéger nos créations.");
            }
        });
    </script>
    <!-- Stat Tracking -->
    <img src="api/track.php" alt="" style="display:none;">
</body>
</html>
