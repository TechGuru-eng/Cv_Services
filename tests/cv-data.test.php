<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../includes/digital-cv-data.php';
require __DIR__ . '/../includes/cv-audit-response.php';
$attack = '</script><script>alert(1)</script><img src=x onerror=alert(1)>';
$data = digital_cv_data(json_encode(['name' => $attack, 'photo' => 'x" onerror="alert(1)', 'experiences' => [['title' => $attack]], 'skills' => [$attack]]));
if (str_contains($data['name'], '<') || $data['photo'] !== '' || !is_array($data['education']) || str_contains($data['experiences'][0]['title'], '<')) throw new RuntimeException('CV injection or normalization failed');
$sample = ['readable' => true, 'sector' => 'Finance', 'breakdown' => array_fill_keys(['job_match', 'ats_friendly', 'density', 'contacts', 'action_verbs'], 80), 'findings' => [['type' => 'info', 'text' => 'Texte lisible']]];
if (cv_audit_result($sample)['score'] !== 80) throw new RuntimeException('Audit weighted scoring failed');
foreach (['unreadable', 'invalid-score', 'invalid-finding'] as $case) {
    $invalid = $sample;
    if ($case === 'unreadable') $invalid['readable'] = false;
    if ($case === 'invalid-score') $invalid['breakdown']['contacts'] = 200;
    if ($case === 'invalid-finding') $invalid['findings'][0]['type'] = '" onmouseover="alert(1)';
    try { cv_audit_result($invalid); } catch (UnexpectedValueException $e) { continue; }
    throw new RuntimeException('Accepted ' . $case);
}
echo "CV injection protection, missing-field normalization and 4 audit response cases passed.\n";
