<?php
// Curated independent purchases. Prices follow HireMe's supplied catalogue.
function linkedin_shop_products(): array
{
    $root = 'https://boutique.hiremeguide.com/';
    return [
        ['name' => '60 modèles de CV professionnels', 'price' => '2 000 XAF', 'url' => $root . '60modelescv', 'icon' => 'file-lines', 'description' => 'Des modèles modifiables pour présenter votre parcours avec clarté.'],
        ['name' => '300 lettres de motivation', 'price' => '600 XAF', 'url' => $root . 'prd_805091', 'icon' => 'envelope-open-text', 'description' => 'Trouvez une base adaptée à votre métier et personnalisez votre candidature.'],
        ['name' => '50 questions d’entretien et réponses', 'price' => '999 XAF', 'url' => $root . 'prd_wp8pbysn', 'icon' => 'comments', 'description' => 'Préparez vos réponses et abordez votre prochain entretien avec confiance.'],
        ['name' => '10 raisons pourquoi tu chômes + plan de 30 jours', 'price' => '1 000 XAF', 'url' => $root . 'prd_ajbvfder', 'icon' => 'calendar-check', 'description' => 'Identifiez vos blocages et organisez votre recherche avec un plan concret.'],
        ['name' => 'Guide Premium des cabinets RH au Cameroun + 6 bonus', 'price' => '5 000 XAF', 'url' => $root . 'prd_0cm5a17j', 'icon' => 'compass', 'description' => 'Repérez les cabinets RH et profitez de six ressources complémentaires.'],
        ['name' => 'Apprends n’importe quoi en ligne gratuitement', 'price' => '1 500 XAF', 'url' => $root . 'prd_k3qt8yto', 'icon' => 'graduation-cap', 'description' => 'Découvrez des pistes pour développer vos compétences grâce aux ressources gratuites.'],
        ['name' => '100 messages professionnels', 'price' => '≈650 XAF / $1', 'url' => $root . 'prd_7ocsyau0', 'icon' => 'paper-plane', 'description' => 'Des messages à personnaliser pour postuler, contacter et relancer les recruteurs.'],
        ['name' => 'Annuaire des entreprises camerounaises', 'price' => '1 000 XAF', 'url' => $root . 'prd_m214cn48', 'icon' => 'building', 'description' => 'Identifiez de nouvelles entreprises à cibler dans vos démarches.'],
    ];
}
