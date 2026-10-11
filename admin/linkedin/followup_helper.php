<?php
require_once __DIR__ . '/../includes/prospect_helper.php';
function linkedinPrepareFollowups(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS linkedin_followups (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, lead_id BIGINT UNSIGNED NOT NULL,
        stage INT NOT NULL, channel VARCHAR(20) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(lead_id,stage)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function linkedinEligibleLeads(PDO $pdo) {
    $leads = $pdo->query("SELECT l.*,p.name AS package_name,COALESCE(f.stage,0) AS followup_stage,f.last_followup
        FROM linkedin_checkout_leads l LEFT JOIN linkedin_packages p ON p.id=l.package_id
        LEFT JOIN (SELECT lead_id,MAX(CASE WHEN channel='whatsapp' THEN stage ELSE 0 END) AS stage,MAX(created_at) AS last_followup FROM linkedin_followups GROUP BY lead_id) f ON f.lead_id=l.id
        WHERE l.status IN ('open','contacted') AND l.order_reference IS NULL
        AND l.updated_at <= DATE_SUB(NOW(), INTERVAL 30 MINUTE) ORDER BY l.updated_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $orders = $pdo->query('SELECT phone AS whatsapp,email FROM linkedin_orders')->fetchAll(PDO::FETCH_ASSOC);
    return array_values(array_filter($leads, static function ($lead) use ($orders) {
        foreach ($orders as $order) if (prospectMatches(['whatsapp'=>$lead['phone'],'email'=>$lead['email']],$order)) return false;
        return true;
    }));
}
function linkedinFollowupMessage(array $lead, int $stage) {
    $name = trim($lead['first_name']) ?: explode(' ',trim($lead['name']))[0];
    $url = 'https://moncvpro.hiremeguide.com/linkedin/commander.php';
    $messages = [
        "Bonjour $name ! Vous avez commencé votre commande d’optimisation LinkedIn chez HireMe. Besoin d’aide pour la finaliser ? Reprenez ici : $url",
        "Bonjour $name ! Votre projet LinkedIn est toujours en attente. Notre équipe peut vous aider à choisir votre forfait et finaliser votre commande : $url",
        "Bonjour $name ! Souhaitez-vous toujours optimiser votre profil LinkedIn ? Contactez-nous pour toute question ou reprenez votre commande : $url",
        "Bonjour $name ! Une question sur les prestations LinkedIn ? Nous pouvons vous expliquer les livrables avant votre commande : $url",
        "Bonjour $name ! Besoin d’aide pour choisir le forfait LinkedIn adapté à votre objectif professionnel ? Parlons-en : $url",
        "Bonjour $name ! Notre équipe reste disponible pour vous accompagner dans votre optimisation LinkedIn : $url",
        "Bonjour $name ! Vous pouvez retrouver les forfaits et les détails de votre optimisation LinkedIn ici : $url",
        "Bonjour $name ! Votre projet LinkedIn est-il toujours d’actualité ? Dites-nous si vous souhaitez poursuivre : $url",
        "Bonjour $name ! Nous restons à votre disposition pour finaliser votre commande LinkedIn quand vous serez prêt : $url",
        "Bonjour $name ! Dernier rappel concernant votre projet d’optimisation LinkedIn. Si vous souhaitez reprendre, voici le lien : $url"
    ];
    return $messages[min(9,max(0,$stage-1))];
}
