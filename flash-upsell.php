<?php
// flash-upsell.php
// Interceptor page to offer OTO (One Time Offer)

$name = htmlspecialchars($_GET['name'] ?? 'Client');
$pack = htmlspecialchars($_GET['pack'] ?? 'Pack CV');
$amount = htmlspecialchars($_GET['amount'] ?? 0);

// Smart Upsell Logic
$upsellOffer = [];

if (stripos($pack, 'Etudiant') !== false) {
    $upsellOffer = [
        'title' => 'Ajoutez la Lettre de Motivation Parfaite',
        'desc' => 'Multipliez vos chances par 3 avec une lettre ciblée et persuasive.',
        'price' => 1000,
        'old_price' => 3000,
        'item_name' => 'Option Lettre de Motivation'
    ];
} elseif (stripos($pack, 'Pro') !== false) {
    $upsellOffer = [
        'title' => 'Optimisation LinkedIn -50%',
        'desc' => 'Transformez votre profil LinkedIn en aimant à recruteurs.',
        'price' => 2500,
        'old_price' => 5000,
        'item_name' => 'Option LinkedIn'
    ];
} elseif (stripos($pack, 'Senior') !== false) {
    $upsellOffer = [
        'title' => 'Traitement Prioritaire 24h',
        'desc' => 'Passez devant tout le monde et recevez vos documents en 24h chrono.',
        'price' => 5000,
        'old_price' => 10000,
        'item_name' => 'Option Prioritaire'
    ];
} else {
    // Default Upsell (e.g. for Expert or others)
    $upsellOffer = [
        'title' => 'Guide "Réussir son Entretien"',
        'desc' => 'Le PDF ultime pour négocier votre salaire et convaincre.',
        'price' => 1500,
        'old_price' => 4500,
        'item_name' => 'Ebook Entretien'
    ];
}

$nextParams = http_build_query([
    'name' => $name,
    'pack' => $pack,
    'amount' => $amount
]);

// URL if they accept (We append the upsell)
$acceptParams = http_build_query([
    'name' => $name,
    'pack' => $pack,
    'amount' => $amount,
    'upsell' => $upsellOffer['item_name'],
    'upsell_price' => $upsellOffer['price']
]);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offre Spéciale !</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <!-- Favicons -->
    <link rel="icon" href="https://moncvpro.hiremeguide.com/assets/favicons/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://moncvpro.hiremeguide.com/assets/favicons/favicon-16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="https://moncvpro.hiremeguide.com/assets/favicons/favicon-32.png">
    <link rel="icon" type="image/png" sizes="48x48" href="https://moncvpro.hiremeguide.com/assets/favicons/favicon-48.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://moncvpro.hiremeguide.com/assets/favicons/apple-touch-icon-180.png">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    animation: { 'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite' }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center p-4">

    <div class="max-w-xl w-full text-center">
        
        <!-- Progress Bar -->
        <div class="w-full bg-gray-800 rounded-full h-2.5 mb-8">
            <div class="bg-yellow-500 h-2.5 rounded-full" style="width: 80%"></div>
        </div>

        <div class="bg-white text-gray-900 rounded-3xl p-8 shadow-2xl relative overflow-hidden border-4 border-yellow-500">
            
            <!-- Badge -->
            <div class="absolute top-4 right-4 bg-red-600 text-white text-xs font-black px-3 py-1 rounded-full uppercase tracking-widest animate-pulse">
                Offre Unique
            </div>

            <h1 class="text-3xl font-black mb-2 text-red-600">ATTENDEZ ! 🛑</h1>
            <p class="text-gray-500 font-medium mb-6">Votre commande est incomplète...</p>

            <img src="https://cdn-icons-png.flaticon.com/512/3659/3659903.png" class="w-24 h-24 mx-auto mb-6 opacity-80" alt="Gift">

            <h2 class="text-2xl font-bold mb-2"><?= $upsellOffer['title'] ?></h2>
            <p class="text-gray-600 mb-6"><?= $upsellOffer['desc'] ?></p>

            <div class="bg-gray-100 rounded-xl p-4 mb-8 flex items-center justify-center gap-4">
                <span class="text-gray-400 line-through text-lg font-bold"><?= number_format($upsellOffer['old_price'], 0, ',', ' ') ?> F</span>
                <span class="text-3xl font-black text-green-600"><?= number_format($upsellOffer['price'], 0, ',', ' ') ?> FCFA</span>
            </div>

            <div class="space-y-3">
                <a href="flash-merci.php?<?= $acceptParams ?>" class="block w-full bg-green-500 hover:bg-green-600 text-white font-black py-4 rounded-xl text-lg shadow-lg transform transition hover:scale-[1.02]">
                    OUI, j'ajoute cette offre ✅
                </a>
                
                <a href="flash-merci.php?<?= $nextParams ?>" class="block text-gray-400 text-xs hover:text-gray-600 underline mt-4">
                    Non merci, je ne veux pas profiter de cette réduction
                </a>
            </div>

            <div class="mt-6 text-[10px] text-gray-400">
                L'ajout sera indiqué sur le message WhatsApp final. Vous pourrez régler le supplément directement.
            </div>

        </div>
    </div>

</body>
</html>
