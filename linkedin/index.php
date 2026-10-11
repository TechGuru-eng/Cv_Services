<?php
// linkedin/index.php
// HireMe - Optimisation de Profil LinkedIn Landing Page (MonCvPro Redesign)

require_once __DIR__ . '/../admin/config/db.php';

// Fetch Active Packages from DB (with graceful fallback)
$packages = [];
try {
    $stmtPkg = $pdo->query("SELECT * FROM linkedin_packages WHERE is_active = 1 ORDER BY sort_order ASC");
    $packages = $stmtPkg->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $packages = [];
}

if (empty($packages)) {
    $packages = [
        [
            'id' => 1,
            'name' => 'Pack Essentiel',
            'slug' => 'essentiel',
            'price' => 5000,
            'tagline' => 'Profil propre & visible sous 48h',
            'is_popular' => 0,
            'deliverables_json' => json_encode(['Titre pro & mots-clés SEO', 'Bannière HD personnalisée', 'Section Infos optimisée', 'Guide d\'activation pas-à-pas'])
        ],
        [
            'id' => 2,
            'name' => 'Pack Pro',
            'slug' => 'pro',
            'price' => 10000,
            'tagline' => 'Profil complet + CV ATS synchronisé',
            'is_popular' => 1,
            'deliverables_json' => json_encode(['Titre + Résumé + Expériences', 'Bannière HD sur-mesure', 'Audit mots-clés recruteurs', 'CV ATS inclus & synchronisé', 'Recommandations d\'approche RH'])
        ],
        [
            'id' => 3,
            'name' => 'Pack Executive',
            'slug' => 'premium',
            'price' => 25000,
            'tagline' => 'Stratégie carrière & approche décideurs',
            'is_popular' => 0,
            'deliverables_json' => json_encode(['Refonte complète 360° profil', 'Pack 3 Bannières personnalisées', 'Messages de prospection ciblés', 'Simulation d\'entretien LinkedIn', 'Support prioritaire 30 jours'])
        ]
    ];
}

