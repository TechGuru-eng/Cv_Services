<?php
// linkedin/index.php
// HireMe - Optimisation de Profil LinkedIn Landing Page (MonCvPro Redesign)

require_once __DIR__ . '/../admin/config/db.php';

// Fetch Active Packages from DB (with graceful fallback)
$packages = [];
try {
    $stmtPkg = $pdo->query("SELECT * FROM linkedin_packages WHERE is_active = 1 ORDER BY sort_order ASC");
    $packages = $stmtPkg->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $packages = [];
}

if (empty($packages)) {
    $packages = [
        [
            'id' => 1,
            'name' => 'Pack Essentiel',
            'slug' => 'essentiel',
            'price' => 5000,
            'tagline' => 'Profil propre & visible sous 48h',
            'is_popular' => 0,
            'deliverables_json' => json_encode(['Titre pro & mots-clés SEO', 'Bannière HD personnalisée', 'Section Infos optimisée', 'Guide d\'activation pas-à-pas'])
        ],
        [
            'id' => 2,
            'name' => 'Pack Pro',
            'slug' => 'pro',
            'price' => 10000,
            'tagline' => 'Profil complet + CV ATS synchronisé',
            'is_popular' => 1,
            'deliverables_json' => json_encode(['Titre + Résumé + Expériences', 'Bannière HD sur-mesure', 'Audit mots-clés recruteurs', 'CV ATS inclus & synchronisé', 'Recommandations d\'approche RH'])
        ],
        [
            'id' => 3,
            'name' => 'Pack Executive',
            'slug' => 'premium',
            'price' => 25000,
            'tagline' => 'Stratégie carrière & approche décideurs',
            'is_popular' => 0,
            'deliverables_json' => json_encode(['Refonte complète 360° profil', 'Pack 3 Bannières personnalisées', 'Messages de prospection ciblés', 'Simulation d\'entretien LinkedIn', 'Support prioritaire 30 jours'])
        ]
    ];
}

// Fetch Testimonials from DB
$testimonials = [];
try {
    $stmtTest = $pdo->query("SELECT * FROM linkedin_testimonials WHERE is_approved = 1 ORDER BY id DESC LIMIT 6");
    $testimonials = $stmtTest->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $testimonials = [];
}

if (empty($testimonials)) {
    $testimonials = [
        [
            'name' => 'Samuel E.',
            'profession' => 'Ingénieur Réseaux & Télécoms • Douala',
            'comment' => '2 semaines après la refonte de mon profil par HireMe, j\'ai reçu 3 propositions d\'entretien directes de cabinets RH.',
            'rating' => 5
        ],
        [
            'name' => 'Nathalie M.',
            'profession' => 'Responsable RH & Paie • Yaoundé',
            'comment' => 'La bannière et le titre SEO ont tout changé. Mon profil a gagné +300% de vues en moins de 10 jours.',
            'rating' => 5
        ],
        [
            'name' => 'Marc-Aurèle K.',
            'profession' => 'Business Developer B2B • Kribi',
            'comment' => 'Service rapide, efficace et sans prise de tête. Rien à redire, les livrables sont directement exploitables.',
            'rating' => 5
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HireMe LinkedIn — Propulsez votre Carrière avec -60% (HIREME60)</title>
    <meta name="description" content="Transformez votre profil LinkedIn en aimant à recruteurs. Titre SEO, résumé percutant, bannière HD et visibilité algorithmique. -60% avec HIREME60.">

    <!-- Open Graph -->
    <meta property="og:title" content="HireMe LinkedIn – Soyez Recruté en 48h">
    <meta property="og:description" content="Passez d'un profil ignoré au top 1%. Rédaction experte, mots-clés RH et bannière premium. Profitez de -60% !">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://moncvpro.hiremeguide.com/linkedin">

    <!-- Favicons -->
    <link rel="icon" href="../assets/favicons/favicon-32.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS (MonCvPro Theme) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#0ea5e9',   // Sky Blue (MonCvPro)
                        primaryDark: '#0284c7',
                        accent: '#22c55e',    // Emerald Green
                        neutral: '#111827',   // Slate Dark
                        bg: '#f8fafc'         // Light Gray Slate 50
                    },
                    boxShadow: {
                        'soft': '0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03)',
                        'glow': '0 0 25px rgba(14, 165, 233, 0.35)',
                        'glow-accent': '0 0 25px rgba(34, 197, 94, 0.35)',
                    }
                }
            }
        }
    </script>

    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(12px);
        }
        @keyframes floatSlow {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 15px rgba(14, 165, 233, 0.25); }
            50% { box-shadow: 0 0 35px rgba(14, 165, 233, 0.55); }
        }
        @keyframes badgeFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-6px) rotate(1deg); }
        }
        .anim-float {
            animation: floatSlow 5s ease-in-out infinite;
        }
        .anim-badge {
            animation: badgeFloat 4s ease-in-out infinite;
        }
        .anim-glow {
            animation: pulseGlow 3s infinite;
        }
        .card-hover {
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .card-hover:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 30px -10px rgba(14, 165, 233, 0.15);
        }
    </style>
    <script src="../assets/js/site-language.js" defer></script>
