<?php
require __DIR__ . '/../admin/config/db.php';
require __DIR__ . '/../admin/linkedin/followup_helper.php';
linkedinPrepareFollowups($pdo);
$pdo->beginTransaction();
try {
    if (!prospectMatches(['whatsapp'=>'670123456','email'=>''], ['whatsapp'=>'+237 670 123 456','email'=>''])) throw new RuntimeException('Phone identity mismatch');
    if (!prospectMatches(['email'=>'QA@EXAMPLE.INVALID'], ['email'=>'qa@example.invalid'])) throw new RuntimeException('Email identity mismatch');
    $token = bin2hex(random_bytes(32));
    $insert=$pdo->prepare("INSERT INTO linkedin_checkout_leads(token_hash,first_name,email,phone,status,updated_at,order_reference) VALUES(?,?,?,?,?,?,?)");
    $ids=[];
    foreach ([['open','2020-01-01 00:00:00',null],['open',date('Y-m-d H:i:s'),null],['converted','2020-01-01 00:00:00',null],['dismissed','2020-01-01 00:00:00',null],['awaiting_payment','2020-01-01 00:00:00','TEST-REF']] as $i=>$case) {
        $insert->execute([hash('sha256',$token.$i),'QA','qa-'.$token.$i.'@example.invalid','',...$case]);
        $ids[]=(int)$pdo->lastInsertId();
    }
    $eligible=array_column(linkedinEligibleLeads($pdo),'id');
    if (!in_array($ids[0],$eligible)) throw new RuntimeException('Abandoned lead missing');
    foreach(array_slice($ids,1) as $id) if(in_array($id,$eligible)) throw new RuntimeException('Ineligible lead included');
    $log=$pdo->prepare('INSERT INTO linkedin_followups(lead_id,stage,channel) VALUES(?,?,?)');
    $log->execute([$ids[0],1,'whatsapp']); $log->execute([$ids[0],2,'sms']);
    foreach(linkedinEligibleLeads($pdo) as $lead) if((int)$lead['id']===$ids[0] && (int)$lead['followup_stage']!==1) throw new RuntimeException('SMS changed WhatsApp level');
    $order=$pdo->query("SELECT phone,email FROM linkedin_orders WHERE phone<>'' OR email<>'' LIMIT 1")->fetch();
    if($order){
        $pdo->prepare('UPDATE linkedin_checkout_leads SET phone=?,email=?,updated_at=? WHERE id=?')->execute([$order['phone'],$order['email'],'2020-01-01 00:00:00',$ids[0]]);
        if(in_array($ids[0],array_column(linkedinEligibleLeads($pdo),'id'))) throw new RuntimeException('Existing customer included');
    }
    echo "Eligibility, activity delay, status exclusion, customer matching and independent SMS tracking passed.\n";
} finally { $pdo->rollBack(); }
