<?php
require_once __DIR__ . '/environment.php';

function queue_transactional_notification(PDO $pdo, string $type, int $id, string $event, string $channel, string $recipient, string $subject, string $body): void {
    if ($recipient === '') return;
    $pdo->prepare('INSERT IGNORE INTO transactional_notifications (order_type, order_id, event, channel, recipient, subject, body) VALUES (?, ?, ?, ?, ?, ?, ?)')->execute([$type, $id, $event, $channel, $recipient, $subject, $body]);
}

function order_notification_content(string $type, array $order, string $event): array {
    $base = rtrim(getenv('MONCV_BASE_URL') ?: 'https://moncvpro.hiremeguide.com', '/');
    $linkedin = $type === 'linkedin';
    $label = $linkedin ? 'Optimisation LinkedIn' : 'CV';
    $ref = $linkedin ? $order['reference'] : $order['order_ref'];
    $amount = number_format((float)($linkedin ? $order['total_amount'] : $order['amount']), 0, ',', ' ') . ' FCFA';
    $date = $order['created_at'] ?? date('Y-m-d H:i:s');
    $admin = $base . ($linkedin ? '/admin/linkedin/order_details.php?id=' : '/admin/order-details.php?id=') . (int)$order['id'];
    $tracking = $base . ($linkedin ? '/linkedin/commande.php?' . http_build_query(['ref' => $ref, 'token' => $order['access_token']]) : '/track-cv.php?' . http_build_query(['ref' => $ref]));
    $name = html_entity_decode(trim(($order['first_name'] ?? '') . ' ' . $order['name']), ENT_QUOTES, 'UTF-8');
    if ($event === 'completed') {
        $body = "MonCVPro - Nouvelle commande complète\nType : $label\nRéf : $ref\nDate : $date (UTC+1)\nMontant : $amount\nÀ vérifier : $admin";
        return ['subject'=>"Nouvelle commande $label : $ref | MonCVPro", 'sms'=>$body, 'email'=>$body];
    }
    return [
        'subject' => "Votre commande $ref est approuvée | MonCVPro",
        'sms' => "Bonjour $name, votre commande $label ($ref) est approuvée. Notre équipe travaille actuellement sur votre commande. Merci pour votre confiance ! Suivi : $tracking - MonCVPro",
        'email' => "Bonjour $name,\n\nBonne nouvelle : votre commande $label ($ref) a été approuvée par notre équipe. Votre paiement est validé et nous travaillons actuellement sur votre commande.\n\nCommande : $label\nRéférence : $ref\nDate de commande : $date (UTC+1)\nMontant : $amount\n\nConsultez l'avancement et retrouvez votre livraison depuis votre espace de suivi :\n$tracking\n\nMerci de nous confier cette étape de votre parcours professionnel.\nL'équipe MonCVPro\n"
    ];
}

function queue_order_event(PDO $pdo, string $type, array $order, string $event): void {
    $content = order_notification_content($type, $order, $event);
    $id = (int)$order['id'];
    if ($event === 'completed') {
        queue_transactional_notification($pdo, $type, $id, $event, 'sms', getenv('ORDER_ADMIN_PHONE') ?: '+237654250688', '', $content['sms']);
        queue_transactional_notification($pdo, $type, $id, $event, 'email', getenv('ORDER_ADMIN_EMAIL') ?: 'moncvpro@hiremeguide.com', $content['subject'], $content['email']);
    } else {
        queue_transactional_notification($pdo, $type, $id, $event, 'sms', $order['phone'] ?? $order['whatsapp'] ?? '', '', $content['sms']);
        queue_transactional_notification($pdo, $type, $id, $event, 'email', $order['email'] ?? '', $content['subject'], $content['email']);
    }
}

function queue_chariow_return_alert(PDO $pdo, array $order): void {
    $content=order_notification_content('linkedin',$order,'completed');
    $notice=$order['payment_status']==='CONFIRMED' ? "Paiement Chariow confirmé." : "Retour du client depuis Chariow. Paiement à vérifier : ce retour ne prouve pas le paiement.";
    queue_transactional_notification($pdo,'linkedin',(int)$order['id'],'completed','sms',getenv('ORDER_ADMIN_PHONE') ?: '+237654250688','',$notice."\n".$content['sms']);
}

function send_order_notifications(PDO $pdo, ?callable $transport = null): void {
    // A committed checkout must remain successful when a provider is unavailable.
    try {
        $ids = $pdo->query('SELECT id FROM transactional_notifications WHERE sent_at IS NULL AND (next_attempt_at IS NULL OR next_attempt_at <= NOW()) ORDER BY id LIMIT 10')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ids as $id) {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('SELECT * FROM transactional_notifications WHERE id = ? AND sent_at IS NULL AND (next_attempt_at IS NULL OR next_attempt_at <= NOW()) FOR UPDATE');
                $stmt->execute([$id]);
                $item = $stmt->fetch();
                if (!$item) { $pdo->commit(); continue; }
                if ($transport !== null) {
                    $transport($item);
                } elseif ($item['channel'] === 'sms') {
                    require_once __DIR__ . '/../admin/config/sms_helper.php';
                    // The SMS budget/attempt must commit before contacting TextBee.
                    $pdo->commit();
                    $result = SMSHelper::sendOrderSMS((int)$item['id']);
                    if (!$result['success'] && empty($result['terminal'])) throw new RuntimeException('SMS delivery deferred');
                    $pdo->prepare('UPDATE transactional_notifications SET sent_at = NOW(), attempts = attempts + 1 WHERE id = ? AND sent_at IS NULL')->execute([$id]);
                    continue;
                } else {
                    if (!getenv('SMTP_PASSWORD')) throw new RuntimeException('SMTP is not configured');
                    require_once __DIR__ . '/../PHPMailer/src/Exception.php';
                    require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
                    require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = getenv('SMTP_HOST') ?: 'smtp.hostinger.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = getenv('SMTP_USERNAME') ?: 'moncvpro@hiremeguide.com';
                    $mail->Password = getenv('SMTP_PASSWORD');
                    $mail->SMTPSecure = getenv('SMTP_ENCRYPTION') ?: 'ssl';
                    $mail->Port = (int)(getenv('SMTP_PORT') ?: 465);
                    $mail->Timeout = 10;
                    $mail->CharSet = 'UTF-8';
                    $mail->setFrom(getenv('SMTP_FROM_EMAIL') ?: $mail->Username, 'MonCVPro');
                    $mail->addAddress($item['recipient']);
                    $mail->Subject = $item['subject'];
                    $mail->Body = $item['body'];
                    $mail->send();
                }
                $pdo->prepare('UPDATE transactional_notifications SET sent_at = NOW(), attempts = attempts + 1 WHERE id = ?')->execute([$id]);
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $pdo->prepare('UPDATE transactional_notifications SET attempts = attempts + 1, next_attempt_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id = ? AND sent_at IS NULL')->execute([$id]);
                error_log('Order notification deferred (id ' . (int)$id . ').');
            }
        }
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Order notification worker unavailable.');
    }
}
