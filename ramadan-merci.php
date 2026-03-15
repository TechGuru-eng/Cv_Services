<?php
$name = $_GET['name'] ?? "Cher/Chère client(e)";
$pack = $_GET['pack'] ?? "Offre Ramadan";
$adminNumber = "237654250688";

// Prefilled WhatsApp Message
$message = "Bonjour MonCv Pro, je suis " . $name . ". Je viens de commander mon " . $pack . " pour le Ramadan. Voici ma preuve de paiement. Merci de l'approuver ! 🌙✨";
$waLink = "https://wa.me/" . $adminNumber . "?text=" . urlencode($message);
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande Reçue ! — HireMe CV Services</title>

    <!-- Favicons -->
    <link rel="icon" href="https://moncvpro.hiremeguide.com/assets/favicons/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://moncvpro.hiremeguide.com/assets/favicons/favicon-16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="https://moncvpro.hiremeguide.com/assets/favicons/favicon-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://moncvpro.hiremeguide.com/assets/favicons/apple-touch-icon-180.png">

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
                        primary: '#059669', // emerald-600
                        darkBlue: '#022c22', // emerald-950
                        neutral: '#064e3b', // emerald-900
                        bg: '#f0fdf4', // emerald-50
                        ramadan: '#d97706', // amber-600
                    }
                }
            }
        }
    </script>
    <style>
        .page-gradient {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
        }
    </style>
</head>
<body class="page-gradient min-h-screen flex items-center justify-center p-6 font-sans antialiased">

    <div class="max-w-xl w-full bg-white rounded-[2.5rem] shadow-2xl border border-green-50 p-8 md:p-12 text-center relative overflow-hidden">
        
        <!-- Professional Decoration -->
        <div class="absolute top-0 left-0 w-full h-2 bg-ramadan"></div>

        <!-- Success Icon -->
        <div class="inline-flex items-center justify-center w-20 h-20 bg-amber-50 rounded-full mb-8">
            <svg class="w-10 h-10 text-ramadan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <h1 class="text-3xl md:text-5xl font-black text-neutral mb-6 tracking-tight">
            Merci <span class="text-ramadan italic"><?= htmlspecialchars($name) ?></span> !
        </h1>

        <p class="text-lg text-gray-600 mb-8 leading-relaxed">
            <strong>Ramadan Mubarak !</strong> 🌙 <br> 
            Nous avons bien reçu votre commande <strong><?= htmlspecialchars($pack) ?></strong>. <br>
            C'est le début d'une nouvelle étape brillante pour votre carrière en ce mois béni.
        </p>

        <!-- Instruction Box -->
        <div class="bg-green-50 border border-green-100 rounded-3xl p-6 text-left mb-10">
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

        <div class="mt-10 text-gray-500 text-[10px] uppercase tracking-widest font-bold border-t border-green-50 pt-6 flex justify-center gap-6">
            <a href="ramadan.php" class="hover:text-primary transition-colors">Retour Offre</a>
            <a href="index.html" class="hover:text-primary transition-colors">Accueil</a>
        </div>
    </div>

</body>
</html>
