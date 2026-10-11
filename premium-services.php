<?php
session_start();
$secret_code = 'EXCELLENCE24';
$error = '';

// Check Option 1: URL Parameter
if (isset($_GET['vip_code']) && $_GET['vip_code'] === $secret_code) {
    $_SESSION['vip_unlocked'] = true;
    header("Location: premium-services.php");
    exit();
}

// Check Option 2: Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vip_code'])) {
    if ($_POST['vip_code'] === $secret_code) {
        $_SESSION['vip_unlocked'] = true;
        header("Location: premium-services.php");
        exit();
    } else {
        $error = "Code d'accès incorrect.";
    }
}

// If not unlocked, show the VIP screen
if (!isset($_SESSION['vip_unlocked']) || $_SESSION['vip_unlocked'] !== true) {
?>
<!DOCTYPE html>
<html lang="fr">
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

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-0WXT4Q08R2"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'G-0WXT4Q08R2');
        
        function trackEvent(eventName, eventData = {}) {
            fetch('api/track.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ event_name: eventName, event_data: eventData })
            }).catch(err => console.error("Tracking error:", err));
        }
        trackEvent('vip_login_view');
    </script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès VIP – HireMe CV</title>
    <!-- Favicons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0ea5e9',
                        gold: '#fbbf24',
                        neutral: '#111827',
                    }
                }
            }
        }
    </script>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="bg-neutral min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white/5 backdrop-blur-xl border border-white/10 p-8 rounded-3xl shadow-2xl text-center">
        <div class="w-16 h-16 bg-gold/10 rounded-full flex items-center justify-center mx-auto mb-6 border border-gold/30">
            <svg class="w-8 h-8 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        </div>
        <h1 class="text-2xl font-black text-white mb-2">Accès Exclusif</h1>
        <p class="text-gray-400 text-sm mb-8">Veuillez entrer votre code VIP pour accéder aux services premium.</p>
        
        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-400 text-sm py-2 px-4 rounded-lg mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="password" name="vip_code" placeholder="Code d'accès" required
                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-center tracking-widest placeholder:text-gray-500 focus:outline-none focus:border-gold/50 focus:ring-1 focus:ring-gold/50 transition-all mb-4">
            
            <button type="submit"
                class="w-full bg-gradient-to-r from-gold to-yellow-600 text-neutral font-black py-3 rounded-xl hover:shadow-[0_0_20px_rgba(251,191,36,0.3)] transition-all">
                DÉVERROUILLER
            </button>
        </form>
    </div>
</body>
</html>
<?php
    exit();
}
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
    fbq('track', 'ViewContent', { content_name: 'Premium Services' });
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=984926333941411&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-0WXT4Q08R2"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'G-0WXT4Q08R2');
        
        // Custom Tracker
        function trackEvent(eventName, eventData = {}) {
            fetch('api/track.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ event_name: eventName, event_data: eventData })
            }).catch(err => console.error("Tracking error:", err));
        }
        trackEvent('premium_page_view');
    </script>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Premium – HireMe CV</title>
    <meta name="description" content="Propulsez votre carrière avec nos CV et Lettres de Motivation professionnels Premium. Design exceptionnel, contenu percutant.">
    
    <!-- Favicons -->
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: '#0ea5e9',
                        primaryDark: '#0284c7',
                        accent: '#22c55e',
                        danger: '#ef4444',
                        neutral: '#111827',
                        bg: '#f8fafc',
                        gold: '#fbbf24',
                        goldDark: '#f59e0b',
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'float-delayed': 'float 6s ease-in-out 3s infinite',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-15px)' },
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- AOS CSS for Animations -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/site-language.js?v=4" defer></script>
</head>

