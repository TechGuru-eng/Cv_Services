<?php
$name = $_GET['name'] ?? "Madame";
$pack = $_GET['pack'] ?? "Offre 8 Mars";
$adminNumber = "237654250688";

// Prefilled WhatsApp Message
$message = "Bonjour MonCv Pro, je suis " . $name . ". Je viens de commander mon " . $pack . ". Voici ma preuve de paiement. Merci de l'approuver ! ✨💜";
$waLink = "https://wa.me/" . $adminNumber . "?text=" . urlencode($message);
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande Reçue ! — HireMe CV Services</title>

    <!-- Favicons -->
    <!-- Tailwind & Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#0ea5e9',
                        darkBlue: '#0369a1',
                        neutral: '#111827',
                        bg: '#f8fafc',
                    }
                }
            }
        }
    </script>
    <style>
        .page-gradient {
            background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);
        }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="page-gradient min-h-screen flex items-center justify-center p-6 font-sans antialiased">

    <div class="max-w-xl w-full bg-white rounded-[2.5rem] shadow-2xl border border-gray-100 p-8 md:p-12 text-center relative overflow-hidden">
        
        <!-- Professional Decoration -->
        <div class="absolute top-0 left-0 w-full h-2 bg-female"></div>

        <!-- Success Icon -->
        <div class="inline-flex items-center justify-center w-20 h-20 bg-pink-50 rounded-full mb-8">
            <svg class="w-10 h-10 text-female" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <h1 class="text-3xl md:text-5xl font-black text-neutral mb-6 tracking-tight">
            Merci <span class="text-female italic"><?= htmlspecialchars($name) ?></span> !
        </h1>

        <p class="text-lg text-gray-500 mb-8 leading-relaxed">
            Bonne fête du <strong>8 Mars</strong> ! 🎉 <br> 
            Nous avons bien reçu votre commande <strong><?= htmlspecialchars($pack) ?></strong>. <br>
            C'est le début d'une nouvelle étape brillante pour votre carrière.
        </p>

        <!-- Instruction Box -->
        <div class="bg-blue-50 border border-blue-100 rounded-3xl p-6 text-left mb-10">
            <div class="flex items-start gap-4">
                <div class="bg-white p-3 rounded-2xl shadow-sm shrink-0">
                    <span class="text-xl">📄</span>
                </div>
                <div>
                    <h3 class="font-bold text-darkBlue text-lg">Dernière étape</h3>
                    <p class="text-gray-600 text-sm mt-1 leading-relaxed">
                        Pour valider votre commande et lancer la rédaction, envoyez votre preuve de paiement sur WhatsApp en cliquant sur le bouton ci-dessous.
                    </p>
                </div>
            </div>
        </div>

        <!-- WhatsApp Button -->
        <a href="<?= $waLink ?>" target="_blank" class="block w-full bg-[#25D366] hover:bg-[#20ba56] text-white font-extrabold py-5 rounded-full shadow-lg shadow-green-100 transition-all transform hover:scale-[1.02] flex items-center justify-center gap-3 text-lg tracking-wide uppercase">
            Envoyer ma preuve
        </a>

        <div class="mt-10 text-gray-400 text-[10px] uppercase tracking-widest font-bold border-t border-gray-50 pt-6 flex justify-center gap-6">
            <a href="special-promotion.php" class="hover:text-primary transition-colors">Retour Offre</a>
            <a href="index.html" class="hover:text-primary transition-colors">Accueil</a>
        </div>
    </div>

</body>
</html>