</head>

<body class="bg-bg text-neutral antialiased font-sans flex flex-col min-h-screen">

    <!-- 1. TOP PROMO BANNER (Links to MonCvPro main site) -->
    <div id="topPromoBanner" class="bg-gradient-to-r from-blue-700 via-indigo-600 via-sky-600 to-blue-800 bg-[length:200%_200%] animate-gradient-x text-white text-center py-2 px-4 text-[11px] font-black relative z-[60] border-b border-white/20 shadow-md">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-center gap-3">
            <a href="../index.html" class="inline-flex items-center gap-1.5 bg-white/20 hover:bg-white text-white hover:text-blue-900 px-3 py-0.5 rounded-full text-[10px] font-extrabold transition-all no-underline">
                <i class="fa-solid fa-arrow-left text-[9px]"></i> MonCV Pro (Accueil CV)
            </a>
            <span class="uppercase tracking-widest font-black">
                🇨🇲 -60% SUR L'OPTIMISATION LINKEDIN • CODE : <span class="bg-yellow-400 text-slate-950 px-2 py-0.5 rounded font-mono shadow-sm">HIREME60</span>
            </span>
            <a href="commander" class="bg-yellow-400 text-slate-950 px-3.5 py-0.5 rounded-full text-[10px] font-black hover:bg-white transition-all shadow-md no-underline animate-pulse">
                COMMANDER (-60%)
            </a>
        </div>
    </div>

    <!-- 2. NAVIGATION HEADER (Brand links to MonCvPro site) -->
    <nav class="sticky top-0 z-50 glass-effect border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <!-- Brand logo links back to MonCvPro home -->
                <a href="../index.html" class="flex-shrink-0 flex items-center gap-2 group" title="Retourner sur MonCV Pro">
                    <span class="w-9 h-9 rounded-xl bg-primary text-white flex items-center justify-center font-black text-lg shadow-md shadow-sky-500/20 group-hover:scale-105 transition-transform">HM</span>
                    <div class="flex flex-col">
                        <span class="text-xl font-extrabold text-primary tracking-tight leading-none">HireMe<span class="text-neutral">LinkedIn</span></span>
                        <span class="text-[9px] font-bold text-gray-400 tracking-wider">Par MonCV Pro</span>
                    </div>
                </a>

                <!-- Desktop Nav -->
                <div class="hidden md:flex items-center space-x-7 text-xs font-bold">
                    <a href="../index.html" class="text-primary hover:text-sky-700 bg-sky-50 px-3 py-1.5 rounded-xl border border-sky-200 flex items-center gap-1.5 transition-all hover:bg-sky-100">
                        <i class="fa-solid fa-house text-[11px]"></i>
                        <span>Accueil MonCV Pro</span>
                    </a>
                    <a href="#services" class="text-gray-600 hover:text-primary transition-colors">Services</a>
                    <a href="#diagnostic" class="text-gray-600 hover:text-primary transition-colors flex items-center">
                        <span class="bg-sky-100 text-sky-700 text-[9px] px-2 py-0.5 rounded-full mr-1 font-extrabold animate-pulse">TEST</span>
                        Score Profil
                    </a>
                    <a href="#tarifs" class="text-gray-600 hover:text-primary transition-colors">Tarifs (-60%)</a>
                    <a href="#faq" class="text-gray-600 hover:text-primary transition-colors">FAQ</a>
                </div>

                <div class="hidden md:flex items-center gap-3">
                    <a href="commander" class="inline-flex items-center px-5 py-2.5 rounded-full shadow-glow text-white bg-primary hover:bg-sky-600 text-xs font-black transition-all transform hover:scale-105">
                        <i class="fa-solid fa-rocket mr-2 text-[11px]"></i> Commander (-60%)
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex md:hidden">
                    <button type="button" onclick="toggleMobileMenu()" class="text-gray-600 hover:text-primary p-2" aria-label="Menu">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div id="mobileMenu" class="hidden md:hidden absolute top-16 left-0 right-0 bg-white border-b border-gray-200 shadow-xl z-40 p-4 space-y-2 text-sm font-bold">
            <a href="../index.html" class="block px-3 py-2.5 rounded-xl text-primary bg-sky-50 border border-sky-200">
                <i class="fa-solid fa-house mr-2"></i> Retour au site MonCV Pro
            </a>
            <a href="#services" class="block px-3 py-2 rounded-xl text-gray-700 hover:bg-gray-100" onclick="toggleMobileMenu()">Services</a>
            <a href="#diagnostic" class="block px-3 py-2 rounded-xl text-gray-700 hover:bg-gray-100" onclick="toggleMobileMenu()">Calculateur de Score</a>
            <a href="#tarifs" class="block px-3 py-2 rounded-xl text-gray-700 hover:bg-gray-100" onclick="toggleMobileMenu()">Tarifs (-60%)</a>
            <a href="commander" class="block w-full text-center mt-3 px-5 py-3 rounded-xl font-extrabold text-white bg-primary hover:bg-sky-600 shadow-md" onclick="toggleMobileMenu()">
                Commander (-60%)
            </a>
        </div>
    </nav>

    <main class="flex-grow">
        <!-- 3. HERO SECTION (Punchy Copy + 3D Visual Mockup Image + Animated Badges) -->
        <section class="relative pt-10 pb-16 px-4 sm:pt-16 sm:pb-20 overflow-hidden border-b border-gray-200 bg-gradient-to-b from-sky-50/60 via-bg to-white">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                    
                    <!-- Left: Punchy Copy -->
                    <div class="lg:col-span-7 text-center lg:text-left space-y-6">
                        <!-- Urgency badge -->
                        <div class="inline-flex items-center gap-2 bg-red-50 border border-red-200 px-3.5 py-1.5 rounded-full animate-pulse">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            <span class="text-red-700 text-xs font-bold uppercase tracking-wider">Offre Spéciale : -60% avec HIREME60</span>
                        </div>

                        <!-- Main Heading -->
                        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold text-neutral tracking-tight leading-tight">
                            Ne cherchez plus les recruteurs. <br>
                            <span class="bg-clip-text text-transparent bg-gradient-to-r from-primary via-blue-600 to-indigo-700">Faites-les venir à vous.</span>
                        </h1>
                        
                        <!-- Crisp, Uncluttered Subtitle -->
                        <p class="text-base sm:text-lg text-gray-600 font-medium leading-relaxed max-w-xl mx-auto lg:mx-0">
                            Titre percutant, résumé captivant, mots-clés ATS et bannière sur-mesure. Transformez votre profil en vitrine irrésistible en <strong class="text-neutral">48h chrono</strong>.
                        </p>

                        <!-- CTA Buttons -->
                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-2">
                            <a href="commander" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 rounded-full shadow-glow text-white bg-primary hover:bg-sky-600 text-base font-extrabold transition-all transform hover:-translate-y-1 hover:scale-105">
                                Propulser mon profil LinkedIn
                                <i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
                            </a>
                            <a href="#diagnostic" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-4 rounded-full bg-white border border-gray-300 hover:border-primary text-gray-700 hover:text-primary text-sm font-bold shadow-soft transition-all">
                                <i class="fa-solid fa-chart-simple mr-2 text-primary"></i> Tester mon score gratuit
                            </a>
                        </div>

                        <!-- Trust proof -->
                        <div class="pt-6 border-t border-gray-200 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-xs text-gray-500">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-shield-halved text-accent text-sm"></i>
                                <span class="font-bold text-neutral">100% Confidentiel</span> (sans mot de passe)
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-bolt text-yellow-500 text-sm"></i>
                                <span class="font-bold text-neutral">Livraison 48h</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-award text-primary text-sm"></i>
                                <span class="font-bold text-neutral">Garantie Satisfaction</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Visual 3D Showcase Image & Floating Animated Badges -->
                    <div class="lg:col-span-5 relative">
                        <div class="relative mx-auto max-w-md lg:max-w-none anim-float">
                            <!-- Main Visual Showcase Image -->
                            <div class="relative rounded-3xl overflow-hidden shadow-2xl border-2 border-white/80 bg-white">
                                <img src="../assets/images/linkedin-hero-banner.png" alt="Aperçu Optimisation Profil LinkedIn HireMe" class="w-full h-auto object-cover transform hover:scale-105 transition-transform duration-500">
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/20 via-transparent to-transparent pointer-events-none"></div>
                            </div>

                            <!-- Floating Badge 1: Top 1% Visibilité -->
                            <div class="absolute -top-4 -left-4 sm:-left-6 bg-white/95 backdrop-blur-md border border-sky-200 shadow-xl rounded-2xl p-3 flex items-center gap-3 anim-badge">
                                <div class="w-10 h-10 rounded-xl bg-sky-100 text-primary flex items-center justify-center text-lg">
                                    <i class="fa-solid fa-arrow-trend-up"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Vues de profil</p>
                                    <p class="text-sm font-extrabold text-neutral">+340% en 14j</p>
                                </div>
                            </div>

                            <!-- Floating Badge 2: Recruiter Verified -->
                            <div class="absolute -bottom-4 -right-4 sm:-right-6 bg-white/95 backdrop-blur-md border border-emerald-200 shadow-xl rounded-2xl p-3 flex items-center gap-3 anim-badge" style="animation-delay: 1.5s;">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-accent flex items-center justify-center text-lg">
                                    <i class="fa-solid fa-circle-check"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Statut</p>
                                    <p class="text-sm font-extrabold text-neutral">Recruiter-Ready</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- 4. SERVICES / CE QUE NOUS OPTIMISONS (Streamlined to 6 Crisp Visual Cards) -->
        <section id="services" class="py-16 md:py-20 bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <span class="text-xs uppercase font-extrabold tracking-widest text-primary bg-sky-50 px-3 py-1 rounded-full border border-sky-200">Expertise 360°</span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-neutral mt-3">Ce que nous optimisons pour vous</h2>
                    <p class="mt-2 text-gray-500 text-sm">Chaque élément est calibré pour séduire l'algorithme et convaincre les recruteurs.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Feature 1 -->
                    <div class="card-hover bg-bg rounded-2xl p-6 border border-gray-200">
                        <div class="w-12 h-12 rounded-xl bg-sky-100 text-primary flex items-center justify-center text-xl font-bold mb-4">
                            <i class="fa-solid fa-heading"></i>
                        </div>
                        <h3 class="text-base font-extrabold text-neutral mb-2">Titre Professionnel SEO</h3>
                        <p class="text-xs text-gray-600 leading-relaxed font-medium">Mots-clés stratégiques pour apparaître en 1ère position dans les recherches des recruteurs.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="card-hover bg-bg rounded-2xl p-6 border border-gray-200">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 text-accent flex items-center justify-center text-xl font-bold mb-4">
                            <i class="fa-solid fa-image"></i>
                        </div>
                        <h3 class="text-base font-extrabold text-neutral mb-2">Bannière Profil HD Sur-Mesure</h3>
                        <p class="text-xs text-gray-600 leading-relaxed font-medium">Design visuel moderne et percutant qui assoit votre crédibilité dès la 1ère seconde.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="card-hover bg-bg rounded-2xl p-6 border border-gray-200">
                        <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl font-bold mb-4">
                            <i class="fa-solid fa-align-left"></i>
                        </div>
                        <h3 class="text-base font-extrabold text-neutral mb-2">Section "Infos" Captivante</h3>
                        <p class="text-xs text-gray-600 leading-relaxed font-medium">Storytelling persuasif mettant en lumière votre valeur ajoutée et vos réussites clés.</p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="card-hover bg-bg rounded-2xl p-6 border border-gray-200">
                        <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl font-bold mb-4">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                        <h3 class="text-base font-extrabold text-neutral mb-2">Expériences Chiffrées</h3>
                        <p class="text-xs text-gray-600 leading-relaxed font-medium">Restructuration orientée résultats et livrables mesurables pour un impact maximal.</p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="card-hover bg-bg rounded-2xl p-6 border border-gray-200">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl font-bold mb-4">
                            <i class="fa-solid fa-magnifying-glass-chart"></i>
                        </div>
                        <h3 class="text-base font-extrabold text-neutral mb-2">Audit Mots-Clés Recruteurs</h3>
                        <p class="text-xs text-gray-600 leading-relaxed font-medium">Intégration naturelle des compétences recherchées par les cabinets RH et chasseurs de têtes.</p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="card-hover bg-bg rounded-2xl p-6 border border-gray-200">
                        <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl font-bold mb-4">
                            <i class="fa-solid fa-paper-plane"></i>
                        </div>
                        <h3 class="text-base font-extrabold text-neutral mb-2">Stratégie Réseau & Visibilité</h3>
                        <p class="text-xs text-gray-600 leading-relaxed font-medium">Templates de messages pour contacter directement les décideurs et décrocher des entretiens.</p>
                    </div>
                </div>

                <!-- Showcase Banner Graphic -->
                <div class="mt-12 rounded-3xl overflow-hidden border border-gray-200 shadow-xl bg-white p-4 sm:p-8">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                        <div class="lg:col-span-5 space-y-4">
                            <span class="text-xs font-extrabold text-primary uppercase tracking-widest bg-sky-50 px-3 py-1 rounded-full border border-sky-200">Design Professionnel</span>
                            <h3 class="text-2xl font-extrabold text-neutral">Bannières personnalisées haut de gamme</h3>
                            <p class="text-xs text-gray-600 leading-relaxed">Fini les bannières grises par défaut. Nos designers créent une identité visuelle digne d'un cadre de top entreprise.</p>
                            <a href="commander" class="inline-flex items-center text-xs font-extrabold text-primary hover:text-sky-700">
                                Choisir mon pack avec bannière incluse →
                            </a>
                        </div>
                        <div class="lg:col-span-7">
                            <img src="../assets/images/linkedin-hero-3d.jpg" alt="Exemples de Bannières LinkedIn HireMe" class="w-full rounded-2xl shadow-md border border-gray-100 object-cover">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. AVANT / APRÈS (Punchy Comparison) -->
        <section class="py-16 md:py-20 bg-bg border-b border-gray-200">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-xs uppercase font-extrabold tracking-widest text-primary bg-sky-50 px-3 py-1 rounded-full border border-sky-200">Impact Concret</span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-neutral mt-3">Avant vs Après HireMe</h2>
                </div>

                <!-- High-res Before/After Graphic Card -->
                <div class="mb-10 max-w-3xl mx-auto rounded-3xl overflow-hidden border border-sky-200 shadow-xl bg-white p-2">
                    <img src="../assets/images/linkedin-before-after.png" alt="Transformation Profil LinkedIn Avant / Après" class="w-full h-auto object-contain rounded-2xl">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- AVANT -->
                    <div class="rounded-3xl bg-red-50/60 border border-red-200 p-6 sm:p-8 space-y-4">
                        <div class="inline-flex items-center gap-2 bg-red-100 text-red-700 font-extrabold text-xs px-3 py-1 rounded-full">
                            <i class="fa-solid fa-circle-xmark"></i> AVANT (PROFIL IGNORÉ)
                        </div>
                        <ul class="space-y-3 text-xs text-gray-700 font-medium">
                            <li class="flex items-center gap-3">
                                <i class="fa-solid fa-xmark text-red-500 shrink-0 font-bold"></i>
                                <span>Titre générique invisible aux algorithmes recruteurs</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="fa-solid fa-xmark text-red-500 shrink-0 font-bold"></i>
                                <span>Résumé vide ou impersonnel sans valeur ajoutée</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="fa-solid fa-xmark text-red-500 shrink-0 font-bold"></i>
                                <span>Bannière grise par défaut qui décrédibilise le profil</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="fa-solid fa-xmark text-red-500 shrink-0 font-bold"></i>
                                <span>Zéro message d'opportunité en messagerie</span>
                            </li>
                        </ul>
                    </div>

                    <!-- APRÈS -->
                    <div class="rounded-3xl bg-emerald-50/60 border border-emerald-200 p-6 sm:p-8 space-y-4 shadow-sm">
                        <div class="inline-flex items-center gap-2 bg-emerald-100 text-emerald-800 font-extrabold text-xs px-3 py-1 rounded-full">
                            <i class="fa-solid fa-circle-check"></i> APRÈS HIREME (TOP 1%)
                        </div>
                        <ul class="space-y-3 text-xs text-gray-700 font-medium">
                            <li class="flex items-center gap-3">
                                <i class="fa-solid fa-check text-accent shrink-0 font-bold"></i>
                                <span>Titre percutant positionné sur les requêtes recruteurs</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="fa-solid fa-check text-accent shrink-0 font-bold"></i>
                                <span>Storytelling engageant et réalisations chiffrées</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="fa-solid fa-check text-accent shrink-0 font-bold"></i>
                                <span>Bannière HD sur-mesure aux normes professionnelles</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="fa-solid fa-check text-accent shrink-0 font-bold"></i>
                                <span>Visibilité multipliée par 3 auprès des cabinets RH</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. CALCULATEUR SCORE PROFIL (Fast 7-Question Simulator) -->
        <section id="diagnostic" class="py-16 md:py-20 bg-white border-b border-gray-200">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10">
                    <span class="text-xs uppercase font-extrabold tracking-widest text-primary bg-sky-50 px-3 py-1 rounded-full border border-sky-200">Test Gratuit</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-neutral mt-3">Quel est le score de votre profil ?</h2>
                    <p class="mt-2 text-gray-500 text-xs">Évaluez votre attractivité en 30 secondes chrono.</p>
                </div>

                <div class="bg-bg rounded-3xl border border-gray-200 p-6 sm:p-8 shadow-soft">
                    <form id="diagnosticForm" class="space-y-3">
                        <?php
                        $questions = [
                            "Avez-vous une photo de profil nette et professionnelle ?",
                            "Votre titre indique-t-il votre métier exact et vos mots-clés cibles ?",
                            "Votre section 'Infos' contient-elle au moins 2 paragraphes structurés ?",
                            "Vos expériences détaillent-elles des résultats chiffrés ?",
                            "Avez-vous renseigné au moins 10 compétences pertinentes ?",
                            "Votre bannière LinkedIn est-elle personnalisée (non grise) ?",
                            "Recevez-vous régulièrement des visites de recruteurs ?"
                        ];
                        foreach ($questions as $idx => $q):
                        ?>
                        <div class="flex items-center justify-between gap-3 p-3.5 rounded-xl bg-white border border-gray-200 text-xs">
                            <span class="font-bold text-neutral"><?= ($idx + 1) ?>. <?= $q ?></span>
                            <div class="flex items-center gap-2 shrink-0">
                                <label class="cursor-pointer font-bold px-3 py-1 rounded-lg border border-gray-200 bg-gray-50 hover:bg-sky-50 has-[:checked]:bg-primary has-[:checked]:text-white transition-colors">
                                    <input type="radio" name="q<?= $idx ?>" value="15" class="hidden" required> Oui
                                </label>
                                <label class="cursor-pointer font-bold px-3 py-1 rounded-lg border border-gray-200 bg-gray-50 hover:bg-red-50 has-[:checked]:bg-red-500 has-[:checked]:text-white transition-colors">
                                    <input type="radio" name="q<?= $idx ?>" value="0" class="hidden"> Non
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="text-center pt-4">
                            <button type="button" onclick="calculateScore()" class="bg-primary hover:bg-sky-600 text-white font-extrabold text-xs px-8 py-3.5 rounded-full shadow-glow transition-all">
                                Calculer mon score instantané
                            </button>
                        </div>
                    </form>

                    <!-- Score Output -->
                    <div id="scoreResult" class="hidden mt-6 pt-6 border-t border-gray-200 text-center space-y-3">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white border-4 border-primary text-2xl font-black text-neutral shadow-lg" id="scoreValue">
                            0 / 100
                        </div>
                        <h3 class="text-base font-extrabold text-neutral" id="scoreTitle">Diagnostic terminé</h3>
                        <p class="text-xs text-gray-600 max-w-md mx-auto" id="scoreDesc"></p>
                        <div class="pt-2">
                            <a href="commander" class="inline-flex items-center gap-2 bg-primary hover:bg-sky-600 text-white font-extrabold text-xs px-6 py-3 rounded-full shadow-glow">
                                <span>Optimiser mon profil avec -60%</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 7. PRICING SECTION -->
        <section id="tarifs" class="py-16 md:py-20 bg-bg border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <span class="text-xs uppercase font-extrabold tracking-widest text-primary bg-sky-50 px-3 py-1 rounded-full border border-sky-200">Tarifs Promo (-60%)</span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-neutral mt-3">Choisissez votre formule</h2>
                    <p class="mt-2 text-gray-500 text-xs">Paiement sécurisé MTN / Orange Money • Code : HIREME60</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch max-w-5xl mx-auto">
                    <?php foreach ($packages as $pkg): 
                        $deliverables = json_decode($pkg['deliverables_json'], true) ?: [];
                        $isPopular = (bool)$pkg['is_popular'];
                        $delayText = ($pkg['slug'] === 'premium') ? '3-5 jours' : '48-72h';
                    ?>
                    <div class="rounded-3xl flex flex-col justify-between p-6 sm:p-8 relative transition-all duration-300 <?= $isPopular ? 'bg-white border-2 border-primary shadow-glow md:scale-105 z-10' : 'bg-white border border-gray-200 shadow-soft' ?>">
                        
                        <?php if ($isPopular): ?>
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-primary text-white font-black text-[10px] uppercase tracking-widest px-4 py-1 rounded-full shadow-md flex items-center gap-1.5">
                            <i class="fa-solid fa-star text-[10px]"></i>
                            <span>LE PLUS RECOMMANDÉ</span>
                        </div>
                        <?php endif; ?>

                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <h3 class="text-lg font-black text-neutral"><?= htmlspecialchars($pkg['name']) ?></h3>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 border border-sky-200">
                                    <?= $delayText ?>
                                </span>
                            </div>

                            <!-- Price -->
                            <div class="mb-4">
                                <span class="text-3xl font-black text-neutral"><?= number_format($pkg['price'], 0, ',', ' ') ?></span>
                                <span class="text-sm font-bold text-primary ml-1">FCFA</span>
                            </div>
                            
                            <p class="text-xs font-bold text-gray-500 mb-6 italic">"<?= htmlspecialchars($pkg['tagline']) ?>"</p>

                            <!-- Bullet Points -->
                            <ul class="space-y-2.5 text-xs text-gray-700 mb-2 font-medium">
                                <?php 
                                $visibleItems = array_slice($deliverables, 0, 3);
                                $hiddenItems = array_slice($deliverables, 3);
                                foreach ($visibleItems as $deliv): ?>
                                <li class="flex items-start gap-2">
                                    <i class="fa-solid fa-circle-check text-accent mt-0.5 shrink-0 text-xs"></i>
                                    <span><?= htmlspecialchars($deliv) ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>

                            <?php if (!empty($hiddenItems)): ?>
                            <ul class="space-y-2.5 text-xs text-gray-700 mb-4 font-medium hidden">
                                <?php foreach ($hiddenItems as $deliv): ?>
                                <li class="flex items-start gap-2">
                                    <i class="fa-solid fa-circle-check text-accent mt-0.5 shrink-0 text-xs"></i>
                                    <span><?= htmlspecialchars($deliv) ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" onclick="toggleLinkedInPackDetails(this)" class="text-xs font-bold text-primary hover:text-sky-700 flex items-center gap-1.5 mb-6 transition-colors group">
                                <span>Voir plus (+<?= count($hiddenItems) ?> avantages)</span>
                                <i class="fa-solid fa-chevron-down text-[10px] group-hover:translate-y-0.5 transition-transform"></i>
                            </button>
                            <?php else: ?>
                            <div class="mb-6"></div>
                            <?php endif; ?>
                        </div>

                        <div class="pt-4 border-t border-gray-100">
                            <a href="commander?package=<?= $pkg['id'] ?>" class="w-full inline-flex items-center justify-center gap-2 <?= $isPopular ? 'bg-primary hover:bg-sky-600 text-white shadow-glow' : 'bg-neutral hover:bg-gray-800 text-white' ?> font-black text-xs py-3.5 rounded-xl transition-all shadow-md">
                                <span>Commander <?= htmlspecialchars($pkg['name']) ?></span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- 8. TEMOIGNAGES -->
        <?php if (!empty($testimonials)): ?>
        <section class="py-16 bg-white border-b border-gray-200">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10">
                    <span class="text-xs uppercase font-extrabold tracking-widest text-primary bg-sky-50 px-3 py-1 rounded-full border border-sky-200">Avis Candidats</span>
                    <h2 class="text-2xl font-extrabold text-neutral mt-2">Ils ont boosté leur visibilité</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($testimonials as $t): ?>
                    <div class="p-5 rounded-2xl bg-bg border border-gray-200 shadow-soft space-y-3">
                        <div class="flex text-yellow-400 text-xs gap-1">
                            <?php for($i=0; $i<$t['rating']; $i++): ?>
                                <i class="fa-solid fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="text-xs text-gray-600 italic">"<?= htmlspecialchars($t['comment']) ?>"</p>
                        <div class="pt-2 border-t border-gray-200">
                            <strong class="block text-xs font-bold text-neutral"><?= htmlspecialchars($t['name']) ?></strong>
                            <span class="text-[10px] text-gray-400"><?= htmlspecialchars($t['profession']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- 9. FAQ -->
        <section id="faq" class="py-16 bg-bg border-b border-gray-200">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10">
                    <span class="text-xs uppercase font-extrabold tracking-widest text-primary bg-sky-50 px-3 py-1 rounded-full border border-sky-200">Questions Fréquentes</span>
                    <h2 class="text-2xl font-extrabold text-neutral mt-2">Tout ce que vous devez savoir</h2>
                </div>

                <div class="space-y-3">
                    <?php
                    $faqs = [
                        ["Dois-je vous fournir mon mot de passe LinkedIn ?", "Non, absolument pas. HireMe ne vous demandera jamais vos identifiants. Vous recevez vos textes optimisés et vos bannières prêts à copier-coller avec un guide d'intégration illustré."],
                        ["Puis-je commander si mon profil est totalement vide ?", "Oui. Il vous suffit de nous envoyer votre CV lors de la commande, et nous rédigeons l'intégralité de votre profil de A à Z."],
                        ["Le service fonctionne-t-il aussi pour les CV ?", "Oui ! Si vous souhaitez faire rédiger votre CV complet, visitez notre site principal MonCvPro.com ou choisissez le Pack Pro qui inclut les deux."],
                        ["Quels sont les délais de livraison ?", "Votre profil optimisé est livré sous 48h à 72h ouvrées (3 à 5 jours pour le Pack Executive)."]
                    ];
                    foreach ($faqs as $idx => $f):
                    ?>
                    <div class="rounded-xl bg-white border border-gray-200 overflow-hidden">
                        <button type="button" onclick="toggleFaq(<?= $idx ?>)" class="w-full p-4 text-left font-bold text-xs text-neutral flex justify-between items-center gap-4 hover:text-primary transition-colors">
                            <span><?= htmlspecialchars($f[0]) ?></span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-primary transition-transform duration-300" id="faqIcon<?= $idx ?>"></i>
                        </button>
                        <div id="faqContent<?= $idx ?>" class="hidden px-4 pb-4 text-xs text-gray-600 leading-relaxed border-t border-gray-100 pt-3">
                            <?= htmlspecialchars($f[1]) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- 10. FOOTER (Prominent Links to MonCvPro site) -->
    <footer class="bg-neutral text-gray-400 py-12 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
            <div class="flex justify-center items-center gap-3">
                <a href="../index.html" class="flex items-center gap-2 text-white font-extrabold text-lg no-underline hover:text-primary transition-colors">
                    <span class="w-7 h-7 rounded-lg bg-primary text-white flex items-center justify-center font-black text-xs">HM</span>
                    <span>HireMe LinkedIn</span>
                </a>
            </div>

            <!-- MonCvPro Portal Links -->
            <div class="flex flex-wrap justify-center items-center gap-6 text-xs font-semibold text-gray-300">
                <a href="../index.html" class="hover:text-primary transition-colors"><i class="fa-solid fa-house mr-1 text-primary"></i> Accueil MonCvPro.com</a>
                <a href="../index.html#pricing" class="hover:text-primary transition-colors">Tarifs & Modèles CV</a>
                <a href="suivi-linkedin" class="hover:text-primary transition-colors">Suivi Commande LinkedIn</a>
                <a href="commander" class="hover:text-primary transition-colors">Commander Profil (-60%)</a>
            </div>

            <p class="max-w-md mx-auto text-gray-500 text-[11px]">
                HireMe LinkedIn est propulsé par la plateforme MonCvPro.com, référence de la valorisation professionnelle et de la rédaction de CV de haute qualité au Cameroun et en Afrique.
            </p>

            <p class="text-gray-600 pt-4 border-t border-gray-800 text-[10px]">
                &copy; <?= date('Y') ?> MonCvPro / HireMe. Tous droits réservés.
            </p>
        </div>
    </footer>

    <!-- STICKY MOBILE CTA -->
    <div class="fixed bottom-0 left-0 right-0 p-3 bg-white/95 backdrop-blur-md border-t border-gray-200 md:hidden z-40 shadow-2xl">
        <a href="commander" class="w-full inline-flex items-center justify-center gap-2 bg-primary hover:bg-sky-600 text-white font-extrabold text-xs py-3 rounded-xl shadow-glow">
            <i class="fa-solid fa-rocket text-xs"></i>
            <span>Optimiser mon profil (-60%)</span>
        </a>
    </div>

    <!-- SCRIPTS -->
    <script>
        function toggleLinkedInPackDetails(btn) {
            const hiddenList = btn.previousElementSibling;
            if (!hiddenList) return;
            if (hiddenList.classList.contains('hidden')) {
                hiddenList.classList.remove('hidden');
                btn.innerHTML = '<span>Réduire (-)</span> <i class="fa-solid fa-chevron-up text-[10px]"></i>';
            } else {
                hiddenList.classList.add('hidden');
                const count = hiddenList.children.length;
                btn.innerHTML = `<span>Voir plus (+${count} avantages)</span> <i class="fa-solid fa-chevron-down text-[10px]"></i>`;
            }
        }

        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }

        function toggleFaq(index) {
            const content = document.getElementById(`faqContent${index}`);
            const icon = document.getElementById(`faqIcon${index}`);
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.classList.add('rotate-180');
            } else {
                content.classList.add('hidden');
                icon.classList.remove('rotate-180');
            }
        }

        function calculateScore() {
            const form = document.getElementById('diagnosticForm');
            const inputs = form.querySelectorAll('input[type="radio"]:checked');
            if (inputs.length < 7) {
                alert('Veuillez répondre aux 7 questions pour calculer votre score.');
                return;
            }
            let score = 0;
            inputs.forEach(input => {
                score += parseInt(input.value);
            });

            document.getElementById('scoreResult').classList.remove('hidden');
            document.getElementById('scoreValue').innerText = `${score} / 100`;

            const title = document.getElementById('scoreTitle');
            const desc = document.getElementById('scoreDesc');

            if (score <= 45) {
                title.innerText = "Profil critique : quasi invisible aux recruteurs";
                desc.innerText = "Votre profil manque des mots-clés essentiels et de mise en page moderne. Une refonte est fortement recommandée pour capter les opportunités.";
            } else if (score <= 75) {
                title.innerText = "Profil moyen : potentiel inexploité";
                desc.innerText = "Bonne base, mais votre titre et votre résumé ne valorisent pas assez vos accomplissements auprès des algorithmes de recherche.";
            } else {
                title.innerText = "Bon profil : prêt pour le niveau supérieur";
                desc.innerText = "Votre profil est soigné. Une optimisation ciblée vous permettra de cibler les postes à haute responsabilité et d'accélérer vos démarches.";
            }

            document.getElementById('scoreResult').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>
