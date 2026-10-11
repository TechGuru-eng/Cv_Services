<?php
require_once __DIR__ . '/order-notifications.php';

function chariow_signature_valid(string $raw, string $signature, string $secret): bool {
    return $secret !== '' && preg_match('/^sha256=[a-f0-9]{64}$/', $signature) === 1 && hash_equals('sha256=' . hash_hmac('sha256', $raw, $secret), $signature);
}

function chariow_products(): array {
    return ['prd_zvevvoid'=>['essentiel',5000], 'prd_w08gjak7'=>['pro',10000], 'prd_u06y9fgw'=>['premium',25000]];
}

// Called inside a transaction; both the sale and the matching order are locked.
function chariow_match_sale(PDO $pdo, array $payload, string $reference): array {
    $product = chariow_products()[$payload['product']['id'] ?? ''] ?? null;
    if (!$product) return ['status'=>'review','reason'=>'Produit non reconnu.'];
    $value=$payload['sale']['amount']['value'] ?? null;
    $currency=$payload['sale']['amount']['currency'] ?? '';
    if (($payload['sale']['status'] ?? '') !== 'completed' || $currency !== 'XAF' || !is_numeric($value) || (float)$value !== (float)$product[1]) return ['status'=>'review','reason'=>'Statut, montant ou devise incorrect.'];
    if (!preg_match('/^HM-LI-\d{6}$/', $reference)) return ['status'=>'review','reason'=>'Référence de commande absente ou invalide.'];
    $stmt=$pdo->prepare('SELECT o.*, p.slug FROM linkedin_orders o JOIN linkedin_packages p ON p.id=o.package_id WHERE o.reference=? FOR UPDATE');
    $stmt->execute([$reference]); $order=$stmt->fetch();
    if (!$order) return ['status'=>'review','reason'=>'Commande introuvable.'];
    if ($order['slug'] !== $product[0] || (float)$order['total_amount'] !== (float)$value) return ['status'=>'review','reason'=>'La formule ou le montant ne correspond pas à la commande.'];
    $email=strtolower(trim($payload['customer']['email'] ?? ''));
    if (!filter_var($email,FILTER_VALIDATE_EMAIL) || $email !== strtolower(trim($order['email']))) return ['status'=>'review','reason'=>'Adresse email différente de celle de la commande.'];
    $stmt=$pdo->prepare("SELECT id FROM linkedin_payments WHERE order_id=? AND payment_method='CHARIOW' AND status='PENDING' FOR UPDATE");
    $stmt->execute([$order['id']]); $paymentId=$stmt->fetchColumn();
    if (!$paymentId || $order['payment_status'] !== 'PENDING') return ['status'=>'review','reason'=>'La commande ne possède pas de paiement Chariow en attente.'];
    $stmt=$pdo->prepare('SELECT sale_id FROM chariow_sales WHERE order_id=?');
    $stmt->execute([$order['id']]);
    if ($stmt->fetchColumn()) return ['status'=>'review','reason'=>'Commande déjà associée à une autre vente.'];
    $pdo->prepare("UPDATE linkedin_orders SET payment_status='CONFIRMED', order_status='PAIEMENT_CONFIRME' WHERE id=?")->execute([$order['id']]);
    $pdo->prepare("UPDATE linkedin_payments SET status='CONFIRMED',transaction_ref=? WHERE id=?")->execute([$payload['sale']['id'],$paymentId]);
    $pdo->prepare("INSERT INTO linkedin_order_history (order_id,action,note,created_by) VALUES (?,'PAIEMENT_VALIDE',?,'Chariow')")->execute([$order['id'],'Vente Chariow vérifiée : '.$payload['sale']['id']]);
    queue_order_event($pdo,'linkedin',$order,'approved');
    queue_order_event($pdo,'linkedin',$order,'completed');
    return ['status'=>'matched','reason'=>'Paiement Chariow vérifié.','order_id'=>(int)$order['id']];
}

function receive_chariow_sale(PDO $pdo, string $raw, string $delivery): array {
    $payload=json_decode($raw,true,512,JSON_THROW_ON_ERROR);
    if (($payload['event'] ?? '') !== 'successful.sale') return ['status'=>'ignored'];
    if (!preg_match('/^sal_[a-zA-Z0-9_-]+$/', $payload['sale']['id'] ?? '')) throw new InvalidArgumentException('Invalid sale ID');
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT IGNORE INTO chariow_deliveries (delivery_id,body_hash) VALUES (?,?)')->execute([$delivery,hash('sha256',$raw)]);
        $stmt=$pdo->prepare('SELECT body_hash FROM chariow_deliveries WHERE delivery_id=? FOR UPDATE');
        $stmt->execute([$delivery]);
        if ($stmt->fetchColumn() !== hash('sha256',$raw)) throw new InvalidArgumentException('Conflicting delivery');
        $saleId=$payload['sale']['id'];
        $stmt=$pdo->prepare('SELECT * FROM chariow_sales WHERE sale_id=? FOR UPDATE');
        $stmt->execute([$saleId]);
        if ($existing=$stmt->fetch()) { $pdo->commit(); return ['status'=>$existing['status'],'duplicate'=>true]; }
        $amount=$payload['sale']['amount']['value'] ?? null;
        $pdo->prepare('INSERT INTO chariow_sales (sale_id,payload,product_id,customer_email,amount,currency) VALUES (?,?,?,?,?,?)')->execute([$saleId,$raw,$payload['product']['id'] ?? '',$payload['customer']['email'] ?? '',is_numeric($amount)?$amount:null,$payload['sale']['amount']['currency'] ?? '']);
        // Simple service links do not support documented metadata injection.
        // Never guess an order from email alone; missing metadata goes to admin review.
        $ref=$payload['sale']['custom_metadata']['order_ref'] ?? '';
        $result=chariow_match_sale($pdo,$payload,is_string($ref)?$ref:'');
        if ($result['status']==='review' && ($payload['sale']['status'] ?? '')==='completed' && isset(chariow_products()[$payload['product']['id'] ?? '']) && ($payload['sale']['amount']['currency'] ?? '')==='XAF' && is_numeric($amount) && (float)$amount===(float)chariow_products()[$payload['product']['id']][1]) {
            $pdo->prepare('INSERT IGNORE INTO chariow_sale_alerts (sale_id) VALUES (?)')->execute([$saleId]);
            $stmt=$pdo->prepare('SELECT id FROM chariow_sale_alerts WHERE sale_id=?');
            $stmt->execute([$saleId]);$alertId=(int)$stmt->fetchColumn();
            $pack=chariow_products()[$payload['product']['id']][0];
            $base=rtrim(getenv('MONCV_BASE_URL') ?: 'https://moncvpro.hiremeguide.com','/');
            $body="MonCVPro - Vente LinkedIn confirmée par Chariow\nPack : $pack\nVente : $saleId\nDate : ".date('Y-m-d H:i:s')." (UTC+1)\nMontant : $amount FCFA\nCommande à associer : $base/admin/linkedin/chariow-sales.php";
            queue_transactional_notification($pdo,'chariow',$alertId,'completed','sms',getenv('ORDER_ADMIN_PHONE') ?: '+237654250688','',$body);
        }
        $pdo->prepare('UPDATE chariow_sales SET status=?,reason=?,order_id=? WHERE sale_id=?')->execute([$result['status'],$result['reason'],$result['order_id'] ?? null,$saleId]);
        $pdo->commit(); return $result;
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
