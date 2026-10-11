<?php
require_once 'admin/config/db.php';
date_default_timezone_set('Africa/Douala');

// Hardcoded Deadline: 03 April 2026 18:00:00 (from flyer)
$deadline = '2026-04-03 18:00:00';
if (time() > strtotime($deadline)) {
    // Expired
    header("Location: index.html");
    exit;
}

$price = 5000;
$positions = [
    "Chef Boulanger",
    "Caissier.e Principal.e",
    "Un Boulanger",
    "Assistante Administrative",
    "Comptable",
    "Employé Libre-Service (09)",
    "Maître d'Hôtel",
    "Boucher / Charcutier",
    "Chauffeur / Livreur (02)",
    "Manutentionnaire (03)",
    "Manutentionnaire (03) ", // Extra space to distinguish if needed, but unique titles are better
    "Caissier (06)"
];
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recrutement Neptune Oil 2026 | Candidature Assistée 100% Validée</title>
    <meta name="description" content="Candidatez chez Neptune Oil avec un dossier de pro. CV ATS, Lettre de Motivation et Dépôt Assisté pour seulement 5000 FCFA. 12 postes ouverts à Obala.">
    
    <!-- SEO & Social Meta -->
    <link rel="canonical" href="https://moncvpro.hiremeguide.com/neptune-promo.php" />
    <meta property="og:site_name" content="HireMe CV Services">
    <meta property="og:title" content="Recrutement Neptune Oil 2026 : Votre dossier validé à 100%">
    <meta property="og:description" content="Maximisez vos chances ! CV + Lettre + Candidature assistée. Prix Promo : 5000 FCFA. Postes ouverts : Comptable, Chauffeur, Caissier, etc.">
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/img/neptune-preview.png">
    <meta property="og:url" content="https://moncvpro.hiremeguide.com/neptune-promo.php">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    
    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "ProfessionalService",
      "name": "HireMe CV Services - Neptune Promo",
      "description": "Assistance au recrutement pour Neptune Oil. Rédaction de CV et lettre de motivation.",
      "url": "https://moncvpro.hiremeguide.com/neptune-promo.php",
      "telephone": "+237654250688",
      "priceRange": "5000 XAF",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "Obala",
        "addressCountry": "CM"
      }
    }
    </script>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Favicons -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        neptune: '#004A99', // Neptune Oil Deep Blue
                        norange: '#F39200', // Neptune Oil Orange
                        dark: '#0f172a',
                        gold: '#fbbf24'
                    }
                }
            }
        }
    </script>
    <style>
        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(229, 231, 235, 0.5);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #004A99 0%, #002D5B 100%);
        }
        .text-gradient {
            background: linear-gradient(to right, #004A99, #F39200);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="bg-gray-50 text-gray-900 font-[Inter] antialiased">

    <!-- Top Urgent Bar -->
    <div class="bg-norange text-white text-[10px] md:text-xs font-bold text-center py-3 px-4 shadow sticky top-0 z-50">
        <span class="animate-pulse">🚀 OFFRE LIMITÉE : NOUS POSTULONS POUR VOUS !</span>
        <span id="countdown" class="font-mono text-sm ml-2 bg-black/20 px-2 py-1 rounded">...</span>
    </div>

    <!-- Trust Navigation -->
    <nav class="bg-white border-b border-gray-100 py-3 shadow-sm relative z-40">
        <div class="max-w-4xl mx-auto px-4 flex justify-between items-center">
            <a href="index.html" class="flex items-center gap-2 text-gray-800 hover:text-neptune transition">
                <span class="font-black text-xl tracking-tighter">MonCV<span class="text-neptune">Pro</span></span>
                <span class="hidden sm:inline-block bg-gray-100 text-gray-500 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Site Officiel</span>
            </a>
            <a href="index.html" class="text-xs font-bold text-gray-500 hover:text-neptune flex items-center gap-1 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Visiter le site principal
            </a>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        
        <!-- Hero Section -->
        <div class="text-center mb-10">
            <div class="inline-block bg-blue-100 text-neptune px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest mb-6">
                Campagne de Recrutement Neptune Oil 2026
            </div>
            
            <h1 class="text-4xl md:text-6xl font-black mb-6 text-neptune leading-tight">
                Décrochez votre poste chez <span class="text-norange">Neptune Oil</span>
            </h1>
            <p class="text-xl text-gray-600 font-medium max-w-2xl mx-auto leading-relaxed">
                Ne manquez pas cette opportunité. Nous concevons votre CV, votre lettre de motivation et <b>nous soumettons votre candidature à votre place</b>.
            </p>
        </div>

        <!-- The Offer Card -->
        <div class="bg-white rounded-[2.5rem] shadow-2xl overflow-hidden mb-12 border border-gray-100">
            <div class="gradient-bg p-8 md:p-12 text-white text-center relative overflow-hidden">
                <div class="absolute top-0 right-0 p-24 bg-white/10 rounded-full -mr-16 -mt-16 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 p-20 bg-norange/20 rounded-full -ml-16 -mb-16 blur-3xl"></div>
                
                <h2 class="text-2xl font-black uppercase tracking-widest mb-4 relative z-10">Pack Neptune Expert</h2>
                <div class="flex items-center justify-center gap-4 relative z-10">
                    <span class="text-lg opacity-60 line-through">12 000 FCFA</span>
                    <span class="text-5xl md:text-6xl font-black text-norange drop-shadow-lg">5 000 FCFA</span>
                </div>
            </div>
            
            <div class="p-8 md:p-12 grid md:grid-cols-2 gap-10 items-center">
                <div class="space-y-6">
                    <h3 class="font-black text-gray-800 text-xl flex items-center gap-2">
                        <span class="w-2 h-8 bg-norange rounded-full"></span>
                        Services Inclus :
                    </h3>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3 group">
                            <div class="mt-1 bg-green-100 text-green-600 p-1 rounded-full group-hover:scale-110 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <span class="font-bold text-gray-800">Refonte CV Design & ATS</span>
                                <p class="text-sm text-gray-500">Optimisé pour les outils de sélection modernes.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3 group">
                            <div class="mt-1 bg-green-100 text-green-600 p-1 rounded-full group-hover:scale-110 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <span class="font-bold text-gray-800">Lettre de Motivation Ciblée</span>
                                <p class="text-sm text-gray-500">Rédigée spécifiquement pour Neptune Oil.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3 group">
                            <div class="mt-1 bg-blue-100 text-blue-600 p-1 rounded-full group-hover:scale-110 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <span class="font-bold text-gray-800 text-neptune">Candidature Assistée</span>
                                <p class="text-sm text-gray-500">Nous effectuons le dépôt pour vous !</p>
                            </div>
                        </li>
                    </ul>
                </div>
                
                <div class="bg-gray-50 p-8 rounded-3xl border-2 border-dashed border-gray-200 text-center">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-6">Disponibilité limitée</p>
                    <button onclick="scrollToForm()" class="w-full bg-neptune hover:bg-blue-800 text-white font-black py-5 rounded-2xl shadow-xl shadow-blue-900/20 transition transform hover:scale-[1.03] active:scale-95">
                        COMMANDER LE PACK
                    </button>
                    <div class="mt-6 flex flex-wrap justify-center gap-4 grayscale opacity-50">
                        <img src="assets/img/mtn_momo_logo.png" alt="MTN" class="h-10 object-contain">
                        <img src="assets/img/orange_money_logo.png" alt="Orange" class="h-10 object-contain">
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Jobs Grid -->
        <div class="mb-16">
            <h3 class="text-2xl font-black text-neptune text-center mb-8 uppercase tracking-widest">Postes Ouverts (Obala)</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($positions as $pos): ?>
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition text-center flex flex-col justify-center min-h-[100px]">
                    <span class="text-xs font-black text-norange uppercase mb-1">Neptune Oil</span>
                    <span class="text-sm font-bold text-gray-800 leading-tight"><?= trim($pos) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <p class="text-center text-xs text-gray-400 mt-6 font-medium">Lieu : Obala • Date limite : 03 Avril 2026 à 18h</p>
        </div>

        <!-- Comparison Section -->
        <div class="grid md:grid-cols-2 gap-8 mb-16">
            <div class="bg-red-50 p-8 rounded-3xl border border-red-100">
                <h4 class="text-red-900 font-black mb-4">Postuler Seul</h4>
                <ul class="space-y-3">
                    <li class="text-red-700 text-sm flex items-center gap-2">✕ CV non optimisé (rejeté par l'ATS)</li>
                    <li class="text-red-700 text-sm flex items-center gap-2">✕ Lettre générique peu convaincante</li>
                    <li class="text-red-700 text-sm flex items-center gap-2">✕ Risque d'erreur lors du dépôt</li>
                    <li class="text-red-700 text-sm flex items-center gap-2 font-bold">✕ 90% de risque de rejet</li>
                </ul>
            </div>
            <div class="bg-green-50 p-8 rounded-3xl border border-green-100">
                <h4 class="text-green-900 font-black mb-4">Pack Neptune Pro</h4>
                <ul class="space-y-3">
                    <li class="text-green-700 text-sm flex items-center gap-2">✓ CV Expert aux normes Neptune</li>
                    <li class="text-green-700 text-sm flex items-center gap-2">✓ Lettre de motivation irrésistible</li>
                    <li class="text-green-700 text-sm flex items-center gap-2">✓ Dépôt sécurisé par nos experts</li>
                    <li class="text-green-700 text-sm flex items-center gap-2 font-bold">✓ Dossier validé à 100%</li>
                </ul>
            </div>
        </div>

        <!-- Order Form -->
        <div id="orderForm" class="max-w-2xl mx-auto scroll-mt-24">
            <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-12 border border-gray-100 relative">
                <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 gradient-bg text-white px-8 py-3 rounded-full font-black text-sm uppercase tracking-widest shadow-xl whitespace-nowrap z-10">
                    Formulaire de Commande
                </div>

                <form id="neptuneForm" class="mt-6 space-y-8">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-gray-500 uppercase ml-1">Nom complet</label>
                            <input type="text" id="name" required placeholder="Votre nom" class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-4 focus:ring-4 focus:ring-neptune/10 focus:border-neptune outline-none transition">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-gray-500 uppercase ml-1">Ville</label>
                            <input type="text" id="city" required placeholder="Votre ville" class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-4 focus:ring-4 focus:ring-neptune/10 focus:border-neptune outline-none transition">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black text-gray-500 uppercase ml-1">Poste Convoité</label>
                        <select id="position" required class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-4 focus:ring-4 focus:ring-neptune/10 focus:border-neptune outline-none transition appearance-none">
                            <option value="" disabled selected>Choisir un poste...</option>
                            <?php foreach ($positions as $pos): ?>
                                <option value="<?= htmlspecialchars(trim($pos)) ?>"><?= htmlspecialchars(trim($pos)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black text-gray-500 uppercase ml-1">Numéro WhatsApp</label>
                        <input type="tel" id="whatsapp" required placeholder="Ex: 6xxxxxxx" class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-4 focus:ring-4 focus:ring-neptune/10 focus:border-neptune outline-none transition">
                    </div>

                    <div class="pt-8 border-t border-gray-100">
                         <label class="text-sm font-black text-gray-800 uppercase block mb-4 flex items-center gap-2">
                            Paiement Mobile (<?= number_format($price, 0, ',', ' ') ?> FCFA)
                            <span class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full">SÉCURISÉ</span>
                         </label>
                         
                         <div class="grid grid-cols-2 gap-4 mb-6">
                            <label class="cursor-pointer group">
                                <input type="radio" name="method" value="MTN" class="peer hidden" checked onchange="updateUSSD()">
                                <div class="p-4 rounded-2xl border-2 border-gray-100 peer-checked:border-yellow-400 peer-checked:bg-yellow-50 text-center transition hover:border-yellow-200 transform peer-checked:scale-[1.02]">
                                    <span class="text-sm font-black text-gray-700">MTN Money</span>
                                </div>
                            </label>
                            <label class="cursor-pointer group">
                                <input type="radio" name="method" value="Orange" class="peer hidden" onchange="updateUSSD()">
                                <div class="p-4 rounded-2xl border-2 border-gray-100 peer-checked:border-orange-500 peer-checked:bg-orange-50 text-center transition hover:border-orange-200 transform peer-checked:scale-[1.02]">
                                    <span class="text-sm font-black text-gray-700">Orange Money</span>
                                </div>
                            </label>
                        </div>

                        <div class="bg-blue-50 p-6 rounded-3xl border border-blue-100 text-center mb-6 overflow-hidden">
                            <p class="text-xs text-blue-600 font-bold mb-2">Cliquez pour composer le code :</p>
                            <a id="ussdLink" href="#" class="text-xl sm:text-2xl font-mono font-black text-blue-900 block bg-white py-4 rounded-2xl border-2 border-blue-200 shadow-sm hover:shadow-md transition active:scale-95 break-all">
                                ...
                            </a>
                            <p class="text-[10px] text-gray-400 mt-3 font-bold uppercase tracking-wider">Récepteur : NJAI NDINTEK HANDSON</p>
                        </div>

                        <div class="space-y-3">
                            <label class="text-xs font-black text-gray-500 uppercase ml-1">Preuve de paiement</label>
                            <input type="text" id="transId" placeholder="ID de Transaction (falcutatif)" class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-4 mb-2 text-sm">
                            <div class="relative group">
                                <input type="file" id="proof" accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-neptune file:text-white hover:file:bg-blue-800 transition">
                            </div>
                        </div>
                    </div>

                    <button type="submit" id="submitBtn" class="w-full gradient-bg text-white font-black py-5 rounded-2xl shadow-2xl hover:shadow-blue-900/40 transition-all transform hover:-translate-y-1 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed text-lg">
                        CONFIRMER MA COMMANDE
                    </button>
                    
                </form>
            </div>
        </div>
        
        <div class="mt-16 text-center text-gray-400 text-sm mb-32">
            <p class="flex items-center justify-center gap-2">
                <svg class="w-5 h-5 text-neptune" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.466c-.072-.125-.262-.202-.551-.346-.289-.144-1.711-.845-1.975-.941-.266-.097-.459-.145-.652.144-.193.289-.748.941-.916 1.135-.17.192-.338.214-.627.071-.289-.144-1.222-.451-2.327-1.437-.864-.77-1.448-1.72-1.616-2.009-.17-.289-.018-.445.127-.589.131-.131.289-.338.434-.506.145-.169.192-.289.289-.481.096-.193.048-.361-.024-.506-.072-.145-.652-1.572-.893-2.152-.234-.564-.471-.486-.646-.495-.168-.009-.361-.009-.554-.009-.193 0-.506.072-.77.361-.265.289-1.011.989-1.011 2.414 0 1.424 1.036 2.8 1.18 2.993.144.192 2.037 3.111 4.935 4.362 2.898 1.251 2.898.835 3.427.787.528-.048 1.711-.7 1.951-1.376.241-.675.241-1.253.17-1.376z"/></svg>
                Aide? <a href="https://wa.me/237654250688" class="text-neptune font-black hover:underline">WhatsApp : +237 654 250 688</a>
            </p>
            <p class="mt-4 text-xs opacity-60">HireMe Guide - 2026. Non officiel. Service d'assistance à la candidature.</p>
        </div>

    </div>

    <!-- Floating WhatsApp Support Widget -->
    <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-4 pointer-events-none">
        <!-- Support Sheet -->
        <div id="supportWidget" class="bg-white w-[300px] sm:w-[320px] rounded-[2rem] shadow-2xl border border-slate-100 overflow-hidden transform scale-90 opacity-0 translate-y-10 transition-all duration-300 pointer-events-none mb-4">
            <div class="bg-gradient-to-br from-emerald-600 to-teal-700 p-6 text-white relative">
                <button onclick="toggleSupportWidget()" class="absolute top-4 right-4 text-white/60 hover:text-white transition-colors pointer-events-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.151-.174.2-.298.3-.495.099-.198.05-.372-.025-.52-.075-.149-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" /></svg>
                </div>
                <h4 class="text-lg font-black leading-tight">Besoin d'aide ?</h4>
                <p class="text-white/80 text-xs">Un expert Neptune est en ligne.</p>
            </div>
            <div class="p-6 bg-white">
                <textarea id="supportMsg" rows="3" class="w-full rounded-2xl border-slate-100 bg-slate-50 p-4 text-xs focus:ring-emerald-500 focus:border-emerald-500 transition-all mb-4 placeholder:text-slate-400 border shadow-inner pointer-events-auto" placeholder="Écrivez votre question ici..."></textarea>
                <button onclick="sendWhatsAppSupport()" class="w-full bg-emerald-600 text-white font-black py-3 rounded-xl hover:bg-emerald-700 transition-all shadow-lg flex items-center justify-center gap-2 group/wa pointer-events-auto">
                    <span>AIDE SUR WHATSAPP</span>
                    <svg class="w-4 h-4 transition-transform group-hover/wa:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                </button>
            </div>
        </div>
        <!-- Bubble -->
        <button id="supportBubble" onclick="toggleSupportWidget()" class="pointer-events-auto w-16 h-16 bg-emerald-600 text-white rounded-full flex items-center justify-center shadow-[0_8px_30px_rgb(0,0,0,0.12)] border-2 border-white hover:scale-110 active:scale-95 transition-all duration-300 relative group">
            <div class="absolute inset-0 bg-emerald-500 rounded-full animate-ping opacity-20 group-hover:hidden"></div>
            <svg class="w-8 h-8 fill-current relative z-10" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338-11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.151-.174.2-.298.3-.495.099-.198.05-.372-.025-.52-.075-.149-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" /></svg>
        </button>
    </div>
el. Service d'assistance à la candidature.</p>
        </div>

    </div>

    <!-- Logic -->
    <script>
        const PRICE = <?= $price ?>;
        
        // Countdown
        const deadline = new Date("<?= $deadline ?>").getTime();
        function updateCountdown() {
            const now = new Date().getTime();
            const t = deadline - now;
            if (t < 0) {
                document.getElementById("countdown").innerText = "OFFRE TERMINÉE";
                return;
            }
            const days = Math.floor(t / (1000 * 60 * 60 * 24));
            const hours = Math.floor((t % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((t % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((t % (1000 * 60)) / 1000);
            document.getElementById("countdown").innerText = `${days}j ${hours}h ${minutes}m ${seconds}s`;
        }
        setInterval(updateCountdown, 1000);
        updateCountdown();

        function scrollToForm() {
            document.getElementById('orderForm').scrollIntoView({behavior: 'smooth'});
        }

        // USSD
        function updateUSSD() {
            const method = document.querySelector('input[name="method"]:checked').value;
            const link = document.getElementById('ussdLink');
            if (method === 'MTN') {
                const code = `*126*14*654250688*${PRICE}#`;
                link.innerText = code;
                link.href = `tel:${code.replace(/#/g, '%23')}`;
            } else {
                const code = `#150*1*1*659197506*${PRICE}#`;
                link.innerText = code;
                link.href = `tel:${code.replace(/#/g, '%23')}`;
            }
        }
        updateUSSD();

        // Submit
        document.getElementById('neptuneForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerText;
            btn.innerText = 'ENVOI EN COURS...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('name', document.getElementById('name').value);
            formData.append('city', document.getElementById('city').value);
            formData.append('whatsapp', document.getElementById('whatsapp').value);
            formData.append('paymentMethod', document.querySelector('input[name="method"]:checked').value);
            formData.append('transactionId', document.getElementById('transId').value || 'N/A');
            const proof = document.getElementById('proof').files[0];
            if(proof) formData.append('proofFile', proof);
            
            // Hardcoded Order Details
            formData.append('package_name', 'Pack Neptune Oil Expert - ' + document.getElementById('position').value);
            formData.append('amount', PRICE);

            try {
                const res = await fetch('save-order.php', { method: 'POST', body: formData });
                const json = await res.json();
                
                if (json.success) {
                    const name = encodeURIComponent(document.getElementById('name').value);
                    const position = encodeURIComponent(document.getElementById('position').value);
                    window.location.href = `neptune-merci.php?name=${name}&job=${position}`;
                } else {
                    alert('Erreur: ' + (json.message || 'Inconnue'));
                    btn.disabled = false;
                    btn.innerText = originalText;
                }
            } catch (err) {
                console.error(err);
                alert('Erreur de connexion.');
                btn.disabled = false;
                btn.innerText = originalText;
            }
        });

        // --- Lead Capture ---
        function saveLead() {
            const data = {
                name: document.getElementById('name').value,
                whatsapp: document.getElementById('whatsapp').value,
                source: 'Neptune Oil Page',
                details: {
                    city: document.getElementById('city').value,
                    job: document.getElementById('position').value,
                    price: PRICE
                }
            };
            
            if (data.whatsapp.length > 5) {
                fetch('api/save_lead.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                }).catch(err => console.log('Lead silence', err));
            }
        }

        let debounceTimer;
        ['name', 'city', 'whatsapp'].forEach(id => {
            const el = document.getElementById(id);
            if(el) {
                el.addEventListener('blur', saveLead);
                el.addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(saveLead, 2000);
                });
            }
        });

        // WhatsApp Widget Logic
        function toggleSupportWidget() {
            const widget = document.getElementById('supportWidget');
            const bubble = document.getElementById('supportBubble');
            if (widget.classList.contains('opacity-0')) {
                widget.classList.remove('opacity-0', 'translate-y-10', 'scale-90', 'pointer-events-none');
                widget.classList.add('pointer-events-auto');
                bubble.classList.add('scale-0');
            } else {
                widget.classList.add('opacity-0', 'translate-y-10', 'scale-90', 'pointer-events-none');
                widget.classList.remove('pointer-events-auto');
                bubble.classList.remove('scale-0');
            }
        }
        function sendWhatsAppSupport() {
            const msg = document.getElementById('supportMsg').value;
            const name = document.getElementById('name').value || 'Candidat';
            if (!msg.trim()) { alert("Décrivez votre souci."); return; }
            const fullText = `Bonjour ! Je suis ${name}. J'ai une question sur le recrutement Neptune Oil : ${msg}`;
            window.open(`https://wa.me/237654250688?text=${encodeURIComponent(fullText)}`, '_blank');
        }
    </script>
    <img src="api/track.php" alt="" style="display:none;">
</body>
</html>
