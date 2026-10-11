<?php
require __DIR__ . '/../auth.php';
require __DIR__ . '/../includes/csrf.php';
$packages=$pdo->query('SELECT id,name FROM linkedin_packages WHERE is_active=1 ORDER BY sort_order')->fetchAll(PDO::FETCH_ASSOC);
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf(); $urls=[];
 foreach($packages as $p){$key='chariow_url_'.$p['id'];$url=trim($_POST[$key]??'');if($url && (!filter_var($url,FILTER_VALIDATE_URL)||parse_url($url,PHP_URL_SCHEME)!=='https')){$error='Utilisez un lien HTTPS valide.';break;}$urls[$key]=$url;}
 if(!$error){$stmt=$pdo->prepare('INSERT INTO linkedin_settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');foreach($urls as $k=>$v)$stmt->execute([$k,$v]);header('Location: chariow.php?saved=1');exit;}
}
$settings=$pdo->query('SELECT setting_key,setting_value FROM linkedin_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
?><!doctype html><html lang="fr"><head>
<base href="<?= htmlspecialchars(rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/', ENT_QUOTES, 'UTF-8') ?>"><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Liens Chariow LinkedIn</title><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="../../assets/css/linkedin-admin.css?v=20261010">
    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="../../assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="../../assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="../../assets/favicons/apple-touch-icon-180.png?v=20261011">
</head><body><div class="li-admin-shell"><?php require __DIR__ . "/navigation.php"; ?><main class="li-admin-content"><a href="index.php" class="text-sky-700">← Dashboard LinkedIn</a><h1 class="text-3xl font-bold my-6">Paiement international Chariow</h1><p class="text-slate-500 mb-6">Un lien de boutique par forfait. Configurez le retour après paiement dans Chariow vers l’URL publique de <code>/linkedin/retour-chariow.php</code>. Le retour ne confirme pas le paiement : vérifiez la vente dans Chariow puis confirmez la commande dans l’administration. Les options et codes promo du site ne s’appliquent pas à ces liens fixes.</p><?php if($error):?><p role="alert"><?=htmlspecialchars($error)?></p><?php elseif(isset($_GET['saved'])):?><p class="text-green-700">Liens enregistrés.</p><?php endif;?><form method="POST" class="li-admin-card grid gap-5 max-w-3xl"><?php csrf_field();foreach($packages as $p):$key='chariow_url_'.$p['id'];?><label><?=htmlspecialchars($p['name'])?><input type="url" name="<?=$key?>" value="<?=htmlspecialchars($settings[$key]??'',ENT_QUOTES)?>" placeholder="https://…" class="block w-full bg-white border border-slate-300 p-3 rounded-xl mt-2"></label><?php endforeach;?><button class="li-admin-button">Enregistrer</button></form></main></div></body></html>
