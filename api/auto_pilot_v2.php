<?php
http_response_code(403);
exit('Relances email automatiques désactivées.');
require_once __DIR__ . '/../includes/cron-auth.php';
if (!getenv('SMTP_PASSWORD')) { http_response_code(503); header('Content-Type: application/json'); exit(json_encode(['success' => false, 'message' => 'Service email non configuré.'])); }
// api/auto_pilot_v2.php
// The "Silent Seller" V2 - Automated Email Follow-ups with Open & Click Tracking.
// Runs on background cron to convert abandoned carts and CV Audits.
// Supported methods: GET

header('Content-Type: application/json');

// 1. Establish DB connection
$dbPath = __DIR__ . '/../admin/config/db.php';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../config/db.php';
}
if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection helper not found']);
    exit;
}
require_once $dbPath;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../PHPMailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/../PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'PHPMailer library not found']);
    exit;
}

// 2. SMTP Settings
$smtp_config = [
    'host' => 'smtp.hostinger.com',
    'username' => 'admin@hiremegroup.com',
    'password' => (getenv('SMTP_PASSWORD') ?: ''),
    'port' => 465,
    'fromName' => 'HireMe CV Services'
];

try {
    // --- STAGE 0 to 1 (The Reminder - 15 mins) ---
    $stmt1 = $pdo->prepare("SELECT * FROM prospects WHERE status = 'pending' AND followup_stage = 0 AND created_at <= (NOW() - INTERVAL 15 MINUTE) LIMIT 5");
    $stmt1->execute();
    $prospects1 = $stmt1->fetchAll();

    $sent1 = 0;
    foreach ($prospects1 as $p) {
        if (sendFollowupEmail($p, 1, $smtp_config)) {
            $pdo->prepare("UPDATE prospects SET followup_stage = 1, last_email_sent = NOW() WHERE id = ?")->execute([$p['id']]);
            $sent1++;
        }
    }

    // --- STAGE 1 to 2 (The Discount - 24 hours) ---
    $stmt2 = $pdo->prepare("SELECT * FROM prospects WHERE status = 'pending' AND followup_stage = 1 AND last_email_sent <= (NOW() - INTERVAL 24 HOUR) LIMIT 5");
    $stmt2->execute();
    $prospects2 = $stmt2->fetchAll();

    $sent2 = 0;
    foreach ($prospects2 as $p) {
        if (sendFollowupEmail($p, 2, $smtp_config)) {
            $pdo->prepare("UPDATE prospects SET followup_stage = 2, last_email_sent = NOW() WHERE id = ?")->execute([$p['id']]);
            $sent2++;
        }
    }

    // ==========================================
    // --- CV AUDIT AUTOMATION SEQUENCES ---
    // ==========================================

    // --- AUDIT STAGE 0 to 1 (Report Fallback - 5 mins) ---
    $stmtAudit1 = $pdo->prepare("SELECT * FROM prospects WHERE status = 'lead_audit' AND followup_stage = 0 AND created_at <= (NOW() - INTERVAL 5 MINUTE) LIMIT 5");
    $stmtAudit1->execute();
    $audits1 = $stmtAudit1->fetchAll();

    $sentAudit1 = 0;
    foreach ($audits1 as $p) {
        if (sendAuditFollowupEmail($p, 1, $smtp_config)) {
            $pdo->prepare("UPDATE prospects SET followup_stage = 1, last_email_sent = NOW() WHERE id = ?")->execute([$p['id']]);
            $sentAudit1++;
        }
    }

    // --- AUDIT STAGE 1 to 2 (Friendly Reminder - 12 hours) ---
    $stmtAudit2 = $pdo->prepare("SELECT * FROM prospects WHERE status = 'lead_audit' AND followup_stage = 1 AND last_email_sent <= (NOW() - INTERVAL 12 HOUR) LIMIT 5");
    $stmtAudit2->execute();
    $audits2 = $stmtAudit2->fetchAll();

    $sentAudit2 = 0;
    foreach ($audits2 as $p) {
        if (sendAuditFollowupEmail($p, 2, $smtp_config)) {
            $pdo->prepare("UPDATE prospects SET followup_stage = 2, last_email_sent = NOW() WHERE id = ?")->execute([$p['id']]);
            $sentAudit2++;
        }
    }

    // --- AUDIT STAGE 2 to 3 (Final Discount - 24 hours) ---
    $stmtAudit3 = $pdo->prepare("SELECT * FROM prospects WHERE status = 'lead_audit' AND followup_stage = 2 AND last_email_sent <= (NOW() - INTERVAL 24 HOUR) LIMIT 5");
    $stmtAudit3->execute();
    $audits3 = $stmtAudit3->fetchAll();

    $sentAudit3 = 0;
    foreach ($audits3 as $p) {
        if (sendAuditFollowupEmail($p, 3, $smtp_config)) {
            $pdo->prepare("UPDATE prospects SET followup_stage = 3, last_email_sent = NOW() WHERE id = ?")->execute([$p['id']]);
            $sentAudit3++;
        }
    }

    echo json_encode([
        'success' => true, 
        'processed_stage1' => count($prospects1), 
        'sent_stage1' => $sent1,
        'processed_stage2' => count($prospects2),
        'sent_stage2' => $sent2,
        'processed_audit_stage1' => count($audits1),
        'sent_audit_stage1' => $sentAudit1,
        'processed_audit_stage2' => count($audits2),
        'sent_audit_stage2' => $sentAudit2,
        'processed_audit_stage3' => count($audits3),
        'sent_audit_stage3' => $sentAudit3
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Sends a follow-up email for standard prospects
 */
function sendFollowupEmail($prospect, $stage, $config) {
    if (empty($prospect['email'])) return false;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $config['port'];
        $mail->setFrom($config['username'], $config['fromName']);
        $mail->addAddress($prospect['email']);
        $mail->addReplyTo($config['username'], $config['fromName']);
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);

        $firstName = explode(' ', $prospect['name'])[0];
        $packageName = $prospect['package_name'] ?? 'CV Premium';

        // Campaign identifiers
        $campaignId = "cron_autopilot_stage_" . $stage;
        $encodedEmail = urlencode($prospect['email']);
        $trackingBase = "https://moncvpro.hiremeguide.com/api";

        // Open tracking pixel
        $openPixel = "<img src='{$trackingBase}/track_email_open?email={$encodedEmail}&campaign={$campaignId}&version=A' width='1' height='1' style='display:none;' />";

        if ($stage == 1) {
            $mail->Subject = "Besoin d'aide pour votre CV, $firstName ?";
            
            // Tracked CTA link
            $rawCta = "https://moncvpro.hiremeguide.com/#order";
            $trackedCta = "{$trackingBase}/track_email_click?email={$encodedEmail}&campaign={$campaignId}&version=A&url=" . urlencode($rawCta);

            $body = "
            <div style='font-family: sans-serif; max-width: 600px; margin: auto; padding: 20px; color: #334155;'>
                <h2 style='color: #0ea5e9;'>Bonjour $firstName ! 👋</h2>
                <p>J'ai remarqué que vous avez commencé à configurer votre <strong>" . htmlspecialchars($packageName) . "</strong>, mais vous n'avez pas finalisé.</p>
                <p>Saviez-vous que <strong>75% des CV sont rejetés</strong> par les logiciels ATS avant même d'être vus par un humain ?</p>
                <p>Nous sommes là pour vous aider à passer cette barrière. Ne laissez pas votre carrière au hasard.</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$trackedCta}' style='background: #0ea5e9; color: white; padding: 15px 30px; border-radius: 50px; text-decoration: none; font-weight: bold; display: inline-block;'>Terminer ma commande</a>
                </div>
                <p style='font-size: 13px; color: #94a3b8;'>Si vous avez des questions, répondez simplement à cet email.</p>
                {$openPixel}
            </div>";
        } else if ($stage == 2) {
            $mail->Subject = "Cadeau : -20% sur votre commande HireMe 🎁";

            // Tracked CTA link
            $rawCta = "https://moncvpro.hiremeguide.com/#order";
            $trackedCta = "{$trackingBase}/track_email_click?email={$encodedEmail}&campaign={$campaignId}&version=A&url=" . urlencode($rawCta);

            $body = "
            <div style='font-family: sans-serif; max-width: 600px; margin: auto; padding: 20px; color: #334155;'>
                <h2 style='color: #111827;'>Une surprise pour vous, $firstName !</h2>
                <p>Vous hésitez encore à passer à l'étape supérieure ?</p>
                <p>Pour vous aider à prendre la meilleure décision pour votre futur, je vous offre un code promo exclusif de <strong>-20%</strong> valable uniquement aujourd'hui.</p>
                <div style='background: #f1f5f9; padding: 20px; border-radius: 12px; text-align: center; border: 2px dashed #0ea5e9; margin: 20px 0;'>
                    <span style='font-size: 24px; font-weight: bold; color: #0ea5e9; font-family: monospace;'>WELCOME20</span>
                </div>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$trackedCta}' style='background: #111827; color: white; padding: 15px 30px; border-radius: 50px; text-decoration: none; font-weight: bold; display: inline-block;'>Utiliser mon code maintenant</a>
                </p>
                <p style='font-size: 11px; color: #94a3b8;'>Offre non cumulable avec d'autres promotions en cours.</p>
                {$openPixel}
            </div>";
        }

        $mail->Body = $body;
        return $mail->send();
    } catch (Exception $e) {
        error_log("AutoPilot V2 Mail Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Sends a follow-up email for CV Audit prospects
 */
function sendAuditFollowupEmail($prospect, $stage, $config) {
    if (empty($prospect['email'])) return false;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $config['port'];
        $mail->setFrom($config['username'], $config['fromName']);
        $mail->addAddress($prospect['email']);
        $mail->addReplyTo($config['username'], $config['fromName']);
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);

        $firstName = explode(' ', $prospect['name'])[0];
        if (empty($firstName)) $firstName = 'Candidat';

        $cvScore = $prospect['cv_score'] ?? 45;
        $targetJob = $prospect['target_job'] ?? 'Inconnu';

        // Campaign identifiers
        $campaignId = "cron_autopilot_audit_stage_" . $stage;
        $encodedEmail = urlencode($prospect['email']);
        $trackingBase = "https://moncvpro.hiremeguide.com/api";

        // Open tracking pixel
        $openPixel = "<img src='{$trackingBase}/track_email_open?email={$encodedEmail}&campaign={$campaignId}&version=A' width='1' height='1' style='display:none;' />";
        $rawCta = "https://moncvpro.hiremeguide.com/#order";
        $trackedCta = "{$trackingBase}/track_email_click?email={$encodedEmail}&campaign={$campaignId}&version=A&url=" . urlencode($rawCta);

        if ($stage == 1) {
            // Stage 1 Report Fallback (if immediate fail)
            $mail->Subject = "Analyse de votre CV - Conseils d'experts 📊";
            $body = "
            <div style=\"font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; max-width: 600px; margin: auto; padding: 30px; color: #334155; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;\">
              <div style=\"text-align: center; margin-bottom: 25px;\">
                <span style=\"font-size: 40px;\">📊</span>
                <h2 style=\"color: #059669; font-size: 24px; font-weight: bold; margin-top: 10px;\">Résultats de votre Audit CV, {$firstName} !</h2>
              </div>
              <p style=\"font-size: 16px; line-height: 1.6; color: #475569;\">
                Merci d'avoir utilisé notre outil d'analyse gratuite de CV pour le poste de <strong>" . htmlspecialchars($targetJob) . "</strong>.
              </p>
              <div style=\"background-color: #ecfdf5; border: 1px solid #a7f3d0; padding: 20px; margin: 25px 0; border-radius: 12px; text-align: center;\">
                <p style=\"margin: 0; font-size: 15px; color: #065f46;\">Votre score de compatibilité ATS estimé est de :</p>
                <div style=\"font-size: 48px; font-weight: 900; color: #059669; margin: 10px 0;\">{$cvScore} / 100</div>
                <p style=\"margin: 0; font-size: 13px; color: #047857;\"><strong>Statut :</strong> Score critique. Des modifications sont fortement recommandées pour passer les filtres robotisés.</p>
              </div>
              <p style=\"font-size: 16px; line-height: 1.6; color: #475569;\">
                Un score en dessous de 70 signifie que votre CV manque de mots-clés essentiels, de structuration claire ou contient des formats de tableaux illisibles pour les outils de recrutement.
              </p>
              <p style=\"font-size: 16px; line-height: 1.6; color: #475569;\">
                Confiez la réécriture de votre CV à nos professionnels et passez à la vitesse supérieure dès aujourd'hui.
              </p>
              <div style=\"text-align: center; margin: 35px 0;\">
                <a href=\"{$trackedCta}\" style=\"background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 14px 28px; border-radius: 50px; text-decoration: none; font-weight: bold; font-size: 16px; display: inline-block;\">Fixer mon CV maintenant</a>
              </div>
              <hr style=\"border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;\" />
              <p style=\"font-size: 13px; color: #64748b; line-height: 1.5; text-align: center;\">
                Obtenez un CV professionnel rédigé par des spécialistes pour seulement 14 900 FCFA.<br />
                <strong>L'équipe MonCVPro</strong> · <a href=\"https://moncvpro.hiremeguide.com\" style=\"color: #0ea5e9; text-decoration: none;\">moncvpro.hiremeguide.com</a>
              </p>
              {$openPixel}
            </div>";
        } else if ($stage == 2) {
            // Stage 2: Friendly Reminder (after 12 hours)
            $mail->Subject = "Comment optimiser votre CV pour le poste de " . $targetJob . " ? 🚀";
            $body = "
            <div style=\"font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; max-width: 600px; margin: auto; padding: 30px; color: #334155; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;\">
              <div style=\"text-align: center; margin-bottom: 25px;\">
                <span style=\"font-size: 40px;\">👋</span>
                <h2 style=\"color: #0ea5e9; font-size: 24px; font-weight: bold; margin-top: 10px;\">Bonjour {$firstName} !</h2>
              </div>
              <p style=\"font-size: 16px; line-height: 1.6; color: #475569;\">
                Il y a quelques heures, vous avez effectué une analyse de votre CV pour le poste de <strong>" . htmlspecialchars($targetJob) . "</strong>, obtenant un score de <strong>{$cvScore}/100</strong>.
              </p>
              <p style=\"font-size: 16px; line-height: 1.6; color: #475569;\">
                Saviez-vous que la plupart des candidats ne réalisent pas que leur CV est invisible aux yeux des recruteurs à cause des filtres ATS ?
              </p>
              <div style=\"background-color: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 15px; margin: 20px 0; border-radius: 4px;\">
                <p style=\"font-size: 15px; line-height: 1.5; color: #0369a1; margin: 0;\">
                  Notre équipe d'experts rédige des CV sur-mesure optimisés pour les recruteurs et les algorithmes ATS.
                </p>
              </div>
              <div style=\"text-align: center; margin: 35px 0;\">
                <a href=\"{$trackedCta}\" style=\"background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; padding: 14px 28px; border-radius: 50px; text-decoration: none; font-weight: bold; font-size: 16px; display: inline-block;\">Optimiser mon CV</a>
              </div>
              <hr style=\"border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;\" />
              <p style=\"font-size: 13px; color: #64748b; line-height: 1.5; text-align: center;\">
                Des questions ? Répondez simplement à cet e-mail.<br />
                <strong>L'équipe MonCVPro</strong> · <a href=\"https://moncvpro.hiremeguide.com\" style=\"color: #0ea5e9; text-decoration: none;\">moncvpro.hiremeguide.com</a>
              </p>
              {$openPixel}
            </div>";
        } else if ($stage == 3) {
            // Stage 3: Final Promo (after 24 hours)
            $mail->Subject = "Cadeau : -20% sur la refonte de votre CV 🎁";
            $body = "
            <div style=\"font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; max-width: 600px; margin: auto; padding: 30px; color: #334155; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;\">
              <div style=\"text-align: center; margin-bottom: 25px;\">
                <span style=\"font-size: 40px;\">🎁</span>
                <h2 style=\"color: #111827; font-size: 24px; font-weight: bold; margin-top: 10px;\">Offre spéciale de bienvenue pour vous, {$firstName} !</h2>
              </div>
              <p style=\"font-size: 16px; line-height: 1.6; color: #475569;\">
                Ne laissez pas un CV non optimisé freiner vos opportunités de carrière.
              </p>
              <p style=\"font-size: 16px; line-height: 1.6; color: #475569;\">
                Pour vous aider à franchir le pas, nous vous offrons aujourd'hui une réduction spéciale de <strong>20%</strong> sur la refonte complète de votre CV.
              </p>
              <div style=\"background-color: #f8fafc; border: 2px dashed #0ea5e9; padding: 25px; margin: 25px 0; border-radius: 12px; text-align: center;\">
                <p style=\"margin: 0 0 10px 0; font-size: 14px; color: #64748b; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;\">Votre code promo unique :</p>
                <span style=\"font-size: 32px; font-weight: 900; color: #0ea5e9; letter-spacing: 2px; font-family: monospace; display: block; margin-bottom: 10px;\">WELCOME20</span>
                <p style=\"margin: 0; font-size: 12px; color: #94a3b8;\">*Offre valable uniquement pendant 48 heures.</p>
              </div>
              <div style=\"text-align: center; margin: 35px 0;\">
                <a href=\"{$trackedCta}\" style=\"background: linear-gradient(135deg, #111827 0%, #1f2937 100%); color: white; padding: 14px 28px; border-radius: 50px; text-decoration: none; font-weight: bold; font-size: 16px; display: inline-block;\">Profiter des -20% maintenant</a>
              </div>
              <hr style=\"border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;\" />
              <p style=\"font-size: 13px; color: #64748b; line-height: 1.5; text-align: center;\">
                Un CV professionnel multiplie vos chances d'embauche par 3.<br />
                <strong>L'équipe MonCVPro</strong> · <a href=\"https://moncvpro.hiremeguide.com\" style=\"color: #0ea5e9; text-decoration: none;\">moncvpro.hiremeguide.com</a>
              </p>
              {$openPixel}
            </div>";
        }

        $mail->Body = $body;
        $mail->AltBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $body));

        return $mail->send();
    } catch (Exception $e) {
        error_log("AutoPilot V2 Audit Mail Error: " . $e->getMessage());
        return false;
    }
}
?>