<body class="bg-bg text-neutral antialiased font-sans flex flex-col min-h-screen overflow-x-hidden">

    <!-- Header -->
    <header class="bg-white/90 backdrop-blur-md border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center">
                <a href="index.html" class="text-2xl font-black text-primary tracking-tight hover:opacity-80 transition-opacity">
                    HireMe<span class="text-neutral">CV</span> 
                    <span class="text-gold ml-2 text-[10px] font-black tracking-widest uppercase rounded-full border-2 border-gold/40 bg-gold/10 px-2 py-0.5 align-middle shadow-[0_0_10px_rgba(251,191,36,0.2)]">Premium</span>
                </a>
            </div>
            <a href="index.html" class="text-sm font-bold text-gray-600 hover:text-primary transition-colors flex items-center gap-2 group">
                <i class="fa-solid fa-arrow-left transform group-hover:-translate-x-1 transition-transform"></i> Accueil
            </a>
        </div>
    </header>

    <!-- Hero Section (DARK & PREMIUM) -->
    <section class="relative pt-20 pb-32 overflow-hidden bg-neutral min-h-[85vh] flex items-center">
        <!-- Glowing Orbs Background -->
        <div class="absolute inset-0 z-0 overflow-hidden">
            <div class="absolute -top-40 -left-40 w-[30rem] h-[30rem] bg-primary/20 rounded-full mix-blend-screen filter blur-[100px] opacity-70 animate-float" style="animation-delay: 0s;"></div>
            <div class="absolute top-10 -right-20 w-[25rem] h-[25rem] bg-gold/20 rounded-full mix-blend-screen filter blur-[100px] opacity-60 animate-float" style="animation-delay: 2s;"></div>
            <div class="absolute -bottom-40 left-1/3 w-[35rem] h-[35rem] bg-accent/10 rounded-full mix-blend-screen filter blur-[120px] opacity-50 animate-float" style="animation-delay: 4s;"></div>
            
            <!-- Grid pattern overlay -->
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiLz48L3N2Zz4=')] opacity-50 mask-image:linear-gradient(to_bottom,white,transparent)"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                
                <!-- Left Content: Text -->
                <div class="text-left" data-aos="fade-right" data-aos-duration="1000">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gold/10 text-gold font-bold text-xs tracking-widest uppercase mb-8 border border-gold/30 shadow-[0_0_15px_rgba(251,191,36,0.15)]">
                        L'Excellence à votre portée
                    </div>
                    <h1 class="text-5xl md:text-6xl font-black text-white mb-6 leading-[1.1] tracking-tight">
                        Décrochez le job de vos rêves avec des <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary via-primary to-accent drop-shadow-lg">Outils Premium</span>
                    </h1>
                    <p class="text-lg text-gray-400 mb-10 leading-relaxed max-w-lg">
                        Passez devant 90% des candidats avec un CV percutant et une Lettre de motivation sur-mesure. Rédigés par des experts RH, optimisés pour les recruteurs (ATS).
                    </p>
                    
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <a href="#tarifs" class="w-full sm:w-auto px-8 py-4 bg-primary text-white rounded-xl font-black text-lg shadow-[0_10px_25px_-5px_rgba(14,165,233,0.5)] hover:bg-primaryDark hover:-translate-y-1 transition-all duration-300 flex items-center justify-center gap-3">
                            Voir les Offres <i class="fa-solid fa-arrow-down animate-bounce"></i>
                        </a>
                        
                        <div class="flex items-center gap-4 text-sm font-semibold text-gray-300">
                            <div class="flex items-center gap-2"><i class="fa-solid fa-bolt text-gold"></i> Livraison 48H</div>
                            <div class="flex items-center gap-2"><i class="fa-solid fa-shield-check text-accent"></i> 100% Sécurisé</div>
                        </div>
                    </div>
                </div>

                <!-- Right Content: Floating CV Images -->
                <div class="relative block mt-16 lg:mt-0 h-[400px] lg:h-[600px] w-full" data-aos="fade-left" data-aos-duration="1200">
                    <div class="relative w-full max-w-[300px] mx-auto lg:max-w-none lg:mx-0 h-full">
                        <!-- Image 3 (Back) -->
                        <div class="absolute right-0 lg:right-0 top-0 lg:top-4 w-40 lg:w-64 rounded-xl shadow-2xl transform rotate-6 animate-float-delayed z-0 overflow-hidden border-2 border-white/10">
                            <img src="assets/img/cv-sample-3.jpg" alt="Exemple CV" class="w-full h-auto object-cover">
                        </div>

                        <!-- Image 2 (Middle) -->
                        <div class="absolute right-12 lg:right-24 top-12 lg:top-20 w-44 lg:w-64 rounded-xl shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)] transform -rotate-3 animate-float z-10 overflow-hidden border-2 border-white/20">
                            <img src="assets/img/cv-sample-2.jpg" alt="Exemple CV" class="w-full h-auto object-cover">
                        </div>

                        <!-- Image 1 (Front) -->
                        <div class="absolute left-0 lg:left-0 top-24 lg:top-36 w-52 lg:w-72 rounded-xl shadow-[0_35px_60px_-15px_rgba(0,0,0,0.7)] transform rotate-2 animate-float z-20 overflow-hidden border-4 border-primary/50">
                            <img src="assets/img/cv-sample-1.jpg" alt="Exemple CV" class="w-full h-auto object-cover">
                            <!-- Badge -->
                            <div class="absolute -right-2 -top-2 bg-gold text-neutral font-black text-xs uppercase px-3 py-1.5 rounded-lg shadow-lg transform rotate-12 flex items-center gap-1">
                                <i class="fa-solid fa-check-circle"></i> Optimisé ATS
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Pricing Section -->
    <section id="tarifs" class="py-24 bg-bg relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-20" data-aos="fade-up">
                <h2 class="text-3xl md:text-5xl font-black text-neutral mb-6">Investissez dans votre Avenir</h2>
                <p class="text-gray-500 text-lg max-w-2xl mx-auto">Choisissez l'offre qui correspond à votre besoin immédiat. Un investissement minime pour des retombées massives sur votre carrière.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch max-w-6xl mx-auto">
                
                <!-- CV Premium -->
                <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="0" class="bg-white border border-gray-100 rounded-[2rem] p-8 shadow-xl hover:shadow-2xl transition-shadow duration-300 relative group flex flex-col">
                    <div class="absolute top-8 right-8 text-primary/10 group-hover:text-primary/20 transition-colors">
                        <i class="fa-solid fa-file-invoice text-7xl"></i>
                    </div>
                    <div class="relative z-10 flex-grow">
                        <h3 class="text-2xl font-black text-neutral mb-2">CV Professionnel</h3>
                        <p class="text-gray-500 text-sm mb-6">Idéal pour moderniser votre profil et passer les filtres RH.</p>
                        <div class="mb-8">
                            <span class="text-4xl font-black text-primary">2500</span>
                            <span class="text-gray-400 font-bold ml-1 text-sm">FCFA</span>
                        </div>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-start gap-3 text-sm font-semibold text-gray-700">
                                <i class="fa-solid fa-check-circle text-primary mt-0.5"></i> Rédaction experte
                            </li>
                            <li class="flex items-start gap-3 text-sm font-semibold text-gray-700">
                                <i class="fa-solid fa-check-circle text-primary mt-0.5"></i> Design Premium au choix
                            </li>
                            <li class="flex items-start gap-3 text-sm font-semibold text-gray-700">
                                <i class="fa-solid fa-check-circle text-primary mt-0.5"></i> Optimisation mots-clés (ATS)
                            </li>
                        </ul>
                    </div>
                    <a href="paiement-premium.php?package=CV%20Premium&amount=2500" class="mt-auto block w-full py-4 text-center border-2 border-primary text-primary font-bold rounded-xl hover:bg-primary hover:text-white transition-all duration-300">
                        Commander mon CV
                    </a>
                </div>

                <!-- BUNDLE: CV + Lettre (Center, Highlighted) -->
                <div data-aos="fade-up" data-aos-duration="1000" data-aos-delay="100" class="bg-neutral text-white rounded-[2rem] p-1 shadow-2xl transform lg:-translate-y-6 relative z-20 flex flex-col">
                    <div class="absolute -top-5 inset-x-0 flex justify-center z-30">
                        <span class="bg-gradient-to-r from-gold to-goldDark text-neutral font-black text-xs uppercase tracking-widest py-2 px-6 rounded-full shadow-[0_5px_15px_rgba(251,191,36,0.4)]">Le Plus Populaire</span>
                    </div>
                    <div class="bg-gradient-to-b from-gray-800 to-[#0B1120] rounded-[1.8rem] p-8 h-full relative overflow-hidden flex flex-col">
                        <!-- Glow effect -->
                        <div class="absolute top-0 right-0 w-40 h-40 bg-gold/20 rounded-full mix-blend-screen filter blur-2xl"></div>
                        
                        <div class="relative z-10 flex-grow">
                            <h3 class="text-3xl font-black text-white mb-2">Pack Ultime</h3>
                            <p class="text-gray-400 text-sm mb-6">Le duo parfait : CV Premium + Lettre de Motivation.</p>
                            <div class="mb-8 flex items-end gap-2">
                                <span class="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-gold to-yellow-200">3000</span>
                                <span class="text-gray-400 font-bold mb-1 text-sm">FCFA</span>
                                <span class="text-xs line-through text-gray-500 ml-2 mb-1">3200F</span>
                            </div>
                            
                            <div class="bg-white/5 border border-white/10 rounded-xl p-4 mb-8">
                                <p class="text-xs text-gold font-bold mb-3 uppercase tracking-widest">Inclus dans ce pack :</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-3 text-sm font-semibold text-gray-200">
                                        <i class="fa-solid fa-check text-gold mt-1"></i> Tout le contenu du CV Premium
                                    </li>
                                    <li class="flex items-start gap-3 text-sm font-semibold text-gray-200">
                                        <i class="fa-solid fa-check text-gold mt-1"></i> Lettre de motivation ultra-ciblée
                                    </li>
                                    <li class="flex items-start gap-3 text-sm font-semibold text-gray-200">
                                        <i class="fa-solid fa-check text-gold mt-1"></i> Cohérence de design parfaite
                                    </li>
                                    <li class="flex items-start gap-3 text-sm font-semibold text-gray-200">
                                        <i class="fa-solid fa-check text-gold mt-1"></i> Support VIP WhatsApp
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <a href="paiement-premium.php?package=Pack%20Ultime&amount=3000" class="mt-auto block w-full py-4 text-center bg-gradient-to-r from-gold to-goldDark text-neutral font-black rounded-xl transition-all duration-300 shadow-[0_0_15px_rgba(251,191,36,0.3)] hover:shadow-[0_0_25px_rgba(251,191,36,0.6)] hover:-translate-y-1">
                            Prendre le Pack Ultime
                        </a>
                    </div>
                </div>

                <!-- Lettre de motivation -->
                <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="200" class="bg-white border border-gray-100 rounded-[2rem] p-8 shadow-xl hover:shadow-2xl transition-shadow duration-300 relative group flex flex-col">
                    <div class="absolute top-8 right-8 text-primary/10 group-hover:text-primary/20 transition-colors">
                        <i class="fa-solid fa-envelope-open-text text-7xl"></i>
                    </div>
                    <div class="relative z-10 flex-grow">
                        <h3 class="text-2xl font-black text-neutral mb-2">Lettre Simple</h3>
                        <p class="text-gray-500 text-sm mb-6">L'accroche parfaite pour prouver votre motivation au recruteur.</p>
                        <div class="mb-8">
                            <span class="text-4xl font-black text-primary">700</span>
                            <span class="text-gray-400 font-bold ml-1 text-sm">FCFA</span>
                        </div>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-start gap-3 text-sm font-semibold text-gray-700">
                                <i class="fa-solid fa-check-circle text-primary mt-0.5"></i> Rédaction persuasive
                            </li>
                            <li class="flex items-start gap-3 text-sm font-semibold text-gray-700">
                                <i class="fa-solid fa-check-circle text-primary mt-0.5"></i> Adaptée à votre domaine
                            </li>
                            <li class="flex items-start gap-3 text-sm font-semibold text-gray-700">
                                <i class="fa-solid fa-check-circle text-primary mt-0.5"></i> Mise en page pro
                            </li>
                        </ul>
                    </div>
                    <a href="paiement-premium.php?package=Lettre%20de%20Motivation&amount=700" class="mt-auto block w-full py-4 text-center border-2 border-primary text-primary font-bold rounded-xl hover:bg-primary hover:text-white transition-all duration-300">
                        Commander la Lettre
                    </a>
                </div>

            </div>
        </div>
    </section>

    <!-- Trust / Reassurance Section -->
    <section class="py-20 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-bg rounded-3xl p-8 md:p-12 border border-gray-100">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <div data-aos="fade-right">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md bg-accent/10 text-accent font-bold text-xs uppercase mb-6">
                            <i class="fa-solid fa-shield-halved"></i> 100% Sans Risque
                        </div>
                        <h3 class="text-3xl md:text-4xl font-black text-neutral mb-6 leading-tight">Commander en ligne n'a jamais été aussi sûr.</h3>
                        <p class="text-gray-600 mb-8 leading-relaxed text-lg">Nous avons mis en place un processus de commande transparent et un support hyper-réactif pour vous accompagner du paiement jusqu'à la livraison.</p>
                        
                        <div class="space-y-6">
                            <div class="flex items-start gap-5">
                                <div class="w-12 h-12 rounded-full bg-white shadow-md flex items-center justify-center shrink-0 border border-gray-50">
                                    <i class="fa-solid fa-mobile-screen-button text-primary text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-black text-neutral text-lg">Paiement Mobile Sécurisé</h4>
                                    <p class="text-sm text-gray-500 mt-1">Utilisez MTN MoMo ou Orange Money en toute sérénité via notre interface officielle.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-5">
                                <div class="w-12 h-12 rounded-full bg-white shadow-md flex items-center justify-center shrink-0 border border-gray-50">
                                    <i class="fa-brands fa-whatsapp text-accent text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-black text-neutral text-lg">Support Client VIP</h4>
                                    <p class="text-sm text-gray-500 mt-1">Un conseiller dédié vous accueille sur WhatsApp dès votre paiement effectué.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div data-aos="fade-left" class="relative">
                        <!-- Decorative background element -->
                        <div class="absolute -inset-4 bg-gradient-to-br from-primary/20 to-accent/20 opacity-50 blur-2xl rounded-full"></div>
                        
                        <div class="bg-white border border-gray-100 rounded-3xl p-8 shadow-[0_20px_50px_-12px_rgba(0,0,0,0.1)] relative z-10 text-center">
                            <div class="flex justify-center mb-6">
                                <div class="flex gap-1 text-gold text-lg">
                                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                                </div>
                            </div>
                            <p class="text-gray-700 font-medium italic mb-8 text-lg leading-relaxed">"J'avais peur de payer en ligne mais le service client m'a rassuré sur WhatsApp. J'ai reçu mon CV Premium en 24h et j'ai eu un entretien 3 jours après. C'est un vrai investissement !"</p>
                            
                            <div class="flex items-center justify-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-gray-200 overflow-hidden">
                                    <img src="https://i.pravatar.cc/150?img=47" alt="Nadine" class="w-full h-full object-cover">
                                </div>
                                <div class="text-left">
                                    <h5 class="font-black text-neutral">Nadine M.</h5>
                                    <span class="text-xs font-bold text-accent flex items-center gap-1"><i class="fa-solid fa-circle-check"></i> Cliente Vérifiée</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-neutral text-white py-12 border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center md:text-left flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <a href="index.html" class="text-2xl font-black text-white tracking-tight">
                    HireMe<span class="text-primary">CV</span>
                </a>
                <p class="text-gray-400 mt-2 text-sm">L'expertise au service de votre carrière.</p>
            </div>
            <p class="text-gray-500 text-sm">© 2026 HireMe CV. Tous droits réservés.</p>
        </div>
    </footer>

    <!-- AOS Script for Animations -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            offset: 50,
        });
    </script>
</body>
</html>
