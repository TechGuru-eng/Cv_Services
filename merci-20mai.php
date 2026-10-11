<?php
$name = $_GET['name'] ?? 'Cher Client';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Succès ! 🇨🇲 | HireMe 20 Mai</title>
    
    <!-- Social Preview -->
    <meta property="og:title" content="Succès ! Ma carrière décolle avec HireMe 🇨🇲">
    <meta property="og:description" content="Je viens de profiter de l'offre spéciale 20 Mai. Faites comme moi !">
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/20mai-promo-preview.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="https://moncvpro.hiremeguide.com/assets/20mai-promo-preview.png">
    
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
    fbq('track', 'Purchase');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=984926333941411&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->

    <!-- Favicons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #000d0a; color: white; }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .gold-shimmer { background: linear-gradient(90deg, #ffcc00, #ffffff, #ffcc00); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-size: 200% auto; animation: shine 3s linear infinite; }
        @keyframes shine { to { background-position: 200% center; } }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="min-h-screen py-12 px-4">
    <!-- Logo / Brand -->
    <a href="index.html" class="fixed top-8 left-4 md:left-8 z-50 text-xl font-black italic tracking-tighter hover:scale-105 transition-transform group">
        HireMe<span class="text-cmRed group-hover:text-cmYellow transition-colors">20Mai</span>
    </a>

    <div class="max-w-4xl mx-auto">
        <!-- Main Confirmation -->
        <div class="glass rounded-[3rem] p-10 md:p-16 text-center mb-12 border-t-8 border-[#007A5E]">
            <div class="text-6xl mb-6">🇨🇲</div>
            <h1 class="text-4xl md:text-6xl font-black mb-6 tracking-tighter uppercase italic">
                C'est <span class="gold-shimmer">Validé !</span>
            </h1>
            <p class="text-slate-400 font-bold mb-10 leading-relaxed uppercase text-[10px] md:text-xs tracking-[0.3em]">
                Félicitations <?= htmlspecialchars($name) ?>. Votre avenir professionnel commence ici.
            </p>
            
            <div class="bg-white/5 p-8 rounded-3xl mb-10 border border-white/10">
                <p class="text-xs font-black text-[#ffcc00] mb-2 uppercase tracking-widest">Prochaine Étape :</p>
                <p class="text-sm text-slate-300 font-light">Nos experts analysent votre commande. Un conseiller vous contactera sur <span class="text-white font-bold">WhatsApp</span> dans les prochaines minutes.</p>
            </div>

            <a href="https://wa.me/237654250688?text=Bonjour,%20je%20viens%20de%20valider%20ma%20commande%20sp%C3%A9ciale%2020%20Mai !" class="inline-block bg-[#007A5E] text-white font-black px-12 py-5 rounded-2xl shadow-2xl hover:scale-105 transition-all uppercase tracking-widest text-xs">
                Accélérer via WhatsApp
            </a>
        </div>

        <!-- Upsell Section -->
        <div class="text-center mb-10">
            <h2 class="text-2xl font-black uppercase italic tracking-widest text-white">Complétez Votre Dossier 🎁</h2>
            <p class="text-slate-500 text-[10px] uppercase font-bold tracking-widest mt-2">Offres exclusives post-commande</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Product 1 -->
            <div class="glass p-6 rounded-[2.5rem] text-center">
                <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest mb-2">Pack 300 Lettres</p>
                <p class="text-lg font-black text-[#ffcc00] mb-4">750 F</p>
                <div id="chariow-widget" data-product-id="prd_805091" data-store-domain="boutique.hiremeguide.com" data-style="showcase" data-border-style="rounded" data-cta-width="xs" data-cta-animation="shine" data-locale="en" data-primary-color="#ffcc00" data-background-color="#000d0a"></div>
            </div>

            <!-- Product 2 -->
            <div class="glass p-6 rounded-[2.5rem] text-center border-t-2 border-[#ffcc00]/20">
                <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest mb-2">60 Modèles CV</p>
                <p class="text-lg font-black text-[#ffcc00] mb-4">3 000 F</p>
                <div id="chariow-widget" data-product-id="prd_ip7non" data-store-domain="boutique.hiremeguide.com" data-style="showcase" data-border-style="rounded" data-cta-width="xs" data-cta-animation="shine" data-locale="en" data-primary-color="#ffcc00" data-background-color="#000d0a"></div>
            </div>

            <!-- Product 3 -->
            <div class="glass p-6 rounded-[2.5rem] text-center">
                <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest mb-2">Guide Anti-Arnaques</p>
                <p class="text-lg font-black text-red-500 mb-4 uppercase">Gratuit</p>
                <div id="chariow-widget" data-product-id="prd_pj5reu" data-store-domain="boutique.hiremeguide.com" data-style="showcase" data-border-style="rounded" data-cta-width="xs" data-cta-animation="shine" data-locale="en" data-primary-color="#ffcc00" data-background-color="#000d0a"></div>
            </div>
        </div>
    </div>

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
</body>
</html>
