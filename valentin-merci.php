<?php
$name = $_GET['name'] ?? "l'ami";
$pack = $_GET['pack'] ?? "Valentine Deal";
$adminNumber = "237654250688";

// Prefilled WhatsApp Message
$message = "Bonjour MonCv Pro, je suis " . $name . ". Je viens de commander mon " . $pack . ". Voici ma preuve de paiement. Merci de l'approuver ! ❤️";
$waLink = "https://wa.me/" . $adminNumber . "?text=" . urlencode($message);
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande Reçue ! - Valentin MonCv Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                    },
                    colors: {
                        romantic: '#ef4444',
                    }
                }
            }
        }
    </script>
    <style>
        .romantic-gradient {
            background: linear-gradient(135deg, #fff1f2 0%, #fff7ed 100%);
        }
        .animate-bounce-slow {
            animation: bounce 3s infinite;
        }
    </style>
</head>
<body class="romantic-gradient min-h-screen flex items-center justify-center p-6 font-sans antialiased">

    <div class="max-w-2xl w-full bg-white/80 backdrop-blur-xl rounded-[3rem] shadow-2xl overflow-hidden border border-rose-100 p-8 md:p-12 text-center relative">
        
        <!-- Floating Hearts Decoration -->
        <div class="absolute top-10 left-10 text-rose-200 text-4xl animate-pulse">❤️</div>
        <div class="absolute bottom-10 right-10 text-rose-200 text-4xl animate-pulse" style="animation-delay: 1s">❤️</div>

        <!-- Success Icon -->
        <div class="inline-flex items-center justify-center w-24 h-24 bg-rose-50 rounded-full mb-8 animate-bounce-slow">
            <svg class="w-12 h-12 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <h1 class="font-serif text-3xl md:text-5xl font-black text-slate-900 mb-6">
            Merci <span class="text-rose-600"><?= htmlspecialchars($name) ?></span> !
        </h1>

        <p class="text-lg text-slate-600 mb-8 leading-relaxed">
            L'amour et la réussite vont de pair ! ❤️ <br> 
            Nous avons bien reçu votre commande <strong><?= htmlspecialchars($pack) ?></strong>. 
            Votre futur brillant est en train de se dessiner.
        </p>

        <!-- Instruction Box -->
        <div class="bg-rose-50 border border-rose-100 rounded-3xl p-6 text-left mb-10 shadow-sm">
            <div class="flex items-start">
                <div class="bg-white p-3 rounded-2xl shadow-sm mr-4 shrink-0">
                    <span class="text-2xl">✨</span>
                </div>
                <div>
                    <h3 class="font-bold text-rose-900 text-lg">Dernière Étape</h3>
                    <p class="text-rose-800 text-sm mt-1 leading-relaxed">
                        Pour un traitement <strong>prioritaire</strong>, cliquez sur le bouton ci-dessous pour informer l'administrateur et faire approuver votre offre immédiatement.
                    </p>
                </div>
            </div>
        </div>

        <!-- WhatsApp Button -->
        <a href="<?= $waLink ?>" target="_blank" class="block w-full bg-[#25D366] hover:bg-[#20ba56] text-white font-black py-5 rounded-[2rem] shadow-xl shadow-green-200 transition-all transform hover:scale-[1.02] flex items-center justify-center gap-3 text-lg tracking-wide">
            <svg class="w-7 h-7 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.149-1.04 1.016-1.04 2.479 0 1.462 1.066 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
            Contactez l'Admin sur WhatsApp
        </a>

        <div class="mt-8 text-slate-400 text-xs uppercase tracking-widest font-bold">
            <a href="index.html" class="hover:text-rose-600 transition-colors">Retour à l'accueil</a>
        </div>
    </div>

</body>
</html>
