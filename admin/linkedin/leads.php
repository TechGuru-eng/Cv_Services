<?php
require __DIR__ . '/../auth.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/followup_helper.php';
require_once __DIR__ . '/../config/sms_helper.php';
linkedinPrepareFollowups($pdo);
$flash = $_SESSION['linkedin_followup_flash'] ?? '';
unset($_SESSION['linkedin_followup_flash']);
$leads = linkedinEligibleLeads($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nonce = (string)($_POST['nonce'] ?? '');
    if (!$nonce || !hash_equals($_SESSION['linkedin_followup_nonce'] ?? '', $nonce)) {
        $_SESSION['linkedin_followup_flash'] = 'Formulaire déjà utilisé ou expiré. Réessayez.';
        header('Location: leads.php'); exit;
    }
    unset($_SESSION['linkedin_followup_nonce']);
    $lead = null;
    foreach ($leads as $candidate) if ((int)$candidate['id'] === (int)($_POST['id'] ?? 0)) $lead = $candidate;
    $action = $_POST['action'] ?? '';
    $redirect = null;
    if (!$lead) {
        $_SESSION['linkedin_followup_flash'] = 'Ce prospect ne peut plus être relancé.';
    } elseif ($action === 'status' && in_array($_POST['status'] ?? '', ['open','contacted','dismissed'], true)) {
        $pdo->prepare("UPDATE linkedin_checkout_leads SET status=?,updated_at=updated_at WHERE id=? AND status IN ('open','contacted') AND order_reference IS NULL")->execute([$_POST['status'],$lead['id']]);
        $_SESSION['linkedin_followup_flash'] = 'Statut enregistré.';
    } elseif (in_array($action, ['whatsapp','sms'], true) && strlen(prospectPhone($lead['phone'])) >= 8) {
        $stage = min(10, (int)$lead['followup_stage'] + 1);
        if ((int)($_POST['stage'] ?? 0) !== $stage) {
            $_SESSION['linkedin_followup_flash'] = 'Une relance a déjà été enregistrée. Rafraîchissez la page.';
        } else {
            $text = linkedinFollowupMessage($lead, $stage);
            $pdo->beginTransaction();
            try {
                $lock = $pdo->prepare('SELECT status,order_reference FROM linkedin_checkout_leads WHERE id=? FOR UPDATE');
                $lock->execute([$lead['id']]); $current = $lock->fetch();
                $check = $pdo->prepare("SELECT COALESCE(MAX(stage),0) FROM linkedin_followups WHERE lead_id=? AND channel='whatsapp'");
                $check->execute([$lead['id']]);
                if (!$current || !in_array($current['status'],['open','contacted'],true) || $current['order_reference'] !== null || (int)$check->fetchColumn() !== (int)$lead['followup_stage']) throw new RuntimeException('Ce dossier a changé. Rafraîchissez la page.');
                if ($action === 'sms') {
                    $result = SMSHelper::sendSMS(prospectPhone($lead['phone']), $text);
                    if (empty($result['success'])) throw new RuntimeException($result['error'] ?? 'Échec de l’envoi SMS.');
                } else {
                    $redirect = 'https://wa.me/' . prospectPhone($lead['phone']) . '?text=' . rawurlencode($text);
                }
                $pdo->prepare('INSERT INTO linkedin_followups (lead_id,stage,channel) VALUES (?,?,?)')->execute([$lead['id'],$stage,$action]);
                $pdo->prepare("UPDATE linkedin_checkout_leads SET status='contacted',updated_at=updated_at WHERE id=?")->execute([$lead['id']]);
                $pdo->commit();
                $_SESSION['linkedin_followup_flash'] = $action === 'sms' ? 'SMS préparé. Approbation requise dans le tableau de bord SMS.' : 'WhatsApp ouvert. La livraison du message n’est pas confirmée.';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $redirect = null;
                $_SESSION['linkedin_followup_flash'] = $e instanceof RuntimeException ? $e->getMessage() : 'Impossible d’enregistrer cette relance.';
            }
        }
    }
    header('Location: ' . ($redirect ?: 'leads.php')); exit;
}
$_SESSION['linkedin_followup_nonce'] = bin2hex(random_bytes(32));
function leadEscape($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="fr"><head>
<base href="<?= htmlspecialchars(rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/', ENT_QUOTES, 'UTF-8') ?>"><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Prospects & relances LinkedIn</title><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="../../assets/css/linkedin-admin.css?v=20261010">
    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="../../assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="../../assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="../../assets/favicons/apple-touch-icon-180.png?v=20261011">
</head><body><div class="li-admin-shell"><?php require __DIR__ . '/navigation.php'; ?><main class="li-admin-content">
<div class="flex flex-wrap justify-between gap-4 mb-8"><div><p class="text-sm font-bold text-sky-700 mb-3">SUIVI COMMERCIAL</p><h1 class="text-3xl font-extrabold">Prospects & relances</h1><p class="text-slate-500 mt-3">Recontactez les visiteurs qui ont laissé leurs coordonnées sans commander.</p></div><a class="li-admin-button secondary self-start" href="../sms-panel.php">Configuration & journal SMS ↗</a></div>
<?php if ($flash): ?><p role="status" class="p-4 rounded-xl bg-sky-50 text-sky-800 mb-6"><?=leadEscape($flash)?></p><?php endif; ?>
<div class="li-admin-card mb-6"><strong><?=count($leads)?> prospects à suivre</strong><p class="text-sm text-slate-500 mt-2">Les saisies actives depuis moins de 30 minutes et les personnes ayant déjà commandé sont exclues. Les niveaux L1 à L10 suivent les ouvertures WhatsApp ; les SMS sont enregistrés séparément. Une ouverture WhatsApp ne confirme pas l’envoi du message.</p></div>
<?php if (!$leads): ?><div class="li-admin-card text-center py-12"><h2 class="font-bold text-lg">Aucune relance en attente</h2><p class="text-slate-500 mt-2">Les prospects éligibles apparaîtront ici après avoir quitté leur commande.</p></div><?php endif; ?>
<div class="grid gap-5 xl:grid-cols-2"><?php foreach ($leads as $lead): $stage=min(10,(int)$lead['followup_stage']+1); $hasPhone=strlen(prospectPhone($lead['phone']))>=8; ?>
<article class="li-admin-card"><div class="flex justify-between gap-3"><h2 class="text-lg font-bold"><?=leadEscape(trim($lead['first_name'].' '.$lead['name']) ?: 'Visiteur')?></h2><span class="bg-sky-50 text-sky-700 rounded-full px-3 py-1 text-xs font-bold">Relance L<?=$stage?></span></div><p class="text-sm text-slate-500 mt-2"><?=leadEscape($lead['email'])?> · <?=leadEscape($lead['phone'])?></p><p class="mt-4 font-semibold"><?=leadEscape($lead['package_name'] ?? 'Forfait non choisi')?></p><p class="text-sm text-slate-500">Étape <?=(int)$lead['last_step']?>/5 · <?=leadEscape($lead['country'])?></p><p class="text-xs text-slate-500 mt-3">Dernière activité : <?=leadEscape($lead['updated_at'])?><br>Dernière relance : <?=leadEscape($lead['last_followup'] ?? 'Aucune')?></p><p class="bg-slate-50 rounded-xl p-4 text-sm text-slate-600 my-4"><?=leadEscape(linkedinFollowupMessage($lead,$stage))?></p>
<form method="post" class="flex flex-wrap gap-2"><?php csrf_field(); ?><input type="hidden" name="nonce" value="<?=leadEscape($_SESSION['linkedin_followup_nonce'])?>"><input type="hidden" name="id" value="<?=(int)$lead['id']?>"><input type="hidden" name="stage" value="<?=$stage?>"><button name="action" value="whatsapp" class="li-admin-button" <?=$hasPhone?'':'disabled'?>>Ouvrir WhatsApp · L<?=$stage?></button><button name="action" value="sms" class="li-admin-button secondary" <?=$hasPhone?'':'disabled'?>>Envoyer un SMS · L<?=$stage?></button><?php if (!$hasPhone): ?><p class="text-sm text-slate-500">Numéro manquant ou incomplet.</p><?php endif; ?><?php if ($lead["email"]): ?><a class="li-admin-button secondary" href="mailto:<?=leadEscape($lead["email"])?>?subject=<?=rawurlencode("Votre optimisation LinkedIn HireMe")?>&amp;body=<?=rawurlencode(linkedinFollowupMessage($lead,$stage))?>">Email</a><?php endif; ?><div class="w-full flex gap-2 mt-3"><select name="status" aria-label="Statut du prospect" class="border border-slate-200 rounded-lg p-2"><?php foreach (['open'=>'À relancer','contacted'=>'Contacté','dismissed'=>'Classer sans suite'] as $value=>$label): ?><option value="<?=$value?>" <?=$lead['status']===$value?'selected':''?>><?=$label?></option><?php endforeach; ?></select><button name="action" value="status" class="li-admin-button secondary">Enregistrer</button></div></form></article>
<?php endforeach; ?></div></main></div></body></html>
