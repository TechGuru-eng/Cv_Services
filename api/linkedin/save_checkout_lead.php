<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
require __DIR__ . '/../../admin/config/db.php';
require __DIR__ . '/../../includes/upload_helper.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
if (!rate_limit_ok('linkedin-leads', 120, 600)) { http_response_code(429); exit; }
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || !preg_match('/^[a-f0-9]{64}$/', $data['token'] ?? '')) { http_response_code(400); exit; }
$value = static function ($key, $limit) use ($data) { return mb_substr(trim(is_string($data[$key] ?? null) ? $data[$key] : ''), 0, $limit); };
$email = $value('email', 254);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $email = '';
$phone = $value('phone', 40);
if (strlen(preg_replace('/\D/', '', $phone)) < 8 || preg_match('/[a-z@]/i', $phone)) $phone = '';
if (!$email && !$phone) { echo json_encode(['success' => false]); exit; }
try {
 $stmt = $pdo->prepare("INSERT INTO linkedin_checkout_leads (token_hash,name,first_name,email,phone,country,package_id,last_step) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=IF(status='converted',name,VALUES(name)),first_name=IF(status='converted',first_name,VALUES(first_name)),email=IF(status='converted',email,VALUES(email)),phone=IF(status='converted',phone,VALUES(phone)),country=IF(status='converted',country,VALUES(country)),package_id=IF(status='converted',package_id,VALUES(package_id)),last_step=IF(status='converted',last_step,GREATEST(last_step,VALUES(last_step))),updated_at=IF(status='converted',updated_at,NOW())");
 $stmt->execute([hash('sha256', $data['token']), $value('name',200),$value('first_name',200),$email,$phone,$value('country',100),max(0,(int)($data['package_id']??0)),min(5,max(1,(int)($data['step']??1)))]);
 echo json_encode(['success' => true]);
} catch (Throwable $e) { http_response_code(500); echo json_encode(['success'=>false]); }
