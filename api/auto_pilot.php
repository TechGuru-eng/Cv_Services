<?php
http_response_code(403);
exit('Relances email automatiques désactivées.');
require_once __DIR__ . '/../includes/cron-auth.php';
if (!getenv('SMTP_PASSWORD')) { http_response_code(503); header('Content-Type: application/json'); exit(json_encode(['success' => false, 'message' => 'Service email non configuré.'])); }
// api/auto_pilot.php
// The "Silent Seller" - Automated Email Follow-ups
// Runs on background to convert abandoned carts.

header('Content-Type: application/json');
require_once '../admin/config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (file_exists('../vendor/autoload.php')) {
    require '../vendor/autoload.php';
} elseif (file_exists('../PHPMailer/src/PHPMailer.php')) {
    require '../PHPMailer/src/Exception.php';
    require '../PHPMailer/src/PHPMailer.php';
    require '../PHPMailer/src/SMTP.php';
}

// 1. Settings
$smtp_config = [
    'host' => 'smtp.hostinger.com',
    'username' => 'newsletter@hiremeguide.com',
    'password' => (getenv('SMTP_PASSWORD') ?: ''),
    'port' => 465
];

// 2. Fetch Prospects to Follow up
// Stage 0 -> 1 (after 15 mins)
// Stage 1 -> 2 (after 24 hours)
$now = new DateTime();

try {
    // --- STAGE 0 to 1 (The Reminder - 15 mins) ---
    $stmt1 = $pdo->prepare("SELECT * FROM prospects WHERE status = 'pending' AND followup_stage = 0 AND created_at <= (NOW() - INTERVAL 15 MINUTE) LIMIT 5");
    $stmt1->execute();
    $prospects1 = $stmt1->fetchAll();

    foreach ($prospects1 as $p) {
        if (sendFollowupEmail($p, 1, $smtp_config)) {
            $pdo->prepare("UPDATE prospects SET followup_stage = 1, last_email_sent = NOW() WHERE id = ?")->execute([$p['id']]);
        }
    }

    // --- STAGE 1 to 2 (The Discount - 24 hours) ---
    $stmt2 = $pdo->prepare("SELECT * FROM prospects WHERE status = 'pending' AND followup_stage = 1 AND last_email_sent <= (NOW() - INTERVAL 24 HOUR) LIMIT 5");
    $stmt2->execute();
    $prospects2 = $stmt2->fetchAll();

    foreach ($prospects2 as $p) {
        if (sendFollowupEmail($p, 2, $smtp_config)) {
            $pdo->prepare("UPDATE prospects SET followup_stage = 2, last_email_sent = NOW() WHERE id = ?")->execute([$p['id']]);
        }
    }

    echo json_encode(['success' => true, 'processed_stage1' => count($prospects1), 'processed_stage2' => count($prospects2)]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Sends a follow-up email based on the stage
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
        $mail->setFrom($config['username'], 'HireMe CV Services');
        $mail->addAddress($prospect['email']);
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);

        $firstName = explode(' ', $prospect['name'])[0];

        if ($stage == 1) {
            $mail->Subject = "Besoin d'aide pour votre CV, $firstName ?";
            $body = "
            <div style='font-family: sans-serif; max-width: 600px; margin: auto; padding: 20px; color: #334155;'>
                <h2 style='color: #0ea5e9;'>Bonjour $firstName ! 👋</h2>
                <p>J'ai remarqué que vous avez commencé à configurer votre <strong>" . $prospect['package_name'] . "</strong>, mais vous n'avez pas finalisé.</p>
                <p>Saviez-vous que <strong>75% des CV sont rejetés</strong> par les logiciels ATS avant même d'être vus par un humain ?</p>
                <p>Nous sommes là pour vous aider à passer cette barrière. Ne laissez pas votre carrière au hasard.</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='https://moncvpro.hiremeguide.com/#order' style='background: #0ea5e9; color: white; padding: 15px 30px; border-radius: 50px; text-decoration: none; font-weight: bold;'>Terminer ma commande</a>
                </div>
                <p style='font-size: 13px; color: #94a3b8;'>Si vous avez des questions, répondez simplement à cet email.</p>
            </div>";
        } else if ($stage == 2) {
            $mail->Subject = "Cadeau : -20% sur votre commande HireMe 🎁";
            $body = "
            <div style='font-family: sans-serif; max-width: 600px; margin: auto; padding: 20px; color: #334155;'>
                <h2 style='color: #111827;'>Une surprise pour vous, $firstName !</h2>
                <p>Vous hésitez encore à passer à l'étape supérieure ?</p>
                <p>Pour vous aider à prendre la meilleure décision pour votre futur, je vous offre un code promo exclusif de <strong>-20%</strong> valable uniquement aujourd'hui.</p>
                <div style='background: #f1f5f9; padding: 20px; border-radius: 12px; text-align: center; border: 2px dashed #0ea5e9;'>
                    <span style='font-size: 24px; font-weight: bold; color: #0ea5e9; font-family: monospace;'>WELCOME20</span>
                </div>
                <p style='text-align: center; margin-top: 20px;'>
                    <a href='https://moncvpro.hiremeguide.com/#order' style='background: #111827; color: white; padding: 15px 30px; border-radius: 50px; text-decoration: none; font-weight: bold;'>Utiliser mon code maintenant</a>
                </p>
                <p style='font-size: 11px; color: #94a3b8;'>Offre non cumulable avec d'autres promotions en cours.</p>
            </div>";
        }

        $mail->Body = $body;
        return $mail->send();
    } catch (Exception $e) {
        error_log("AutoPilot Mail Error: " . $e->getMessage());
        return false;
    }
}
?>
