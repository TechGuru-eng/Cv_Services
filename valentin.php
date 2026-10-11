<?php
require_once 'admin/config/db.php';
date_default_timezone_set('Africa/Douala');

// 1. Settings (Optional integration with DB settings if needed later)
$promoEndTime = '2026-02-14 23:59:59';
$singlePrice = 8500;
$couplePrice = 14500;
$validToken = 'valentin2026'; // Simple access token for tracking if needed
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
    <title>Offre Spéciale Valentin - MonCv Pro</title>
    <link rel="canonical" href="https://moncvpro.hiremeguide.com/valentin.php" />
    <!-- SEO & Meta Tags -->
    <meta name="description" content="Célébrez l'amour et la réussite. Profitez de notre Deal Carrière de la Saint-Valentin pour booster votre avenir professionnel. CV & Lettre (FR/EN) + Top 20 Entreprises.">
    <meta name="keywords" content="Saint-Valentin, CV, Lettre de Motivation, Carrière, Emploi, Cameroun, Recrutement">
    <meta name="author" content="MonCv Pro">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://moncvpro.hiremeguide.com/valentin.php">
    <meta property="og:title" content="Offre Spéciale Valentin ❤️ - Bâtissez votre Avenir Ensemble">
    <meta property="og:description" content="Découvrez notre Valentine Career Deal : Pack Solo ou Couple pour propulser votre carrière avec nos experts RH.">
    <meta property="og:image" content="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?q=80&w=1200&auto=format&fit=crop">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://moncvpro.hiremeguide.com/valentin.php">
    <meta property="twitter:title" content="Offre Spéciale Valentin ❤️ - Bâtissez votre Avenir Ensemble">
    <meta property="twitter:description" content="Pack Solo ou Couple pour propulser votre carrière avec nos experts RH. Profitez de la réduction limitée !">
    <meta property="twitter:image" content="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?q=80&w=1200&auto=format&fit=crop">

    <!-- Favicons -->
    <!-- Tailwind & Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Playfair+Display:ital,wght@0,700;0,900;1,700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                    },
                    colors: {
                        heart: '#ef4444',
                        'romantic-rose': '#f43f5e',
                        warm: '#fff7ed',
                        gold: '#fbbf24',
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-warm': 'pulseWarm 2s infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        },
                        pulseWarm: {
                            '0%, 100%': { opacity: 1, transform: 'scale(1)' },
                            '50%': { opacity: 0.8, transform: 'scale(1.05)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .romantic-gradient {
            background: linear-gradient(135deg, #fff1f2 0%, #fff7ed 100%);
        }
        .text-gradient {
            background: linear-gradient(to right, #ef4444, #f43f5e);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .heart-particle {
            position: absolute;
            pointer-events: none;
            z-index: 0;
            opacity: 0.3;
        }
    <!-- Facebook Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window,document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
     fbq('init', '1406940804387366'); 
    fbq('track', 'PageView');
    </script>
    <noscript>
     <img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=1406940804387366&ev=PageView&noscript=1"/>
    </noscript>
    <!-- End Facebook Pixel Code -->
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="romantic-gradient font-[Inter] text-slate-800 antialiased overflow-x-hidden">

    <!-- Floating Background Hearts (Simplified) -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute top-20 left-10 text-rose-200 animate-float" style="font-size: 40px;">❤️</div>
        <div class="absolute top-40 right-20 text-rose-100 animate-float" style="animation-delay: 2s; font-size: 60px;">❤️</div>
        <div class="absolute bottom-40 left-1/4 text-rose-100 animate-float" style="animation-delay: 4s; font-size: 30px;">❤️</div>
        <div class="absolute bottom-20 right-1/3 text-rose-200 animate-float" style="animation-delay: 1s; font-size: 50px;">❤️</div>
    </div>

    <!-- Top Urgency Banner -->
    <div class="bg-rose-600 text-white text-xs font-bold text-center py-2 px-4 sticky top-0 z-50 uppercase tracking-widest shadow-md">
        <span>⏰ OFFRE LIMITÉE — PLUS QUE <span id="bannerTimer">...</span> POUR PROFITER DU DEAL VALENTIN !</span>
    </div>

    <main class="relative z-10">
        
        <!-- Hero Section -->
        <header class="max-w-7xl mx-auto px-6 pt-16 pb-12 text-center lg:text-left grid lg:grid-cols-2 items-center gap-12">
            <div class="order-2 lg:order-1">
                <div class="inline-flex items-center gap-2 bg-rose-100 text-rose-600 px-4 py-1 rounded-full text-xs font-black uppercase tracking-widest mb-6">
                    <span>❤️ Saint-Valentin 2026</span>
                </div>
                <h1 class="font-serif text-5xl md:text-7xl font-black mb-6 leading-tight">
                    Bâtissez votre <span class="text-gradient">Avenir</span> Ensemble.
                </h1>
                <p class="text-lg md:text-xl text-slate-600 mb-8 leading-relaxed max-w-xl">
                    Parce que réussir sa carrière est le plus beau cadeau que l'on puisse s'offrir à soi-même ou à son partenaire. Découvrez notre <b>Valentine Career Deal</b> exclusif.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="#offres" class="bg-rose-600 hover:bg-rose-700 text-white px-8 py-4 rounded-2xl font-bold shadow-lg shadow-rose-200 transition-all transform hover:scale-105 text-center">
                        Voir les Packs &darr;
                    </a>
                    <a href="#pourquoi" class="bg-white text-rose-600 border border-rose-200 px-8 py-4 rounded-2xl font-bold hover:bg-rose-50 transition-all text-center">
                        En savoir plus
                    </a>
                </div>
            </div>
            <div class="order-1 lg:order-2 relative group px-4">
                <div class="absolute -inset-4 bg-rose-200/50 rounded-3xl blur-2xl group-hover:blur-3xl transition-all duration-700"></div>
                <img src="https://images.unsplash.com/photo-1621112904887-419379ce6824?q=80&w=800&auto=format&fit=crop" 
                     alt="Couple professionnel souriant" 
                     class="relative rounded-3xl shadow-2xl border-4 border-white transform hover:-rotate-1 transition-transform duration-500 w-full object-cover aspect-[4/3]">
                <!-- Heart Badge over Image -->
                <div class="absolute -bottom-6 -right-6 bg-white p-4 rounded-2xl shadow-xl border border-rose-100 animate-bounce">
                    <p class="text-rose-600 text-xs font-black uppercase tracking-widest text-center">Bonus Inclus</p>
                    <p class="text-slate-800 font-bold text-sm">Liste Top 20 Entreprises 🇨🇲</p>
                </div>
            </div>
        </header>

        <!-- What You Get (Iconic List) -->
        <section id="contenu" class="max-w-7xl mx-auto px-6 py-20">
            <div class="text-center mb-16">
                <h2 class="font-serif text-3xl md:text-5xl font-black mb-4">Ce que contient votre Pack</h2>
                <div class="w-20 h-1.5 bg-rose-600 mx-auto rounded-full"></div>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="glass p-6 md:p-8 rounded-3xl text-center hover:shadow-xl transition-shadow border-rose-100 group">
                    <div class="w-16 h-16 bg-rose-100 rounded-2xl flex items-center justify-center text-rose-600 text-3xl mb-6 mx-auto group-hover:bg-rose-600 group-hover:text-white transition-colors">📄</div>
                    <h3 class="text-xl font-bold mb-4">CV Professionnel (FR & EN)</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Design moderne et rédaction stratégique en Français et en Anglais pour maximiser vos chances partout.</p>
                </div>
                <!-- Feature 2 -->
                <div class="glass p-6 md:p-8 rounded-3xl text-center hover:shadow-xl transition-shadow border-rose-100 group">
                    <div class="w-16 h-16 bg-rose-100 rounded-2xl flex items-center justify-center text-rose-600 text-3xl mb-6 mx-auto group-hover:bg-rose-600 group-hover:text-white transition-colors">✉️</div>
                    <h3 class="text-xl font-bold mb-4">Lettre de Motivation (FR & EN)</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Des lettres d'accompagnement percutantes et personnalisées pour captiver les recruteurs dès les premières lignes.</p>
                </div>
                <!-- Feature 3 -->
                <div class="glass p-6 md:p-8 rounded-3xl text-center hover:shadow-xl transition-shadow border-rose-100 group">
                    <div class="w-16 h-16 bg-rose-100 rounded-2xl flex items-center justify-center text-rose-600 text-3xl mb-6 mx-auto group-hover:bg-rose-600 group-hover:text-white transition-colors">🏢</div>
                    <h3 class="text-xl font-bold mb-4">Top 20 Entreprises 🇨🇲</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Une liste exclusive de 20 entreprises au Cameroun qui recrutent activement en 2026. Allez droit au but.</p>
                </div>
            </div>
        </section>

        <!-- Pack Comparison -->
        <section id="offres" class="py-20 bg-white/50 backdrop-blur-sm">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-16">
                    <p class="text-rose-600 font-black uppercase tracking-widest text-sm mb-2">Choisir votre Deal</p>
                    <h2 class="font-serif text-3xl md:text-5xl font-black mb-4">Une offre pour chacun</h2>
                </div>

                <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                    <!-- Single Pack -->
                    <div class="glass p-6 md:p-10 rounded-[3rem] border-2 border-transparent hover:border-rose-200 transition-all group flex flex-col items-center text-center">
                        <div class="text-5xl mb-6 grayscale group-hover:grayscale-0 transition-opacity">👤</div>
                        <h3 class="text-2xl font-black mb-2">Pack Solo</h3>
                        <p class="text-slate-500 mb-6 text-sm">Investissez en vous-même.</p>
                        <div class="mb-8">
                            <span class="text-sm text-slate-400 line-through">10 200 XAF</span>
                            <div class="text-4xl font-black text-slate-900"><?= number_format($singlePrice, 0, ',', ' ') ?> <small class="text-sm font-bold">XAF</small></div>
                        </div>
                        <ul class="space-y-4 mb-10 text-left w-full text-slate-600">
                            <li class="flex items-center gap-3"><span class="text-rose-500 font-bold">✓</span> 1 CV (FR & EN)</li>
                            <li class="flex items-center gap-3"><span class="text-rose-500 font-bold">✓</span> 1 Lettre (FR & EN)</li>
                            <li class="flex items-center gap-3"><span class="text-rose-500 font-bold">✓</span> Liste 20 Entreprises 🇨🇲</li>
                        </ul>
                        <button onclick="scrollToForm('Solo', <?= $singlePrice ?>)" class="w-full bg-slate-800 text-white py-4 rounded-2xl font-bold hover:bg-slate-900 transition-colors">
                            Commander Solo
                        </button>
                    </div>

                    <!-- Couple Pack -->
                    <div class="bg-rose-600 p-6 md:p-10 rounded-[3rem] shadow-2xl shadow-rose-200 relative overflow-hidden group flex flex-col items-center text-center text-white transform md:scale-105">
                        <!-- Best Value Tag -->
                        <div class="absolute top-0 right-0 bg-yellow-400 text-rose-800 font-black text-[10px] uppercase py-2 px-8 rotate-45 translate-x-1/3 -translate-y-1/4 shadow-lg">MEILLEUR DEAL</div>
                        
                        <div class="text-5xl mb-6">💑</div>
                        <h3 class="text-2xl font-black mb-2">Pack Couple</h3>
                        <p class="text-rose-200 mb-6 text-sm">Bâtissez votre futur à deux.</p>
                        <div class="mb-8">
                            <span class="text-sm text-rose-300 line-through">20 400 XAF</span>
                            <div class="text-4xl font-black text-white"><?= number_format($couplePrice, 0, ',', ' ') ?> <small class="text-sm font-bold">XAF</small></div>
                        </div>
                        <ul class="space-y-4 mb-10 text-left w-full text-rose-100">
                            <li class="flex items-center gap-3"><span class="text-yellow-300 font-bold text-lg">★</span> <b>2 CVs</b> (FR & EN)</li>
                            <li class="flex items-center gap-3"><span class="text-yellow-300 font-bold text-lg">★</span> <b>2 Lettres</b> (FR & EN)</li>
                            <li class="flex items-center gap-3"><span class="text-yellow-300 font-bold text-lg">★</span> Liste 20 Entreprises 🇨🇲</li>
                        </ul>
                        <button onclick="scrollToForm('Couple', <?= $couplePrice ?>)" class="w-full bg-white text-rose-600 py-4 rounded-2xl font-bold hover:bg-rose-50 transition-colors shadow-lg">
                            Profiter à Deux
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Choose Us -->
        <section id="pourquoi" class="max-w-7xl mx-auto px-6 py-24 text-center">
            <h2 class="font-serif text-3xl md:text-5xl font-black mb-12">Pourquoi nous faire confiance ?</h2>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 md:gap-12">
                <div>
                    <div class="text-3xl md:text-4xl font-black text-rose-600 mb-2">+500</div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">CVs Rédigés</p>
                </div>
                <div>
                    <div class="text-3xl md:text-4xl font-black text-rose-600 mb-2">98%</div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Satisfaction</p>
                </div>
                <div>
                    <div class="text-3xl md:text-4xl font-black text-rose-600 mb-2">48H</div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Livraison Rapide</p>
                </div>
                <div>
                    <div class="text-3xl md:text-4xl font-black text-rose-600 mb-2">2026</div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Expertise Actuelle</p>
                </div>
            </div>
        </section>

        <!-- Final Order Form -->
        <section id="commande" class="py-16 md:py-24 romantic-gradient border-t border-rose-100">
            <div class="max-w-4xl mx-auto px-4 md:px-6">
                <div class="glass p-6 md:p-12 rounded-[2.5rem] md:rounded-[3rem] shadow-2xl relative">
                    <div class="text-center mb-10">
                        <h2 class="font-serif text-3xl font-black mb-2">Finalisez votre Commande</h2>
                        <p class="text-slate-500 text-sm">Remplissez le formulaire ci-dessous pour commencer.</p>
                    </div>

                    <form id="valentinForm" class="space-y-6">
                        <!-- Pack Selection Visual -->
                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <label class="cursor-pointer group">
                                <input type="radio" name="pack_type" value="Solo" class="peer hidden" onchange="updateFormPrice(<?= $singlePrice ?>)">
                                <div class="p-4 border-2 border-slate-100 rounded-2xl text-center peer-checked:border-rose-600 peer-checked:bg-rose-50 transition-all">
                                    <p class="text-sm font-bold group-hover:text-rose-600">Solo</p>
                                    <p class="text-xs text-slate-400">8 500 XAF</p>
                                </div>
                            </label>
                            <label class="cursor-pointer group">
                                <input type="radio" name="pack_type" value="Couple" class="peer hidden" onchange="updateFormPrice(<?= $couplePrice ?>)">
                                <div class="p-4 border-2 border-slate-100 rounded-2xl text-center peer-checked:border-rose-600 peer-checked:bg-rose-50 transition-all">
                                    <p class="text-sm font-bold group-hover:text-rose-600">Couple</p>
                                    <p class="text-xs text-slate-400">14 500 XAF</p>
                                </div>
                            </label>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black uppercase text-slate-400 mb-2 ml-1">Nom Complet</label>
                                <input type="text" id="name" required class="w-full bg-slate-50 border-0 rounded-2xl p-4 text-slate-900 focus:ring-2 focus:ring-rose-600">
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase text-slate-400 mb-2 ml-1">E-mail</label>
                                <input type="email" id="email" required class="w-full bg-slate-50 border-0 rounded-2xl p-4 text-slate-900 focus:ring-2 focus:ring-rose-600">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase text-slate-400 mb-2 ml-1">WhatsApp</label>
                            <input type="tel" id="whatsapp" required class="w-full bg-slate-50 border-0 rounded-2xl p-4 text-slate-900 focus:ring-2 focus:ring-rose-600" placeholder="6...">
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label class="block text-xs font-black uppercase text-slate-400 mb-4 ml-1">Moyen de Paiement</label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="cursor-pointer">
                                    <input type="radio" name="pay_method" value="MTN" class="peer hidden" checked onchange="updateUssd()">
                                    <div class="p-4 border border-slate-200 rounded-2xl text-center peer-checked:border-yellow-400 peer-checked:bg-yellow-50 transition-all">
                                        <p class="text-xs font-bold text-slate-600">MTN Mobile Money</p>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="pay_method" value="Orange" class="peer hidden" onchange="updateUssd()">
                                    <div class="p-4 border border-slate-200 rounded-2xl text-center peer-checked:border-orange-500 peer-checked:bg-orange-50 transition-all">
                                        <p class="text-xs font-bold text-slate-600">Orange Money</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- USSD Box -->
                        <div class="bg-slate-900 p-4 md:p-6 rounded-3xl text-center text-white">
                            <p class="text-[10px] uppercase font-bold text-slate-400 mb-2 tracking-widest">Composer le code suivant :</p>
                            <a id="ussdLink" href="#" class="block bg-slate-800 p-3 md:p-4 rounded-xl border border-slate-700 hover:bg-slate-700 transition-colors">
                                <span id="ussdCode" class="text-xl md:text-2xl font-mono font-black tracking-tighter text-yellow-400 break-all leading-tight">...</span>
                            </a>
                            <p class="text-[10px] text-slate-500 mt-3 italic">Bénéficiaire : NJAI NDINTEK HANDSON</p>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase text-slate-400 mb-2 ml-1">ID Transaction / Preuve</label>
                            <input type="text" id="transId" placeholder="ID de la transaction" required class="w-full bg-slate-50 border-0 rounded-2xl p-4 text-slate-900 mb-3 focus:ring-2 focus:ring-rose-600">
                            
                            <div class="flex items-center gap-2 mb-2 ml-1">
                                <span class="bg-rose-100 text-rose-600 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Requis</span>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest italic">Uploadez la capture d'écran de votre paiement</p>
                            </div>
                            <input type="file" id="proof" accept="image/*" required class="w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-6 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-white hover:file:bg-slate-900 focus:outline-none">
                        </div>

                        <button type="submit" id="submitBtn" class="w-full bg-rose-600 text-white font-black py-5 rounded-3xl shadow-xl shadow-rose-200 hover:scale-[1.02] transition-transform text-lg uppercase tracking-widest disabled:opacity-50">
                            BOOSTER MA CARRIÈRE ❤️
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-12 text-center text-slate-400 text-xs uppercase tracking-widest">
            <p>&copy; 2026 MonCv Pro. Tous droits réservés.</p>
            <div class="mt-4 space-x-4">
                <a href="index.html" class="hover:text-rose-600">Accueil</a>
                <a href="conditions-generales.html" class="hover:text-rose-600">CGV</a>
            </div>
        </footer>

    </main>

    <!-- Scripts -->
    <script>
        let currentPrice = <?= $singlePrice ?>;
        const endTime = new Date("<?= $promoEndTime ?>").getTime();

        function updateTimer() {
            const now = new Date().getTime();
            const diff = endTime - now;
            if (diff < 0) {
                document.getElementById('bannerTimer').innerText = "Vente Terminée";
                return;
            }
            const d = Math.floor(diff / (1000 * 60 * 60 * 24));
            const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const s = Math.floor((diff % (1000 * 60)) / 1000);
            
            let timerStr = "";
            if (d > 0) timerStr += d + "j ";
            timerStr += (h < 10 ? "0" + h : h) + "h " + (m < 10 ? "0" + m : m) + "m " + (s < 10 ? "0" + s : s) + "s";
            document.getElementById('bannerTimer').innerText = timerStr;
        }
        setInterval(updateTimer, 1000);
        updateTimer();

        window.scrollToForm = function(type, price) {
            const radio = document.querySelector('input[name="pack_type"][value="' + type + '"]');
            if(radio) radio.checked = true;
            updateFormPrice(price);
            document.getElementById('commande').scrollIntoView({ behavior: 'smooth' });
        }

        window.updateFormPrice = function(price) {
            currentPrice = price;
            updateUssd();
        }

        window.updateUssd = function() {
            const payMethod = document.querySelector('input[name="pay_method"]:checked').value;
            const ussdEl = document.getElementById('ussdCode');
            const linkEl = document.getElementById('ussdLink');
            
            if (payMethod === 'MTN') {
                const code = `*126*14*654250688*${currentPrice}#`;
                ussdEl.innerText = code;
                linkEl.href = `tel:${code.replace('#', '%23')}`;
            } else {
                const code = `#150*1*1*659197506*${currentPrice}#`;
                ussdEl.innerText = code;
                linkEl.href = `tel:${code.replace('#', '%23')}`;
            }
        }
        updateUssd();

        // Form Submission
        document.getElementById('valentinForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = "PATIENTEZ...";

            try {
                const formData = new FormData();
                formData.append('name', document.getElementById('name').value);
                formData.append('email', document.getElementById('email').value);
                formData.append('whatsapp', document.getElementById('whatsapp').value);
                formData.append('paymentMethod', document.querySelector('input[name="pay_method"]:checked').value);
                formData.append('transactionId', document.getElementById('transId').value);
                formData.append('proofFile', document.getElementById('proof').files[0]);
                
                const packType = document.querySelector('input[name="pack_type"]:checked')?.value || 'Inconnu';
                formData.append('package_name', 'VALENTIN ' + packType);
                formData.append('amount', currentPrice);

                const res = await fetch('save-order.php', { method: 'POST', body: formData });
                const json = await res.json();
                
                if (json.success) {
                    const params = new URLSearchParams({
                        name: document.getElementById('name').value,
                        pack: 'Valentin ' + packType
                    });
                    window.location.href = 'valentin-merci.php?' + params.toString();
                } else {
                    throw new Error(json.message || "Une erreur est survenue");
                }
            } catch (err) {
                alert(err.message);
                btn.disabled = false;
                btn.innerText = originalText;
            }
        });

        // Tracking & Stats (Simulated base on existing project pattern)
        fetch('api/get-flash-stats.php?page=valentin').catch(() => {});

        // --- LEAD CAPTURE SYSTEM ---
        const captureInputs = ['name', 'email', 'whatsapp'];
        const captureEvents = ['blur', 'input']; // Capture on blur and input for faster updates

        function captureLead() {
            const data = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                whatsapp: document.getElementById('whatsapp').value,
                source: 'Valentin',
                details: {
                    pack: document.querySelector('input[name="pack_type"]:checked')?.value || 'Not Selected',
                    price: currentPrice
                }
            };

            if (data.whatsapp.length > 5 || data.email.length > 5) {
                fetch('api/save_lead.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                }).catch(() => {}); // Silent fail
            }
        }

        captureInputs.forEach(id => {
            const el = document.getElementById(id);
            if(el) {
                el.addEventListener('blur', captureLead);
                // Debounce input to avoid spamming
                let timeout;
                el.addEventListener('input', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(captureLead, 1000);
                });
            }
        });
        
        // Also capture when pack changes
        document.querySelectorAll('input[name="pack_type"]').forEach(el => {
            el.addEventListener('change', captureLead);
        });
    </script>
</body>
</html>
