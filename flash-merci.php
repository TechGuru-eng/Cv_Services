<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande Confirmée - Vente Flash</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Favicons -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        gold: '#FFD700',
                        dark: '#111827',
                        accent: '#22c55e'
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
<body class="bg-dark text-white min-h-screen flex flex-col items-center justify-center p-4">

    <?php
    $name = htmlspecialchars($_GET['name'] ?? 'Client');
    $pack = htmlspecialchars($_GET['pack'] ?? 'Pack CV');
    
    // Define Benefits mapping based on Pack Name
    $benefits = [
        'Pack Etudiant' => [
            'CV Design Simple',
            'Correction basique',
            'Livraison Format PDF'
        ],
        'Pack Pro' => [
            'CV Design Premium',
            'Lettre de Motivation',
            'Optimisation Mots-clés',
            'Format Word + PDF'
        ],
        'Pack Senior' => [
            'Tout du Pack Pro',
            'Optimisation Profil LinkedIn',
            'Réécriture complète du CV'
        ],
        'Pack Expert' => [
            'CV Français + Anglais',
            'Création Portfolio Professionnel',
            'Priorité absolue (Service VIP)'
        ],
        'Pack 300 Lettres' => [
            'Accès immédiat à 300+ modèles Word',
            'Rédigés par des experts RH',
            'Capte l\'attention en 3 secondes',
            '100% Modifiables & Éditables'
        ]
    ];
    
    // fallback
    $currentBenefits = $benefits[$pack] ?? [
        'Rédaction professionnelle',
        'Mise en forme moderne',
        'Service client dédié',
        'Livraison rapide'
    ];
    
    // Check for Upsell
    $upsellItem = htmlspecialchars($_GET['upsell'] ?? '');
    $upsellPrice = htmlspecialchars($_GET['upsell_price'] ?? 0);

    // Whatsapp Message
    $waNumber = "237654250688";
    $waMessage = "Bonjour, je suis $name. Je viens de payer pour le $pack (Offre Flash).";
    
    if ($upsellItem) {
        $waMessage .= " J'ai aussi ajouté l'option '$upsellItem' à " . $upsellPrice . " FCFA. Merci de me confirmer le montant total.";
        $pack .= " + " . $upsellItem;
    } else {
        $waMessage .= " J'aimerais qu'on commence le travail sur mon dossier.";
    }

    $waLink = "https://wa.me/$waNumber?text=" . urlencode($waMessage);
    ?>

    <div class="max-w-2xl w-full bg-gray-800 border border-gray-700 rounded-3xl p-8 sm:p-12 text-center shadow-2xl relative overflow-hidden">
        
        <!-- Confetti/Success Icon -->
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-gold to-yellow-500"></div>
        <div class="w-20 h-20 bg-green-500/20 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <h1 class="text-3xl sm:text-4xl font-black mb-2">Merci, <?= $name ?> !</h1>
        <p class="text-gray-400 text-lg mb-8">Votre commande pour le <span class="text-gold font-bold"><?= $pack ?></span> a bien été reçue.</p>

        <?php if ($upsellItem): ?>
        <div class="bg-green-900/30 border border-green-500/30 p-4 rounded-xl mb-6">
            <p class="text-green-400 font-bold text-sm">✅ Option "<?= $upsellItem ?>" ajoutée !</p>
            <p class="text-xs text-green-300 mt-1">N'oubliez pas d'inclure les <?= $upsellPrice ?> FCFA supplémentaires.</p>
        </div>
        <?php endif; ?>

        <!-- What's Included -->
        <div class="bg-gray-900/50 rounded-2xl p-6 mb-8 text-left border border-gray-700">
            <h3 class="text-gray-300 font-bold uppercase text-xs tracking-widest mb-4 border-b border-gray-700 pb-2">Ce qui est inclus dans votre pack :</h3>
            <ul class="space-y-3">
                <?php foreach ($currentBenefits as $benefit): ?>
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-gold mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-gray-200"><?= $benefit ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Next Steps -->
        <div class="mb-8">
            <p class="text-gray-300 mb-4">
                Nous vérifions actuellement votre preuve de paiement. Pour accélérer le processus et démarrer votre commande immédiatement, cliquez sur le bouton ci-dessous pour nous écrire sur WhatsApp.
            </p>
        </div>

        <!-- CTA -->
        <a href="<?= $waLink ?>" target="_blank" class="block w-full bg-[#25D366] hover:bg-[#128C7E] text-white font-bold py-4 px-6 rounded-xl transition-all transform hover:scale-[1.02] shadow-lg flex items-center justify-center text-lg">
            <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24">
                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.151-.174.2-.298.3-.495.099-.198.05-.372-.025-.52-.075-.149-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
            </svg>
            Commencer mon dossier sur WhatsApp
        </a>

        <div class="mt-6 text-xs text-gray-500">
            En cliquant, vous serez redirigé vers WhatsApp avec un message pré-rempli.
        </div>

    </div>

</body>
</html>