function landingEscape($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$linkedinBase = preg_replace('~/(?:linkedin(?:/.*)?|suivi-linkedin)/?$~', '/linkedin/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
    <base href="<?= landingEscape($linkedinBase) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/linkedin-landing.css?v=4">
    <script src="../assets/js/site-language.js?v=6" defer></script>
<title>Optimisation de profil LinkedIn au Cameroun | HireMe</title>
    <meta name="description" content="Optimisez votre profil LinkedIn avec HireMe : titre, résumé, expériences et bannière professionnelle pour valoriser votre parcours auprès des recruteurs.">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="https://moncvpro.hiremeguide.com/linkedin/">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="HireMe">
    <meta property="og:title" content="Optimisation de profil LinkedIn au Cameroun | HireMe">
    <meta property="og:description" content="Optimisez votre profil LinkedIn avec HireMe : titre, résumé, expériences et bannière professionnelle pour valoriser votre parcours auprès des recruteurs.">
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/images/linkedin/linkedin-flyer-fr.png">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1734">
    <meta property="og:image:height" content="907">
    <meta property="og:image:alt" content="HireMe — Un profil LinkedIn à la hauteur de votre talent. Découvrez nos offres sur moncvpro.hiremeguide.com/linkedin/.">
    <meta property="og:url" content="https://moncvpro.hiremeguide.com/linkedin/">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Optimisation de profil LinkedIn au Cameroun | HireMe">
    <meta name="twitter:description" content="Optimisez votre profil LinkedIn avec HireMe : titre, résumé, expériences et bannière professionnelle pour valoriser votre parcours auprès des recruteurs.">
    <meta name="twitter:image" content="https://moncvpro.hiremeguide.com/assets/images/linkedin/linkedin-flyer-fr.png">
    <meta name="twitter:image:alt" content="HireMe — Un profil LinkedIn à la hauteur de votre talent. Découvrez nos offres sur moncvpro.hiremeguide.com/linkedin/.">
    <script type="application/ld+json">{"@context":"https://schema.org","@type":"WebPage","name":"Optimisation de profil LinkedIn au Cameroun | HireMe","description":"Optimisez votre profil LinkedIn avec HireMe : titre, résumé, expériences et bannière professionnelle pour valoriser votre parcours auprès des recruteurs.","url":"https://moncvpro.hiremeguide.com/linkedin/","inLanguage":"fr","isPartOf":{"@type":"WebSite","name":"HireMe","url":"https://moncvpro.hiremeguide.com/"}}</script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="../assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="linkedin-landing">
<a class="skip-link" href="#main">Aller au contenu</a>
<div class="li-announcement"><span>Votre prochaine étape professionnelle commence par un profil soigné.</span><a href="../index.html">Découvrir HireMe CV →</a></div>
<header class="li-header">
    <a class="li-brand" href="../index.html" aria-label="HireMe, accueil"><span class="li-brand-mark" aria-hidden="true">H</span><span class="li-brand-name" translate="no">HireMe<span>LinkedIn</span></span></a>
    <nav class="li-desktop-nav" aria-label="Navigation principale"><a href="#services">Le service</a><a href="#process">Notre approche</a><a href="#tarifs">Les offres</a><a href="suivi-linkedin">Suivre ma commande</a></nav>
    <div class="li-language-slot"></div>
    <a class="li-header-cta" href="commander">Commencer →</a>
    <details class="li-mobile-nav"><summary aria-label="Ouvrir le menu"><span></span><span></span></summary><nav aria-label="Navigation mobile"><a href="#services">Le service</a><a href="#process">Notre approche</a><a href="#tarifs">Les offres</a><a href="suivi-linkedin">Suivre ma commande</a><a href="../index.html">Accueil HireMe CV</a></nav></details>
</header>
<main id="main">
<section class="li-hero li-wrap">
    <div class="li-hero-copy"><p class="li-eyebrow">VOTRE PARCOURS, MIS EN LUMIÈRE</p><h1>Un profil qui ouvre <em>la conversation.</em></h1><p class="li-lead">Vous avez les compétences. Donnons à votre profil LinkedIn la clarté et la personnalité qu’il mérite.</p><div class="li-hero-actions"><a class="li-button" href="#tarifs">Trouver mon offre <span aria-hidden="true">↗</span></a><a class="li-text-link" href="#services">Explorer le service <span aria-hidden="true">→</span></a></div><a class="li-text-link li-diagnostic-link" href="diagnostic">Faire le point sur mon profil →</a><div class="li-trust"><span>Sans partage de mot de passe</span><span>Rédaction personnalisée</span></div></div>
    <div class="li-hero-art" data-tilt>
        <div class="li-art-orbit" aria-hidden="true"></div>
        <div class="li-art-pill"><span class="li-dot"></span><span>LinkedIn, autrement.</span></div>
        <img class="li-hero-image" src="../assets/images/linkedin/profile-hero.jpg" alt="Professionnel préparant sa présence en ligne" width="1024" height="1024" fetchpriority="high">
        <div class="li-art-chip li-chip-top"><span class="li-mini-in" aria-hidden="true" translate="no">in</span><div><strong>Votre expertise, visible.</strong><span>Votre identité, affirmée.</span></div></div>
        <div class="li-art-chip li-chip-bottom"><span class="li-chip-check" aria-hidden="true">✓</span><div><strong>Un profil qui vous ressemble</strong><span>Clair. Cohérent. Professionnel.</span></div></div>
        <div class="li-art-footnote"><span>LE PROCHAIN CHAPITRE COMMENCE ICI</span><span aria-hidden="true">↗</span></div>
    </div>
</section>
<section class="li-principles"><div class="li-wrap"><p>UNE MEILLEURE PREMIÈRE IMPRESSION</p><span>Un titre précis</span><span>Un récit authentique</span><span>Une identité soignée</span></div></section>
<section class="li-editorial li-wrap">
    <div class="li-editorial-image" data-tilt><img src="../assets/images/linkedin/profile-cover-example.jpg" alt="Exemple de bannière et de présentation LinkedIn" width="736" height="736" loading="lazy"><div class="li-photo-label"><span>Une présence pensée pour vous.</span><span aria-hidden="true">↗</span></div></div>
    <div class="li-editorial-copy"><p class="li-eyebrow">PLUS QU’UNE MISE À JOUR</p><h2>Faites de votre profil<br><em>un vrai point de rencontre.</em></h2><p>Un recruteur, un partenaire, une nouvelle relation professionnelle : votre profil donne envie d’en savoir plus sur vous.</p><p>Nous relions votre expérience, vos ambitions et votre identité dans une présentation naturelle et soignée.</p><a class="li-text-link" href="#services">Voir ce qui change <span aria-hidden="true">↗</span></a><div class="li-editorial-signature"><img src="../assets/images/linkedin/linkedin-devices.jpg" alt="LinkedIn sur vos appareils" width="236" height="151" loading="lazy"><span>Votre présence professionnelle.<br>Sur tous les écrans.</span></div></div>
</section>
<section id="services" class="li-section li-wrap"><div class="li-section-heading"><div><p class="li-eyebrow">CE QUE NOUS TRAVAILLONS</p><h2>Chaque détail raconte<br>votre valeur.</h2></div><p>Nous vous aidons à présenter votre expérience avec justesse, sans inventer ni exagérer votre parcours.</p></div><div class="li-services"><article><span class="li-service-number">01 /</span><h3>Un titre qui vous situe</h3><p>Votre métier, votre spécialité et les mots-clés pertinents pour comprendre votre profil.</p><span class="li-service-tag">Titre & mots-clés</span></article><article><span class="li-service-number">02 /</span><h3>Un récit qui vous ressemble</h3><p>Une section À propos claire et personnelle qui relie vos expériences à vos ambitions.</p><span class="li-service-tag">Section À propos</span></article><article><span class="li-service-number">03 /</span><h3>Un parcours facile à lire</h3><p>Des expériences structurées, des responsabilités explicites et des compétences bien présentées.</p><span class="li-service-tag">Expériences & compétences</span></article><article><span class="li-service-number">04 /</span><h3>Une présence cohérente</h3><p>Une bannière et des conseils de présentation adaptés à votre identité professionnelle.</p><span class="li-service-tag">Identité visuelle</span></article></div></section>
<section class="li-comparison li-wrap"><div class="li-comparison-intro"><p class="li-eyebrow">UNE ÉVOLUTION QUI SE VOIT</p><h2>Même parcours.<br>Une lecture plus claire.</h2><p>Nous valorisons ce qui existe déjà : votre expérience, vos compétences et votre personnalité.</p></div><div class="li-comparison-pair"><article><span>AVANT L’OPTIMISATION</span><h3>Une présentation à affiner</h3><ul><li>Un titre qui manque de précision</li><li>Des expériences peu structurées</li><li>Une identité visuelle à harmoniser</li></ul></article><article><span>APRÈS L’OPTIMISATION</span><h3>Un profil qui vous représente</h3><ul><li>Un positionnement clair</li><li>Un parcours facile à parcourir</li><li>Une présentation cohérente</li></ul></article></div></section>
<section id="process" class="li-section li-wrap"><div class="li-section-heading"><div><p class="li-eyebrow">NOTRE APPROCHE</p><h2>Simple pour vous.<br>Soigné par nous.</h2></div><p>Votre compte reste entre vos mains. Nous préparons les éléments et vous guidons pour les intégrer.</p></div><ol class="li-process"><li><span>01</span><h3>Vous partagez votre parcours</h3><p>Choisissez une offre et transmettez les informations utiles via notre formulaire sécurisé.</p></li><li><span>02</span><h3>Nous préparons votre profil</h3><p>Nous travaillons vos contenus et votre présentation selon l’offre choisie.</p></li><li><span>03</span><h3>Vous faites évoluer votre présence</h3><p>Recevez vos éléments et les indications nécessaires pour mettre à jour votre profil.</p></li></ol></section>
<section id="tarifs" class="li-pricing"><div class="li-wrap"><div class="li-section-heading"><div><p class="li-eyebrow">UNE OFFRE POUR VOTRE PROCHAINE ÉTAPE</p><h2>Choisissez votre<br>point de départ.</h2></div><p>Consultez les prestations incluses. Les tarifs affichés correspondent aux offres actuellement disponibles.</p></div><div class="li-packages">
<?php foreach ($packages as $pkg):
    $features = json_decode($pkg['deliverables_json'] ?? '[]', true);
    if (!is_array($features)) $features = [];
    $popular = !empty($pkg['is_popular']);
?>
<article class="li-package <?= $popular ? 'li-package-featured' : '' ?>">
<?php if ($popular): ?><p class="li-package-recommended">NOTRE RECOMMANDATION</p><?php endif; ?>
<h3><?= landingEscape($pkg['name']) ?></h3><p class="li-package-tagline"><?= landingEscape($pkg['tagline'] ?? '') ?></p><div class="li-price"><strong translate="no"><?= number_format((float)$pkg['price'], 0, ',', ' ') ?></strong><span translate="no">FCFA</span></div>
<?php if (!empty($pkg['delivery_time'])): ?><p class="li-delivery"><?= landingEscape($pkg['delivery_time']) ?></p><?php endif; ?>
<ul><?php foreach (array_slice($features, 0, 5) as $feature): if (!is_string($feature)) continue; ?><li><?= landingEscape($feature) ?></li><?php endforeach; ?></ul>
<?php if (count($features) > 5): ?><details class="li-package-details"><summary>Toutes les prestations</summary><ul><?php foreach (array_slice($features, 5) as $feature): if (!is_string($feature)) continue; ?><li><?= landingEscape($feature) ?></li><?php endforeach; ?></ul></details><?php endif; ?>
<a class="li-button" href="commander?package=<?= (int)$pkg['id'] ?>">Choisir cette offre <span aria-hidden="true">→</span></a>
</article>
<?php endforeach; ?>
</div><p class="li-pricing-note">Paiement par Mobile Money. Vous avez un code promo ? Appliquez-le lors de votre commande.</p></div></section>
<section id="faq" class="li-section li-wrap li-faq"><div><p class="li-eyebrow">VOS QUESTIONS</p><h2>Tout est clair<br>avant de commencer.</h2><a class="li-text-link" href="suivi-linkedin">Vous avez déjà commandé ? Suivre ma commande →</a></div><div class="li-faq-list"><details><summary>Dois-je partager mon mot de passe LinkedIn ?</summary><p>Non. Nous préparons vos contenus et vos éléments visuels. Vous gardez le contrôle de votre compte et effectuez les mises à jour.</p></details><details><summary>Puis-je commencer avec un profil incomplet ?</summary><p>Oui. Transmettez votre parcours et vos objectifs. Les informations utiles sont demandées dans le formulaire de commande.</p></details><details><summary>Le CV est-il inclus dans mon offre ?</summary><p>Cela dépend de l’offre choisie. Vérifiez les prestations indiquées sur chaque offre avant de commander.</p></details><details><summary>Comment suivre ma commande ?</summary><p>Utilisez la page de suivi avec votre référence de commande. Vous pourrez consulter l’avancement et accéder aux éléments disponibles.</p><a href="suivi-linkedin">Ouvrir le suivi →</a></details><details><summary>Comment connaître le délai de livraison ?</summary><p>Consultez les indications de votre offre et les informations affichées lors de la commande.</p></details></div></section>
<section class="li-final li-wrap"><div class="li-final-brand" aria-hidden="true"><img src="../assets/images/linkedin/linkedin-brand.jpg" alt="" width="1198" height="632" loading="lazy"></div><p class="li-eyebrow">LA SUITE VOUS APPARTIENT</p><h2>Faisons place à<br>votre prochain chapitre.</h2><a class="li-button" href="#tarifs">Découvrir les offres <span aria-hidden="true">↗</span></a></section>
</main>
<footer class="li-footer li-wrap"><a class="li-brand" href="../index.html"><span class="li-brand-mark" aria-hidden="true">H</span><span class="li-brand-name" translate="no">HireMe<span>LinkedIn</span></span></a><div><a href="../index.html">HireMe CV</a><a href="suivi-linkedin">Suivi de commande</a><a href="../conditions-generales">Conditions générales</a><a href="../politique-confidentialite">Confidentialité</a></div><p>© <?= date('Y') ?> HireMe. Tous droits réservés.</p></footer>
<div class="li-mobile-cta"><span>Votre profil, en mieux.</span><a href="#tarifs">Voir les offres →</a></div>
<script src="../assets/js/linkedin-landing.js?v=2" defer></script>
<script>document.querySelectorAll('a[href^="#"]').forEach(link => { link.href = new URL(link.getAttribute('href'), location.href).href; }); document.querySelectorAll('.li-mobile-nav a').forEach(link => link.addEventListener('click', () => { document.querySelector('.li-mobile-nav').open = false; }));</script>
</body>
</html>
