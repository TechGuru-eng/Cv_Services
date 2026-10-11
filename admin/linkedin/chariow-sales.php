<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../../includes/chariow-webhook.php';
$message='';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verify_csrf();
    try {
        $pdo->beginTransaction();
        $stmt=$pdo->prepare("SELECT * FROM chariow_sales WHERE sale_id=? AND status='review' FOR UPDATE");
        $stmt->execute([$_POST['sale_id'] ?? '']); $sale=$stmt->fetch();
        if (!$sale) throw new RuntimeException('Vente déjà traitée ou introuvable.');
        $payload=json_decode($sale['payload'],true,512,JSON_THROW_ON_ERROR);
        $result=chariow_match_sale($pdo,$payload,trim($_POST['reference'] ?? ''));
        $pdo->prepare('UPDATE chariow_sales SET status=?,reason=?,order_id=? WHERE sale_id=?')->execute([$result['status'],$result['reason'],$result['order_id'] ?? null,$sale['sale_id']]);
        $pdo->commit(); $message=$result['reason'];
        if ($result['status']==='matched') send_order_notifications($pdo);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message='Association impossible. Vérifiez la vente et la commande.';
    }
}
$sales=$pdo->query('SELECT s.*,o.reference FROM chariow_sales s LEFT JOIN linkedin_orders o ON o.id=s.order_id ORDER BY s.received_at DESC LIMIT 100')->fetchAll();
function saleEscape($text) { return htmlspecialchars((string)$text,ENT_QUOTES,'UTF-8'); }
?>
<!doctype html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ventes Chariow — LinkedIn</title><link rel="stylesheet" href="../../assets/css/linkedin-admin.css"></head>
<body style="background:#f1f5f9;color:#0f172a;font-family:system-ui;padding:24px"><main style="max-width:1000px;margin:auto">
<a href="index.php">← Administration LinkedIn</a><h1>Ventes Chariow</h1>
<p>Les ventes signées sans référence de commande attendent votre vérification. L’association contrôle le produit, le montant, l’email et le mode de paiement avant confirmation.</p>
<?php if ($message): ?><p role="status"><?=saleEscape($message)?></p><?php endif; ?>
<?php if (!$sales): ?><p>Aucune vente Chariow reçue.</p><?php endif; ?>
<?php foreach ($sales as $sale): ?><section style="background:white;border-radius:12px;padding:20px;margin-block:16px">
<h2><?=saleEscape($sale['sale_id'])?></h2><p><?=saleEscape($sale['product_id'])?> · <?=saleEscape($sale['amount'])?> <?=saleEscape($sale['currency'])?> · <?=saleEscape($sale['customer_email'])?></p>
<p><?=saleEscape($sale['status']==='matched'?'Paiement confirmé : '.$sale['reference']:$sale['reason'])?></p>
<?php if ($sale['status']==='review'): ?><form method="post"><?=csrf_field()?>
<input type="hidden" name="sale_id" value="<?=saleEscape($sale['sale_id'])?>">
<label>Référence de commande <input name="reference" required pattern="HM-LI-[0-9]{6}" placeholder="HM-LI-123456"></label>
<button type="submit">Vérifier et confirmer le paiement</button></form><?php endif; ?>
</section><?php endforeach; ?></main></body></html>
