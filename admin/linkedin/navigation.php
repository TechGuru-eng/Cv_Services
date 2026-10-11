<?php
$linkedinPage = basename($_SERVER['SCRIPT_NAME']);
$linkedinBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$linkedinRoot = rtrim(dirname(dirname($linkedinBase)), '/');
?>
<aside class="li-admin-nav">
    <div class="li-nav-top"><a class="li-admin-brand" href="<?= htmlspecialchars($linkedinBase) ?>/index.php"><span>HM</span><div>HireMe<small>ADMINISTRATION LINKEDIN</small></div></a><button type="button" class="li-menu-toggle" aria-expanded="false" aria-controls="li-admin-menu">Menu <span aria-hidden="true">☰</span></button></div>
    <div id="li-admin-menu" class="li-menu-content">
    <p class="li-admin-nav-label">ESPACE DE GESTION</p>
    <nav aria-label="Administration LinkedIn">
    <?php foreach (['index.php'=>'Tableau de bord','commandes.php'=>'Commandes','leads.php'=>'Prospects & relances','packages.php'=>'Forfaits & options','upsells.php'=>'Produits complémentaires','chariow.php'=>'Paiement Chariow','chariow-sales.php'=>'Ventes Chariow à vérifier','parametres.php'=>'Paramètres & témoignages','promotions.php'=>'Codes promo'] as $file=>$label): ?>
        <a href="<?= htmlspecialchars($linkedinBase . '/' . $file) ?>" <?= ($linkedinPage === $file || ($file === 'commandes.php' && $linkedinPage === 'order_details.php')) ? 'aria-current="page"' : '' ?>><?= $label ?></a>
    <?php endforeach; ?>
    </nav>
    <div class="li-admin-nav-footer"><a href="<?= htmlspecialchars($linkedinRoot) ?>/linkedin/index.php" target="_blank" rel="noopener">Voir le site LinkedIn ↗</a><a href="<?= htmlspecialchars(dirname($linkedinBase)) ?>/index.php">← Administration principale</a></div>
    </div>
</aside>
<script>
(() => {
    const button = document.querySelector('.li-menu-toggle');
    const sidebar = button.closest('.li-admin-nav');
    sidebar.classList.add('li-menu-ready');
    const setOpen = open => { button.setAttribute('aria-expanded', String(open)); sidebar.classList.toggle('li-menu-open', open); };
    button.addEventListener('click', () => setOpen(button.getAttribute('aria-expanded') !== 'true'));
    sidebar.addEventListener('keydown', event => { if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') { setOpen(false); button.focus(); } });
})();
</script>
