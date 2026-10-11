<?php
require __DIR__ . '/../admin/config/db.php';
require __DIR__ . '/../includes/chariow-webhook.php';
// Shadow all mutable tables: no real orders, external calls or notifications.
foreach (['linkedin_orders','linkedin_packages','linkedin_payments','linkedin_order_history','transactional_notifications'] as $table) {
    $row=$pdo->query('SHOW CREATE TABLE '.$table)->fetch(PDO::FETCH_NUM);
    $definition=preg_replace('/,\n\s*CONSTRAINT[^\n]+/','',$row[1]);
    $pdo->exec(str_replace('CREATE TABLE','CREATE TEMPORARY TABLE',$definition));
}
$pdo->exec(str_replace('CREATE TABLE IF NOT EXISTS','CREATE TEMPORARY TABLE',file_get_contents(__DIR__.'/../admin/sql/chariow-webhook.sql')));
$pdo->exec(str_replace('CREATE TABLE IF NOT EXISTS','CREATE TEMPORARY TABLE',file_get_contents(__DIR__.'/../admin/sql/chariow-sale-alerts.sql')));
function cwcheck($condition,$message) { if (!$condition) throw new RuntimeException($message); }
$pdo->exec("INSERT INTO linkedin_packages (id,slug,name,price) VALUES (9001,'pro','Test Pro',10000)");
$pdo->exec("INSERT INTO linkedin_orders (id,uuid,reference,access_token,name,first_name,phone,email,linkedin_url,package_id,total_amount,payment_status,order_status) VALUES (9001,'test-uuid','HM-LI-900001','test-token','Test','Client','+237654250688','test@example.com','https://linkedin.com/in/test',9001,10000,'PENDING','COMMANDE_RECUE')");
$pdo->exec("INSERT INTO linkedin_payments (order_id,payment_method,phone_number,transaction_ref,status) VALUES (9001,'CHARIOW','','','PENDING')");
$payload=['event'=>'successful.sale','sale'=>['id'=>'sal_test','status'=>'completed','amount'=>['value'=>10000,'currency'=>'XAF'],'custom_metadata'=>['order_ref'=>'HM-LI-900001']],'product'=>['id'=>'prd_w08gjak7'],'customer'=>['email'=>'test@example.com']];
$raw=json_encode($payload);$secret='whsec_test_only';$signature='sha256='.hash_hmac('sha256',$raw,$secret);
cwcheck(chariow_signature_valid($raw,$signature,$secret),'Signature verifies');
cwcheck(!chariow_signature_valid($raw.' ',$signature,$secret),'Tampering denied');
cwcheck(!chariow_signature_valid($raw,substr($signature,7),$secret),'Missing prefix denied');
cwcheck(!chariow_signature_valid($raw,$signature,''),'Missing secret denied');
$missing=$payload;$missing['sale']['id']='sal_missing';unset($missing['sale']['custom_metadata']);
cwcheck(receive_chariow_sale($pdo,json_encode($missing),'delivery_missing')['status']==='review','No reference requires review');
$wrong=$payload;$wrong['sale']['id']='sal_wrong';$wrong['sale']['amount']['value']=5000;
cwcheck(receive_chariow_sale($pdo,json_encode($wrong),'delivery_wrong')['status']==='review','Incorrect amount requires review');
$wrongEmail=$payload;$wrongEmail['sale']['id']='sal_email';$wrongEmail['customer']['email']='different@example.com';
cwcheck(receive_chariow_sale($pdo,json_encode($wrongEmail),'delivery_email')['status']==='review','Incorrect customer requires review');
cwcheck($pdo->query('SELECT payment_status FROM linkedin_orders')->fetchColumn()==='PENDING','Invalid sales never change order');
cwcheck(receive_chariow_sale($pdo,$raw,'delivery_valid')['status']==='matched','Matching payment confirms');
cwcheck($pdo->query('SELECT payment_status FROM linkedin_orders')->fetchColumn()==='CONFIRMED','Order confirmed');
cwcheck($pdo->query('SELECT transaction_ref FROM linkedin_payments')->fetchColumn()==='sal_test','Sale reference recorded');
$count=(int)$pdo->query('SELECT COUNT(*) FROM transactional_notifications')->fetchColumn();
cwcheck((int)$pdo->query("SELECT COUNT(*) FROM transactional_notifications WHERE order_type='linkedin' AND order_id=9001 AND event='approved'")->fetchColumn()===2,'Customer SMS and email queued');
cwcheck((int)$pdo->query("SELECT COUNT(*) FROM transactional_notifications WHERE order_type='linkedin' AND order_id=9001 AND event='completed' AND channel='sms'")->fetchColumn()===1,'Admin alerted on confirmation');
queue_chariow_return_alert($pdo,['id'=>9001,'reference'=>'HM-LI-900001','access_token'=>'test-token','name'=>'Test','total_amount'=>10000,'payment_status'=>'CONFIRMED']);
cwcheck((int)$pdo->query('SELECT COUNT(*) FROM transactional_notifications')->fetchColumn()===$count,'Return after webhook cannot duplicate admin alert');
cwcheck(receive_chariow_sale($pdo,$raw,'delivery_valid')['duplicate'],'Repeated delivery deduplicated');
cwcheck(receive_chariow_sale($pdo,$raw,'delivery_replay')['duplicate'],'New replay delivery cannot confirm sale twice');
cwcheck((int)$pdo->query('SELECT COUNT(*) FROM transactional_notifications')->fetchColumn()===$count,'No duplicate notifications');

$returnOrder=['id'=>9002,'reference'=>'HM-LI-900002','access_token'=>'test-token','name'=>'Test','total_amount'=>10000,'payment_status'=>'PENDING'];
queue_chariow_return_alert($pdo,$returnOrder);
queue_chariow_return_alert($pdo,$returnOrder);
queue_order_event($pdo,'linkedin',$returnOrder,'completed');
$stmt=$pdo->query("SELECT body FROM transactional_notifications WHERE order_type='linkedin' AND order_id=9002 AND channel='sms' AND event='completed'");
$alerts=$stmt->fetchAll(PDO::FETCH_COLUMN);
cwcheck(count($alerts)===1 && strpos($alerts[0],'ce retour ne prouve pas le paiement')!==false,'Return first alerts once and does not claim payment confirmation');
echo "PASS: signature, tampering, reference/amount/customer validation, confirmation and replay protection. No real payments or messages.\n";
