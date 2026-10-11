<?php
$name = $_GET['name'] ?? 'Candidat';
$job = $_GET['job'] ?? '';
// WhatsApp Message: "Salut, je suis [Name]. J'ai payé pour le Pack Neptune Expert (5000 FCFA) pour le poste de [Job]..."
$waMessage = "Salut, je suis " . $name . ". J'ai payé pour le Pack Neptune Expert (5000 FCFA). Je souhaite bénéficier de l'assistance pour le recrutement Neptune Oil au poste de : " . $job;
$waLink = "https://wa.me/237654250688?text=" . urlencode($waMessage);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merci - Candidature Neptune Oil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    
    <!-- Favicons -->
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg {
            background: linear-gradient(135deg, #004A99 0%, #002D5B 100%);
        }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="bg-blue-50 flex items-center justify-center min-h-screen p-4">

    <div class="max-w-xl w-full bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-blue-100 text-center relative">
        
        <!-- Decoration -->
        <div class="absolute top-0 left-0 w-full h-3 gradient-bg"></div>

        <div class="p-10 md:p-14">
            <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-8 relative">
                <div class="absolute inset-0 bg-green-200 rounded-full animate-ping opacity-20"></div>
                <svg class="w-12 h-12 text-green-600 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>

            <h1 class="text-3xl md:text-4xl font-black text-gray-900 mb-6 tracking-tight">C'est validé, <?= htmlspecialchars($name) ?> ! 🚀</h1>
            
            <p class="text-gray-600 text-lg mb-10 leading-relaxed">
                Votre commande pour le <b>Pack Neptune Expert</b> a été reçue pour le poste de : <br>
                <span class="text-neptune font-black text-xl uppercase tracking-tight"><?= htmlspecialchars($job) ?></span><br><br>
                Préparez-vous à rejoindre <b>Neptune Oil</b>. Nous prenons le relais pour optimiser votre dossier et soumettre votre candidature.
            </p>

            <div class="bg-blue-50 rounded-3xl p-8 mb-10 border border-blue-100">
                <h3 class="text-xs font-black text-blue-800 uppercase tracking-[0.2em] mb-3">ACTION REQUISE</h3>
                <p class="text-blue-900 font-bold text-lg">Cliquez ci-dessous pour confirmer sur WhatsApp et démarrer la procédure immédiatement.</p>
            </div>

            <a href="<?= $waLink ?>" class="block w-full bg-[#25D366] hover:bg-[#128C7E] text-white font-black text-xl py-5 rounded-2xl shadow-2xl shadow-green-500/40 transition transform hover:-translate-y-1 active:translate-y-0 flex items-center justify-center gap-4">
                <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.466c-.072-.125-.262-.202-.551-.346-.289-.144-1.711-.845-1.975-.941-.266-.097-.459-.145-.652.144-.193.289-.748.941-.916 1.135-.17.192-.338.214-.627.071-.289-.144-1.222-.451-2.327-1.437-.864-.77-1.448-1.72-1.616-2.009-.17-.289-.018-.445.127-.589.131-.131.289-.338.434-.506.145-.169.192-.289.289-.481.096-.193.048-.361-.024-.506-.072-.145-.652-1.572-.893-2.152-.234-.564-.471-.486-.646-.495-.168-.009-.361-.009-.554-.009-.193 0-.506.072-.77.361-.265.289-1.011.989-1.011 2.414 0 1.424 1.036 2.8 1.18 2.993.144.192 2.037 3.111 4.935 4.362 2.898 1.251 2.898.835 3.427.787.528-.048 1.711-.7 1.951-1.376.241-.675.241-1.253.17-1.376z"/></svg>
                CONFIRMER SUR WHATSAPP
            </a>
            
            <p class="mt-8 text-gray-400 text-xs font-medium uppercase tracking-widest">
                Assistance HireMe Guide - Support Expert
            </p>
        </div>
    </div>

    <!-- Stat Tracking -->
    <img src="api/track.php" alt="" style="display:none;">
</body>
</html>
