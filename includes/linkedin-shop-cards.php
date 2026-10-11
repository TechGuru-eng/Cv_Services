<?php require_once __DIR__ . '/linkedin-shop-products.php'; ?>
<section class="hm-resources" aria-label="Ressources professionnelles">
    <div class="hm-resources-heading"><span>LES RESSOURCES HIREME</span><h2>Donnez une suite à vos ambitions.</h2><p>Des outils pratiques pour vos candidatures, vos entretiens et votre prochaine étape.</p></div>
    <div class="hm-resource-grid">
        <?php foreach (linkedin_shop_products() as $resource): ?>
        <article class="hm-resource-card">
            <div class="hm-resource-icon" aria-hidden="true"><i class="fa-solid fa-<?= htmlspecialchars($resource['icon']) ?>"></i></div>
            <div class="hm-resource-content">
                <h3><?= htmlspecialchars($resource['name']) ?></h3>
                <p><?= htmlspecialchars($resource['description']) ?></p>
            </div>
            <div class="hm-resource-bottom"><strong translate="no"><?= htmlspecialchars($resource['price']) ?></strong><a href="<?= htmlspecialchars($resource['url']) ?>" target="_blank" rel="noopener noreferrer"><span>Voir le produit</span> <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></div>
        </article>
        <?php endforeach; ?>
    </div>
    <p class="hm-resource-note">Achats séparés sur la boutique HireMe. Ils ne sont pas ajoutés à votre commande LinkedIn. Le prix final et les éventuelles promotions sont indiqués sur la boutique.</p>
</section>
