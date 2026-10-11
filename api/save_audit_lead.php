<?php
require_once __DIR__ . '/' . (strpos(__FILE__, DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR) !== false ? '../' : '') . 'includes/upload_helper.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !rate_limit_ok(basename(__FILE__), 20, 600)) { http_response_code(429); header('Content-Type: application/json'); exit(json_encode(['success' => false, 'message' => 'Trop de requêtes. Réessayez plus tard.'])); }
// api/save_audit_lead.php
header('Content-Type: application/json');
session_start();

// Resilient DB path connection helper
$dbPath = __DIR__ . '/../admin/config/db.php';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../config/db.php';
}
if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fichier de connexion BD introuvable.']);
    exit;
}
require_once $dbPath;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$email = $_POST['email'] ?? '';
$whatsapp = $_POST['whatsapp'] ?? '';
$auditData = $_SESSION['last_audit'] ?? null;
if (!$auditData || ($auditData['timestamp'] ?? 0) < time() - 3600) {
    http_response_code(422);
    exit(json_encode(['success' => false, 'message' => 'Effectuez une analyse de CV avant de demander le rapport.']));
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email valide requis']);
    exit;
}

try {
    // Check if prospect already exists
    $stmt = $pdo->prepare("SELECT id, name FROM prospects WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    $score = intval($auditData['score'] ?? 0);
    $job = $auditData['job'] ?? 'Inconnu';
    $name = $existing ? $existing['name'] : explode('@', $email)[0];

    if ($existing) {
        // Update existing prospect with audit info (only set whatsapp if currently empty)
        $stmt = $pdo->prepare("UPDATE prospects SET 
            status = 'lead_audit',
            whatsapp = IF(whatsapp = '' OR whatsapp IS NULL, ?, whatsapp),
            followup_stage = 0,
            cv_score = ?,
            target_job = ?,
            notes = CONCAT(IFNULL(notes,''), '\nAudit CV effectué: Score ', ?, ' pour le poste de ', ?),
            updated_at = NOW()
            WHERE id = ?");
        $stmt->execute([$whatsapp, $score, $job, $score, $job, $existing['id']]);
    } else {
        // Create new prospect
        $stmt = $pdo->prepare("INSERT INTO prospects (email, whatsapp, name, package_name, status, source, cv_score, target_job, followup_stage, notes, created_at) 
                               VALUES (?, ?, ?, ?, 'lead_audit', 'cv_audit', ?, ?, 0, ?, NOW())");
        $notes = "Audit CV effectué: Score " . $score . " pour le poste de " . $job;
        $stmt->execute([$email, $whatsapp, $name, 'Audit Gratuit', $score, $job, $notes]);
    }

    // Queue Stage 1 Audit Report email (Stage 1) instead of sending synchronously to prevent SMTP connection timeouts
    $queueSuccessful = false;
    try {
        $subject = "Analyse de votre CV - Conseils d'experts 📊";
        $campaignId = "autopilot_audit_report";
        $version = "A";
        $fromName = "HireMe CV Services";

        // Generate tracking params
        $encodedEmail = urlencode($email);
        
        $httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
        $protocol = $isHttps ? "https://" : "http://";
        $trackingBase = $protocol . $httpHost . dirname($_SERVER['SCRIPT_NAME']);
        $trackingBase = rtrim($trackingBase, '/\\');

        $openPixelUrl = "{$trackingBase}/track_email_open?email={$encodedEmail}&campaign={$campaignId}&version=A";
        $openPixelTag = "\n<img src=\"{$openPixelUrl}\" width=\"1\" height=\"1\" style=\"display:none;\" />";

        $rawCta = "https://moncvpro.hiremeguide.com/#order";
        $trackedCta = "{$trackingBase}/track_email_click?email={$encodedEmail}&campaign={$campaignId}&version=A&url=" . urlencode($rawCta);

        $body = "
        <div style=\"font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; max-width: 600px; margin: auto; padding: 30px; color: #334155; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);\">
          <div style=\"text-align: center; margin-bottom: 25px;\">
            <span style=\"font-size: 40px;\">📊</span>
            <h2 style=\"color: #059669; font-size: 24px; font-weight: bold; margin-top: 10px;\">Résultats de votre Audit CV, {$firstName} !</h2>
          </div>
          <p style=\"font-size: 16px; line-height: 1.6; color: #475569;\">
            Merci d'avoir utilisé notre outil d'analyse gratuite de CV pour le poste de <strong>" . htmlspecialchars($job) . "</strong>.
          </p>
          <div style=\"background-color: #ecfdf5; border: 1px solid #a7f3d0; padding: 20px; margin: 25px 0; border-radius: 12px; text-align: center;\">
            <p style=\"margin: 0; font-size: 15px; color: #065f46;\">Votre score de compatibilité ATS estimé est de :</p>
            <div style=\"font-size: 48px; font-weight: 900; color: #059669; margin: 10px 0;\">{$score} / 100</div>
            <p style=\"margin: 0; font-size: 13px; color: #047857;\"><strong>Statut :</strong> Score critique. Des modifications sont fortement recommandées pour passer les filtres robotisés.</p>
          </div>
          <p style=\"font-size: 16px; line-height: 1.6; color: #475569;\">
            Un score en dessous de 70 signifie que votre CV manque de mots-clés essentiels, de structuration claire ou contient des formats de tableaux illisibles pour les outils de recrutement.
          </p>
          <p style=\"font-size: 16px; line-height: 1.6; color: #475569;\">
            Confiez la réécriture de votre CV à nos professionnels et passez à la vitesse supérieure dès aujourd'hui.
          </p>
          <div style=\"text-align: center; margin: 35px 0;\">
            <a href=\"{$trackedCta}\" style=\"background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 14px 28px; border-radius: 50px; text-decoration: none; font-weight: bold; font-size: 16px; display: inline-block; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.3);\">Fixer mon CV maintenant</a>
          </div>
          <hr style=\"border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;\" />
          <p style=\"font-size: 13px; color: #64748b; line-height: 1.5; text-align: center;\">
            Obtenez un CV professionnel rédigé par des spécialistes pour seulement 14 900 FCFA.<br />
            <strong>L'équipe MonCVPro</strong> · <a href=\"https://moncvpro.hiremeguide.com\" style=\"color: #0ea5e9; text-decoration: none;\">moncvpro.hiremeguide.com</a>
          </p>
          {$openPixelTag}
        </div>";

        $altBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $body));

        // Insert into database queue
        $stmtQ = $pdo->prepare("
            INSERT INTO mailbatch_queue (email, name, subject, body, alt_body, from_name, campaign_id, version, status, scheduled_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmtQ->execute([$email, $name, $subject, $body, $altBody, $fromName, $campaignId, $version]);
        $queueSuccessful = true;
    } catch (Exception $queueEx) {
        error_log("Immediate Audit Queue Error: " . $queueEx->getMessage());
    }

    if ($queueSuccessful) {
        // Update followup_stage to 1 and last_email_sent
        $stmtUp = $pdo->prepare("UPDATE prospects SET followup_stage = 1, last_email_sent = NOW() WHERE email = ? AND status = 'lead_audit'");
        $stmtUp->execute([$email]);
    }

    echo json_encode(['success' => $queueSuccessful, 'message' => $queueSuccessful
        ? 'Rapport enregistré et email en attente d’envoi.'
        : 'Le rapport n’a pas pu être planifié. Réessayez plus tard.']);
    
    // Clear session audit
    if ($queueSuccessful) unset($_SESSION['last_audit']);

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Le rapport n’a pas pu être enregistré. Réessayez plus tard.']);
}
?>
