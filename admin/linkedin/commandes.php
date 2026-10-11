<?php
// admin/linkedin/commandes.php
// Order Management List Page for LinkedIn Module

require_once __DIR__ . '/../auth.php';

$search = trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : '');
$statusFilter = trim(is_string($_GET['status'] ?? null) ? $_GET['status'] : '');
$paymentFilter = trim(is_string($_GET['payment'] ?? null) ? $_GET['payment'] : '');

$params = [];
$whereClauses = ["1=1"];

if ($search !== '') {
    $whereClauses[] = "(o.reference LIKE ? OR o.name LIKE ? OR o.first_name LIKE ? OR o.phone LIKE ? OR o.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($statusFilter)) {
    $whereClauses[] = "o.order_status = ?";
    $params[] = $statusFilter;
}

if (!empty($paymentFilter)) {
    $whereClauses[] = "o.payment_status = ?";
    $params[] = $paymentFilter;
}

$whereSql = implode(" AND ", $whereClauses);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM linkedin_orders o WHERE $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pageSize = 20;
$pageCount = max(1, (int)ceil($total / $pageSize));
$page = min($pageCount, max(1, (int)(is_scalar($_GET['page'] ?? 1) ? ($_GET['page'] ?? 1) : 1)));
$offset = ($page - 1) * $pageSize;
$stmt = $pdo->prepare("SELECT o.*, p.name as package_name FROM linkedin_orders o LEFT JOIN linkedin_packages p ON o.package_id = p.id WHERE $whereSql ORDER BY o.id DESC LIMIT $pageSize OFFSET $offset");
$stmt->execute($params);
$orders = $stmt->fetchAll();
$summary = $pdo->query("SELECT COUNT(*) total, COALESCE(SUM(payment_status = 'PAYMENT_SUBMITTED'),0) review, COALESCE(SUM(order_status = 'EN_COURS_D_OPTIMISATION'),0) progress, COALESCE(SUM(order_status = 'TERMINE'),0) done FROM linkedin_orders")->fetch();
$paymentLabels = ['PENDING'=>'En attente', 'PAYMENT_SUBMITTED'=>'À vérifier', 'CONFIRMED'=>'Confirmé', 'REJECTED'=>'Rejeté'];
$statusLabels = ['COMMANDE_RECUE'=>'Commande reçue', 'PAIEMENT_EN_VERIFICATION'=>'Paiement à vérifier', 'PAIEMENT_CONFIRME'=>'Paiement confirmé', 'EN_COURS_D_OPTIMISATION'=>'En optimisation', 'TERMINE'=>'Terminé'];
function liEscape($value) { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
function liOrdersLink($changes) { return 'commandes.php?' . http_build_query(array_merge(['q'=>$GLOBALS['search'], 'payment'=>$GLOBALS['paymentFilter'], 'status'=>$GLOBALS['statusFilter']], $changes)); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<base href="<?= htmlspecialchars(rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/', ENT_QUOTES, 'UTF-8') ?>">
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Commandes LinkedIn — HireMe Administration</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/linkedin-admin.css?v=20261010">

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="../../assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="../../assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="../../assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body><div class="li-admin-shell">
<?php require __DIR__ . '/navigation.php'; ?>
<main class="li-admin-content li-orders">
<header class="li-page-heading"><div><p class="li-eyebrow">ESPACE LINKEDIN / COMMANDES</p><h1>Commandes LinkedIn</h1><p class="li-muted">Suivez vos clients, vérifiez les paiements et pilotez chaque prestation.</p></div><a class="li-admin-button secondary" href="../../linkedin/index.php" target="_blank" rel="noopener">Voir le site ↗</a></header>
<section class="li-stats" aria-label="Vue d’ensemble des commandes">
<?php foreach ([['Toutes les commandes','total',''],['Paiements à vérifier','review','PAYMENT_SUBMITTED'],['En optimisation','progress',''],['Prestations terminées','done','']] as $i=>$metric): ?>
<a class="li-stat" href="<?= liEscape(liOrdersLink(['q'=>'','payment'=>$metric[2],'status'=>$i===2?'EN_COURS_D_OPTIMISATION':($i===3?'TERMINE':''),'page'=>1])) ?>"><span><?= $metric[0] ?></span><strong><?= (int)$summary[$metric[1]] ?></strong><small><?= $i===1 ? 'Vérifier les justificatifs →' : 'Afficher les commandes →' ?></small></a>
<?php endforeach; ?>
</section>
<section class="li-order-panel" aria-labelledby="orders-heading">
<div class="li-panel-heading"><div><h2 id="orders-heading">Liste des commandes</h2><p class="li-muted"><?= $total ?> résultat<?= $total > 1 ? 's' : '' ?><?= ($search!=='' || $paymentFilter!=='' || $statusFilter!=='') ? ' pour vos filtres' : ' au total' ?></p></div><span class="li-count">20 par page</span></div>
<form method="GET" action="commandes.php" class="li-order-filters">
<label class="li-search">Rechercher<input type="search" name="q" value="<?= liEscape($search) ?>" placeholder="Référence, nom, téléphone ou email"></label>
<label>Paiement<select name="payment"><option value="">Tous les paiements</option><?php foreach ($paymentLabels as $value=>$label): ?><option value="<?= $value ?>" <?= $paymentFilter===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label>
<label>Avancement<select name="status"><option value="">Tous les statuts</option><?php foreach ($statusLabels as $value=>$label): ?><option value="<?= $value ?>" <?= $statusFilter===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label>
<div class="li-filter-actions"><button class="li-admin-button" type="submit">Filtrer</button><a class="li-reset" href="commandes.php">Réinitialiser</a></div>
</form>
<?php if (!$orders): ?>
<div class="li-empty"><h3>Aucune commande trouvée</h3><p>Essayez une autre recherche ou retirez les filtres.</p><a href="commandes.php" class="li-admin-button secondary">Voir toutes les commandes</a></div>
<?php else: ?>
<div class="li-table-wrap"><table class="li-orders-table"><caption class="li-sr-only">Commandes LinkedIn et actions de gestion</caption><thead><tr><th scope="col">Client / référence</th><th scope="col">Prestation</th><th scope="col">Montant</th><th scope="col">Paiement</th><th scope="col">Avancement</th><th scope="col">Date</th><th scope="col"><span class="li-sr-only">Actions</span></th></tr></thead><tbody>
<?php foreach ($orders as $ord): $profileUrl = (string)($ord['linkedin_url'] ?? ''); $validProfile = filter_var($profileUrl, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($profileUrl, PHP_URL_SCHEME) ?? ''), ['http','https'], true); ?>
<tr><td data-label="Client" class="li-client"><strong><?= liEscape(trim(($ord['first_name'] ?? '').' '.($ord['name'] ?? ''))) ?></strong><a class="li-reference" href="order_details.php?id=<?= (int)$ord['id'] ?>"><?= liEscape($ord['reference']) ?></a><span><?= liEscape($ord['phone']) ?></span><span class="li-email"><?= liEscape($ord['email']) ?></span></td>
<td data-label="Prestation"><strong><?= liEscape($ord['package_name'] ?? 'Forfait indisponible') ?></strong><?php if ($validProfile): ?><a class="li-profile" href="<?= liEscape($profileUrl) ?>" target="_blank" rel="noopener noreferrer">Voir le profil ↗</a><?php else: ?><span class="li-muted">Profil non renseigné</span><?php endif; ?></td>
<td data-label="Montant" class="li-amount"><?= number_format((float)$ord['total_amount'],0,',',' ') ?><small>FCFA</small></td>
<td data-label="Paiement"><span class="li-badge <?= $ord['payment_status']==='CONFIRMED'?'success':($ord['payment_status']==='REJECTED'?'danger':($ord['payment_status']==='PAYMENT_SUBMITTED'?'warning':'neutral')) ?>"><?= liEscape($paymentLabels[$ord['payment_status']] ?? $ord['payment_status']) ?></span></td>
<td data-label="Avancement"><span class="li-badge <?= $ord['order_status']==='TERMINE'?'success':($ord['order_status']==='EN_COURS_D_OPTIMISATION'?'info':'neutral') ?>"><?= liEscape($statusLabels[$ord['order_status']] ?? $ord['order_status']) ?></span></td>
<td data-label="Date" class="li-date"><time datetime="<?= liEscape(date('c',strtotime($ord['created_at']))) ?>"><?= date('d/m/Y',strtotime($ord['created_at'])) ?><small><?= date('H:i',strtotime($ord['created_at'])) ?></small></time></td>
<td class="li-order-action"><a class="li-manage" aria-label="Gérer la commande <?= liEscape($ord['reference']) ?>" href="order_details.php?id=<?= (int)$ord['id'] ?>">Gérer <span aria-hidden="true">→</span></a></td></tr>
<?php endforeach; ?></tbody></table></div>
<nav class="li-pagination" aria-label="Pagination des commandes"><span><?= $offset+1 ?>–<?= min($offset+$pageSize,$total) ?> sur <?= $total ?></span><div><?php if ($page>1): ?><a href="<?= liEscape(liOrdersLink(['page'=>$page-1])) ?>">← Précédent</a><?php endif; ?><span>Page <?= $page ?> / <?= $pageCount ?></span><?php if ($page<$pageCount): ?><a href="<?= liEscape(liOrdersLink(['page'=>$page+1])) ?>">Suivant →</a><?php endif; ?></div></nav>
<?php endif; ?></section>
</main></div></body></html>
