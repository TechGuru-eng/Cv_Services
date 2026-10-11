<?php
class SMSApproval {
    public static function segments(string $message): int {
        // Conservative GSM estimate; unicode uses UTF-16 code units, including emoji.
        $gsm = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
        $extension = '^{}\\[~]|€';
        $length = 0;
        foreach (preg_split('//u', $message, -1, PREG_SPLIT_NO_EMPTY) as $char) {
            if (mb_strpos($gsm, $char) !== false) $length++;
            elseif (mb_strpos($extension, $char) !== false) $length += 2;
            else {
                $length = strlen(mb_convert_encoding($message, 'UTF-16BE', 'UTF-8')) / 2;
                return (int)($length <= 70 ? 1 : ceil($length / 67));
            }
        }
        return (int)($length <= 160 ? 1 : ceil($length / 153));
    }

    public static function queue(PDO $pdo, string $recipient, string $message): array {
        $recipient = SMSHelper::formatPhoneNumber($recipient);
        if (!preg_match('/^\+[1-9]\d{7,14}$/', $recipient) || trim($message) === '') return ['success'=>false, 'error'=>'Numéro ou message invalide.'];
        $fingerprint = hash('sha256', $recipient . "\n" . preg_replace('/\s+/u', ' ', trim($message)));
        $pdo->prepare('INSERT IGNORE INTO sms_approval_requests (recipient,message,fingerprint,current_fingerprint,segments) VALUES (?,?,?,?,?)')->execute([$recipient, $message, $fingerprint, $fingerprint, self::segments($message)]);
        return ['success'=>true, 'queued'=>true, 'error'=>'', 'message'=>'SMS en attente de votre approbation dans le tableau de bord SMS.'];
    }

    public static function edit(PDO $pdo, int $id, string $csrf, string $message): array {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($_SESSION['admin']) || empty($_SESSION['sms_csrf']) || !hash_equals($_SESSION['sms_csrf'], $csrf)) return ['success'=>false,'error'=>'Modification administrateur requise.'];
        $message=trim($message);
        if ($message === '' || mb_strlen($message) > 2000) return ['success'=>false,'error'=>'Le message doit contenir entre 1 et 2 000 caractères.'];
        try {
            $pdo->beginTransaction();
            $stmt=$pdo->prepare("SELECT * FROM sms_approval_requests WHERE id=? AND status='pending' FOR UPDATE");
            $stmt->execute([$id]); $item=$stmt->fetch();
            if (!$item) { $pdo->rollBack(); return ['success'=>false,'error'=>'Seuls les SMS en attente peuvent être modifiés.']; }
            $hash=hash('sha256',$item['recipient']."\n".preg_replace('/\s+/u',' ', $message));
            $stmt=$pdo->prepare('SELECT id FROM sms_approval_requests WHERE id<>? AND (fingerprint=? OR current_fingerprint=?) LIMIT 1');
            $stmt->execute([$id,$hash,$hash]);
            if ($stmt->fetchColumn()) { $pdo->rollBack(); return ['success'=>false,'error'=>'Ce message existe déjà pour ce destinataire.']; }
            $pdo->prepare("UPDATE sms_approval_requests SET message=?,current_fingerprint=?,segments=? WHERE id=? AND status='pending'")->execute([$message,$hash,self::segments($message),$id]);
            $pdo->commit();
            return ['success'=>true,'message'=>'Message enregistré. Aucun SMS envoyé. Vérifiez le coût puis approuvez l’envoi.'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return ['success'=>false,'error'=>'Modification impossible : message déjà présent ou erreur de sauvegarde.'];
        }
    }

    public static function used(PDO $pdo): int {
        $period = date('Y-m');
        $stmt = $pdo->prepare('SELECT used FROM sms_monthly_budget WHERE period=?');
        $stmt->execute([$period]);
        $used = $stmt->fetchColumn();
        if ($used !== false) return (int)$used;
        // Include historical website sends before approval mode was introduced.
        $stmt = $pdo->prepare("SELECT message FROM sms_logs WHERE created_at >= ? AND status IN ('success','delivered')");
        $stmt->execute([$period . '-01 00:00:00']);
        $baseline = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $message) $baseline += self::segments($message);
        $pdo->prepare('INSERT IGNORE INTO sms_monthly_budget (period,used) VALUES (?,?)')->execute([$period,$baseline]);
        $stmt = $pdo->prepare('SELECT used FROM sms_monthly_budget WHERE period=?');
        $stmt->execute([$period]);
        return (int)$stmt->fetchColumn();
    }

    public static function approve(PDO $pdo, int $id, string $csrf, callable $deliver): array {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($_SESSION['admin']) || empty($_SESSION['sms_csrf']) || !hash_equals($_SESSION['sms_csrf'], $csrf)) return ['success'=>false, 'error'=>'Approbation administrateur requise.'];
        return self::dispatch($pdo, $id, $deliver);
    }

    public static function dispatch(PDO $pdo, int $id, callable $deliver): array {
        if ($pdo->inTransaction()) return ['success'=>false, 'error'=>'Une opération est déjà en cours.'];
        $lock = $pdo->query("SELECT GET_LOCK('moncv_sms_approval', 5)")->fetchColumn();
        if (!$lock) return ['success'=>false,'error'=>'Un envoi est déjà en cours.'];
        try {
            self::used($pdo);
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM sms_approval_requests WHERE id=? AND status='pending' FOR UPDATE");
            $stmt->execute([$id]); $item=$stmt->fetch();
            if (!$item) { $pdo->rollBack(); return ['success'=>false,'terminal'=>true,'error'=>'SMS déjà traité ou annulé.']; }
            $stmt=$pdo->prepare('UPDATE sms_monthly_budget SET used=used+? WHERE period=? AND used+?<=300');
            $stmt->execute([$item['segments'], date('Y-m'), $item['segments']]);
            if ($stmt->rowCount() !== 1) { $pdo->rollBack(); return ['success'=>false,'error'=>'Limite mensuelle de 300 SMS atteinte.']; }
            // Persist the attempt before contacting the provider: refresh/crash never retries it.
            $pdo->prepare("UPDATE sms_approval_requests SET status='sending',attempted_at=NOW() WHERE id=?")->execute([$id]);
            $pdo->commit();
            try { $result=$deliver($item['recipient'],$item['message']); }
            catch (Throwable $e) { $result=['success'=>false,'error'=>'Résultat de la passerelle incertain. Aucun nouvel envoi automatique.']; }
            $pdo->prepare('UPDATE sms_approval_requests SET status=?,error_message=? WHERE id=?')->execute([$result['success'] ? 'accepted' : 'failed', $result['success'] ? null : substr($result['error'] ?? 'Échec',0,255),$id]);
            $result['terminal'] = true;
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return ['success'=>false,'error'=>'Approbation non terminée. Consultez le statut avant toute nouvelle action.'];
        } finally { $pdo->query("SELECT RELEASE_LOCK('moncv_sms_approval')"); }
    }
}
