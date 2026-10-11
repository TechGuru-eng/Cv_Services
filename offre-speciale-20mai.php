<?php
require_once 'admin/config/db.php';
date_default_timezone_set('Africa/Douala');

// Settings
$promoEndTime = '2026-05-21 23:59:59';
$prices = [
    'Etudiant' => 1000,
    'Pro' => 2500,
    'Senior' => 7500,
    'Expert' => 65000
];
$oldPrices = [
    'Etudiant' => 2000,
    'Pro' => 5000,
    'Senior' => 11000,
    'Expert' => 100000
];

$packFeatures = [
    'Etudiant' => ['CV Design Simple', 'Correction basique', 'Livraison PDF'],
    'Pro' => ['CV Design Premium', 'Lettre de Motivation', 'Optimisation Mots-clés', 'Format Word + PDF'],
    'Senior' => ['Tout du Pack Pro', 'Optimisation LinkedIn', 'Réécriture complète'],
    'Expert' => ['CV Français + Anglais', 'Portfolio Professionnel', 'Priorité absolue']
];
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
<!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '984926333941411');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=984926333941411&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Édition Spéciale 20 Mai 🇨🇲 | Excellence & Unité Nationale</title>
    <meta property="og:title" content="Édition Spéciale 20 Mai 🇨🇲 | Excellence & Unité Nationale">
    <meta property="og:description" content="Célébrez l'Unité Nationale avec -50% sur tous nos packs CV. Propulsez votre carrière avec la rigueur de l'élite.">
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/20mai-promo-preview.png">
    <meta property="og:url" content="https://moncvpro.hiremeguide.com/offre-speciale-20mai.php">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="https://moncvpro.hiremeguide.com/assets/20mai-promo-preview.png">
    
    <!-- Favicons -->
    <!-- Tailwind & Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;900&family=Playfair+Display:ital,wght@0,900;1,900&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                    },
                    colors: {
                        premiumGreen: '#002c22',
                        accentGold: '#D4AF37',
                        cmRed: '#CE1126',
                        cmYellow: '#FCD116',
                    }
                }
            }
        }
    </script>
    <style>
        .premium-blur { backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .hero-zoom { animation: slow-zoom 20s infinite alternate; }
        @keyframes slow-zoom { from { transform: scale(1); } to { transform: scale(1.15); } }
        
        .whatsapp-pulse { animation: pulse-green 2s infinite; }
        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(37, 211, 102, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }
        }

        .gold-shimmer {
            background: linear-gradient(45deg, #D4AF37 25%, #FFF9E3 50%, #D4AF37 75%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shimmer 3s linear infinite;
        }
        @keyframes shimmer { to { background-position: 200% center; } }
        
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="bg-[#000d0a] text-white antialiased font-sans">

    <!-- Audio Engine -->
    <audio id="bgMusic" loop>
        <source src="https://storage.googleapis.com/producer-app-public/clips/4ea529ee-b881-4f5b-96bb-766b38dc74cc.m4a" type="audio/mp4">
    </audio>

    <!-- Music Toggle -->
    <button id="musicToggle" class="fixed bottom-8 left-8 z-[100] glass p-4 rounded-full shadow-2xl border border-white/20 transition-all hover:scale-110 group">
        <div class="flex items-center gap-2">
            <div id="soundWave" class="hidden flex items-end gap-0.5 h-4">
                <div class="w-0.5 bg-cmYellow animate-[bounce_1s_infinite_0.1s]"></div>
                <div class="w-0.5 bg-cmYellow animate-[bounce_1s_infinite_0.3s]"></div>
                <div class="w-0.5 bg-cmYellow animate-[bounce_1s_infinite_0.5s]"></div>
            </div>
            <svg id="muteIcon" class="w-5 h-5 fill-slate-400" viewBox="0 0 24 24"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.06.05-.11.1-.17.14L16.27 21 21 16.27 22.27 15 4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>
            <span class="text-[9px] font-black uppercase tracking-widest text-white hidden group-hover:block ml-2">Musique</span>
        </div>
    </button>

    <!-- Assistance WhatsApp -->
    <a href="https://wa.me/237654250688" class="fixed bottom-8 right-8 z-[100] bg-[#25D366] p-5 rounded-full shadow-2xl whatsapp-pulse hover:scale-110 transition-all">
        <svg class="w-8 h-8 fill-white" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.149-1.04 1.016-1.04 2.479 0 1.462 1.066 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
    </a>

    <!-- Logo / Brand -->
    <a href="index.html" class="fixed top-20 left-4 md:left-8 z-50 text-2xl font-black italic tracking-tighter hover:scale-105 transition-transform group">
        HireMe<span class="text-cmRed group-hover:text-cmYellow transition-colors">20Mai</span>
    </a>

    <!-- Top Banner -->
    <div class="bg-cmRed text-white text-[10px] md:text-xs font-black text-center py-3 px-4 sticky top-0 z-50 uppercase tracking-[0.3em]">
        🇨🇲 FÊTE NATIONALE : -50% SUR L'EXCELLENCE — FIN DANS <span id="bannerTimer" class="text-cmYellow italic">...</span> 🇨🇲
    </div>

    <main>
        <!-- Section 1: Hero (Military Precision) -->
        <header class="relative min-h-[90vh] md:h-screen flex items-center justify-center overflow-hidden px-4">
            <div class="absolute inset-0 z-0">
                <img src="premium_military_precision_1778814136794.png" class="w-full h-full object-cover opacity-40 hero-zoom">
                <div class="absolute inset-0 bg-gradient-to-b from-black/20 via-[#000d0a]/90 to-[#000d0a]"></div>
            </div>

            <div class="max-w-7xl mx-auto relative z-10 text-center py-16">
                <p class="text-[9px] font-black uppercase tracking-[0.5em] mb-6 text-cmYellow">Vigueur • Discipline • Succès</p>
                <h1 class="text-4xl sm:text-6xl md:text-[9rem] font-black leading-tight md:leading-[0.8] tracking-tighter uppercase italic mb-10">
                    L'Élite <br> <span class="gold-shimmer">Camerounaise</span>
                </h1>
                <p class="text-base md:text-2xl text-slate-400 max-w-2xl mx-auto font-light leading-relaxed mb-10">
                    Le 20 Mai, nous célébrons l'Unité. Aujourd'hui, nous propulsons votre carrière avec la rigueur des plus grands.
                </p>
                <a href="#offres" class="inline-block bg-cmRed px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-[10px] md:text-sm hover:scale-105 transition-transform active:scale-95 shadow-xl">
                    Débloquer mon Pack -50%
                </a>
            </div>
        </header>

        <!-- Section 2: Cultural Identity (Toghu Handshake) -->
        <section class="py-20 md:py-32 bg-[#000d0a] px-4">
            <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-12 md:gap-20 items-center">
                <div class="relative group order-2 md:order-1">
                    <img src="premium_unity_handshake_1778814122457.png" class="rounded-[2.5rem] md:rounded-[4rem] shadow-2xl border border-white/10 transition-transform duration-1000">
                    <div class="absolute bottom-4 left-4 right-4 md:-bottom-10 md:-left-10 bg-cmYellow text-darkGreen p-6 md:p-8 rounded-3xl md:rounded-[3rem] font-black">
                        <p class="text-[9px] uppercase tracking-widest mb-1 text-premiumGreen">Force de l'Union</p>
                        <p class="text-xl md:text-2xl italic tracking-tighter">Votre CV, <br>Notre Fierté.</p>
                    </div>
                </div>
                <div class="space-y-6 md:space-y-8 order-1 md:order-2">
                    <h2 class="text-3xl md:text-7xl font-black tracking-tighter leading-none uppercase italic">Une Culture <br><span class="text-cmYellow italic">De La Gagne.</span></h2>
                    <p class="text-slate-400 text-base md:text-xl font-light leading-relaxed">
                        Chaque CV que nous concevons est une pièce unique, comme les motifs d'un <span class="text-white font-bold">Toghu</span>.
                    </p>
                    <div class="flex gap-8 md:gap-12">
                        <div>
                            <p class="text-3xl md:text-5xl font-black text-white">20/20</p>
                            <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest mt-1">Qualité</p>
                        </div>
                        <div>
                            <p class="text-3xl md:text-5xl font-black text-white">24h</p>
                            <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest mt-1">Vitesse</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 3: The Monument of Success -->
        <section class="py-20 md:py-32 relative overflow-hidden px-4">
            <div class="absolute inset-0 z-0">
                <img src="premium_landmark_unity_1778814166725.png" class="w-full h-full object-cover opacity-20">
                <div class="absolute inset-0 bg-gradient-to-b md:bg-gradient-to-r from-[#000d0a] via-transparent to-[#000d0a]"></div>
            </div>
            
            <div class="max-w-7xl mx-auto relative z-10 grid grid-cols-1 md:grid-cols-2 gap-12 md:gap-20 items-center">
                <div class="order-2 md:order-1">
                    <h2 class="text-3xl md:text-7xl font-black tracking-tighter leading-tight uppercase italic mb-6">Bâtir <br><span class="gold-shimmer italic">L'Impossible.</span></h2>
                    <p class="text-slate-400 text-base md:text-xl font-light leading-relaxed mb-8">
                        Comme le Monument de la Réunification, votre carrière doit s'élever.
                    </p>
                    <img src="premium_modern_leader_1778814151701.png" class="w-24 h-24 md:w-32 md:h-32 rounded-full border-2 md:border-4 border-cmYellow shadow-2xl object-cover">
                </div>
                <div class="order-1 md:order-2">
                    <div class="glass-card p-8 md:p-12 rounded-3xl md:rounded-[4rem]">
                        <p class="text-2xl md:text-4xl font-serif italic text-cmYellow mb-4">"Mon CV a changé de dimension."</p>
                        <p class="text-[10px] font-black uppercase tracking-widest text-white">— Dr. Amadou, Yaoundé</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 4: Pricing (The Vision) -->
        <section id="offres" class="py-32 bg-[#000d0a]">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-24">
                    <h2 class="text-5xl md:text-8xl font-black italic gold-shimmer uppercase tracking-tighter">Les Offres de l'Unité</h2>
                    <p class="text-slate-500 font-black uppercase tracking-[0.4em] text-[10px] mt-4">Une nation forte, des CV puissants</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <?php foreach($prices as $pack => $price): ?>
                    <div class="glass-card p-10 rounded-[3rem] hover:border-cmYellow/50 transition-all group relative flex flex-col h-full overflow-hidden">
                        <?php if($pack == 'Pro'): ?>
                        <div class="absolute top-0 right-0 bg-cmYellow text-black text-[9px] font-black px-6 py-2 uppercase tracking-widest rounded-bl-3xl">POPULAIRE</div>
                        <?php endif; ?>
                        
                        <h3 class="text-3xl font-black mb-6 text-white italic tracking-tighter">Pack <?= $pack ?></h3>
                        
                        <div class="mb-10">
                            <span class="text-sm text-slate-600 line-through font-bold"><?= number_format($oldPrices[$pack], 0, ',', ' ') ?> F</span>
                            <div class="text-5xl font-black text-cmYellow tracking-tighter mt-1"><?= number_format($price, 0, ',', ' ') ?> <small class="text-xs font-black">F</small></div>
                        </div>

                        <ul class="space-y-6 mb-12 text-sm text-slate-400 font-medium flex-grow">
                            <?php foreach($packFeatures[$pack] as $feature): ?>
                            <li class="flex items-center gap-3">
                                <span class="w-1 h-1 bg-cmRed rounded-full"></span> 
                                <?= $feature ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>

                        <button onclick="scrollToForm('<?= $pack ?>', <?= $price ?>)" class="w-full bg-white text-black py-5 rounded-3xl font-black hover:bg-cmYellow transition-all uppercase text-[10px] tracking-widest">
                            CHOISIR CE PACK
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Final Horizon Image -->
        <section class="h-96 relative">
            <img src="premium_success_horizon_1778814182183.png" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-[#000d0a]/60 flex items-center justify-center">
                <h2 class="text-4xl md:text-7xl font-black italic uppercase gold-shimmer text-center">L'Avenir Vous Appartient.</h2>
            </div>
        </section>

        <!-- Command Form -->
        <section id="commande" class="py-32 bg-[#000806]">
            <div class="max-w-4xl mx-auto px-4">
                <div class="glass-card p-12 md:p-20 rounded-[4rem] border-white/10 shadow-2xl">
                    <div class="text-center mb-16">
                        <p class="text-[10px] font-black text-cmRed uppercase tracking-[0.5em] mb-4">Protocole de Commande</p>
                        <h2 class="text-4xl font-black uppercase italic tracking-tighter">Prise de Commande Tactique</h2>
                    </div>

                    <form id="uniteForm" class="space-y-12">
                        <!-- Pack Selection -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <?php foreach($prices as $pack => $price): ?>
                            <label class="cursor-pointer group">
                                <input type="radio" name="pack_type" value="<?= $pack ?>" data-price="<?= $price ?>" class="peer hidden">
                                <div class="p-6 border border-white/10 rounded-3xl text-center peer-checked:border-cmYellow peer-checked:bg-cmYellow/10 transition-all hover:bg-white/5">
                                    <p class="text-[9px] font-black text-slate-500 group-hover:text-white uppercase"><?= $pack ?></p>
                                    <p class="text-sm font-black text-white"><?= number_format($price, 0, ',', ' ') ?>F</p>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>

                        <!-- Data Fields -->
                        <div class="grid md:grid-cols-2 gap-8">
                            <input type="text" id="name" name="name" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-5 text-white font-bold focus:ring-2 focus:ring-cmYellow outline-none transition-all" placeholder="Nom Complet">
                            <input type="tel" id="whatsapp" name="whatsapp" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-5 text-white font-bold focus:ring-2 focus:ring-cmYellow outline-none transition-all" placeholder="WhatsApp (6...)">
                        </div>
                        
                        <!-- Payment Hub -->
                        <div class="grid grid-cols-2 gap-6">
                            <label class="cursor-pointer">
                                <input type="radio" name="pay_method" value="MTN" class="peer hidden" checked onchange="updateUssd()">
                                <div class="p-8 border-2 border-white/10 rounded-[3rem] text-center peer-checked:border-yellow-400 peer-checked:bg-yellow-400/5 transition-all">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/9/93/New-mtn-logo.jpg" class="h-10 mx-auto rounded-lg mb-4">
                                    <p class="text-[10px] font-black text-white uppercase">MTN MoMo</p>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="pay_method" value="Orange" class="peer hidden" onchange="updateUssd()">
                                <div class="p-8 border-2 border-white/10 rounded-[3rem] text-center peer-checked:border-orange-500 peer-checked:bg-orange-500/5 transition-all">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/c/c8/Orange_logo.svg" class="h-10 mx-auto rounded-lg mb-4">
                                    <p class="text-[10px] font-black text-white uppercase">Orange Money</p>
                                </div>
                            </label>
                        </div>

                        <div class="bg-black/60 p-6 md:p-10 rounded-[2.5rem] md:rounded-[3rem] border border-white/5 text-center overflow-hidden">
                            <p class="text-[9px] uppercase font-black text-slate-500 mb-4 tracking-[0.2em]">Cliquer pour composer le code</p>
                            <a id="ussdLink" href="#" class="block bg-white/5 p-6 md:p-8 rounded-3xl border border-white/10 hover:border-cmYellow transition-all group">
                                <span id="ussdCode" class="text-base sm:text-xl md:text-4xl font-mono font-black text-white tracking-tighter group-hover:text-cmYellow break-all leading-tight">...</span>
                            </a>
                            <p class="text-[8px] text-slate-600 mt-4 uppercase font-bold tracking-widest">Bénéficiaire : NJAI NDINTEK HANDSON</p>
                        </div>
                            <p class="text-[9px] text-slate-500 mt-6 font-bold uppercase tracking-widest text-center"> NJAI NDINTEK HANDSON</p>
                        </div>

                        <div class="space-y-8">
                            <input type="text" id="transactionId" name="transactionId" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-5 text-white font-mono font-bold text-center text-xl focus:ring-2 focus:ring-cmYellow outline-none" placeholder="ID TRANSACTION">
                            
                            <div class="bg-white/5 border-2 border-dashed border-white/10 rounded-[3.5rem] p-12 text-center relative hover:border-cmYellow transition-all">
                                <input type="file" id="proof" name="proof" accept="image/*" required class="absolute inset-0 opacity-0 cursor-pointer">
                                <div id="previewLabel">
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Capture d'écran du paiement</p>
                                </div>
                            </div>
                        </div>

                        <button type="submit" id="submitBtn" class="w-full bg-cmYellow text-black font-black py-8 rounded-[2.5rem] shadow-[0_20px_50px_rgba(252,209,22,0.3)] hover:scale-[1.02] active:scale-95 transition-all text-xl tracking-[0.2em] uppercase">
                            FINALISER MA COMMANDE 🇨🇲
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Section 5: Complementary Offers (Chariow Hub) -->
        <section id="accessoires" class="py-32 bg-[#000806] px-4">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-5xl font-black uppercase italic tracking-tighter gold-shimmer">Accessoires du Succès</h2>
                    <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px] mt-4">Complétez votre dossier pour un impact maximum</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Pack 300 Lettres -->
                    <div class="glass-card p-8 rounded-[3rem] text-center border-t-4 border-cmYellow">
                        <h3 class="text-xl font-black text-white mb-2 italic">Pack 300 Lettres</h3>
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-6 italic">Modèles de Motivation</p>
                        
                        <div class="mb-8">
                            <span class="text-sm text-slate-600 line-through font-bold">5 000 F</span>
                            <div class="text-4xl font-black text-cmYellow tracking-tighter">750 <small class="text-xs">F</small></div>
                        </div>

                        <!-- Chariow Widget -->
                        <div class="flex justify-center">
                            <div id="chariow-widget" data-product-id="prd_805091"
                                data-store-domain="boutique.hiremeguide.com"
                                data-style="showcase"
                                data-border-style="rounded"
                                data-cta-width="xs"
                                data-cta-animation="shine"
                                data-locale="en"
                                data-primary-color="#ffcc00"
                                data-background-color="#FFFFFF"></div>
                        </div>
                    </div>

                    <!-- 60 Modèles CV -->
                    <div class="glass-card p-8 rounded-[3rem] text-center border-t-4 border-cmYellow">
                        <h3 class="text-xl font-black text-white mb-2 italic">60 Modèles CV</h3>
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-6 italic">Modèles Modifiables</p>
                        
                        <div class="mb-8">
                            <span class="text-sm text-slate-600 line-through font-bold">11 000 F</span>
                            <div class="text-4xl font-black text-cmYellow tracking-tighter">3 000 <small class="text-xs">F</small></div>
                        </div>

                        <!-- Chariow Widget -->
                        <div class="flex justify-center">
                            <div id="chariow-widget" data-product-id="prd_ip7non"
                                data-store-domain="boutique.hiremeguide.com"
                                data-style="showcase"
                                data-border-style="rounded"
                                data-cta-width="xs"
                                data-cta-animation="shine"
                                data-locale="en"
                                data-primary-color="#ffcc00"
                                data-background-color="#FFFFFF"></div>
                        </div>
                    </div>

                    <!-- Guide Anti-Arnaques -->
                    <div class="glass-card p-8 rounded-[3rem] text-center border-t-4 border-cmRed">
                        <h3 class="text-xl font-black text-white mb-2 italic">Guide Anti-Arnaques</h3>
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-6 italic">Cadeau National</p>
                        
                        <div class="mb-8">
                            <span class="text-sm text-slate-600 line-through font-bold">1 000 F</span>
                            <div class="text-4xl font-black text-cmRed tracking-tighter uppercase">Gratuit</div>
                        </div>

                        <!-- Chariow Widget -->
                        <div class="flex justify-center">
                            <div id="chariow-widget" data-product-id="prd_pj5reu"
                                data-store-domain="boutique.hiremeguide.com"
                                data-style="showcase"
                                data-border-style="rounded"
                                data-cta-width="xs"
                                data-cta-animation="shine"
                                data-locale="en"
                                data-primary-color="#ffcc00"
                                data-background-color="#FFFFFF"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-20 text-center border-t border-white/5">
            <div class="max-w-7xl mx-auto px-4">
                <p class="text-4xl font-black italic tracking-tighter mb-8">HireMe<span class="text-cmRed italic">20Mai</span></p>
                <p class="text-slate-600 text-[9px] uppercase tracking-[0.6em] font-black">&copy; 2026 HireMe Guide Services. Un Peuple, Un Avenir, Un Succès.</p>
            </div>
        </footer>
    </main>

    <!-- Tracking -->
    <img src="api/track.php?v=20mai" alt="" style="display:none;">

    <!-- Chariow Hub -->
    <script>
    (function() {
      var script = document.createElement('script');
      script.src = 'https://js.chariowcdn.com/v1/widget.min.js';
      script.async = true;
      document.head.appendChild(script);

      var link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://js.chariowcdn.com/v1/widget.min.css';
      document.head.appendChild(link);
    })();
    </script>

    <script>
        // Audio Logic
        const music = document.getElementById('bgMusic');
        const toggleBtn = document.getElementById('musicToggle');
        const soundWave = document.getElementById('soundWave');
        const muteIcon = document.getElementById('muteIcon');
        let isPlaying = false;

        music.volume = 0.8; // High volume for celebration

        function togglePlay() {
            if (isPlaying) {
                music.pause();
                soundWave.classList.add('hidden');
                muteIcon.classList.remove('hidden');
            } else {
                music.play().catch(() => {});
                soundWave.classList.remove('hidden');
                muteIcon.classList.add('hidden');
            }
            isPlaying = !isPlaying;
        }

        toggleBtn.addEventListener('click', togglePlay);

        // Satisfy Browser Autoplay (Play on first click)
        document.addEventListener('click', function startOnInteraction() {
            if (!isPlaying) {
                togglePlay();
                document.removeEventListener('click', startOnInteraction);
            }
        }, { once: true });

        // Timer Logic
        let currentPrice = 1000;
        const endTime = new Date("<?= $promoEndTime ?>").getTime();

        function updateTimer() {
            const now = new Date().getTime();
            const diff = endTime - now;
            const timerEl = document.getElementById('bannerTimer');
            if (diff < 0) { timerEl.innerText = "MISSION TERMINÉE"; return; }
            const d = Math.floor(diff / (1000 * 60 * 60 * 24));
            const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const s = Math.floor((diff % (1000 * 60)) / 1000);
            timerEl.innerText = `${d}J ${h}H ${m}M ${s}S`;
        }
        setInterval(updateTimer, 1000); updateTimer();

        window.scrollToForm = function(type, price) {
            const radio = document.querySelector(`input[name="pack_type"][value="${type}"]`);
            if(radio) radio.checked = true;
            currentPrice = price;
            updateUssd();
            document.getElementById('commande').scrollIntoView({ behavior: 'smooth' });
        }

        window.updateUssd = function() {
            const payMethod = document.querySelector('input[name="pay_method"]:checked')?.value;
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
            el.addEventListener('change', updateUssd);
        });

        document.getElementById('proof').addEventListener('change', function(e) {
            const label = document.getElementById('previewLabel');
            if(this.files && this.files[0]) {
                label.innerHTML = `<p class="text-cmYellow font-black uppercase text-xs">✅ Fichier Reçu : ${this.files[0].name.substring(0, 15)}...</p>`;
            }
        });

        document.getElementById('uniteForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const packRadio = document.querySelector('input[name="pack_type"]:checked');
            if(!packRadio) { alert("Veuillez sélectionner un package d'excellence."); return; }

            btn.disabled = true;
            btn.innerText = "AUTHENTIFICATION...";

            try {
                const formData = new FormData();
                formData.append('name', document.getElementById('name').value);
                formData.append('whatsapp', document.getElementById('whatsapp').value);
                formData.append('paymentMethod', document.querySelector('input[name="pay_method"]:checked').value);
                formData.append('proofFile', document.getElementById('proof').files[0]);
                formData.append('transactionId', document.getElementById('transactionId').value);
                formData.append('package_name', '20MAI ' + packRadio.value);
                formData.append('amount', currentPrice);

                const res = await fetch('save-order.php', { method: 'POST', body: formData });
                const json = await res.json();
                
                if (json.success) {
                    window.location.href = `merci-20mai.php?name=${encodeURIComponent(document.getElementById('name').value)}`;
                } else {
                    alert(json.message);
                    btn.disabled = false;
                    btn.innerText = "FINALISER MA RÉUSSITE 🇨🇲";
                }
            } catch (err) {
                alert("Échec de la connexion tactique.");
                btn.disabled = false;
                btn.innerText = "FINALISER MA RÉUSSITE 🇨🇲";
            }
        });

        setTimeout(updateUssd, 200);
    </script>
</body>
</html>
