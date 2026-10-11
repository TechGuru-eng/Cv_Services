<?php
$name = $_GET['name'] ?? 'Candidat';
// WhatsApp Message: "Salut, je suis [Name]. J'ai payé pour le Pack Camtel (6000 FCFA)..."
$waMessage = "Salut, je suis " . $name . ". J'ai payé pour le Pack Camtel (6000 FCFA). Je souhaite vérifier mon paiement et commencer la procédure.";
$waLink = "https://wa.me/237654250688?text=" . urlencode($waMessage);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merci - Candidature Camtel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    
    <!-- Favicons -->
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="bg-blue-50 flex items-center justify-center min-h-screen p-4">

    <div class="max-w-xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden border border-blue-100 text-center relative">
        
        <!-- Confetti / Decoration -->
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-blue-400 via-blue-600 to-blue-800"></div>

        <div class="p-10">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>

            <h1 class="text-3xl font-black text-gray-900 mb-4">Félicitations, <?= htmlspecialchars($name) ?> ! 🚀</h1>
            
            <p class="text-gray-600 text-lg mb-8 leading-relaxed">
                Votre commande a été reçue avec succès. <br>
                Vous avez fait le bon choix pour votre carrière. Nous allons maintenant optimiser votre dossier pour maximiser vos chances chez <b>CAMTEL</b>.
            </p>

            <div class="bg-blue-50 rounded-2xl p-6 mb-8 border border-blue-100">
                <h3 class="text-sm font-bold text-blue-800 uppercase tracking-widest mb-2">Prochaine Étape</h3>
                <p class="text-blue-900 font-medium">Pour activer votre commande immédiatement, cliquez sur le bouton ci-dessous pour nous envoyer une confirmation rapide sur WhatsApp.</p>
            </div>

            <a href="<?= $waLink ?>" class="block w-full bg-[#25D366] hover:bg-[#128C7E] text-white font-black text-lg py-4 rounded-xl shadow-lg shadow-green-500/30 transition transform hover:-translate-y-1 flex items-center justify-center gap-3">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.466c-.072-.125-.262-.202-.551-.346-.289-.144-1.711-.845-1.975-.941-.266-.097-.459-.145-.652.144-.193.289-.748.941-.916 1.135-.17.192-.338.214-.627.071-.289-.144-1.222-.451-2.327-1.437-.864-.77-1.448-1.72-1.616-2.009-.17-.289-.018-.445.127-.589.131-.131.289-.338.434-.506.145-.169.192-.289.289-.481.096-.193.048-.361-.024-.506-.072-.145-.652-1.572-.893-2.152-.234-.564-.471-.486-.646-.495-.168-.009-.361-.009-.554-.009-.193 0-.506.072-.77.361-.265.289-1.011.989-1.011 2.414 0 1.424 1.036 2.8 1.18 2.993.144.192 2.037 3.111 4.935 4.362 2.898 1.251 2.898.835 3.427.787.528-.048 1.711-.7 1.951-1.376.241-.675.241-1.253.17-1.376z"/></svg>
                CONFIRMER SUR WHATSAPP
            </a>
            
            <p class="mt-6 text-gray-400 text-sm">
                Un expert prendra votre dossier en charge dès validation du paiement.
            </p>
        </div>
    </div>

    <!-- Stat Tracking -->
    <img src="api/track.php" alt="" style="display:none;">
</body>
</html>
