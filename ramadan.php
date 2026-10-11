<?php
require_once 'admin/config/db.php';
date_default_timezone_set('Africa/Douala');

// Settings
$promoEndTime = '2026-03-25 23:59:59';
$prices = [
    'Etudiant' => 1000,
    'Pro' => 3000,
    'Senior' => 8000,
    'Expert' => 30000
];
$oldPrices = [
    'Etudiant' => 1850,
    'Pro' => 4440,
    'Senior' => 11000,
    'Expert' => 33000
];
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
    <title>Offre Spéciale Ramadan | Mon CV Pro</title>
    <link rel="canonical" href="https://moncvpro.hiremeguide.com/ramadan.php" />
    <meta name="description" content="Offre Spéciale Ramadan : Célébrez le mois béni en boostant votre carrière avec nos tarifs exceptionnels sur les CV professionnels.">
    
    <!-- Open Graph -->
    <meta property="og:title" content="Offre Spéciale Ramadan | Mon CV Pro">
    <meta property="og:description" content="Saisissez nos offres exceptionnelles de Ramadan sur les packs de CV professionnels.">
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/img/ramadan-hero.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="https://moncvpro.hiremeguide.com/assets/img/ramadan-hero.png">

    <!-- Favicons -->
    <!-- Tailwind & Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,900;1,900&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                    },
                    colors: {
                        primary: '#059669', // emerald-600
                        accent: '#f59e0b', // amber-500
                        neutral: '#064e3b', // emerald-900
                        bg: '#f0fdf4', // emerald-50
                        darkBlue: '#022c22', // emerald-950
                        ramadan: '#d97706', // amber-600 for accents
                    }
                }
            }
        }
    </script>
    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        @keyframes whatsapp-shake {
            0%, 100% { transform: translateY(0) scale(1.1); }
            50% { transform: translateY(-10px) scale(1.15); }
        }
        .whatsapp-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 100;
            transition: all 0.3s ease;
            animation: whatsapp-shake 2s ease-in-out infinite;
            transform: scale(1.1);
        }
        .whatsapp-float:hover {
            animation: none;
            transform: scale(1.2) rotate(5deg);
        }
        .pattern-bg {
            background-color: #f0fdf4;
            background-image: radial-gradient(#dcfce7 1px, transparent 1px), radial-gradient(#fef3c7 1px, transparent 1px);
            background-size: 40px 40px;
            background-position: 0 0, 20px 20px;
        }
        .hero-gradient {
            background: linear-gradient(to bottom, rgba(5, 150, 105, 0.05), rgba(217, 119, 6, 0.05));
        }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="bg-bg text-neutral antialiased font-sans pattern-bg">

    <!-- Top Urgent Banner -->
    <div class="bg-neutral text-white text-[10px] md:text-sm font-bold text-center py-2.5 px-4 sticky top-0 z-50 uppercase tracking-widest shadow-md border-b-2 border-ramadan overflow-hidden whitespace-nowrap text-ellipsis">
        <span>OFFRE RAMADAN : PLUS QUE <span id="bannerTimer" class="text-accent font-black">...</span> POUR PROFITER DES BÉNÉDICTIONS !</span>
    </div>

    <!-- Navigation -->
    <nav class="glass-effect border-b border-gray-100 shadow-sm sticky top-[34px] md:top-[40px] z-40">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
            <span class="text-2xl font-extrabold text-primary tracking-tight">HireMe<span class="text-ramadan">CV</span></span>
            <div class="hidden md:flex gap-8">
                <a href="index.html" class="text-gray-600 hover:text-ramadan font-medium transition-colors">Accueil</a>
                <a href="cv-models" class="text-gray-600 hover:text-ramadan font-medium transition-colors">Modèles</a>
                <a href="https://wa.me/237654250688" class="text-gray-600 hover:text-ramadan font-medium transition-colors">Contact</a>
            </div>
        </div>
    </nav>

    <main>
        
        <!-- Hero Section -->
        <header class="relative overflow-hidden hero-gradient border-b border-green-100">
            <div class="max-w-7xl mx-auto px-4 pt-16 pb-16 md:pt-24 md:pb-24 grid md:grid-cols-2 gap-12 items-center">
                <div class="text-center md:text-left">
                    <div class="inline-flex items-center gap-2 bg-amber-50 text-ramadan px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-widest mb-8 border border-amber-100">
                        Mois Béni de Ramadan
                    </div>
                    <h1 class="text-4xl md:text-7xl font-black mb-6 leading-tight tracking-tight text-neutral">
                        Propulsez votre CV pendant le <span class="text-ramadan italic">Ramadan</span>.
                    </h1>
                    <p class="text-lg md:text-xl text-gray-600 mb-10 leading-relaxed max-w-xl">
                        À l'occasion du <strong>Mois de Ramadan</strong>, nous vous aidons à décrocher de nouvelles opportunités. Bénéficiez de tarifs exceptionnels pour un CV qui vous ressemble et qui performe.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start">
                        <a href="#offres" class="bg-ramadan hover:bg-amber-700 text-white px-10 py-4 rounded-full font-bold shadow-lg shadow-amber-200 transition-all transform hover:scale-105 text-center uppercase tracking-wide">
                            Voir les Tarifs Spéciaux
                        </a>
                        <a href="#commande" class="bg-white text-neutral border border-gray-200 px-10 py-4 rounded-full font-bold hover:bg-gray-50 transition-all text-center uppercase tracking-wide">
                            Commande Rapide
                        </a>
                    </div>
                </div>
                <div class="relative group">
                    <div class="absolute -inset-4 bg-gradient-to-tr from-primary to-ramadan opacity-20 blur-2xl rounded-full group-hover:opacity-30 transition-opacity"></div>
                    <img src="assets/img/ramadan-hero.png" alt="Ramadan Hero" class="relative rounded-[3rem] shadow-2xl border-4 border-white transform rotate-2 hover:rotate-0 transition-transform duration-700">
                    <div class="absolute -bottom-6 -right-6 bg-white p-6 rounded-3xl shadow-xl border border-gray-100 max-w-[200px] hidden md:block animate-bounce-slow">
                        <p class="text-xs font-bold text-ramadan uppercase tracking-widest mb-1">Bénédictions</p>
                        <p class="text-sm font-black text-neutral uppercase">Préparez votre avenir professionnel.</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Pricing Section -->
        <section id="offres" class="py-16 md:py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-16">
                    <p class="text-ramadan font-bold uppercase tracking-widest text-xs mb-2">Offre de Ramadan</p>
                    <h2 class="text-3xl md:text-5xl font-black mb-4 text-neutral tracking-tight">Tarifs Exceptionnellement Réduits</h2>
                    <p class="text-gray-500 text-sm max-w-xl mx-auto italic">Saisissez l'opportunité de ce mois béni pour booster votre carrière.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach($prices as $pack => $price): ?>
                    <div class="bg-bg p-8 rounded-3xl border border-green-100 hover:border-ramadan transition-all group flex flex-col h-full shadow-sm hover:shadow-xl relative overflow-hidden">
                        <?php if($pack == 'Pro'): ?>
                        <div class="absolute top-0 right-0 bg-ramadan text-white text-[9px] font-black px-4 py-1.5 uppercase tracking-widest rounded-bl-2xl">MIEUX NOTÉ</div>
                        <?php endif; ?>
                        <h3 class="text-xl font-bold mb-4 text-neutral">Pack <?= $pack ?></h3>
                        <div class="mb-8">
                            <span class="text-xs text-gray-400 line-through font-medium"><?= number_format($oldPrices[$pack], 0, ',', ' ') ?> XAF</span>
                            <div class="text-3xl font-extrabold text-ramadan"><?= number_format($price, 0, ',', ' ') ?> <small class="text-xs font-bold uppercase">XAF</small></div>
                        </div>
                        <ul class="space-y-4 mb-10 text-sm text-gray-700 flex-grow">
                            <li class="flex items-center gap-3"><svg class="w-4 h-4 text-ramadan" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>CV Design Premium</li>
                            <li class="flex items-center gap-3"><svg class="w-4 h-4 text-ramadan" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>Optimisation ATS</li>
                            <?php if($pack == 'Pro' || $pack == 'Senior' || $pack == 'Expert'): ?>
                            <li class="flex items-center gap-3"><svg class="w-4 h-4 text-ramadan" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>Lettre de Motivation</li>
                            <?php endif; ?>
                            <?php if($pack == 'Senior' || $pack == 'Expert'): ?>
                            <li class="flex items-center gap-3"><svg class="w-4 h-4 text-ramadan" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>Optimisation LinkedIn</li>
                            <?php endif; ?>
                            <?php if($pack == 'Expert'): ?>
                            <li class="flex items-center gap-3"><svg class="w-4 h-4 text-ramadan" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>CV Français & Anglais</li>
                            <?php endif; ?>
                        </ul>
                        <button onclick="scrollToForm('<?= $pack ?>', <?= $price ?>)" class="w-full bg-neutral text-white py-4 rounded-2xl font-bold hover:bg-ramadan transition-colors shadow-lg shadow-gray-200 uppercase text-xs tracking-widest">
                            Choisir ce Pack
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Order Form Section -->
        <section id="commande" class="py-16 md:py-24 bg-bg">
            <div class="max-w-3xl mx-auto px-4">
                <div class="bg-white p-8 md:p-12 rounded-[2.5rem] shadow-2xl border border-green-50">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl font-black mb-2 text-neutral tracking-tight">Finalisez votre Commande</h2>
                        <p class="text-gray-500 text-sm">Précision et rapidité : nos experts s'occupent de tout.</p>
                    </div>

                    <form id="promoForm" class="space-y-10">
                        <!-- Pack Selection -->
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-500 mb-4 tracking-widest">1. Choisir votre Pack</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <?php foreach($prices as $pack => $price): ?>
                                <label class="cursor-pointer group">
                                    <input type="radio" name="pack_type" value="<?= $pack ?>" data-price="<?= $price ?>" class="peer hidden">
                                    <div class="p-4 border-2 border-gray-100 rounded-2xl text-center peer-checked:border-ramadan peer-checked:bg-amber-50 transition-all hover:bg-green-50">
                                        <p class="text-xs font-bold"><?= $pack ?></p>
                                        <p class="text-[10px] text-gray-500"><?= number_format($price, 0, ',', ' ') ?> F</p>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- User Info -->
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase text-gray-500 ml-1">Nom Complet</label>
                                <input type="text" id="name" name="name" required class="w-full bg-bg border-0 rounded-2xl px-6 py-4 text-neutral focus:ring-2 focus:ring-ramadan transition-all" placeholder="Votre nom">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase text-gray-500 ml-1">Numéro WhatsApp</label>
                                <input type="tel" id="whatsapp" name="whatsapp" required class="w-full bg-bg border-0 rounded-2xl px-6 py-4 text-neutral focus:ring-2 focus:ring-ramadan transition-all" placeholder="6...">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase text-gray-500 ml-1">E-mail (Facultatif)</label>
                            <input type="email" id="email" name="email" class="w-full bg-bg border-0 rounded-2xl px-6 py-4 text-neutral focus:ring-2 focus:ring-ramadan transition-all" placeholder="votre@email.com">
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-500 mb-4 ml-1">2. Moyen de Paiement</label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="cursor-pointer">
                                    <input type="radio" name="pay_method" value="MTN" class="peer hidden" checked onchange="updateUssd()">
                                    <div class="p-6 border-2 border-gray-100 rounded-2xl text-center peer-checked:border-yellow-400 peer-checked:bg-yellow-50 transition-all flex flex-col items-center gap-3">
                                        <div class="h-8 flex items-center justify-center">
                                             <img src="https://upload.wikimedia.org/wikipedia/commons/9/93/New-mtn-logo.jpg" alt="MTN" class="h-8 rounded-md object-contain">
                                        </div>
                                        <p class="text-[10px] font-bold text-gray-600 uppercase tracking-tighter">MTN MoMo</p>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="pay_method" value="Orange" class="peer hidden" onchange="updateUssd()">
                                    <div class="p-6 border-2 border-gray-100 rounded-2xl text-center peer-checked:border-orange-500 peer-checked:bg-orange-50 transition-all flex flex-col items-center gap-3">
                                        <div class="h-8 flex items-center justify-center">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/c/c8/Orange_logo.svg" alt="Orange" class="h-8 rounded-md object-contain">
                                        </div>
                                        <p class="text-[10px] font-bold text-gray-600 uppercase tracking-tighter">Orange Money</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- USSD Box -->
                        <div class="bg-neutral p-8 rounded-[2rem] text-center text-white relative overflow-hidden">
                            <p class="text-[10px] uppercase font-bold text-green-200 mb-4 tracking-widest">Code à composer sur votre téléphone :</p>
                            <a id="ussdLink" href="#" class="block bg-white/5 p-5 rounded-2xl border border-white/10 hover:bg-white/10 transition-colors">
                                <span id="ussdCode" class="text-xl md:text-3xl font-mono font-extrabold tracking-tighter text-ramadan break-all leading-none">...</span>
                            </a>
                            <p class="text-[9px] text-green-200 mt-4 uppercase tracking-widest">Bénéficiaire : NJAI NDINTEK HANDSON</p>
                        </div>

                        <!-- Transaction ID -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase text-gray-500 ml-1">4. ID de Transaction</label>
                            <input type="text" id="transactionId" name="transactionId" required class="w-full bg-bg border-0 rounded-2xl px-6 py-4 text-neutral focus:ring-2 focus:ring-ramadan transition-all font-mono" placeholder="Ex: 17718...">
                            <p class="text-[9px] text-gray-500 uppercase tracking-tighter ml-1">Saisissez l'ID reçu par SMS après le paiement</p>
                        </div>

                        <!-- Proof Upload -->
                        <div class="pt-2">
                            <label class="block text-xs font-bold uppercase text-gray-500 mb-4 ml-1">3. Capture d'écran du Paiement</label>
                            <div class="bg-bg border-2 border-dashed border-green-200 rounded-[2rem] p-8 text-center relative hover:border-ramadan transition-all">
                                <input type="file" id="proof" name="proof" accept="image/*" required class="absolute inset-0 opacity-0 cursor-pointer">
                                <div id="previewLabel" class="space-y-2">
                                    <p class="text-sm font-bold text-gray-600 uppercase tracking-wide">Ajouter la preuve de transaction</p>
                                    <p class="text-[10px] text-gray-500 uppercase tracking-tighter">Format Image Uniquement</p>
                                </div>
                            </div>
                        </div>

                        <button type="submit" id="submitBtn" class="w-full bg-ramadan text-white font-extrabold py-5 rounded-full shadow-xl shadow-amber-200 hover:bg-amber-700 transition-all text-lg tracking-wide uppercase active:scale-95">
                            VALIDER MA COMMANDE
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-16 text-center border-t border-green-50 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <p class="text-2xl font-black text-primary tracking-tighter mb-4">MonCV<span class="text-ramadan">Pro</span></p>
                <p class="text-gray-500 text-[10px] uppercase tracking-widest font-bold">&copy; 2026 HireMe Guide Services. Ramadan Mubarak !</p>
                <div class="flex justify-center gap-6 mt-6">
                    <a href="index.html" class="text-xs text-gray-400 hover:text-ramadan font-bold uppercase tracking-widest transition">Accueil</a>
                    <a href="https://wa.me/237654250688" class="text-xs text-gray-400 hover:text-ramadan font-bold uppercase tracking-widest transition">Assistance</a>
                </div>
            </div>
        </footer>

    </main>

    <!-- WhatsApp Hover Button -->
    <a href="https://wa.me/237654250688?text=Bonjour,%20j'ai%20besoin%20d'aide%20pour%20profiter%20de%20l'offre%20sp%C3%A9ciale%20Ramadan." target="_blank" class="whatsapp-float bg-[#25D366] shadow-2xl p-4 rounded-full text-white flex items-center justify-center">
        <svg class="h-8 w-8 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.149-1.04 1.016-1.04 2.479 0 1.462 1.066 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
    </a>

    <!-- Scripts -->
    <script>
        let currentPrice = 1000;
        const endTime = new Date("<?= $promoEndTime ?>").getTime();

        function updateTimer() {
            const now = new Date().getTime();
            const diff = endTime - now;
            const timerEl = document.getElementById('bannerTimer');
            if (diff < 0) {
                if(timerEl) timerEl.innerText = "OFFRE TERMINÉE";
                return;
            }
            const d = Math.floor(diff / (1000 * 60 * 60 * 24));
            const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const s = Math.floor((diff % (1000 * 60)) / 1000);
            
            let timerStr = "";
            if (d > 0) timerStr += d + "j ";
            timerStr += (h < 10 ? "0" + h : h) + "h " + (m < 10 ? "0" + m : m) + "m " + (s < 10 ? "0" + s : s) + "s";
            if(timerEl) timerEl.innerText = timerStr;
        }
        setInterval(updateTimer, 1000);
        updateTimer();

        window.scrollToForm = function(type, price) {
            const radio = document.querySelector('input[name="pack_type"][value="' + type + '"]');
            if(radio) radio.checked = true;
            currentPrice = price;
            updateUssd();
            document.getElementById('commande').scrollIntoView({ behavior: 'smooth' });
            captureLead();
        }

        window.updateUssd = function() {
            const payMethodRadio = document.querySelector('input[name="pay_method"]:checked');
            if(!payMethodRadio) return;
            const payMethod = payMethodRadio.value;
            const ussdEl = document.getElementById('ussdCode');
            const linkEl = document.getElementById('ussdLink');
            const checkedPack = document.querySelector('input[name="pack_type"]:checked');
            if(checkedPack) currentPrice = checkedPack.dataset.price;

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

        document.querySelectorAll('input[name="pack_type"]').forEach(el => {
            el.addEventListener('change', () => { updateUssd(); captureLead(); });
        });

        document.getElementById('proof').addEventListener('change', function(e) {
            const label = document.getElementById('previewLabel');
            if(this.files && this.files[0]) {
                label.innerHTML = `<p class="text-accent font-bold">✅ Prêt : ${this.files[0].name.substring(0, 15)}...</p>`;
            }
        });

        document.getElementById('promoForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerHTML;
            const packRadio = document.querySelector('input[name="pack_type"]:checked');
            if(!packRadio) { alert("Veuillez sélectionner un pack."); return; }

            btn.disabled = true;
            btn.innerText = "TRAITEMENT...";

            try {
                const formData = new FormData();
                formData.append('name', document.getElementById('name').value);
                formData.append('whatsapp', document.getElementById('whatsapp').value);
                formData.append('email', document.getElementById('email').value);
                formData.append('paymentMethod', document.querySelector('input[name="pay_method"]:checked').value);
                formData.append('proofFile', document.getElementById('proof').files[0]);
                formData.append('transactionId', document.getElementById('transactionId').value);
                formData.append('package_name', 'RAMADAN ' + packRadio.value);
                formData.append('amount', currentPrice);

                const res = await fetch('save-order.php', { method: 'POST', body: formData });
                const json = await res.json();
                
                if (json.success) {
                    window.location.href = `ramadan-merci.php?name=${encodeURIComponent(document.getElementById('name').value)}&pack=${encodeURIComponent('Offre Ramadan ' + packRadio.value)}`;
                } else {
                    throw new Error(json.message || "Erreur lors de l'enregistrement");
                }
            } catch (err) {
                alert(err.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });

        // Lead Capture Logic
        function captureLead() {
            const whatsapp = document.getElementById('whatsapp').value;
            const name = document.getElementById('name').value;
            const pack = document.querySelector('input[name="pack_type"]:checked')?.value || 'Inconnu';

            if(whatsapp.length > 5) {
                fetch('api/save_lead.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        name: name,
                        whatsapp: whatsapp,
                        email: document.getElementById('email').value,
                        source: 'Ramadan-Promo',
                        details: `Pack Target: ${pack}`
                    })
                });
            }
        }

        ['name', 'whatsapp', 'email'].forEach(id => {
            const el = document.getElementById(id);
            el.addEventListener('blur', captureLead);
        });

        setTimeout(updateUssd, 200);
    </script>
</body>
</html>
